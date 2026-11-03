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
    $paycat_id = isset($data['paycat_id']) ? (int)$data['paycat_id'] : 1; // Default to ID 1 (Transfer/Cash)
    $discount = isset($data['orders_discount']) ? (float)$data['orders_discount'] : 0;
    $order_msg = isset($data['order_msg']) ? trim($data['order_msg']) : '';
    $orders_channel = !empty($data['orders_channel']) ? trim($data['orders_channel']) : null;
    $orders_country_id = isset($data['orders_country_id']) ? (int)$data['orders_country_id'] : 171;
    $agent_user_id = !empty($data['agent_user_id']) ? (int)$data['agent_user_id'] : null;
    
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

    // COD Fee (Rounded to integer)
    $cod_fee = ($paycat_id == 2) ? round($subtotal * 0.03) : 0;
    $grandtotal = ($subtotal + $shipping_cost + $cod_fee) - $discount;
    
    $orders_is_vat = isset($data['orders_is_vat']) ? (int)$data['orders_is_vat'] : 0;
    
    // สร้างเลขที่ออเดอร์ (orders_no) รูปแบบ YYYYMMDD + สุ่ม 4 หลัก
    $orders_no = date('Ymd') . rand(1000, 9999);
    
    // 2. สร้าง Order (สถานะเริ่มต้น = 1)
    $stmt = $db->prepare("INSERT INTO orders 
        (user_id, orders_no, orders_date, orders_total, orders_delicost, orders_discount, orders_cod,
         orders_grandtotal, orders_status, orders_msg, addr_id, orders_is_vat, paycat_id, 
         orders_channel, orders_country_id, agent_user_id, save_id) 
        VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id, $orders_no, $subtotal, $shipping_cost, $discount, $cod_fee, 
        $grandtotal, $order_msg, $addr_id, $orders_is_vat, $paycat_id, 
        $orders_channel, $orders_country_id, $agent_user_id, $_SESSION['admin_id'] ?? 0
    ]);
    
    $order_id = $db->lastInsertId();

    // 2.1 Update User Last Active
    $stmt_active = $db->prepare("UPDATE user SET user_last_active = NOW() WHERE user_id = ?");
    $stmt_active->execute([$user_id]);
    
    // 3. เพิ่มรายการสินค้า
    $stmt = $db->prepare("INSERT INTO ordetail 
        (orders_id, product_id, ordetail_qty, product_price, subtotal, ordetail_memo, item_cost) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($items as $item) {
        $product_id = (int)$item['product_id'];
        $qty = (int)$item['qty'];
        $price = (float)$item['price'];
        $memo = $item['memo'] ?? '';
        $item_total = $price * $qty;
        
        // ตรวจสอบว่าสินค้ามีอยู่จริง และดึงต้นทุนสินค้ามาด้วย
        $check = $db->prepare("SELECT product_id, product_cprice FROM product WHERE product_id = ?");
        $check->execute([$product_id]);
        $prod = $check->fetch(PDO::FETCH_ASSOC);
        if (!$prod) {
            throw new Exception("ไม่พบสินค้า ID: {$product_id}");
        }
        $item_cost = isset($prod['product_cprice']) ? (float)$prod['product_cprice'] : 0.00;
        
        $stmt->execute([$order_id, $product_id, $qty, $price, $item_total, $memo, $item_cost]);
    }
    
    $db->commit();
    
    // Log Activity
    logStaffAction($db, 'CREATE', 'orders', $order_id, "Created new order #{$orders_no} - Total: " . number_format($grandtotal, 2));
    
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
