<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['order_id'])) {
        throw new Exception('Order ID is required');
    }

    $orderId = (int)$_GET['order_id'];

    // Get addr_id from orders
    $stmt = $db->prepare("SELECT addr_id FROM orders WHERE orders_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || !$order['addr_id']) {
        throw new Exception('Order or Address not found');
    }

    // Get address details
    $stmt = $db->prepare("SELECT * FROM addr WHERE addr_id = ?");
    $stmt->execute([$order['addr_id']]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$address) {
        throw new Exception('Address details not found');
    }

    echo json_encode(['success' => true, 'data' => $address]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
