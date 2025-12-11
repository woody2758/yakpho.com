<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    $addrId = isset($_POST['addr_id']) ? (int)$_POST['addr_id'] : 0;

    if (!$orderId) throw new Exception('Order ID is required');
    if (!in_array($type, ['sender', 'receiver'])) throw new Exception('Invalid address type');

    $column = ($type === 'sender') ? 'addrsender_id' : 'addr_id';

    $stmt = $db->prepare("UPDATE orders SET $column = ? WHERE orders_id = ?");
    $stmt->execute([$addrId, $orderId]);

    echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
