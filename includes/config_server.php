<?php
// ============================
// == includes/config_server.php ==
// ============================

// Session Timeout (1 Year)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 31536000);
    ini_set('session.gc_maxlifetime', 31536000);
}

// Base URL configuration (For Server)
define('BASE_URL', 'https://esther.rest');
define('URL_PATH', '');
define('ADMIN_URL', 'https://esther.rest/admin');

// Database connection
$dsn = "mysql:host=localhost;dbname=woodysay_esther;charset=utf8mb4";
$user = "woodysay_esther";
$pass = "cYuekzwRgSgNNAMqJhwA";
try {
    $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die('DB Error: '.$e->getMessage());
}


// Multilang base with URL prefix support
if (isset($_GET['lang'])) {
$lang = $_GET['lang'];
} elseif (isset($_SESSION['lang'])) {
$lang = $_SESSION['lang'];
} else {
$lang = 'th';
}
$_SESSION['lang'] = $lang;
$LANG = include __DIR__."/../lang/{$lang}.php";

$ver = "1.0.0"; // เปลี่ยนเป็นเลขเวอร์ชั่นคงที่เพื่อให้ Browser ทำการ Cache ไฟล์ CSS/JS ได้

?>
