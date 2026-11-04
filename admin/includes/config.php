<?php
// Session Timeout (1 Year)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 31536000);
    ini_set('session.gc_maxlifetime', 31536000);
}

$host_check = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_production_host = in_array(strtolower(explode(':', $host_check)[0]), ['yakpho.com', 'www.yakpho.com', 'esther.rest', 'www.esther.rest'], true);
$is_localhost_env = !$is_production_host;

try {
    if ($is_localhost_env) {
        $db = new PDO("mysql:host=localhost;dbname=salebnet_thaiherbold;charset=utf8mb4", "root", "");
    } else {
        // Production Server (ใช้ฐานข้อมูลตัวเดียวกับ esther.rest: woodysay_esther)
        $db = new PDO("mysql:host=localhost;dbname=woodysay_esther;charset=utf8mb4", "woodysay_esther", "cYuekzwRgSgNNAMqJhwA");
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage() . "<br>Please check your database credentials in admin/includes/config.php");
}

// ----------------------------------------------------------------------
// AUTO SELF-HEALING DATABASE MIGRATION (Ensures DB is ready on any server)
// ----------------------------------------------------------------------
static $adminSchemaChecked = false;
if (!$adminSchemaChecked && isset($db)) {
    try {
        // 1. Ensure user_tokens table exists
        $db->exec("CREATE TABLE IF NOT EXISTS `user_tokens` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `token` VARCHAR(255) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_token` (`token`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. Ensure member_points_log table exists
        $db->exec("CREATE TABLE IF NOT EXISTS `member_points_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `points_change` INT NOT NULL,
            `balance_after` INT NOT NULL DEFAULT 0,
            `action_type` VARCHAR(50) NOT NULL DEFAULT 'order_reward',
            `reference_id` INT DEFAULT NULL,
            `note` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 3. Ensure user_tags and user_tag_mapping tables exist
        $db->exec("CREATE TABLE IF NOT EXISTS `user_tags` (
            `tag_id` INT AUTO_INCREMENT PRIMARY KEY,
            `tag_name` VARCHAR(100) NOT NULL UNIQUE,
            `tag_color` VARCHAR(20) DEFAULT '#065f46',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS `user_tag_mapping` (
            `mapping_id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `tag_id` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `user_tag_unique` (`user_id`, `tag_id`),
            INDEX `idx_user` (`user_id`),
            INDEX `idx_tag` (`tag_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 4. Ensure addr table exists
        $db->exec("CREATE TABLE IF NOT EXISTS `addr` (
            `addr_id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `addr_name` VARCHAR(255) DEFAULT NULL,
            `addr_mobile` VARCHAR(50) DEFAULT NULL,
            `addr_detail` TEXT DEFAULT NULL,
            `addr_detail2` TEXT DEFAULT NULL,
            `addr_postcode` VARCHAR(20) DEFAULT NULL,
            `country_id` INT DEFAULT 171,
            `provinces_id` INT DEFAULT 1,
            `addr_type` INT DEFAULT 1,
            `addr_status` INT DEFAULT 1,
            `addr_del` INT DEFAULT 0,
            `addr_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 5. Ensure missing columns in user table
        $columnsToEnsure = [
            'role'              => "ALTER TABLE `user` ADD COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'customer'",
            'user_stars'        => "ALTER TABLE `user` ADD COLUMN `user_stars` INT NOT NULL DEFAULT 0",
            'm_stamp_points'    => "ALTER TABLE `user` ADD COLUMN `m_stamp_points` INT NOT NULL DEFAULT 0",
            'member_tier'       => "ALTER TABLE `user` ADD COLUMN `member_tier` VARCHAR(50) NOT NULL DEFAULT 'general'",
            'user_nickname'     => "ALTER TABLE `user` ADD COLUMN `user_nickname` VARCHAR(100) DEFAULT NULL",
            'user_birthdate'    => "ALTER TABLE `user` ADD COLUMN `user_birthdate` VARCHAR(50) DEFAULT NULL",
            'line_user_id'      => "ALTER TABLE `user` ADD COLUMN `line_user_id` VARCHAR(100) DEFAULT NULL",
            'line_display_name' => "ALTER TABLE `user` ADD COLUMN `line_display_name` VARCHAR(150) DEFAULT NULL",
            'line_picture_url'  => "ALTER TABLE `user` ADD COLUMN `line_picture_url` VARCHAR(500) DEFAULT NULL",
            'affiliate_code'    => "ALTER TABLE `user` ADD COLUMN `affiliate_code` VARCHAR(50) DEFAULT NULL",
            'email_verified'    => "ALTER TABLE `user` ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0",
            'user_key'          => "ALTER TABLE `user` ADD COLUMN `user_key` VARCHAR(100) DEFAULT NULL",
            'user_del'          => "ALTER TABLE `user` ADD COLUMN `user_del` INT NOT NULL DEFAULT 0"
        ];

        foreach ($columnsToEnsure as $col => $sql) {
            try {
                $db->query("SELECT `{$col}` FROM `user` LIMIT 1");
            } catch (Throwable $e) {
                try {
                    $db->exec($sql);
                } catch (Throwable $e2) {}
            }
        }

        // 6. Ensure missing columns in orders table
        $ordersCols = [
            'orders_del'          => "ALTER TABLE `orders` ADD COLUMN `orders_del` INT NOT NULL DEFAULT 0",
            'orders_delicosttrue' => "ALTER TABLE `orders` ADD COLUMN `orders_delicosttrue` DECIMAL(10,2) DEFAULT 0.00",
            'orders_country_id'   => "ALTER TABLE `orders` ADD COLUMN `orders_country_id` INT DEFAULT 171",
            'orders_destination'  => "ALTER TABLE `orders` ADD COLUMN `orders_destination` VARCHAR(50) DEFAULT 'domestic'",
            'orders_tracking'     => "ALTER TABLE `orders` ADD COLUMN `orders_tracking` VARCHAR(100) DEFAULT NULL"
        ];
        foreach ($ordersCols as $col => $sql) {
            try {
                $db->query("SELECT `{$col}` FROM `orders` LIMIT 1");
            } catch (Throwable $e) {
                try {
                    $db->exec($sql);
                } catch (Throwable $e2) {}
            }
        }
    } catch (Throwable $e) {
        error_log('[admin/includes/config.php auto-migration] ' . $e->getMessage());
    }
    $adminSchemaChecked = true;
}

// ----------------------------------------------------------------------
// REMEMBER ME: Auto-login Check (Protected with try-catch)
// ----------------------------------------------------------------------
try {
    if ((!isset($_SESSION['admin_logged']) && !isset($_SESSION['scanner_logged'])) && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $stmt = $db->prepare("SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tokenData) {
            $stmtUser = $db->prepare("SELECT * FROM user WHERE user_id = ? LIMIT 1");
            $stmtUser->execute([$tokenData['user_id']]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $role = strtolower($user['role'] ?? 'customer');
                $logged_in_any = false;
                
                // Check Admin roles
                $admin_roles = ['owner', 'admin', 'manager', 'editor', 'staff'];
                if (in_array($role, $admin_roles)) {
                    $_SESSION['admin_logged']   = true;
                    $_SESSION['admin_id']       = $user['user_id'];
                    $_SESSION['admin_username'] = $user['user_username'];
                    $_SESSION['admin_role']     = $user['role'] ?? 'staff';
                    $_SESSION['admin_name']     = $user['user_name'];
                    $_SESSION['admin_picture']  = $user['user_picture'] ?? 'default.png';
                    $logged_in_any = true;
                }
                
                // Check Scanner roles
                $scanner_roles = ['staff', 'manager', 'admin', 'owner', 'packer'];
                if (in_array($role, $scanner_roles)) {
                    $_SESSION['scanner_logged'] = true;
                    $_SESSION['scanner_user_id'] = $user['user_id'];
                    $_SESSION['scanner_name'] = $user['user_name'] . ' ' . ($user['user_lastname'] ?? '');
                    $_SESSION['scanner_role']     = $user['role'] ?? 'staff';
                    $logged_in_any = true;
                }
                
                if (!$logged_in_any) {
                    setcookie('remember_token', '', time() - 3600, '/');
                }
            } else {
                setcookie('remember_token', '', time() - 3600, '/');
            }
        } else {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
} catch (Throwable $e) {
    error_log('[admin/includes/config.php Remember Token Error] ' . $e->getMessage());
}
// ----------------------------------------------------------------------

$ver = "?v=1.0.0"; // เปลี่ยนเป็นเลขเวอร์ชั่นคงที่เพื่อให้ Browser ทำการ Cache ไฟล์ CSS/JS ได้ (เพิ่มความเร็วในการโหลด)
// Backend-only config
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$req_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$is_local_sub = (strpos($req_uri, '/yakpho.com') === 0 || strpos($script_name, '/yakpho.com') === 0);

if ($is_local_sub) {
    define("ROOT_URL", $proto . $host . "/yakpho.com");
    define("BASE_URL", ROOT_URL);
    define("URL_PATH", "/yakpho.com");
    define("ADMIN_URL", ROOT_URL . "/admin");
    define("ADMIN_ASSETS", ADMIN_URL . "/assets");
} else {
    define("ROOT_URL", $proto . $host);
    define("BASE_URL", ROOT_URL);
    define("URL_PATH", "");
    define("ADMIN_URL", ROOT_URL . "/admin");
    define("ADMIN_ASSETS", ADMIN_URL . "/assets");
}

define("UPLOAD_DIR", __DIR__ . "/../../uploads");
define("SYSTEM_LOG", __DIR__ . "/../../logs");
require_once __DIR__ . "/functions.php";


define("ADMIN_NAME", "YakPho Admin");
