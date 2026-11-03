<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // 1. ตาราง yakpho_hero_slides (เก็บข้อมูลหลักของแต่ละ Slide)
    $sql1 = "CREATE TABLE IF NOT EXISTS `yakpho_hero_slides` (
        `slide_id` int(11) NOT NULL AUTO_INCREMENT,
        `slide_image` varchar(255) DEFAULT NULL,
        `slide_order` int(11) NOT NULL DEFAULT '0',
        `slide_status` tinyint(1) NOT NULL DEFAULT '1',
        `btn1_url` varchar(255) DEFAULT NULL,
        `btn2_url` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`slide_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    // 2. ตาราง yakpho_hero_translations (เก็บข้อมูลภาษาต่างๆ ของแต่ละ Slide)
    $sql2 = "CREATE TABLE IF NOT EXISTS `yakpho_hero_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `slide_id` int(11) NOT NULL,
        `lang_code` varchar(10) NOT NULL,
        `slide_title` varchar(255) DEFAULT NULL,
        `slide_subtitle` text DEFAULT NULL,
        `slide_badges` varchar(255) DEFAULT NULL,
        `btn1_text` varchar(100) DEFAULT NULL,
        `btn2_text` varchar(100) DEFAULT NULL,
        PRIMARY KEY (`translation_id`),
        UNIQUE KEY `slide_lang` (`slide_id`,`lang_code`),
        CONSTRAINT `fk_yakpho_hero` FOREIGN KEY (`slide_id`) REFERENCES `yakpho_hero_slides` (`slide_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql1);
    $db->exec($sql2);

    echo "Database tables for Yakpho Hero created successfully!";
} catch (Exception $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
