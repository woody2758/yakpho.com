<?php
header('Content-Type: application/json');
require_once "../db.php";

$data = json_decode(file_get_contents('php://input'), true);
$api_key = $_GET['api_key'] ?? $data['api_key'] ?? '';

if ($api_key !== 'yakpho2026') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid API Key']);
    exit;
}

$target_id = $data['id'] ?? null;
if (!$target_id) {
    echo json_encode(['ok' => false, 'msg' => 'Missing Group ID']);
    exit;
}

try {
    // Delete from queue
    $db->prepare("DELETE FROM creative_post_queue WHERE target_id = ? AND status = 'pending'")->execute([$target_id]);
    
    // Optional: Set post_ready = 0 to prevent it from showing up in the queue again automatically, and set post_quality = 'Poor'
    $db->prepare("UPDATE facebook_groups SET post_ready = 0, post_quality = 'Poor' WHERE id = ?")->execute([$target_id]);

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
