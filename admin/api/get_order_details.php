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
    $stmt = $db->prepare("SELECT d.*, COALESCE(pt.product_name, p.product_name, 'Unknown') as product_name, p.product_code, p.product_picture as product_img, p.vat_type
            FROM ordetail d
            LEFT JOIN product p ON d.product_id = p.product_id
            LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = 'th'
            WHERE d.orders_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Shipping Address
    $address = null;
    if (!empty($order['addr_id'])) {
        $stmt = $db->prepare("SELECT a.*, p.name_th as province_name 
                            FROM addr a 
                            LEFT JOIN provinces p ON a.provinces_id = p.provinces_id 
                            WHERE a.addr_id = ?");
        $stmt->execute([$order['addr_id']]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 3.1 Get Billing Address
    $billing_address = null;
    if (!empty($order['billing_addr_id'])) {
        $stmt = $db->prepare("SELECT a.*, p.name_th as province_name 
                            FROM addr a 
                            LEFT JOIN provinces p ON a.provinces_id = p.provinces_id 
                            WHERE a.addr_id = ?");
        $stmt->execute([$order['billing_addr_id']]);
        $billing_address = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Fallback if no address linked to order
    if (!$address) {
        $address = [
            'addr_name' => '',
            'addr_detail' => '- ไม่พบที่อยู่ -',
            'addr_detail2' => '',
            'province_name' => '',
            'addr_postcode' => '',
            'addr_mobile' => ''
        ];
    }

    // 4. Get All Statuses for Dropdown
    $stmt = $db->query("SELECT * FROM orsts WHERE orsts_del = 0 AND orsts_status = 1 ORDER BY orsts_index ASC");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT * FROM order_slips WHERE orders_id = ? ORDER BY slip_uploaded_at DESC");
    $stmt->execute([$id]);
    $rawSlips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $slips = [];
    foreach ($rawSlips as $slip) {
        $filePath = __DIR__ . '/../../uploads/slips/' . $slip['slip_filename'];
        if (file_exists($filePath)) {
            $slips[] = $slip;
        }
    }

    // 6. Get All Payment Categories
    $stmt = $db->query("SELECT * FROM paycat ORDER BY paycat_id ASC");
    $paycats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Get All Shipping Categories
    $stmt = $db->query("SELECT * FROM transcat ORDER BY transcat_id ASC");
    $transcats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. Get All Sales Channels
    $stmt = $db->query("SELECT * FROM sales_channels WHERE channel_status = 1 ORDER BY channel_id ASC");
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 9. Get All Countries
    $stmt = $db->query("SELECT * FROM countries ORDER BY country_name ASC");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 10. Get All Potential Agents (Staff/Admins)
    $stmt = $db->query("SELECT user_id, user_name, user_lastname, role FROM user WHERE user_del = 0 AND role IN ('admin', 'manager', 'owner', 'staff') ORDER BY user_name ASC");
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 11. Get Current Agent Info if set
    $current_agent = null;
    if (!empty($order['agent_user_id'])) {
        $stmt_agent = $db->prepare("SELECT user_id, user_name, user_lastname, role FROM user WHERE user_id = ?");
        $stmt_agent->execute([$order['agent_user_id']]);
        $current_agent = $stmt_agent->fetch(PDO::FETCH_ASSOC);
    }

    // Backward compatibility: if no slips in new table but orders_slip exists
    if (empty($slips) && !empty($order['orders_slip'])) {
        $filePath = __DIR__ . '/../../uploads/slips/' . $order['orders_slip'];
        if (file_exists($filePath)) {
            $slips[] = [
                'slip_id' => 0,
                'slip_filename' => $order['orders_slip'],
                'slip_uploaded_at' => null
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items,
        'address' => $address,
        'billing_address' => $billing_address,
        'statuses' => $statuses,
        'slips' => $slips,
        'paycats' => $paycats,
        'transcats' => $transcats,
        'channels' => $channels,
        'countries' => $countries,
        'agents' => $agents,
        'current_agent' => $current_agent
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
