<?php
session_start();
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../../includes/functions/user.php";

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $name     = trim($_POST['name']);
    $tel      = trim($_POST['tel']);
    $role     = $_POST['role'];

    // Validation
    if ($password !== $confirm) {
        $_SESSION['alert'] = ['error', 'รหัสผ่านไม่ตรงกัน'];
        header("Location: create.php");
        exit;
    }

    if (check_user_exists($email)) {
        $_SESSION['alert'] = ['error', 'Email นี้มีผู้ใช้งานแล้ว'];
        header("Location: create.php");
        exit;
    }

    // Hash Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert
    $sql = "INSERT INTO user (user_email, user_password, user_name, user_tel, role, user_status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute([$email, $hashed_password, $name, $tel, $role])) {
        $_SESSION['alert'] = ['success', 'เพิ่มสมาชิกเรียบร้อยแล้ว'];
        header("Location: index.php");
    } else {
        $_SESSION['alert'] = ['error', 'เกิดข้อผิดพลาดในการบันทึก'];
        header("Location: create.php");
    }
    exit;

} elseif ($action === 'update') {
    $id       = $_POST['user_id'];
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $name     = trim($_POST['name']);
    $tel      = trim($_POST['tel']);
    $role     = $_POST['role'];
    $status   = $_POST['status'];

    // Check exists
    if (check_user_exists($email, $id)) {
        $_SESSION['alert'] = ['error', 'Email นี้มีผู้ใช้งานแล้ว'];
        header("Location: edit.php?id=$id");
        exit;
    }

    // Update basic info
    $sql = "UPDATE user SET user_email = ?, user_name = ?, user_tel = ?, role = ?, user_status = ?, updated_at = NOW() WHERE user_id = ?";
    $params = [$email, $name, $tel, $role, $status, $id];

    // Update password if provided
    if (!empty($password)) {
        if ($password !== $confirm) {
            $_SESSION['alert'] = ['error', 'รหัสผ่านใหม่ไม่ตรงกัน'];
            header("Location: edit.php?id=$id");
            exit;
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Execute basic update first
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // Then update password
        $stmt = $db->prepare("UPDATE user SET user_password = ? WHERE user_id = ?");
        $stmt->execute([$hashed_password, $id]);
    } else {
        // Just basic update
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

    $_SESSION['alert'] = ['success', 'แก้ไขข้อมูลเรียบร้อยแล้ว'];
    header("Location: index.php");
    exit;
}

header("Location: index.php");
