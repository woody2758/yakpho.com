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

if (empty($orderId) || empty($statusId)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE orders SET orders_status = ?, orders_update = NOW(), update_id = ? WHERE orders_id = ?");
    $stmt->execute([$statusId, $_SESSION['admin_id'] ?? 0, $orderId]);

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
