<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $orderId = isset($_POST['orders_id']) ? (int)$_POST['orders_id'] : 0;
    $name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
    $lastname = isset($_POST['user_lastname']) ? trim($_POST['user_lastname']) : '';
    $mobile = isset($_POST['user_mobile']) ? trim($_POST['user_mobile']) : '';
    $email = isset($_POST['user_email']) ? trim($_POST['user_email']) : '';
    $ordersMsg = isset($_POST['orders_msg']) ? trim($_POST['orders_msg']) : '';

    if (!$userId) {
        throw new Exception('User ID is required');
    }

    if (empty($name)) {
        throw new Exception('First name is required');
    }

    // Update User
    $stmt = $db->prepare("UPDATE user SET 
        user_name = ?, 
        user_lastname = ?, 
        user_mobile = ?, 
        user_email = ?
        WHERE user_id = ?");

    $stmt->execute([$name, $lastname, $mobile, $email, $userId]);

    // Update Order Message if orderId is provided
    if ($orderId > 0) {
        $stmt_order = $db->prepare("UPDATE orders SET orders_msg = ? WHERE orders_id = ?");
        $stmt_order->execute([$ordersMsg, $orderId]);
    }

    echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
