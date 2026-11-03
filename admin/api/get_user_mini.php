<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['user_id'])) {
        throw new Exception('User ID is required');
    }

    $userId = (int)$_GET['user_id'];
    $orderId = isset($_GET['orders_id']) ? (int)$_GET['orders_id'] : 0;

    $stmt = $db->prepare("SELECT user_id, user_name, user_lastname, user_nickname, user_mobile, user_email, user_note, user_google_map, user_website, member_tier, m_stamp_points, line_user_id, line_display_name FROM user WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Fetch primary/default address from addr table
    $stmt_addr = $db->prepare("SELECT addr_id, addr_name, addr_mobile, addr_detail, addr_detail2, provinces_id, addr_postcode, addr_tax_id FROM addr WHERE user_id = ? AND addr_del = 0 ORDER BY addr_position DESC, addr_id DESC LIMIT 1");
    $stmt_addr->execute([$userId]);
    $user['address'] = $stmt_addr->fetch(PDO::FETCH_ASSOC) ?: null;

    // Fetch provinces list for select dropdown
    $stmt_prov = $db->query("SELECT provinces_id, name_th FROM provinces ORDER BY name_th ASC");
    $provinces = $stmt_prov->fetchAll(PDO::FETCH_ASSOC);

    // Fetch orders_msg if orderId is provided
    if ($orderId > 0) {
        $stmt_order = $db->prepare("SELECT orders_msg FROM orders WHERE orders_id = ?");
        $stmt_order->execute([$orderId]);
        $order = $stmt_order->fetch(PDO::FETCH_ASSOC);
        $user['orders_msg'] = $order ? $order['orders_msg'] : '';
    } else {
        $user['orders_msg'] = '';
    }

    echo json_encode([
        'success' => true, 
        'data' => $user,
        'provinces' => $provinces
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
