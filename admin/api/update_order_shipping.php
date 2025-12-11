<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['orders_id']) || !isset($data['transcat_id'])) {
        throw new Exception('Invalid input');
    }

    $orders_id = (int)$data['orders_id'];
    $transcat_id = (int)$data['transcat_id'];
    $tracking = isset($data['orders_tracking']) ? trim($data['orders_tracking']) : '';

    $stmt = $db->prepare("UPDATE orders SET transcat_id = ?, orders_tracking = ?, orders_update = NOW() WHERE orders_id = ?");
    $stmt->execute([$transcat_id, $tracking, $orders_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
