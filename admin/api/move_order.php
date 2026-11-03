<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$orders_id = isset($_POST['orders_id']) ? (int)$_POST['orders_id'] : 0;
$new_user_id = isset($_POST['new_user_id']) ? (int)$_POST['new_user_id'] : 0;

if ($orders_id <= 0 || $new_user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $db->beginTransaction();

    // Verify order exists
    $stmt = $db->prepare("SELECT * FROM orders WHERE orders_id = ?");
    $stmt->execute([$orders_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Order not found");
    }

    // Verify new user exists
    $stmt = $db->prepare("SELECT * FROM user WHERE user_id = ?");
    $stmt->execute([$new_user_id]);
    $newUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$newUser) {
        throw new Exception("Customer not found");
    }

    // Update the user_id in the orders table
    $stmt = $db->prepare("UPDATE orders SET user_id = ? WHERE orders_id = ?");
    $stmt->execute([$new_user_id, $orders_id]);
    
    // Check if order has addr_id, update user_id there too
    if ($order['addr_id'] > 0) {
        $stmt = $db->prepare("UPDATE addr SET user_id = ? WHERE addr_id = ?");
        $stmt->execute([$new_user_id, $order['addr_id']]);
    }

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'ย้ายออเดอร์สำเร็จ']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
