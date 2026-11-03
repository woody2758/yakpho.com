<?php
session_start();
require_once __DIR__ . "/includes/config.php";

// REMEMBER ME: ลบ Token
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $db->prepare("DELETE FROM user_tokens WHERE token = ?");
    $stmt->execute([$token]);
    setcookie('remember_token', '', time() - 3600, '/');
}

// ล้าง Session
session_unset();
session_destroy();

header("Location: ".ADMIN_URL."/");
exit;
?>
