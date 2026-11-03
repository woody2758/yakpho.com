<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // 1. ตาราง yakpho_contact (ข้อมูลหลักที่ใช้ร่วมกันทุกภาษา)
    $sql1 = "CREATE TABLE IF NOT EXISTS `yakpho_contact` (
        `contact_id` int(11) NOT NULL AUTO_INCREMENT,
        `phone` varchar(50) DEFAULT NULL,
        `line_id` varchar(100) DEFAULT NULL,
        `facebook` varchar(100) DEFAULT NULL,
        `email` varchar(150) DEFAULT NULL,
        `whatsapp` varchar(100) DEFAULT NULL,
        `map_embed` text,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`contact_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql1);

    // 2. ตาราง yakpho_contact_translations (ข้อมูลที่ต้องแปลภาษา)
    $sql2 = "CREATE TABLE IF NOT EXISTS `yakpho_contact_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `contact_id` int(11) NOT NULL,
        `lang_code` varchar(10) NOT NULL,
        `title` varchar(255) DEFAULT 'ติดต่อเรา',
        `company_name` varchar(255) DEFAULT NULL,
        `address` text,
        PRIMARY KEY (`translation_id`),
        UNIQUE KEY `idx_contact_lang` (`contact_id`, `lang_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql2);

    // แทรกข้อมูลเริ่มต้น
    $check = $db->query("SELECT COUNT(*) FROM yakpho_contact WHERE contact_id = 1")->fetchColumn();
    if ($check == 0) {
        $db->exec("INSERT INTO yakpho_contact (contact_id, phone, line_id, facebook, email, whatsapp, map_embed) VALUES (1, '(+66) 80 061 7073', '@yakpho', 'YakPho Aroma', 'thaiherbfc@gmail.com', '+66 080 061 7073', '')");
        
        $stmtTrans = $db->prepare("INSERT INTO yakpho_contact_translations (contact_id, lang_code, title, company_name, address) VALUES (?, ?, ?, ?, ?)");
        $stmtTrans->execute([1, 'th', 'ติดต่อเรา', 'บริษัท เอสเธอร์ อโรม่า จำกัด', 'เลขที่ 32/4 ถนนพระยาสุเรนทร์ แขวงสามวาตะวันตก เขตคลองสามวา กรุงเทพมหานคร 10510 ประเทศไทย']);
        $stmtTrans->execute([1, 'en', 'Contact Us', 'Esther Aroma Co., Ltd.', '32/4 Phraya Suren Rd, Sam Wa Tawan Tok, Khlong Sam Wa, Bangkok 10510 Thailand']);
    }

    echo "✅ ตารางฐานข้อมูลสำหรับส่วน 'Contact Us' ถูกสร้างเรียบร้อยแล้ว!\n";

} catch (PDOException $e) {
    echo "❌ มีข้อผิดพลาดในการสร้างฐานข้อมูล: " . $e->getMessage() . "\n";
}
?>
