<?php
require_once "../db.php";
require_login();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$campaign_id = (int)($data['campaign_id'] ?? 0);
$group_ids = $data['group_ids'] ?? [];

if (!$campaign_id || empty($group_ids) || !is_array($group_ids)) {
    echo json_encode(['ok' => false, 'msg' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $db->beginTransaction();

    // Check if campaign exists
    $stmt = $db->prepare("SELECT id FROM fb_campaigns WHERE id = ?");
    $stmt->execute([$campaign_id]);
    if (!$stmt->fetch()) {
        throw new Exception("ไม่พบแคมเปญเป้าหมาย");
    }

    $count = 0;
    // Insert ignoring duplicates
    $insertStmt = $db->prepare("INSERT IGNORE INTO fb_campaign_targets (campaign_id, fb_group_id) VALUES (?, ?)");
    
    // Also optional: update fb_groups to set campaign_tag for UI visibility (optional but nice)
    $tagStmt = $db->prepare("UPDATE facebook_groups SET campaign_tag = CASE WHEN IFNULL(campaign_tag, '') = '' THEN ? ELSE CONCAT(campaign_tag, ',', ?) END WHERE id = ? AND (campaign_tag NOT LIKE ? OR campaign_tag IS NULL)");

    $tag_name = "cp_" . $campaign_id;

    foreach ($group_ids as $gid) {
        $gid = (int)$gid;
        if ($gid > 0) {
            $insertStmt->execute([$campaign_id, $gid]);
            if ($insertStmt->rowCount() > 0) {
                $count++;
            }
            // Update tag
            $tagStmt->execute([$tag_name, $tag_name, $gid, "%$tag_name%"]);
        }
    }

    $db->commit();
    echo json_encode(['ok' => true, 'count' => $count]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
