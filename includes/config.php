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
    $columnsToEnsure = [
        'user_stars'        => "ALTER TABLE user ADD COLUMN user_stars INT DEFAULT 0",
        'm_stamp_points'    => "ALTER TABLE user ADD COLUMN m_stamp_points INT DEFAULT 0",
        'user_nickname'     => "ALTER TABLE user ADD COLUMN user_nickname VARCHAR(100) DEFAULT NULL",
        'user_birthdate'    => "ALTER TABLE user ADD COLUMN user_birthdate VARCHAR(50) DEFAULT NULL",
        'line_display_name' => "ALTER TABLE user ADD COLUMN line_display_name VARCHAR(150) DEFAULT NULL",
        'line_user_id'      => "ALTER TABLE user ADD COLUMN line_user_id VARCHAR(100) DEFAULT NULL",
        'line_picture_url'  => "ALTER TABLE user ADD COLUMN line_picture_url VARCHAR(500) DEFAULT NULL",
        'member_tier'       => "ALTER TABLE user ADD COLUMN member_tier VARCHAR(50) DEFAULT 'general'",
        'affiliate_code'    => "ALTER TABLE user ADD COLUMN affiliate_code VARCHAR(50) DEFAULT NULL",
        'email_verified'    => "ALTER TABLE user ADD COLUMN email_verified TINYINT DEFAULT 0",
        'user_key'          => "ALTER TABLE user ADD COLUMN user_key VARCHAR(100) DEFAULT NULL",
        'user_google_map'   => "ALTER TABLE user ADD COLUMN user_google_map TEXT DEFAULT NULL",
        'user_website'      => "ALTER TABLE user ADD COLUMN user_website VARCHAR(255) DEFAULT NULL",
        'user_note'         => "ALTER TABLE user ADD COLUMN user_note TEXT DEFAULT NULL",
        'user_vip'          => "ALTER TABLE user ADD COLUMN user_vip INT DEFAULT 0"
    ];

    foreach ($columnsToEnsure as $col => $sql) {
        try {
            $db->query("SELECT {$col} FROM user LIMIT 1");
        } catch (Throwable $e) {
            try {
                $db->exec($sql);
            } catch (Throwable $e2) {}
        }
    }

    $addrColumnsToEnsure = [
        'addr_position' => "ALTER TABLE addr ADD COLUMN addr_position INT DEFAULT 0",
        'addr_detail2'  => "ALTER TABLE addr ADD COLUMN addr_detail2 TEXT DEFAULT NULL",
        'addr_forword'  => "ALTER TABLE addr ADD COLUMN addr_forword TEXT DEFAULT NULL",
        'addr_tax_id'   => "ALTER TABLE addr ADD COLUMN addr_tax_id VARCHAR(50) DEFAULT NULL",
        'addr_branch'   => "ALTER TABLE addr ADD COLUMN addr_branch VARCHAR(20) DEFAULT '00000'",
        'addr_update'   => "ALTER TABLE addr ADD COLUMN addr_update DATETIME DEFAULT NULL",
        'save_id'       => "ALTER TABLE addr ADD COLUMN save_id INT DEFAULT 0",
        'update_id'     => "ALTER TABLE addr ADD COLUMN update_id INT DEFAULT 0"
    ];

    foreach ($addrColumnsToEnsure as $col => $sql) {
        try {
            $db->query("SELECT {$col} FROM addr LIMIT 1");
        } catch (Throwable $e) {
            try {
                $db->exec($sql);
            } catch (Throwable $e2) {}
        }
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