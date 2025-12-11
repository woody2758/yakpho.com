<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['user_id'])) {
        throw new Exception('User ID is required');
    }

    $userId = (int)$_GET['user_id'];
    $orderId = isset($_GET['orders_id']) ? (int)$_GET['orders_id'] : 0;

    $stmt = $db->prepare("SELECT user_id, user_name, user_lastname, user_mobile, user_email FROM user WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Fetch orders_msg if orderId is provided
    if ($orderId > 0) {
        $stmt_order = $db->prepare("SELECT orders_msg FROM orders WHERE orders_id = ?");
        $stmt_order->execute([$orderId]);
        $order = $stmt_order->fetch(PDO::FETCH_ASSOC);
        $user['orders_msg'] = $order ? $order['orders_msg'] : '';
    } else {
        $user['orders_msg'] = '';
    }

    echo json_encode(['success' => true, 'data' => $user]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
