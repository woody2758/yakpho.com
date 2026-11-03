<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['order_id'])) {
        throw new Exception('Missing order_id');
    }

    $original_order_id = (int)$data['order_id'];

    $db->beginTransaction();

    // 1. Fetch original order
    $stmt = $db->prepare("SELECT * FROM orders WHERE orders_id = ?");
    $stmt->execute([$original_order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found');
    }

    // 2. Prepare new order data
    $orders_no = date('Ymd') . rand(1000, 9999);
    $track_token = bin2hex(random_bytes(16));
    $admin_id = $_SESSION['admin_id'] ?? 0;

    // Fields to copy (leaving out specific things that shouldn't copy)
    // orders_msg set to empty per user request.
    $insert_sql = "INSERT INTO orders (
        user_id, orders_no, orders_date, orders_total, orders_delicost, 
        orders_discount, orders_cod, orders_grandtotal, orders_status, 
        orders_msg, addr_id, addrsender_id, billing_addr_id, 
        orders_is_vat, paycat_id, transcat_id, orders_channel, orders_country_id, 
        agent_user_id, save_id, track_token, orders_paid_amount, orders_tracking, orders_print
    ) VALUES (
        :user_id, :orders_no, NOW(), :orders_total, :orders_delicost,
        :orders_discount, :orders_cod, :orders_grandtotal, 1,
        '', :addr_id, :addrsender_id, :billing_addr_id,
        :orders_is_vat, :paycat_id, :transcat_id, :orders_channel, :orders_country_id,
        :agent_user_id, :save_id, :track_token, 0, NULL, 0
    )";

    $stmt_insert = $db->prepare($insert_sql);
    $stmt_insert->execute([
        ':user_id' => $order['user_id'],
        ':orders_no' => $orders_no,
        ':orders_total' => $order['orders_total'],
        ':orders_delicost' => $order['orders_delicost'],
        ':orders_discount' => $order['orders_discount'],
        ':orders_cod' => $order['orders_cod'],
        ':orders_grandtotal' => $order['orders_grandtotal'],
        ':addr_id' => $order['addr_id'],
        ':addrsender_id' => $order['addrsender_id'] ?? 0,
        ':billing_addr_id' => $order['billing_addr_id'] ?? 0,
        ':orders_is_vat' => $order['orders_is_vat'],
        ':paycat_id' => $order['paycat_id'],
        ':transcat_id' => $order['transcat_id'] ?? 0,
        ':orders_channel' => $order['orders_channel'],
        ':orders_country_id' => $order['orders_country_id'] ?? 171,
        ':agent_user_id' => $order['agent_user_id'] ?? 0,
        ':save_id' => $admin_id,
        ':track_token' => $track_token
    ]);

    $new_order_id = $db->lastInsertId();

    // 3. Copy items
    $stmt_items = $db->prepare("SELECT * FROM ordetail WHERE orders_id = ?");
    $stmt_items->execute([$original_order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    if (count($items) > 0) {
        $insert_item_sql = "INSERT INTO ordetail (
            orders_id, product_id, ordetail_qty, product_price, subtotal, ordetail_memo, item_cost
        ) VALUES (
            :orders_id, :product_id, :ordetail_qty, :product_price, :subtotal, :ordetail_memo, :item_cost
        )";
        $stmt_insert_item = $db->prepare($insert_item_sql);

        foreach ($items as $item) {
            $stmt_insert_item->execute([
                ':orders_id' => $new_order_id,
                ':product_id' => $item['product_id'],
                ':ordetail_qty' => $item['ordetail_qty'],
                ':product_price' => $item['product_price'],
                ':subtotal' => $item['subtotal'],
                ':ordetail_memo' => $item['ordetail_memo'],
                ':item_cost' => $item['item_cost'] ?? 0
            ]);
        }
    }

    $db->commit();

    // Log Activity
    logStaffAction($db, 'CREATE', 'orders', $new_order_id, "Reordered from #{$order['orders_no']} - New order #{$orders_no}");

    echo json_encode([
        'success' => true,
        'message' => 'สร้างออเดอร์ใหม่สำเร็จ',
        'new_order_id' => $new_order_id
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
