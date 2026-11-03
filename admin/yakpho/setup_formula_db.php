<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // 1. ตาราง yakpho_formula_intro (เก็บข้อมูลส่วนเกริ่นนำ)
    $sql1 = "CREATE TABLE IF NOT EXISTS `yakpho_formula_intro` (
        `intro_id` int(11) NOT NULL AUTO_INCREMENT,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`intro_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    // 2. ตาราง yakpho_formula_intro_translations (เก็บภาษาของส่วนเกริ่นนำ)
    $sql2 = "CREATE TABLE IF NOT EXISTS `yakpho_formula_intro_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `intro_id` int(11) NOT NULL,
        `lang_code` varchar(10) NOT NULL,
        `intro_title` varchar(255) DEFAULT NULL,
        `intro_subtitle` text DEFAULT NULL,
        `intro_description` text DEFAULT NULL,
        PRIMARY KEY (`translation_id`),
        UNIQUE KEY `intro_lang` (`intro_id`,`lang_code`),
        CONSTRAINT `fk_formula_intro` FOREIGN KEY (`intro_id`) REFERENCES `yakpho_formula_intro` (`intro_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // 3. ตาราง yakpho_formula_items (เก็บข้อมูลหลักของแต่ละสูตร)
    $sql3 = "CREATE TABLE IF NOT EXISTS `yakpho_formula_items` (
        `item_id` int(11) NOT NULL AUTO_INCREMENT,
        `image_file` varchar(255) DEFAULT NULL,
        `css_class` varchar(50) DEFAULT 'hot',
        `item_order` int(11) NOT NULL DEFAULT '0',
        `item_status` tinyint(1) NOT NULL DEFAULT '1',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    // 4. ตาราง yakpho_formula_translations (เก็บข้อมูลภาษาต่างๆ ของแต่ละสูตร)
    $sql4 = "CREATE TABLE IF NOT EXISTS `yakpho_formula_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `item_id` int(11) NOT NULL,
        `lang_code` varchar(10) NOT NULL,
        `title` varchar(255) DEFAULT NULL,
        `subtitle` text DEFAULT NULL,
        `bullets` text DEFAULT NULL,
        PRIMARY KEY (`translation_id`),
        UNIQUE KEY `item_lang` (`item_id`,`lang_code`),
        CONSTRAINT `fk_yakpho_formula_item` FOREIGN KEY (`item_id`) REFERENCES `yakpho_formula_items` (`item_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql1);
    $db->exec($sql2);
    $db->exec($sql3);
    $db->exec($sql4);

    // Insert default intro row if not exists
    $stmt = $db->query("SELECT COUNT(*) FROM yakpho_formula_intro");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO yakpho_formula_intro (intro_id) VALUES (1)");
    }

    echo "Database tables for Yakpho Formula created successfully!";
} catch (Exception $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
