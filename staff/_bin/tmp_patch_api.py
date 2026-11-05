import os

# 1. Update get_groups_to_post.php
api1 = r'd:\xampp\htdocs\yakpho.com\staff\api\get_groups_to_post.php'
with open(api1, 'r', encoding='utf-8') as f:
    c = f.read()
c = c.replace(
    "g.strategy_marker, g.id as group_id, g.group_name, g.group_url",
    "g.strategy_marker, g.id as group_id, g.group_name, g.group_url, g.target_zone"
)
c = c.replace(
    "'strategy_marker' => $queue_item['strategy_marker'] ?? 'Observe'",
    "'strategy_marker' => $queue_item['strategy_marker'] ?? 'Observe',\n            'target_zone' => $queue_item['target_zone'] ?? 'Thailand'"
)
with open(api1, 'w', encoding='utf-8') as f:
    f.write(c)

# 2. Update get_creative_content.php
api2 = r'd:\xampp\htdocs\yakpho.com\staff\api\get_creative_content.php'
with open(api2, 'r', encoding='utf-8') as f:
    c = f.read()

old_content_fetch = '''$campaign_tag = trim($_GET['campaign_tag'] ?? '');

// Map group strategy to Content Pool strategy
$content_strategy = 'Red'; // Default
if ($group_strategy === 'Hard') $content_strategy = 'Green';
if ($group_strategy === 'Soft') $content_strategy = 'Yellow';
if ($group_strategy === 'Observe') $content_strategy = 'Red';

// Fetch random text content
if ($campaign_tag !== '') {
    $sql = "SELECT content_text, custom_images FROM creative_contents WHERE ai_strategy = ? AND campaign_tag = ? ORDER BY RAND() LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$content_strategy, $campaign_tag]);
} else {
    $sql = "SELECT content_text, custom_images FROM creative_contents WHERE ai_strategy = ? AND (campaign_tag IS NULL OR campaign_tag = '') ORDER BY RAND() LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$content_strategy]);
}'''

new_content_fetch = '''$campaign_tag = trim($_GET['campaign_tag'] ?? '');
$zone = $_GET['zone'] ?? 'All';

// Map group strategy to Content Pool strategy
$content_strategy = 'Red'; // Default
if ($group_strategy === 'Hard') $content_strategy = 'Green';
if ($group_strategy === 'Soft') $content_strategy = 'Yellow';
if ($group_strategy === 'Observe') $content_strategy = 'Red';

// Fetch random text content
if ($campaign_tag !== '') {
    $sql = "SELECT content_text, custom_images FROM creative_contents WHERE ai_strategy = ? AND campaign_tag = ? AND (target_zone = ? OR target_zone = 'All') ORDER BY RAND() LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$content_strategy, $campaign_tag, $zone]);
} else {
    $sql = "SELECT content_text, custom_images FROM creative_contents WHERE ai_strategy = ? AND (campaign_tag IS NULL OR campaign_tag = '') AND (target_zone = ? OR target_zone = 'All') ORDER BY RAND() LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$content_strategy, $zone]);
}'''
c = c.replace(old_content_fetch, new_content_fetch)

old_img_fetch = '''$img_sql = "SELECT file_path FROM creative_images ORDER BY RAND() LIMIT ?";
    $img_stmt = $db->prepare($img_sql);
    // PDO limitation: limit param might need bindValue
    $img_stmt->bindValue(1, $num_images, PDO::PARAM_INT);
    $img_stmt->execute();'''

new_img_fetch = '''$img_sql = "SELECT file_path FROM creative_images WHERE target_zone = ? OR target_zone = 'All' ORDER BY RAND() LIMIT ?";
    $img_stmt = $db->prepare($img_sql);
    $img_stmt->bindValue(1, $zone, PDO::PARAM_STR);
    $img_stmt->bindValue(2, $num_images, PDO::PARAM_INT);
    $img_stmt->execute();'''
c = c.replace(old_img_fetch, new_img_fetch)

with open(api2, 'w', encoding='utf-8') as f:
    f.write(c)

# 3. Update fb_group_poster.py
bot = r'd:\xampp\htdocs\yakpho.com\staff\bot\fb_group_poster.py'
with open(bot, 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace('async def get_content(strategy):', 'async def get_content(strategy, zone):')
c = c.replace('url = f"{BASE_URL}get_creative_content.php?api_key={API_KEY}&strategy={strategy}"', 'url = f"{BASE_URL}get_creative_content.php?api_key={API_KEY}&strategy={strategy}&zone={zone}"')

c = c.replace("g_strategy = group['strategy_marker']", "g_strategy = group['strategy_marker']\n            g_zone = group.get('target_zone', 'Thailand')")
c = c.replace('content_data = await get_content(g_strategy)', 'content_data = await get_content(g_strategy, g_zone)')

with open(bot, 'w', encoding='utf-8') as f:
    f.write(c)

print("API and Bot patched successfully")
