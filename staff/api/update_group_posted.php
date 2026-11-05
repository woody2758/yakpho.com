<?php
header('Content-Type: application/json');
require_once "../db.php";

$data = json_decode(file_get_contents('php://input'), true);
$api_key = $_GET['api_key'] ?? $data['api_key'] ?? '';

if ($api_key !== 'yakpho2026') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid API Key']);
    exit;
}

$queue_id = $data['queue_id'] ?? null;
$group_id = $data['id'] ?? null;

if (!$queue_id || !$group_id) {
    echo json_encode(['ok' => false, 'msg' => 'Missing Queue ID or Group ID']);
    exit;
}

try {
    // 1. Mark Queue as Completed
    $stmt1 = $db->prepare("UPDATE creative_post_queue SET status = 'completed', completed_at = NOW() WHERE id = ?");
    $stmt1->execute([$queue_id]);

    // 2. Update Group history
    $stmt2 = $db->prepare("UPDATE facebook_groups SET last_posted_at = NOW(), post_count = post_count + 1 WHERE id = ?");
    $stmt2->execute([$group_id]);

    echo json_encode(['ok' => true, 'msg' => 'Recorded post successfully']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
