<?php
session_start();
require __DIR__ . "/includes/config.php";
// รองรับทั้ง email และ identity (กันพลาดจากหน้า form เก่า)
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// เก็บค่าที่กรอกไว้ เพื่อเติมกลับในฟอร์ม
$_SESSION['old_identity'] = $email;

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

// DEBUG ชั่วคราว — ดูค่าจริง
// echo "<pre>";
// echo "Email ที่ส่งมา: "; var_dump($email);
// echo "Password ที่พิมพ์: "; var_dump($password);
// echo "Hash จากฐานข้อมูล: "; var_dump($user['user_password']);
// echo "Result password_verify: ";
// var_dump(password_verify($password, $user['user_password']));
// echo "</pre>";
// exit;



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
$_SESSION['admin_picture'] = $row['user_picture'];   // ⭐ สำคัญ

// Success Alert
$_SESSION['alert'] = ['success', 'ยินดีต้อนรับคุณ ' . $user['user_name']];

// Redirect
header("Location: " . ADMIN_URL . "/dashboard.html");
exit;
