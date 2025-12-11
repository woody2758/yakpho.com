<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['orders_id']) || !isset($data['status'])) {
        throw new Exception('Invalid input');
    }

    $orders_id = (int)$data['orders_id'];
    $status = (int)$data['status']; // 1 = printed, 0 = not printed

    $stmt = $db->prepare("UPDATE orders SET orders_print = ? WHERE orders_id = ?");
    $stmt->execute([$status, $orders_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
