<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['order_id']) || !isset($data['is_vat'])) {
        throw new Exception('Missing required parameters');
    }
    
    $order_id = (int)$data['order_id'];
    $is_vat = (int)$data['is_vat']; // 1 or 0
    
    $db->beginTransaction();
    
    // Check if order exists and not locked
    $stmt = $db->prepare("SELECT orders_status FROM orders WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $order_check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order_check) throw new Exception('ไม่พบออเดอร์');
    
    $lockedStatuses = [6, 11, 7, 8, 9];
    if (in_array((int)$order_check['orders_status'], $lockedStatuses)) {
        throw new Exception('ออเดอร์นี้ถูกล็อกแล้ว ไม่สามารถแก้ไขสถานะ VAT ได้');
    }

    // Update orders_is_vat (or orders_vat if exists, adding both to be safe or assuming one)
    // Based on previous JS edits, we used orders_is_vat
    $stmt = $db->prepare("UPDATE orders SET orders_is_vat = ?, update_id = ?, orders_update = NOW() WHERE orders_id = ?");
    $stmt->execute([$is_vat, $_SESSION['admin_id'] ?? 0, $order_id]);

    // Log Activity
    logStaffAction($db, 'UPDATE', 'orders', $order_id, "Updated VAT status to: " . ($is_vat ? 'YES' : 'NO'));

    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'อัพเดทสถาณะ VAT สำเร็จ',
        'is_vat' => $is_vat
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
