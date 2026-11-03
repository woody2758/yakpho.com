<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid user ID']);
    exit;
}

try {
    // 1. Verify user exists
    $stmt = $db->prepare("SELECT user_id, user_name, user_lastname, user_mobile, user_email FROM user WHERE user_id = ? AND user_del = 0");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
        exit;
    }

    // 2. Check if a valid token already exists (valid for at least 1 more day)
    $stmtToken = $db->prepare("SELECT token, expires_at FROM user_tokens WHERE user_id = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmtToken->execute([$user_id]);
    $existing = $stmtToken->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $token = $existing['token'];
        $expires_at = $existing['expires_at'];
    } else {
        // Generate high-entropy 32-char hex token
        $token = bin2hex(random_bytes(16));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

        $stmtIns = $db->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmtIns->execute([$user_id, $token, $expires_at]);
    }

    $c_code = 'C' . $user_id;
    
    // Build VIP invite URL
    // Detect protocol & host or use BASE_URL
    $vipUrl = BASE_URL . '/th/vip?c=' . urlencode($c_code) . '&token=' . urlencode($token);

    // Personalized message template for LINE / Messenger
    $customerName = trim($user['user_name'] . ' ' . $user['user_lastname']) ?: 'คุณลูกค้า';
    $messageTemplate = "เรียน {$customerName} 🙏\n"
        . "ทาง YakPho Aroma ขอเรียนเชิญท่านเข้าสู่ระบบสมาชิก VIP เพื่อตรวจสอบสิทธิประโยชน์ แต้มสะสม และอัปเดตข้อมูลการจัดส่งของท่าน\n\n"
        . "✨ ลิงก์สิทธิพิเศษ VIP ของท่าน:\n"
        . "{$vipUrl}\n\n"
        . "(รหัสสมาชิก: {$c_code} | ลิงก์มีอายุ 30 วัน)";

    echo json_encode([
        'status' => 'success',
        'c_code' => $c_code,
        'vip_url' => $vipUrl,
        'customer_name' => $customerName,
        'message_template' => $messageTemplate,
        'expires_at' => $expires_at
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

