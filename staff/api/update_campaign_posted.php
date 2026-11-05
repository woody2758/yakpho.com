<?php
header('Content-Type: application/json');
require_once "../db.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid API Key']);
    exit;
}

$queue_id = $input['queue_id'] ?? 0;
$status = $input['status'] ?? 'success'; // success or failed
$error_msg = $input['error_msg'] ?? '';

if (!$queue_id) {
    file_put_contents('bot_update_log.txt', date('Y-m-d H:i:s') . " - Missing queue_id: " . $inputJSON . "\n", FILE_APPEND);
    echo json_encode(['ok' => false, 'msg' => 'Missing queue_id']);
    exit;
}

file_put_contents('bot_update_log.txt', date('Y-m-d H:i:s') . " - Received: " . $inputJSON . "\n", FILE_APPEND);

$stmt = $db->prepare("UPDATE fb_campaign_schedules SET status = ?, error_msg = ? WHERE id = ?");
$success = $stmt->execute([$status, $error_msg, $queue_id]);

if ($success) {
    echo json_encode(['ok' => true, 'msg' => 'Queue updated to ' . $status]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Failed to update queue']);
}
