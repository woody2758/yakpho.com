<?php
/* ===========================================
   Check login status
=========================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: " . ADMIN_URL . "/");
    exit;
}

/* ===========================================
   Prepare Admin Variables (ใช้ในทุกหน้า)
=========================================== */
$ADMIN_ID    = $_SESSION['admin_id']    ?? null;
$ADMIN_NAME  = $_SESSION['admin_name']  ?? "ผู้ดูแลระบบ";
$ADMIN_EMAIL = $_SESSION['admin_email'] ?? "";
$ADMIN_ROLE  = $_SESSION['admin_role']  ?? "";
$ADMIN_PICTURE = $_SESSION['admin_picture'] ?? "default.png";
/* ===========================================
   Role Hierarchy
=========================================== */
$ROLE_LEVEL = [
    'owner'   => 1,
    'admin'   => 2,
    'manager' => 3,
    'editor'  => 4,
    'staff'   => 5
];

/* ===========================================
   Require Role Function
=========================================== */
function require_role($min_role) {
    global $ROLE_LEVEL;

    $current_role = $_SESSION['admin_role'] ?? null;

    if (!$current_role || !isset($ROLE_LEVEL[$current_role])) {
        http_response_code(403);
        echo "Invalid role";
        exit;
    }

    if ($ROLE_LEVEL[$current_role] <= $ROLE_LEVEL[$min_role]) {
        return true;
    }

    $_SESSION['alert'] = ['error', 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้ของระบบ'];
    header("Location: " . ADMIN_URL . "/dashboard.php");
    exit;
}
?>
