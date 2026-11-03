<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['ordetail_id']) || !isset($data['price']) || !isset($data['order_id'])) {
        throw new Exception('Missing required parameters');
    }
    
    $ordetail_id = (int)$data['ordetail_id'];
    $price = (float)$data['price'];
    $order_id = (int)$data['order_id'];
    
    if ($price < 0) throw new Exception('ราคาต้องไม่ติดลบ');
    
    $db->beginTransaction();
    
    // Check if order is locked
    $stmt = $db->prepare("SELECT orders_status, orders_delicost, orders_is_vat FROM orders WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $order_check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order_check) throw new Exception('ไม่พบออเดอร์');
    
    $lockedStatuses = [6, 11, 7, 8, 9];
    if (in_array((int)$order_check['orders_status'], $lockedStatuses)) {
        throw new Exception('ออเดอร์นี้ถูกล็อกแล้ว ไม่สามารถแก้ไขราคาได้');
    }

    // 1. Get current item quantity
    $stmt = $db->prepare("SELECT ordetail_qty FROM ordetail WHERE ordetail_id = ?");
    $stmt->execute([$ordetail_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) throw new Exception('ไม่พบรายการสินค้า');
    
    $qty = (int)$item['ordetail_qty'];
    $subtotal = $price * $qty;

    // 2. Update price and subtotal
    $stmt = $db->prepare("UPDATE ordetail SET product_price = ?, subtotal = ? WHERE ordetail_id = ?");
    $stmt->execute([$price, $subtotal, $ordetail_id]);

    // 3. Recalculate Order Totals
    $stmt = $db->prepare("SELECT SUM(subtotal) as new_subtotal FROM ordetail WHERE orders_id = ?");
    $stmt->execute([$order_id]);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_subtotal = $totals ? (float)$totals['new_subtotal'] : 0.00;
    
    // Fetch discount and paycat_id to correctly preserve calculations
    $stmt_sum = $db->prepare("SELECT orders_discount, paycat_id FROM orders WHERE orders_id = ?");
    $stmt_sum->execute([$order_id]);
    $order_meta = $stmt_sum->fetch(PDO::FETCH_ASSOC);
    
    $shipping = (float)$order_check['orders_delicost'];
    $discount = $order_meta ? (float)$order_meta['orders_discount'] : 0.00;
    $paycat_id = $order_meta ? (int)$order_meta['paycat_id'] : 1;
    
    $cod_fee = ($paycat_id == 2) ? round($new_subtotal * 0.03) : 0;
    $new_grandtotal = ($new_subtotal + $shipping + $cod_fee) - $discount;

    $stmt = $db->prepare("UPDATE orders SET orders_total = ?, orders_cod = ?, orders_grandtotal = ?, update_id = ?, orders_update = NOW() WHERE orders_id = ?");
    $stmt->execute([$new_subtotal, $cod_fee, $new_grandtotal, $_SESSION['admin_id'] ?? 0, $order_id]);

    // Log Activity
    logStaffAction($db, 'UPDATE', 'orders', $order_id, "Updated item price (ID: $ordetail_id) to " . number_format($price, 2));

    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'อัพเดทราคาสำเร็จ',
        'new_subtotal' => $new_subtotal,
        'new_grandtotal' => $new_grandtotal
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
