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
    if (!in_array($type, ['sender', 'receiver', 'billing'])) throw new Exception('Invalid address type');

    $column = 'addr_id';
    if ($type === 'sender') $column = 'addrsender_id';
    if ($type === 'billing') $column = 'billing_addr_id';

    $stmt = $db->prepare("UPDATE orders SET $column = ?, update_id = ?, orders_update = NOW() WHERE orders_id = ?");
    $stmt->execute([$addrId ?: null, $_SESSION['admin_id'] ?? 0, $orderId]);

    // Log Activity
    logStaffAction($db, 'UPDATE', 'orders', $orderId, "Updated order address ($type) to ID: $addrId");

    echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
