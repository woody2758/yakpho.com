<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id']) || !isset($data['items']) || !is_array($data['items'])) {
        throw new Exception('Missing required parameters');
    }
    
    $user_id = (int)$data['user_id'];
    $items = $data['items'];
    $addr_id = isset($data['addr_id']) ? (int)$data['addr_id'] : null;
    $shipping_cost = isset($data['shipping_cost']) ? (float)$data['shipping_cost'] : 0;
    $order_msg = isset($data['order_msg']) ? trim($data['order_msg']) : '';
    
    if (empty($items)) {
        throw new Exception('กรุณาเลือกสินค้าอย่างน้อย 1 รายการ');
    }
    
    // ตรวจสอบ user
    $stmt = $db->prepare("SELECT user_id FROM user WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        throw new Exception('ไม่พบข้อมูลลูกค้า');
    }
    
    $db->beginTransaction();
    
    // 1. คำนวณยอดรวม
    $subtotal = 0;
    foreach ($items as $item) {
        if (!isset($item['product_id']) || !isset($item['qty']) || !isset($item['price'])) {
            throw new Exception('ข้อมูลสินค้าไม่ครบถ้วน');
        }
        $subtotal += (float)$item['price'] * (int)$item['qty'];
    }
    $grandtotal = $subtotal + $shipping_cost;
    
    // 2. สร้าง Order (สถานะเริ่มต้น = 1)
    $stmt = $db->prepare("INSERT INTO orders 
        (user_id, orders_date, orders_subtotal, orders_shipping, 
         orders_grandtotal, orders_status, orders_msg, addr_id) 
        VALUES (?, NOW(), ?, ?, ?, 1, ?, ?)");
    $stmt->execute([$user_id, $subtotal, $shipping_cost, $grandtotal, $order_msg, $addr_id]);
    
    $order_id = $db->lastInsertId();
    
    // 3. เพิ่มรายการสินค้า
    $stmt = $db->prepare("INSERT INTO ordetail 
        (orders_id, product_id, ordetail_qty, unit_price, subtotal) 
        VALUES (?, ?, ?, ?, ?)");
    
    foreach ($items as $item) {
        $product_id = (int)$item['product_id'];
        $qty = (int)$item['qty'];
        $price = (float)$item['price'];
        $item_total = $price * $qty;
        
        // ตรวจสอบว่าสินค้ามีอยู่จริง
        $check = $db->prepare("SELECT product_id FROM product WHERE product_id = ?");
        $check->execute([$product_id]);
        if (!$check->fetch()) {
            throw new Exception("ไม่พบสินค้า ID: {$product_id}");
        }
        
        $stmt->execute([$order_id, $product_id, $qty, $price, $item_total]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'สร้างออเดอร์สำเร็จ',
        'order_id' => $order_id,
        'grandtotal' => $grandtotal
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
