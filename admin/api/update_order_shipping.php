<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['orders_id']) || !isset($data['transcat_id'])) {
        throw new Exception('Invalid input');
    }

    $orders_id = (int)$data['orders_id'];
    $transcat_id = (int)$data['transcat_id'];
    $tracking = isset($data['orders_tracking']) ? trim($data['orders_tracking']) : '';
    
    // จัดการต้นทุนค่าส่งจริง
    $delicosttrue_sql = "";
    $params = [$transcat_id, $tracking];
    
    if (isset($data['orders_delicosttrue']) && $data['orders_delicosttrue'] !== '') {
        $delicosttrue_sql = ", orders_delicosttrue = ?";
        $params[] = (float)$data['orders_delicosttrue'];
    }

    $status_sql = "";
    if ($tracking !== '') {
        $status_sql = ", orders_status = 5"; // 5 = ที่ต้องได้รับ
    }

    $params[] = $_SESSION['admin_id'] ?? 0;
    $params[] = $orders_id;

    $stmt = $db->prepare("UPDATE orders SET transcat_id = ?, orders_tracking = ? {$delicosttrue_sql} {$status_sql}, update_id = ?, orders_update = NOW() WHERE orders_id = ?");
    $stmt->execute($params);

    // Log Activity
    $logMsg = "Updated shipping info (Transcat: $transcat_id, Tracking: $tracking)";
    if (isset($data['orders_delicosttrue']) && $data['orders_delicosttrue'] !== '') {
        $logMsg .= ", True Cost: " . $data['orders_delicosttrue'];
    }
    if ($tracking !== '') {
        $logMsg .= ", Status auto-updated to 5 (ที่ต้องได้รับ)";
    }
    logStaffAction($db, 'UPDATE', 'orders', $orders_id, $logMsg);

    // Send Customer LINE & Email Notification when tracking number is entered
    if ($tracking !== '') {
        try {
            require_once __DIR__ . '/../../includes/resend_helper.php';
            require_once __DIR__ . '/../../includes/line_helper.php';
            $stmtNoti = $db->prepare("
                SELECT o.orders_no, o.track_token, u.user_name, u.user_email, u.line_user_id,
                       a.addr_name, t.transcat_name
                FROM orders o
                LEFT JOIN user u ON o.user_id = u.user_id
                LEFT JOIN addr a ON o.addr_id = a.addr_id
                LEFT JOIN transcat t ON o.transcat_id = t.transcat_id
                WHERE o.orders_id = ?
                LIMIT 1
            ");
            $stmtNoti->execute([$orders_id]);
            $nInfo = $stmtNoti->fetch(PDO::FETCH_ASSOC);
            if ($nInfo) {
                $cName = $nInfo['addr_name'] ?: ($nInfo['user_name'] ?: 'ลูกค้า');
                $cEmail = trim((string)($nInfo['user_email'] ?? ''));
                $cLine = trim((string)($nInfo['line_user_id'] ?? ''));
                $courier = $nInfo['transcat_name'] ?: 'ขนส่งด่วน';
                $tkKey = !empty($nInfo['track_token']) ? $nInfo['track_token'] : $nInfo['orders_no'];
                $tkUrl = ROOT_URL . '/track/' . $tkKey;

                if ($cLine !== '') {
                    $shipLineMsg = "🚚 พัสดุของคุณถูกจัดส่งแล้ว!\n"
                                 . "━━━━━━━━━━━━━━\n"
                                 . "🧾 คำสั่งซื้อ: #{$nInfo['orders_no']}\n"
                                 . "📦 ขนส่ง: {$courier}\n"
                                 . "🔢 เลขพัสดุ: {$tracking}\n"
                                 . "━━━━━━━━━━━━━━\n"
                                 . "🔍 ติดตามสถานะพัสดุ:\n{$tkUrl}";
                    send_line_push_message($db, $cLine, $shipLineMsg);
                }
                if ($cEmail !== '' && filter_var($cEmail, FILTER_VALIDATE_EMAIL)) {
                    send_templated_email($db, $cEmail, 'shipping_tracking', [
                        'customer_name'   => $cName,
                        'order_no'        => (string)$nInfo['orders_no'],
                        'courier_name'    => $courier,
                        'tracking_number' => $tracking,
                        'tracking_url'    => $tkUrl,
                    ]);
                }
            }
        } catch (Throwable $e) {}
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
