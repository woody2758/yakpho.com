<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — Production Server Database Migration Runner
 * ============================================================
 * Accessible via browser: /admin/migrate_member_system.php
 * Automatically detects whether running on Localhost or Production Server
 */

require_once __DIR__ . '/includes/config.php';

// Simple security check: Must be logged in as Admin OR provide secret key
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
        .box { max-width: 750px; margin: 0 auto; background: #ffffff; color: #222; border-radius: 12px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        h2 { color: #0c3b2e; margin-top: 0; }
        .log { background: #1a1a1a; color: #4ade80; font-family: monospace; padding: 14px; border-radius: 8px; font-size: 13px; line-height: 1.6; max-height: 400px; overflow-y: auto; }
        .btn { display: inline-block; background: #0c3b2e; color: #fff; padding: 10px 20px; border-radius: 20px; text-decoration: none; margin-top: 15px; font-weight: 600; }
    </style>
</head>
<body>
<div class="box">
    <h2>🚀 ระบบอัปเดตตารางฐานข้อมูล (Database Migration)</h2>
    <p>กำลังดำเนินการตรวจสอบและอัปเดตตารางฐานข้อมูลสำหรับ <strong><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Localhost') ?></strong>...</p>
    
    <div class="log">
<?php
try {
    echo "Connected to Database successfully.\n";

    // 1. Check & Add columns in user table
    $stmt = $db->query("DESCRIBE user");
    $existingCols = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingCols[strtolower($r['Field'])] = true;
    }

    $userColumnsToAdd = [
        'line_user_id'      => "VARCHAR(64) NULL AFTER fb_id",
        'line_display_name' => "VARCHAR(150) NULL AFTER line_user_id",
        'line_picture_url'  => "VARCHAR(255) NULL AFTER line_display_name",
        'member_tier'       => "ENUM('general', 'vip_spa', 'therapist_partner', 'oem_export') NOT NULL DEFAULT 'general' AFTER user_level",
        'm_stamp_points'    => "INT NOT NULL DEFAULT 0 AFTER user_stars",
        'affiliate_code'    => "VARCHAR(30) NULL AFTER user_key",
        'email_verified'    => "TINYINT(1) NOT NULL DEFAULT 0 AFTER user_email",
    ];

    foreach ($userColumnsToAdd as $col => $definition) {
        if (!isset($existingCols[$col])) {
            $db->exec("ALTER TABLE user ADD COLUMN $col $definition");
            echo " [OK] Added column: user.$col\n";
        } else {
            echo " [INFO] Column user.$col already exists.\n";
        }
    }

    // 2. Create member_points_log table
    $db->exec("
    CREATE TABLE IF NOT EXISTS member_points_log (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        points_change INT NOT NULL,
        balance_after INT NOT NULL,
        action_type ENUM('order_reward', 'order_discount', 'welcome_bonus', 'admin_adjustment', 'review_reward') NOT NULL,
        reference_id INT NULL,
        note VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] Table member_points_log ready.\n";

    // 3. Create resend_email_templates table
    $db->exec("
    CREATE TABLE IF NOT EXISTS resend_email_templates (
        template_id INT AUTO_INCREMENT PRIMARY KEY,
        template_code VARCHAR(50) UNIQUE NOT NULL,
        template_name VARCHAR(150) NOT NULL,
        email_subject VARCHAR(255) NOT NULL,
        email_body_html LONGTEXT NOT NULL,
        available_merge_tags TEXT NULL,
        is_active TINYINT(1) DEFAULT 1,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] Table resend_email_templates ready.\n";

    // 4. Create line_broadcast_logs table
    $db->exec("
    CREATE TABLE IF NOT EXISTS line_broadcast_logs (
        broadcast_id INT AUTO_INCREMENT PRIMARY KEY,
        target_tier VARCHAR(50) NOT NULL,
        message_text TEXT NOT NULL,
        recipient_count INT DEFAULT 0,
        sent_by_user_id INT NOT NULL DEFAULT 0,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] Table line_broadcast_logs ready.\n";

    // 5. Seed templates if empty
    $tCount = (int)$db->query("SELECT COUNT(*) FROM resend_email_templates")->fetchColumn();
    if ($tCount === 0) {
        $templates = [
            [
                'template_code' => 'order_confirmation',
                'template_name' => 'ยืนยันคำสั่งซื้อ & ใบเสร็จรับเงิน (Order Confirmation & Receipt)',
                'email_subject' => 'ยืนยันคำสั่งซื้อ #{order_no} - Yak Pho Aroma (Esther Synergy)',
                'available_merge_tags' => '{customer_name}, {order_no}, {order_date}, {order_items_html}, {subtotal}, {discount_amount}, {delivery_cost}, {grand_total}, {payment_method}, {shipping_address}, {m_stamp_earned}, {m_stamp_balance}, {tracking_url}',
                'email_body_html' => '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif; background:#f4f6f8; padding:20px;"><div style="max-width:600px; margin:0 auto; background:#fff; border-radius:12px; padding:24px;"><h2 style="color:#0c3b2e;">YAK PHO AROMA</h2><p>เรียน คุณ {customer_name}, คำสั่งซื้อ #{order_no} ได้รับเรียบร้อยแล้ว ยอดชำระสุทธิ: {grand_total} บาท (ได้รับ +{m_stamp_earned} M-Stamp)</p><a href="{tracking_url}" style="display:inline-block; background:#0c3b2e; color:#fff; padding:10px 20px; border-radius:20px; text-decoration:none;">ตรวจสอบสถานะพัสดุ</a></div></body></html>'
            ],
            [
                'template_code' => 'shipping_tracking',
                'template_name' => 'แจ้งสถานะจัดส่ง & เลขพัสดุ (Shipping Tracking)',
                'email_subject' => 'พัสดุของคุณถูกจัดส่งแล้ว! เลขพัสดุ {tracking_number} - Yak Pho Aroma',
                'available_merge_tags' => '{customer_name}, {order_no}, {courier_name}, {tracking_number}, {tracking_url}, {shipping_address}',
                'email_body_html' => '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif; background:#f4f6f8; padding:20px;"><div style="max-width:600px; margin:0 auto; background:#fff; border-radius:12px; padding:24px;"><h2 style="color:#0c3b2e;">พัสดุของคุณจัดส่งแล้ว</h2><p>เรียน คุณ {customer_name}, คำสั่งซื้อ #{order_no} ขนส่งโดย {courier_name} เลขพัสดุ: <strong>{tracking_number}</strong></p><a href="{tracking_url}" style="display:inline-block; background:#16a34a; color:#fff; padding:10px 20px; border-radius:20px; text-decoration:none;">ติดตามพัสดุ</a></div></body></html>'
            ],
            [
                'template_code' => 'email_verification_welcome',
                'template_name' => 'ยืนยันอีเมล & แต้มต้อนรับสมาชิกใหม่ (Email Verification & Welcome)',
                'email_subject' => 'ยืนยันอีเมลของคุณเพื่อรับสิทธิ์และแต้มต้อนรับ 20 M-Stamp - Yak Pho Aroma',
                'available_merge_tags' => '{customer_name}, {verify_url}, {m_stamp_bonus}',
                'email_body_html' => '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif; background:#f4f6f8; padding:20px;"><div style="max-width:600px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; text-align:center;"><h2 style="color:#0c3b2e;">ยินดีต้อนรับสู่ Yak Pho Aroma</h2><p>ยืนยันอีเมลเพื่อรับฟรี {m_stamp_bonus} M-Stamp</p><a href="{verify_url}" style="display:inline-block; background:#0c3b2e; color:#fff; padding:12px 24px; border-radius:20px; text-decoration:none;">ยืนยันอีเมลของฉัน</a></div></body></html>'
            ],
            [
                'template_code' => 'm_stamp_summary',
                'template_name' => 'สรุปยอดแต้มสะสม M-Stamp (M-Stamp Monthly Summary)',
                'email_subject' => 'สรุปแต้ม M-Stamp ของคุณ: {m_stamp_balance} แต้ม พร้อมใช้แลกส่วนลด - Yak Pho Aroma',
                'available_merge_tags' => '{customer_name}, {m_stamp_balance}, {member_tier_name}, {recommended_products_url}',
                'email_body_html' => '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif; background:#f4f6f8; padding:20px;"><div style="max-width:600px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; text-align:center;"><h2 style="color:#0c3b2e;">สิทธิพิเศษ M-Stamp</h2><p>ยอดแต้มสะสมปัจจุบันของคุณ: <strong>{m_stamp_balance} แต้ม</strong> (มูลค่า ฿{m_stamp_balance} บาท)</p><a href="{recommended_products_url}" style="display:inline-block; background:#d4af37; color:#0c3b2e; padding:10px 20px; border-radius:20px; text-decoration:none; font-weight:bold;">ช้อปสินค้า</a></div></body></html>'
            ],
            [
                'template_code' => 'affiliate_commission_monthly',
                'template_name' => 'รายงานค่าคอมมิชชันหมอนวด Partner (Affiliate Commission Report)',
                'email_subject' => 'สรุปรายได้ค่าคอมมิชชัน Yak Pho Partner ประจำงวด: ฿{commission_amount} บาท',
                'available_merge_tags' => '{partner_name}, {period_month}, {total_orders}, {total_sales}, {commission_amount}, {payout_status}, {affiliate_dashboard_url}',
                'email_body_html' => '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif; background:#f4f6f8; padding:20px;"><div style="max-width:600px; margin:0 auto; background:#fff; border-radius:12px; padding:24px; text-align:center;"><h2 style="color:#0c3b2e;">⭐ Yak Pho Therapist Partner</h2><p>สรุปรายได้ประจำงวด {period_month}: <strong>฿{commission_amount} บาท</strong> ({payout_status})</p><a href="{affiliate_dashboard_url}" style="display:inline-block; background:#0c3b2e; color:#fff; padding:10px 20px; border-radius:20px; text-decoration:none;">เข้าสู่แดชบอร์ด</a></div></body></html>'
            ],
            [
                'template_code' => 'reorder_spa_reminder',
                'template_name' => 'แจ้งเตือนสั่งซื้อซ้ำร้านนวดสปา VIP (VIP Spa Reorder Reminder)',
                'email_subject' => 'ยาหม่องนวด 1 กก. ของคุณใกล้หมดหรือยัง? สั่งซ้ำเรทโรงงานส่งด่วน - Yak Pho Aroma',
                'available_merge_tags' => '{spa_name}, {last_order_date}, {reorder_url}, {wholesale_rate_summary}',
                'email_body_html' => '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif; background:#f4f6f8; padding:20px;"><div style="max-width:600px; margin:0 auto; background:#fff; border-radius:12px; padding:24px;"><h2 style="color:#0c3b2e;">👑 สิทธิ์ VIP Spa Member</h2><p>เรียน ร้าน {spa_name}, สั่งซื้อยาหม่อง 1 กก. ซ้ำในเรทโรงงาน (เริ่มต้น ฿590/กก.)</p><a href="{reorder_url}" style="display:inline-block; background:#0c3b2e; color:#fff; padding:10px 20px; border-radius:20px; text-decoration:none;">สั่งซื้อซ้ำ</a></div></body></html>'
            ],
        ];

        $insTmpl = $db->prepare("INSERT INTO resend_email_templates (template_code, template_name, email_subject, email_body_html, available_merge_tags) VALUES (?, ?, ?, ?, ?)");
        foreach ($templates as $t) {
            $insTmpl->execute([$t['template_code'], $t['template_name'], $t['email_subject'], $t['email_body_html'], $t['available_merge_tags']]);
            echo " [OK] Seeded template: {$t['template_code']}\n";
        }
    } else {
        echo " [INFO] Email templates already present ($tCount templates).\n";
    }

    // 6. Seed default settings
    $defaultSettings = [
        'resend_api_key'             => '',
        'resend_from_email'          => 'Yak Pho Aroma <orders@yakpho.com>',
        'line_channel_access_token'  => '',
        'line_login_channel_id'      => '',
        'line_login_channel_secret'  => '',
    ];

    $checkSettingStmt = $db->prepare("SELECT COUNT(*) FROM sitesettings WHERE setting_name = ?");
    $insertSettingStmt = $db->prepare("INSERT INTO sitesettings (setting_name, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");

    foreach ($defaultSettings as $key => $val) {
        $checkSettingStmt->execute([$key]);
        if ($checkSettingStmt->fetchColumn() == 0) {
            $insertSettingStmt->execute([$key, $val]);
            echo " [OK] Seeded setting: $key\n";
        }
    }

    echo "\n=== ALL MIGRATIONS COMPLETED SUCCESSFULLY! ===\n";
} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}
?>
    </div>
    <div style="margin-top: 15px;">
        <a href="<?= ADMIN_URL ?>/users/members.php" class="btn">ไปยังหน้าระบบสมาชิก (Admin Members)</a>
    </div>
</div>
</body>
</html>
