<?php
require __DIR__ . "/includes/config.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// รองรับทั้ง email และ identity (กันพลาดจากหน้า form เก่า)
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// เก็บค่าที่กรอกไว้ เพื่อเติมกลับในฟอร์ม
$_SESSION['old_identity'] = $email;

// ตรวจสอบว่าเป็นการส่งฟอร์มมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . ADMIN_URL . "/");
    exit;
}

// ตรวจ empty
if ($email === '' || $password === '') {
    $_SESSION['alert'] = ['error', 'กรุณากรอก Email และรหัสผ่าน'];
    header("Location: " . ADMIN_URL . "/");
    exit;
}

// ดึงข้อมูลจาก email
$stmt = $db->prepare("SELECT * FROM user WHERE user_email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['alert'] = ['error', 'ไม่พบบัญชีนี้'];
    header("Location: " . ADMIN_URL . "/");
    exit;
}

// ตรวจรหัสผ่าน
if (!password_verify($password, $user['user_password'])) {
    $_SESSION['alert'] = ['error', 'รหัสผ่านไม่ถูกต้อง'];
    header("Location: " . ADMIN_URL . "/");
    exit;
}

// ตรวจ role Backend
$allowed_roles = ['owner', 'admin', 'manager', 'editor', 'staff'];
if (!in_array($user['role'], $allowed_roles)) {
    $_SESSION['alert'] = ['error', 'บัญชีนี้ไม่มีสิทธิ์เข้าใช้งานระบบหลังบ้าน'];
    header("Location: " . ADMIN_URL . "/");
    exit;
}

// Login ผ่าน
$_SESSION['admin_logged']   = true;
$_SESSION['admin_id']       = $user['user_id'];
$_SESSION['admin_username'] = $user['user_username'];
$_SESSION['admin_role']     = $user['role'];
$_SESSION['admin_name']     = $user['user_name'];
$_SESSION['admin_picture']  = $user['user_picture'] ?? 'default.png';

// ----------------------------------------------------------------------
// REMEMBER ME: สร้าง Token สำหรับล็อกอินค้างไว้ 1 ปี (365 วัน)
// ----------------------------------------------------------------------
$remember = !empty($_POST['remember']);
if ($remember) {
    $expiry_time = time() + (86400 * 365); // 1 ปี
    $expiry_datetime = date('Y-m-d H:i:s', $expiry_time);
    $token = bin2hex(random_bytes(32));

    $stmtToken = $db->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmtToken->execute([$user['user_id'], $token, $expiry_datetime]);

    setcookie('remember_token', $token, [
        'expires'  => $expiry_time,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} else {
    // ถ้าไม่ได้ติ๊ก ให้เคลียร์ cookie เก่าออก
    if (isset($_COOKIE['remember_token'])) {
        $stmtDel = $db->prepare("DELETE FROM user_tokens WHERE token = ?");
        $stmtDel->execute([$_COOKIE['remember_token']]);
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
// ----------------------------------------------------------------------

// Success Alert
$_SESSION['alert'] = ['success', 'ยินดีต้อนรับคุณ ' . $user['user_name']];

// Redirect
header("Location: " . ADMIN_URL . "/dashboard.html");
exit;
