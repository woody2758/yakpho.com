<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Customer ID']);
    exit;
}

try {
    // Get customer name and info with channel/country from their most recent order
    $stmt_cust = $db->prepare("
        SELECT 
            user_id, 
            user_name, 
            user_lastname, 
            user_nickname, 
            user_mobile, 
            user_tel, 
            user_email, 
            user_picture,
            (
                SELECT o.orders_channel 
                FROM orders o 
                WHERE o.user_id = user.user_id AND o.orders_del = 0 AND o.orders_status NOT IN (8, 9) 
                ORDER BY o.orders_date DESC LIMIT 1
            ) AS last_channel,
            (
                SELECT c.country_name 
                FROM orders o 
                LEFT JOIN countries c ON o.orders_country_id = c.country_id
                WHERE o.user_id = user.user_id AND o.orders_del = 0 AND o.orders_status NOT IN (8, 9) 
                ORDER BY o.orders_date DESC LIMIT 1
            ) AS last_country
        FROM user 
        WHERE user_id = :id
    ");
    $stmt_cust->execute(['id' => $userId]);
    $customer = $stmt_cust->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit;
    }

    // Format customer ID prefix: e.g. C000123
    $customer['formatted_id'] = 'C' . str_pad($customer['user_id'], 6, '0', STR_PAD_LEFT);

    // Get purchase history (excluding cancelled orders 8, 9)
    $stmt_orders = $db->prepare("
        SELECT 
            orders_id,
            orders_no AS orders_number,
            orders_date,
            orders_grandtotal,
            orders_status,
            orders_channel
        FROM orders
        WHERE user_id = :id AND orders_del = 0 AND orders_status NOT IN (8, 9)
        ORDER BY orders_date DESC
    ");
    $stmt_orders->execute(['id' => $userId]);
    $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

    // Get details of products purchased by this user
    $stmt_items = $db->prepare("
        SELECT 
            od.orders_id,
            p.product_code,
            p.product_name,
            od.ordetail_qty,
            od.product_price
        FROM ordetail od
        JOIN product p ON od.product_id = p.product_id
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE o.user_id = :id AND o.orders_del = 0 AND o.orders_status NOT IN (8, 9)
    ");
    $stmt_items->execute(['id' => $userId]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Group items by order ID
    $items_by_order = [];
    foreach ($items as $item) {
        $oid = $item['orders_id'];
        if (!isset($items_by_order[$oid])) {
            $items_by_order[$oid] = [];
        }
        $items_by_order[$oid][] = $item;
    }

    // Attach items to orders
    foreach ($orders as &$order) {
        $oid = $order['orders_id'];
        $order['items'] = $items_by_order[$oid] ?? [];
    }

    echo json_encode([
        'success' => true,
        'customer' => $customer,
        'orders' => $orders
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
