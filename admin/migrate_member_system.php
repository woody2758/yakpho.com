<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — Complete Database Migration Runner
 * ============================================================
 * Accessible via browser: /admin/migrate_member_system.php?key=yakpho_migrate_2026
 * Automatically detects whether running on Localhost or Production Server
 */

require_once __DIR__ . '/includes/config.php';

// Security check: Must be logged in as Admin OR provide secret key
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$secretKey = 'yakpho_migrate_2026';
$isAuthorized = (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) || (($_GET['key'] ?? '') === $secretKey);

if (!$isAuthorized) {
    header('HTTP/1.1 403 Forbidden');
    die('<h3>403 Forbidden: Admin login required or pass ?key=yakpho_migrate_2026</h3>');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>Database Migration - Yak Pho Aroma</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0c3b2e; color: #fff; padding: 30px; }
        .box { max-width: 800px; margin: 0 auto; background: #ffffff; color: #222; border-radius: 12px; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        h2 { color: #0c3b2e; margin-top: 0; }
        .log { background: #1a1a1a; color: #4ade80; font-family: monospace; padding: 16px; border-radius: 8px; font-size: 13px; line-height: 1.6; max-height: 500px; overflow-y: auto; }
        .btn { display: inline-block; background: #0c3b2e; color: #fff; padding: 12px 24px; border-radius: 20px; text-decoration: none; margin-top: 18px; font-weight: 600; }
        .btn:hover { background: #16a34a; }
        .badge-success { background: #16a34a; color: white; padding: 3px 8px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
<div class="box">
    <h2>🚀 ระบบอัปเดตโครงสร้างฐานข้อมูล (Database Migration)</h2>
    <p>กำลังดำเนินการตรวจสอบและอัปเดตตารางฐานข้อมูลสำหรับ <strong><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Localhost') ?></strong>...</p>
    
    <div class="log">
<?php
try {
    echo "✓ เชื่อมต่อฐานข้อมูลสำเร็จ (" . htmlspecialchars($db->getAttribute(PDO::ATTR_CONNECTION_STATUS)) . ")\n\n";

    // Helper function to safely add columns if not exists
    function addColumnIfNotExists(PDO $db, string $table, string $column, string $definition): void {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$exists) {
                $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
                echo " [OK] เพิ่มคอลัมน์: {$table}.{$column}\n";
            } else {
                echo " [INFO] คอลัมน์ {$table}.{$column} มีอยู่แล้วในระบบ\n";
            }
        } catch (Throwable $e) {
            echo " [WARN] เกิดข้อผิดพลาดกับ {$table}.{$column}: " . $e->getMessage() . "\n";
        }
    }

    echo "--- 1. ตรวจสอบตาราง user ---\n";
    $userCols = [
        'role'              => "VARCHAR(50) NOT NULL DEFAULT 'customer'",
        'user_stars'        => "INT NOT NULL DEFAULT 0",
        'm_stamp_points'    => "INT NOT NULL DEFAULT 0",
        'member_tier'       => "VARCHAR(50) NOT NULL DEFAULT 'general'",
        'user_nickname'     => "VARCHAR(100) DEFAULT NULL",
        'user_birthdate'    => "VARCHAR(50) DEFAULT NULL",
        'line_user_id'      => "VARCHAR(100) DEFAULT NULL",
        'line_display_name' => "VARCHAR(150) DEFAULT NULL",
        'line_picture_url'  => "VARCHAR(500) DEFAULT NULL",
        'affiliate_code'    => "VARCHAR(50) DEFAULT NULL",
        'email_verified'    => "TINYINT(1) NOT NULL DEFAULT 0",
        'user_key'          => "VARCHAR(100) DEFAULT NULL",
        'user_google_map'   => "TEXT DEFAULT NULL",
        'user_website'      => "VARCHAR(255) DEFAULT NULL",
        'user_picture'      => "VARCHAR(255) DEFAULT NULL",
        'user_note'         => "TEXT DEFAULT NULL",
        'user_del'          => "INT NOT NULL DEFAULT 0",
        'user_status'       => "INT NOT NULL DEFAULT 1"
    ];

    foreach ($userCols as $col => $def) {
        addColumnIfNotExists($db, 'user', $col, $def);
    }

    // Sync user_stars to m_stamp_points
    $db->exec("UPDATE `user` SET `m_stamp_points` = `user_stars` WHERE `user_stars` > 0 AND (`m_stamp_points` = 0 OR `m_stamp_points` IS NULL)");
    $db->exec("UPDATE `user` SET `role` = 'customer' WHERE `role` = '' OR `role` IS NULL");
    echo " [OK] ปรับปรุงข้อมูลเริ่มต้นของสมาชิกเรียบร้อย\n\n";

    echo "--- 2. ตรวจสอบตาราง orders ---\n";
    $ordersCols = [
        'orders_del'          => "INT NOT NULL DEFAULT 0",
        'orders_delicosttrue' => "DECIMAL(10,2) DEFAULT 0.00",
        'orders_country_id'   => "INT DEFAULT 171",
        'orders_destination'  => "VARCHAR(50) DEFAULT 'domestic'",
        'orders_tracking'     => "VARCHAR(100) DEFAULT NULL",
        'track_token'         => "VARCHAR(64) DEFAULT NULL"
    ];
    foreach ($ordersCols as $col => $def) {
        addColumnIfNotExists($db, 'orders', $col, $def);
    }
    echo " [OK] ตาราง orders พร้อมใช้งาน\n\n";

    echo "--- 3. ตรวจสอบตาราง user_tokens (ลิงก์ VIP 30 วัน & จำรหัสผ่าน) ---\n";
    $db->exec("
    CREATE TABLE IF NOT EXISTS `user_tokens` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `token` VARCHAR(255) NOT NULL,
        `expires_at` DATETIME NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_token` (`token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] ตาราง user_tokens พร้อมใช้งาน\n\n";

    echo "--- 4. ตรวจสอบตาราง member_points_log (ประวัติแต้มสะสม) ---\n";
    $db->exec("
    CREATE TABLE IF NOT EXISTS `member_points_log` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `points_change` INT NOT NULL,
        `balance_after` INT NOT NULL DEFAULT 0,
        `action_type` VARCHAR(50) NOT NULL DEFAULT 'order_reward',
        `reference_id` INT DEFAULT NULL,
        `note` TEXT,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] ตาราง member_points_log พร้อมใช้งาน\n\n";

    echo "--- 5. ตรวจสอบตาราง user_tags และ user_tag_mapping (แท็กจัดการลูกค้า) ---\n";
    $db->exec("
    CREATE TABLE IF NOT EXISTS `user_tags` (
        `tag_id` INT AUTO_INCREMENT PRIMARY KEY,
        `tag_name` VARCHAR(100) NOT NULL UNIQUE,
        `tag_color` VARCHAR(20) DEFAULT '#065f46',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
    CREATE TABLE IF NOT EXISTS `user_tag_mapping` (
        `mapping_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `tag_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `user_tag_unique` (`user_id`, `tag_id`),
        INDEX `idx_user` (`user_id`),
        INDEX `idx_tag` (`tag_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] ตารางแท็กสมาชิกพร้อมใช้งาน\n\n";

    echo "--- 6. ตรวจสอบตาราง affiliates (หมอนวด Partner) ---\n";
    $db->exec("
    CREATE TABLE IF NOT EXISTS `affiliates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `affiliate_code` VARCHAR(50) NOT NULL UNIQUE,
        `total_shops` INT NOT NULL DEFAULT 0,
        `total_commission` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] ตาราง affiliates พร้อมใช้งาน\n\n";

    echo "--- 7. ตรวจสอบตาราง addr (ที่อยู่จัดส่ง) ---\n";
    $db->exec("
    CREATE TABLE IF NOT EXISTS `addr` (
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
        `addr_tax_id` VARCHAR(50) DEFAULT NULL,
        `addr_branch` VARCHAR(20) DEFAULT '00000',
        `addr_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `addr_update` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] ตาราง addr พร้อมใช้งาน\n\n";

    echo "--- 8. ตรวจสอบตาราง shop_info และ order_slips ---\n";
    addColumnIfNotExists($db, 'shop_info', 'promptpay_id', "VARCHAR(30) DEFAULT '0800617073'");
    $db->exec("UPDATE `shop_info` SET `promptpay_id` = `shop_phone` WHERE (`promptpay_id` IS NULL OR `promptpay_id` = '') AND `shop_phone` != ''");
    $db->exec("
    CREATE TABLE IF NOT EXISTS `order_slips` (
        `slip_id` INT AUTO_INCREMENT PRIMARY KEY,
        `orders_id` INT NOT NULL,
        `slip_filename` VARCHAR(255) NOT NULL,
        `slip_uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `uploaded_by` INT DEFAULT 0,
        INDEX `idx_orders_id` (`orders_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // Clean up legacy wholesale rate text from ordetail_memo (reserved for admin production notes)
    $db->exec("UPDATE `ordetail` SET `ordetail_memo` = '' WHERE `ordetail_memo` LIKE 'Rate % THB/kg'");
    echo " [OK] ตาราง shop_info (PromptPay) และ order_slips พร้อมใช้งาน\n\n";

    echo "--- 9. ตรวจสอบตาราง resend_email_templates, line_broadcast_logs และ sitesettings ---\n";
    $db->exec("
    CREATE TABLE IF NOT EXISTS `resend_email_templates` (
        `template_id` INT AUTO_INCREMENT PRIMARY KEY,
        `template_code` VARCHAR(100) NOT NULL UNIQUE,
        `template_name` VARCHAR(255) NOT NULL,
        `email_subject` VARCHAR(255) NOT NULL,
        `email_body_html` LONGTEXT NOT NULL,
        `available_merge_tags` TEXT DEFAULT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $db->exec("
    CREATE TABLE IF NOT EXISTS `line_broadcast_logs` (
        `broadcast_id` INT AUTO_INCREMENT PRIMARY KEY,
        `target_tier` VARCHAR(50) NOT NULL DEFAULT 'all',
        `message_text` TEXT NOT NULL,
        `recipient_count` INT NOT NULL DEFAULT 0,
        `sent_by_user_id` INT DEFAULT 0,
        `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $db->exec("
    CREATE TABLE IF NOT EXISTS `sitesettings` (
        `setting_id` INT(11) NOT NULL AUTO_INCREMENT,
        `setting_name` VARCHAR(100) NOT NULL,
        `setting_value` TEXT DEFAULT NULL,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `save_id` INT(11) DEFAULT 0,
        `update_id` INT(11) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`setting_id`),
        UNIQUE KEY `setting_name` (`setting_name`),
        KEY `idx_setting_name` (`setting_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Ensure default notification settings exist in sitesettings (secrets configured via Admin UI)
    $defaultSiteSettings = [
        'resend_from_email'  => 'Yak Pho Aroma <orders@yakpho.com>',
        'admin_notify_email' => 'thaiherbfc@gmail.com',
    ];
    foreach ($defaultSiteSettings as $sKey => $sVal) {
        $chkS = $db->prepare("SELECT setting_id, setting_value FROM sitesettings WHERE setting_name = ? LIMIT 1");
        $chkS->execute([$sKey]);
        $rowS = $chkS->fetch(PDO::FETCH_ASSOC);
        if (!$rowS) {
            $insS = $db->prepare("INSERT INTO sitesettings (setting_name, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $insS->execute([$sKey, $sVal]);
        } elseif ($sKey === 'resend_from_email' && (stripos((string)$rowS['setting_value'], '@gmail.com') !== false || stripos((string)$rowS['setting_value'], '@thaispa.app') !== false)) {
            $updS = $db->prepare("UPDATE sitesettings SET setting_value = ?, updated_at = NOW() WHERE setting_id = ?");
            $updS->execute([$sVal, $rowS['setting_id']]);
        }
    }
    echo " [OK] ตั้งค่าระบบส่งอีเมล (thaiherbfc@gmail.com) และ LINE แจ้งเตือนเรียบร้อย\n\n";

    echo "--- 10. ตรวจสอบตาราง blog_likes และ blog_comments สำหรับบทความ ---\n";
    $db->exec("
    CREATE TABLE IF NOT EXISTS `blog_likes` (
        `like_id` INT AUTO_INCREMENT PRIMARY KEY,
        `blog_id` INT NOT NULL,
        `user_id` INT DEFAULT 0,
        `voter_hash` VARCHAR(64) NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_blog_voter` (`blog_id`, `voter_hash`),
        INDEX `idx_blog_id` (`blog_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $db->exec("
    CREATE TABLE IF NOT EXISTS `blog_comments` (
        `comment_id` INT AUTO_INCREMENT PRIMARY KEY,
        `blog_id` INT NOT NULL,
        `parent_id` INT DEFAULT 0,
        `user_id` INT NOT NULL,
        `comment_text` TEXT NOT NULL,
        `likes_count` INT NOT NULL DEFAULT 0,
        `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_blog_parent` (`blog_id`, `parent_id`),
        INDEX `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] ตาราง blog_likes และ blog_comments พร้อมใช้งาน\n\n";

    echo "🎉 การอัปเดตโครงสร้างฐานข้อมูลเสร็จสมบูรณ์ 100% เรียบร้อยแล้ว!\n";

} catch (Throwable $e) {
    echo "❌ เกิดข้อผิดพลาดในการอัปเดต: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
    </div>
    
    <div style="margin-top:20px;">
        <span class="badge-success">Migration Ready</span>
        <br>
        <a href="<?= ADMIN_URL ?>/users/" class="btn">ไปยังหน้าจัดการสมาชิก (Admin Users)</a>
        <a href="<?= URL_PATH ?>/th/profile" class="btn" style="background:#d97706;">ไปยังหน้าโปรไฟล์สมาชิก (Profile)</a>
    </div>
</div>
</body>
</html>
