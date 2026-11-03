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

    $stmt = $db->prepare("UPDATE orders SET orders_print = ?, update_id = ?, orders_update = NOW() WHERE orders_id = ?");
    $stmt->execute([$status, $_SESSION['admin_id'] ?? 0, $orders_id]);

    // Log Activity
    logStaffAction($db, 'UPDATE', 'orders', $orders_id, "Toggled print status to: " . ($status ? 'PRINTED' : 'NOT PRINTED'));

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
