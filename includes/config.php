<?php
// ============================
// == includes/config.php ==
// ============================

// Session Timeout (1 Year)
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_lifetime', '31536000');
    @ini_set('session.gc_maxlifetime', '31536000');
}

// Dynamic Base URL configuration (รองรับทั้ง localhost/yakpho.com และ Domain จริง)
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$req_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$is_local_sub = (strpos($req_uri, '/yakpho.com') === 0 || strpos($script_name, '/yakpho.com') === 0);

if ($is_local_sub) {
    define('BASE_URL', '/yakpho.com');
    define('URL_PATH', '/yakpho.com');
    define('ADMIN_URL', '/yakpho.com/admin');
    define('ROOT_URL', $proto . $host . '/yakpho.com');
} else {
    define('BASE_URL', '');
    define('URL_PATH', '');
    define('ADMIN_URL', '/admin');
    define('ROOT_URL', $proto . $host);
}

// Database connection (Auto-detect Localhost/LAN vs Production Server)
$is_production_host = in_array(strtolower(explode(':', $host)[0]), ['yakpho.com', 'www.yakpho.com', 'esther.rest', 'www.esther.rest'], true);
$is_localhost_env = !$is_production_host;

if ($is_localhost_env) {
    $dsn  = "mysql:host=127.0.0.1;dbname=salebnet_thaiherbold;charset=utf8mb4";
    $user = "root";
    $pass = "";
} else {
    // Production Server (ใช้ฐานข้อมูลตัวเดียวกับ esther.rest: woodysay_esther)
    $dsn  = "mysql:host=127.0.0.1;dbname=woodysay_esther;charset=utf8mb4";
    $user = "woodysay_esther";
    $pass = "cYuekzwRgSgNNAMqJhwA";
}

try {
    $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die('DB Error: '.$e->getMessage());
}

// Multilang base with URL prefix support (10 languages)
$allowed_langs = ['th', 'en', 'zh', 'de', 'fr', 'ja', 'ko', 'ru', 'ar', 'he'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs, true)) {
    $lang = $_GET['lang'];
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs, true)) {
    $lang = $_SESSION['lang'];
} else {
    $lang = 'th';
}
$_SESSION['lang'] = $lang;
$lang_code = $lang;
$lang_file = __DIR__ . "/../lang/{$lang}.php";
if (!file_exists($lang_file)) {
    $lang_file = __DIR__ . "/../lang/en.php";
}
$LANG = file_exists($lang_file) ? include $lang_file : [];

$ver = time(); // ป้องกัน cache เก่า

?>