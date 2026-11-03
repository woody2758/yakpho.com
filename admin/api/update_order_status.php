<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['orders_id'] ?? 0;
$statusId = $input['orsts_id'] ?? 0;
$paycatId = $input['paycat_id'] ?? null;
$transcatId = $input['transcat_id'] ?? null;
$tracking = $input['orders_tracking'] ?? null;

if (empty($orderId)) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    // Dynamically build the update query to only update provided fields
    $updates = [];
    $params = [];
    
    if (!empty($statusId)) {
        $updates[] = "orders_status = ?";
        $params[] = $statusId;
    }
    
    if ($paycatId !== null) {
        $updates[] = "paycat_id = ?";
        $params[] = $paycatId;
    }
    
    if (isset($input['orders_discount'])) {
        $updates[] = "orders_discount = ?";
        $params[] = (float)$input['orders_discount'];
    }

    if (isset($input['orders_delicost'])) {
        $updates[] = "orders_delicost = ?";
        $params[] = (float)$input['orders_delicost'];
    }

    // Always recalculate grandtotal if paycat, discount, or shipping is updated
    if ($paycatId !== null || isset($input['orders_discount']) || isset($input['orders_delicost'])) {
        $stmt_sum = $db->prepare("SELECT orders_total, orders_delicost, orders_discount, paycat_id FROM orders WHERE orders_id = ?");
        $stmt_sum->execute([$orderId]);
        $order_meta = $stmt_sum->fetch(PDO::FETCH_ASSOC);
        
        if ($order_meta) {
            $itemsTotal = (float)$order_meta['orders_total'];
            $shipping = isset($input['orders_delicost']) ? (float)$input['orders_delicost'] : (float)$order_meta['orders_delicost'];
            $discount = isset($input['orders_discount']) ? (float)$input['orders_discount'] : (float)$order_meta['orders_discount'];
            $currentPaycat = ($paycatId !== null) ? $paycatId : $order_meta['paycat_id'];

            $codFee = ($currentPaycat == 2) ? round($itemsTotal * 0.03) : 0;
            $newGrandTotal = ($itemsTotal + $shipping + $codFee) - $discount;
            
            $updates[] = "orders_cod = ?";
            $params[] = $codFee;
            $updates[] = "orders_grandtotal = ?";
            $params[] = $newGrandTotal;
        }
    }
    
    if ($transcatId !== null) {
        $updates[] = "transcat_id = ?";
        $params[] = $transcatId;
    }
    
    if ($tracking !== null) {
        $updates[] = "orders_tracking = ?";
        $params[] = $tracking;
    }
    
    if (isset($input['orders_msg'])) {
        $updates[] = "orders_msg = ?";
        $params[] = $input['orders_msg'];
    }

    if (isset($input['billing_type'])) {
        $updates[] = "billing_type = ?";
        $params[] = $input['billing_type'];
    }
    if (isset($input['tax_id'])) {
        $updates[] = "tax_id = ?";
        $params[] = $input['tax_id'];
    }
    if (isset($input['branch_code'])) {
        $updates[] = "branch_code = ?";
        $params[] = $input['branch_code'];
    }
    if (isset($input['orders_channel'])) {
        $updates[] = "orders_channel = ?";
        $params[] = $input['orders_channel'];
    }
    if (isset($input['orders_country_id'])) {
        $updates[] = "orders_country_id = ?";
        $params[] = (int)$input['orders_country_id'];
    }
    if (array_key_exists('agent_user_id', $input)) {
        $updates[] = "agent_user_id = ?";
        $params[] = !empty($input['agent_user_id']) ? (int)$input['agent_user_id'] : null;
    }
    
    $updates[] = "orders_update = NOW()";
    $updates[] = "update_id = ?";
    $params[] = $_SESSION['admin_id'] ?? 0;
    
    $params[] = $orderId;
    
    $sql = "UPDATE orders SET " . implode(", ", $updates) . " WHERE orders_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Log Activity
    $detail = "Updated order #{$orderId}";
    if (isset($input['status'])) $detail .= " | Status: {$input['status']}";
    if (isset($input['billing_type'])) $detail .= " | Billing: {$input['billing_type']}";
    logStaffAction($db, 'UPDATE', 'orders', $orderId, $detail);

    // Send Email Notification
    $notifyEmail = $input['notify_email'] ?? false;
    if ($notifyEmail) {
        $stmt_info = $db->prepare("SELECT o.orders_no, u.user_email, u.user_name, s.orsts_detail 
                                 FROM orders o 
                                 JOIN user u ON o.user_id = u.user_id 
                                 JOIN orsts s ON o.orders_status = s.orsts_id 
                                 WHERE o.orders_id = ?");
        $stmt_info->execute([$orderId]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if ($info && !empty($info['user_email'])) {
            $to = $info['user_email'];
            $subject = "อัปเดตสถานะคำสั่งซื้อ #" . $info['orders_no'];
            $message = "เรียนคุณ " . $info['user_name'] . ",\n\n";
            $message .= "คำสั่งซื้อของคุณ #" . $info['orders_no'] . " ได้เปลี่ยนสถานะเป็น: " . $info['orsts_detail'] . "\n\n";
            $message .= "ขอบคุณที่ใช้บริการ\n";
            $message .= "Yakpho.com";

            $headers = "From: no-reply@yakpho.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            @mail($to, $subject, $message, $headers);
        }
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
