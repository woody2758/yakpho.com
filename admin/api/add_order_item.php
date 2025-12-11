<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['order_id']) || !isset($data['product_id']) || 
        !isset($data['qty']) || !isset($data['price'])) {
        throw new Exception('Missing required parameters');
    }
    
    $order_id = (int)$data['order_id'];
    $product_id = (int)$data['product_id'];
    $qty = (int)$data['qty'];
    $price = (float)$data['price'];
    
    if ($qty <= 0 || $price < 0) {
        throw new Exception('จำนวนหรือราคาไม่ถูกต้อง');
    }
    
    $db->beginTransaction();
    
    // ตรวจสอบว่ามี order อยู่จริง
    $stmt = $db->prepare("SELECT orders_id FROM orders WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    if (!$stmt->fetch()) {
        throw new Exception('ไม่พบออเดอร์');
    }
    
    // ตรวจสอบว่ามี product อยู่จริง
    $stmt = $db->prepare("SELECT product_id, product_name FROM product WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new Exception('ไม่พบสินค้า');
    }
    
    // 1. เพิ่มสินค้า
    $subtotal = $price * $qty;
    $stmt = $db->prepare("INSERT INTO ordetail 
        (orders_id, product_id, ordetail_qty, unit_price, subtotal) 
        VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$order_id, $product_id, $qty, $price, $subtotal]);
    
    // 2. คำนวณยอดรวมใหม่
    $stmt = $db->prepare("SELECT SUM(subtotal) as new_subtotal FROM ordetail WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_subtotal = $result['new_subtotal'];
    
    // 3. ดึงค่าจัดส่ง
    $stmt = $db->prepare("SELECT orders_shipping FROM orders WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
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
        'message' => 'เพิ่มสินค้าสำเร็จ',
        'product_name' => $product['product_name'],
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
