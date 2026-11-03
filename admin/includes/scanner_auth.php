<?php
// Set session to last for 1 year (31536000 seconds)
$lifetime = 31536000;
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params($lifetime);
    ini_set('session.gc_maxlifetime', $lifetime);
    session_start();
}

if (!isset($_SESSION['scanner_logged']) || $_SESSION['scanner_logged'] !== true) {
    // Check if it's an API request
    if (isset($is_api_call) && $is_api_call === true) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    
    header("Location: " . ADMIN_URL . "/scanner/login");
    exit;
}

$SCANNER_USER_ID = $_SESSION['scanner_user_id'] ?? null;
$SCANNER_NAME    = $_SESSION['scanner_name'] ?? '';
$SCANNER_ROLE    = $_SESSION['scanner_role'] ?? '';
?>
