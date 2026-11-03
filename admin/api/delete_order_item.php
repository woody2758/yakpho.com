<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['ordetail_id']) || !isset($data['order_id'])) {
        throw new Exception('Missing required parameters');
    }
    
    $ordetail_id = (int)$data['ordetail_id'];
    $order_id = (int)$data['order_id'];
    
    $db->beginTransaction();
    
    // 1. ตรวจสอบสถานะก่อนลบ
    $stmt = $db->prepare("SELECT orders_status, orders_delicost FROM orders WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $order_check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order_check) {
        throw new Exception('ไม่พบข้อมูลออเดอร์');
    }

    $lockedStatuses = [6, 11, 7, 8, 9];
    if (in_array((int)$order_check['orders_status'], $lockedStatuses)) {
        throw new Exception('ออเดอร์นี้ถูกล็อกแล้ว ไม่สามารถลบรายการสินค้าได้');
    }

    // 2. ลบรายการสินค้า
    $stmt = $db->prepare("DELETE FROM ordetail WHERE ordetail_id = ?");
    $stmt->execute([$ordetail_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('ไม่พบรายการสินค้าที่ต้องการลบ');
    }
    
    // 3. คำนวณยอดรวมใหม่
    $stmt = $db->prepare("SELECT SUM(subtotal) as new_subtotal FROM ordetail WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_subtotal = $result['new_subtotal'] ?? 0;
    
    // Fetch discount and paycat_id to correctly preserve calculations
    $stmt_sum = $db->prepare("SELECT orders_discount, paycat_id FROM orders WHERE orders_id = ?");
    $stmt_sum->execute([$order_id]);
    $order_meta = $stmt_sum->fetch(PDO::FETCH_ASSOC);
    
    $shipping = $order_check['orders_delicost'] ?? 0;
    $discount = $order_meta ? (float)$order_meta['orders_discount'] : 0.00;
    $paycat_id = $order_meta ? (int)$order_meta['paycat_id'] : 1;
    
    $cod_fee = ($paycat_id == 2) ? round($new_subtotal * 0.03) : 0;
    $new_grandtotal = ($new_subtotal + $shipping + $cod_fee) - $discount;
    
    // 4. อัพเดท orders
    $stmt = $db->prepare("UPDATE orders SET 
        orders_total = ?, 
        orders_cod = ?,
        orders_grandtotal = ?,
        update_id = ?,
        orders_update = NOW()
        WHERE orders_id = ?");
    $stmt->execute([$new_subtotal, $cod_fee, $new_grandtotal, $_SESSION['admin_id'] ?? 0, $order_id]);
    
    // Log Activity
    logStaffAction($db, 'UPDATE', 'orders', $order_id, "Deleted item (ID: $ordetail_id) from order");

    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ลบสินค้าสำเร็จ',
        'new_subtotal' => $new_subtotal,
        'new_grandtotal' => $new_grandtotal
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
