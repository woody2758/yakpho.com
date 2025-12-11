<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

try {
    $id = (int)$_GET['id'];

    // 1. Get Order Info
    $stmt = $db->prepare("SELECT o.*, 
            u.user_name, u.user_lastname, u.user_email, u.user_mobile as user_tel,
            s.orsts_detail, s.orsts_color, s.orsts_code
            FROM orders o
            LEFT JOIN user u ON o.user_id = u.user_id
            LEFT JOIN orsts s ON o.orders_status = s.orsts_id
            WHERE o.orders_id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // 2. Get Order Items
    $stmt = $db->prepare("SELECT d.*, p.product_name, p.product_code, p.product_picture as product_img
            FROM ordetail d
            LEFT JOIN product p ON d.product_id = p.product_id
            WHERE d.orders_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Shipping Address
    // Assuming addr_id is stored in orders table or we pick the latest address for user
    // Based on schema, orders doesn't seem to have addr_id directly, checking schema again...
    // Schema check showed 'addr' table has 'user_id'. 
    // Usually orders should have a snapshot of address. If not, we fetch user's current address.
    // Let's try to find address linked to this order if possible, or user's default.
    // For now, fetching user's address.
    $stmt = $db->prepare("SELECT * FROM addr WHERE user_id = ? ORDER BY addr_id DESC LIMIT 1");
    $stmt->execute([$order['user_id']]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. Get All Statuses for Dropdown
    $stmt = $db->query("SELECT * FROM orsts WHERE orsts_del = 0 ORDER BY orsts_index ASC");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Get Payment Proofs
    $stmt = $db->prepare("SELECT * FROM order_slips WHERE orders_id = ? ORDER BY slip_uploaded_at DESC");
    $stmt->execute([$id]);
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Backward compatibility: if no slips in new table but orders_slip exists
    if (empty($slips) && !empty($order['orders_slip'])) {
        $slips[] = [
            'slip_id' => 0,
            'slip_filename' => $order['orders_slip'],
            'slip_uploaded_at' => null
        ];
    }

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items,
        'address' => $address,
        'statuses' => $statuses,
        'slips' => $slips
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
