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
    
    // 1. ลบรายการสินค้า
    $stmt = $db->prepare("DELETE FROM ordetail WHERE ordetail_id = ?");
    $stmt->execute([$ordetail_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('ไม่พบรายการสินค้าที่ต้องการลบ');
    }
    
    // 2. คำนวณยอดรวมใหม่
    $stmt = $db->prepare("SELECT SUM(subtotal) as new_subtotal FROM ordetail WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_subtotal = $result['new_subtotal'] ?? 0;
    
    // 3. ดึงค่าจัดส่ง
    $stmt = $db->prepare("SELECT orders_shipping FROM orders WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('ไม่พบข้อมูลออเดอร์');
    }
    
    $shipping = $order['orders_shipping'] ?? 0;
    $new_grandtotal = $new_subtotal + $shipping;
    
    // 4. อัพเดท orders
    $stmt = $db->prepare("UPDATE orders SET 
        orders_subtotal = ?, 
        orders_grandtotal = ? 
        WHERE orders_id = ?");
    $stmt->execute([$new_subtotal, $new_grandtotal, $order_id]);
    
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
