
<?php
// ============================
// == includes/config.php ==
// ============================
define('URL_PATH', '/yakpho.com');
define('ADMIN_URL', '/yakpho.com/admin');   
$dsn = "mysql:host=localhost;dbname=salebnet_thaiherb;charset=utf8mb4";
$user = "root";
$pass = "";
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

$ver = time(); // ใช้เวลาปัจจุบันเพื่อป้องกัน cache เก่า

?>