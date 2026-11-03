<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['order_id']) || !isset($data['amount'])) {
        throw new Exception('Missing required parameters');
    }
    
    $order_id = (int)$data['order_id'];
    $amount = (float)$data['amount'];
    
    if ($amount < 0) throw new Exception('ยอดเงินต้องไม่ติดลบ');
    
    $db->beginTransaction();
    
    // Check if order exists
    $stmt = $db->prepare("SELECT orders_id FROM orders WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    if (!$stmt->fetch()) throw new Exception('ไม่พบออเดอร์');

    // Update orders_paid_amount and track who updated it
    $stmt = $db->prepare("UPDATE orders SET orders_paid_amount = ?, update_id = ?, orders_update = NOW() WHERE orders_id = ?");
    $stmt->execute([$amount, $_SESSION['admin_id'] ?? 0, $order_id]);

    // Log Activity
    logStaffAction($db, 'UPDATE', 'orders', $order_id, "Updated paid amount to " . number_format($amount, 2));

    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'บันทึกยอดชำระเรียบร้อยแล้ว',
        'paid_amount' => $amount
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
