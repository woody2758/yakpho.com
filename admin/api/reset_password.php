<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check admin role permission if necessary, here we allow admin/owner maybe?
// Let's assume auth.php ensures they are logged in as admin.

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$password = trim($input['password'] ?? '');

if (empty($user_id) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาส่งข้อมูลให้ครบถ้วน']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร']);
    exit;
}

try {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE user SET user_password = ? WHERE user_id = ?");
    $result = $stmt->execute([$hashed_password, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'รีเซ็ตรหัสผ่านสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตรหัสผ่านได้']);
    }
} catch (Exception $e) {
    error_log('Reset password error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดของระบบ']);
}
