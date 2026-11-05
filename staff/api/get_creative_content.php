<?php
header('Content-Type: application/json');
require_once "../db.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid API Key']);
    exit;
}

$group_strategy = $_GET['strategy'] ?? 'Observe'; // Hard, Soft, Observe
$campaign_tag = trim($_GET['campaign_tag'] ?? '');
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
}
$content_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$content_row) {
    echo json_encode(['ok' => false, 'msg' => 'No content found for strategy: ' . $content_strategy]);
    exit;
}

$images = [];

// Check if custom_images exist and have items
$custom_images = [];
if (!empty($content_row['custom_images'])) {
    $custom_images = json_decode($content_row['custom_images'], true) ?: [];
}

if (!empty($custom_images)) {
    // Use custom images
    foreach ($custom_images as $img) {
        $images[] = $img;
    }
} else {
    // Fetch random images (2 to 6 images)
    $num_images = rand(2, 6);
    $img_sql = "SELECT file_path FROM creative_images WHERE target_zone = ? OR target_zone = 'All' ORDER BY RAND() LIMIT ?";
    $img_stmt = $db->prepare($img_sql);
    $img_stmt->bindValue(1, $zone, PDO::PARAM_STR);
    $img_stmt->bindValue(2, $num_images, PDO::PARAM_INT);
    $img_stmt->execute();
    $image_rows = $img_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($image_rows as $row) {
        // The db contains something like "uploads/creative/123.jpg"
        // We should return absolute or relative paths that Python can access.
        // e.g. "D:/xampp/htdocs/yakpho.com/staff/" . $row['file_path']
        $images[] = $row['file_path'];
    }
}

echo json_encode([
    'ok' => true,
    'strategy' => $content_strategy,
    'content' => $content_row['content_text'],
    'images' => $images
]);
