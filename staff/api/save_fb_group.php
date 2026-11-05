<?php
require_once "../db.php";

header('Content-Type: application/json');

// Get API Key from header or param
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
$expected_key = 'yakpho2026';

if ($api_key !== $expected_key) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method Not Allowed']);
    exit;
}

// Receive JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !is_array($data)) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid Data']);
    exit;
}

try {
    // ── จัดการ Migration แบบ On-the-fly (เผื่อบอทรันก่อนที่หน้าตารางจะถูกเปิด) ──
    try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN post_quality VARCHAR(20) NOT NULL DEFAULT 'Good'"); } catch (PDOException $e) {}
    try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN target_zone VARCHAR(50) NOT NULL DEFAULT 'Uncategorized'"); } catch (PDOException $e) {}
    try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN strategy_marker VARCHAR(20) NOT NULL DEFAULT 'Observe'"); } catch (PDOException $e) {}
    try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN ai_reason TEXT"); } catch (PDOException $e) {}

    $stmt = $db->prepare("INSERT INTO facebook_groups (
        group_name, group_url, member_count, privacy_type, location_focus, category_tag, suitability_score, last_activity, strategy_marker, ai_reason, last_scraped_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        group_name = VALUES(group_name),
        member_count = VALUES(member_count),
        privacy_type = VALUES(privacy_type),
        location_focus = VALUES(location_focus),
        category_tag = VALUES(category_tag),
        suitability_score = VALUES(suitability_score),
        last_activity = VALUES(last_activity),
        strategy_marker = VALUES(strategy_marker),
        ai_reason = VALUES(ai_reason),
        last_scraped_at = NOW()");

    $count = 0;
    foreach ($data as $g) {
        $url = rtrim($g['url'] ?? '', '/');
        $stmt->execute([
            $g['name'] ?? '',
            $url,
            (int)($g['member_count'] ?? 0),
            $g['privacy'] ?? 'Public',
            $g['location'] ?? '',
            $g['tags'] ?? '',
            (int)($g['score'] ?? 0),
            $g['activity'] ?? '',
            $g['marker'] ?? 'Observe',
            $g['ai_reason'] ?? ''
        ]);
        if ($stmt->rowCount() > 0) $count++;
    }

    echo json_encode(['ok' => true, 'saved_count' => $count]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
