<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // 1. ตาราง yakpho_scent_intro (เก็บข้อมูลส่วนเกริ่นนำ)
    $sql1 = "CREATE TABLE IF NOT EXISTS `yakpho_scent_intro` (
        `intro_id` int(11) NOT NULL AUTO_INCREMENT,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`intro_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql1);

    // 2. ตาราง yakpho_scent_intro_translations (แปลภาษาให้ Intro)
    $sql2 = "CREATE TABLE IF NOT EXISTS `yakpho_scent_intro_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `intro_id` int(11) NOT NULL,
        `lang_code` varchar(10) NOT NULL,
        `intro_title` varchar(255) DEFAULT NULL,
        `intro_subtitle` varchar(255) DEFAULT NULL,
        `intro_description` text,
        PRIMARY KEY (`translation_id`),
        UNIQUE KEY `idx_intro_lang` (`intro_id`, `lang_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql2);

    // 3. ตาราง yakpho_scent_items (เก็บโครงสร้างและรูปภาพของกลิ่น)
    $sql3 = "CREATE TABLE IF NOT EXISTS `yakpho_scent_items` (
        `item_id` int(11) NOT NULL AUTO_INCREMENT,
        `image_file` varchar(255) DEFAULT NULL,
        `item_order` int(11) NOT NULL DEFAULT '0',
        `item_status` tinyint(1) NOT NULL DEFAULT '1',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql3);

    // 4. ตาราง yakpho_scent_translations (แปลภาษาให้แต่ละกลิ่น)
    $sql4 = "CREATE TABLE IF NOT EXISTS `yakpho_scent_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `item_id` int(11) NOT NULL,
        `lang_code` varchar(10) NOT NULL,
        `title` varchar(255) DEFAULT NULL,
        `subtitle` text,
        PRIMARY KEY (`translation_id`),
        UNIQUE KEY `idx_item_lang` (`item_id`, `lang_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql4);

    // แทรกข้อมูลเริ่มต้นสำหรับ Intro ถ้ายังไม่มี
    $checkIntro = $db->query("SELECT COUNT(*) FROM yakpho_scent_intro WHERE intro_id = 1")->fetchColumn();
    if ($checkIntro == 0) {
        $db->exec("INSERT INTO yakpho_scent_intro (intro_id) VALUES (1)");
        
        $stmtTrans = $db->prepare("INSERT INTO yakpho_scent_intro_translations (intro_id, lang_code, intro_title, intro_subtitle) VALUES (?, ?, ?, ?)");
        $stmtTrans->execute([1, 'th', 'กลิ่นยอดนิยม (12 แบบ)', 'เลือกความหอมที่ตอบโจทย์ร้านคุณ']);
        $stmtTrans->execute([1, 'en', 'Popular Scents (12 Types)', 'Choose the perfect aroma for your spa']);
    }

    echo "✅ ตารางฐานข้อมูลสำหรับส่วน 'กลิ่นยอดนิยม (Scents)' ถูกสร้างเรียบร้อยแล้ว!\n";

} catch (PDOException $e) {
    echo "❌ มีข้อผิดพลาดในการสร้างฐานข้อมูล: " . $e->getMessage() . "\n";
}
?>
