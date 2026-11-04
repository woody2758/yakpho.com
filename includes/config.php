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
    $dsn  = "mysql:host=localhost;dbname=woodysay_esther;charset=utf8mb4";
    $user = "woodysay_esther";
    $pass = "cYuekzwRgSgNNAMqJhwA";
}

try {
    $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die('DB Error: '.$e->getMessage());
}

// Auto self-healing schema migration (Ensures missing columns exist on production DB)
static $schemaChecked = false;
if (!$schemaChecked && isset($db)) {
    try {
        $db->query("SELECT m_stamp_points FROM user LIMIT 1");
    } catch (Throwable $e) {
        try {
            $db->exec("ALTER TABLE user ADD COLUMN m_stamp_points INT DEFAULT 0 AFTER user_stars");
            $db->exec("UPDATE user SET m_stamp_points = user_stars WHERE user_stars > 0");
        } catch (Throwable $e2) {}
    }
    try {
        $db->query("SELECT id FROM member_points_log LIMIT 1");
    } catch (Throwable $e) {
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS member_points_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    points_change INT NOT NULL,
                    balance_after INT NOT NULL DEFAULT 0,
                    action_type VARCHAR(50) NOT NULL DEFAULT 'order_reward',
                    reference_id INT DEFAULT NULL,
                    note TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Throwable $e3) {}
    }
    $schemaChecked = true;
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