<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['orders_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing orders_id']);
    exit;
}

$orders_id = (int)$_POST['orders_id'];
$channel_code = isset($_POST['channel_code']) ? trim($_POST['channel_code']) : '';

try {
    $stmt = $db->prepare("UPDATE orders SET orders_channel = ? WHERE orders_id = ?");
    $stmt->execute([$channel_code, $orders_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
