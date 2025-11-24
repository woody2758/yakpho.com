<?php
session_start();
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

$id = $_GET['id'] ?? 0;

if ($id == $_SESSION['admin_id']) {
    $_SESSION['alert'] = ['error', 'ไม่สามารถลบบัญชีตัวเองได้'];
    header("Location: index.php");
    exit;
}

// Soft Delete
$stmt = $db->prepare("UPDATE user SET user_del = 1, updated_at = NOW() WHERE user_id = ?");
if ($stmt->execute([$id])) {
    $_SESSION['alert'] = ['success', 'ลบสมาชิกเรียบร้อยแล้ว'];
} else {
    $_SESSION['alert'] = ['error', 'เกิดข้อผิดพลาดในการลบ'];
}

header("Location: index.html");
