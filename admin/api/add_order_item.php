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
    
    // ตรวจสอบว่ามี order อยู่จริง และสถานะไม่ถูกล็อก
    $stmt = $db->prepare("SELECT orders_id, orders_status FROM orders WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $order_check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order_check) {
        throw new Exception('ไม่พบออเดอร์');
    }
    
    $lockedStatuses = [6, 11, 7, 8, 9];
    if (in_array((int)$order_check['orders_status'], $lockedStatuses)) {
        throw new Exception('ออเดอร์นี้ถูกล็อกแล้ว ไม่สามารถแก้ไขรายการสินค้าได้');
    }
    
    // ตรวจสอบว่ามี product อยู่จริง
    $stmt = $db->prepare("SELECT product_id, product_name, product_cprice FROM product WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new Exception('ไม่พบสินค้า');
    }
    $item_cost = isset($product['product_cprice']) ? (float)$product['product_cprice'] : 0.00;
    
    // 1. ตรวจสอบว่ามีสินค้าชนิดนี้ในออเดอร์อยู่แล้วหรือไม่
    $stmt = $db->prepare("SELECT ordetail_id, ordetail_qty, product_price FROM ordetail WHERE orders_id = ? AND product_id = ?");
    $stmt->execute([$order_id, $product_id]);
    $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_item) {
        // อัพเดทจำนวนเดิม (ใช้ราคาใหม่ที่ส่งมา)
        $new_qty = $existing_item['ordetail_qty'] + $qty;
        $subtotal = $price * $new_qty;
        
        $stmt = $db->prepare("UPDATE ordetail SET 
            ordetail_qty = ?, 
            product_price = ?, 
            subtotal = ?,
            item_cost = ? 
            WHERE ordetail_id = ?");
        $stmt->execute([$new_qty, $price, $subtotal, $item_cost, $existing_item['ordetail_id']]);
    } else {
        // เพิ่มรายการใหม่
        $subtotal = $price * $qty;
        $stmt = $db->prepare("INSERT INTO ordetail 
            (orders_id, product_id, ordetail_qty, product_price, subtotal, item_cost) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $product_id, $qty, $price, $subtotal, $item_cost]);
    }
    
    // 2. คำนวณยอดรวมใหม่
    $stmt = $db->prepare("SELECT SUM(subtotal) as new_subtotal FROM ordetail WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_subtotal = $result['new_subtotal'] ?? 0;
    
    // 3. ดึงข้อมูลประกอบการคำนวณยอดสุทธิ (ค่าจัดส่ง, ส่วนลด, ช่องทางชำระเงิน) เพื่อรักษาความถูกต้องของระบบเงิน
    $stmt = $db->prepare("SELECT orders_delicost, orders_discount, paycat_id FROM orders WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $shipping = $order ? (float)$order['orders_delicost'] : 0.00;
    $discount = $order ? (float)$order['orders_discount'] : 0.00;
    $paycat_id = $order ? (int)$order['paycat_id'] : 1;
    
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
    logStaffAction($db, 'UPDATE', 'orders', $order_id, "Added item to order: " . ($product['product_name'] ?? "ID $product_id"));

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
