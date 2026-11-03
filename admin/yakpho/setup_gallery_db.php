<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // 1. ตาราง yakpho_gallery_categories
    $sql1 = "CREATE TABLE IF NOT EXISTS `yakpho_gallery_categories` (
        `category_id` INT(11) NOT NULL AUTO_INCREMENT,
        `category_name` VARCHAR(255) NOT NULL,
        `sort_order` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql1);
    
    // 2. ตาราง yakpho_gallery_images
    $sql2 = "CREATE TABLE IF NOT EXISTS `yakpho_gallery_images` (
        `image_id` INT(11) NOT NULL AUTO_INCREMENT,
        `category_id` INT(11) NOT NULL,
        `image_file` VARCHAR(255) NOT NULL,
        `sort_order` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`image_id`),
        FOREIGN KEY (`category_id`) REFERENCES `yakpho_gallery_categories`(`category_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql2);

    // Insert Default Data
    $stmtCheck = $db->query("SELECT COUNT(*) FROM yakpho_gallery_categories");
    if ($stmtCheck->fetchColumn() == 0) {
        $db->exec("INSERT INTO yakpho_gallery_categories (category_name, sort_order) VALUES ('สินค้าทั่วไป (General)', 1)");
        $db->exec("INSERT INTO yakpho_gallery_categories (category_name, sort_order) VALUES ('เบื้องหลังการผลิต (Behind the Scenes)', 2)");
    }

    echo "✅ สร้างตาราง Gallery เรียบร้อยแล้ว!\n";
} catch (PDOException $e) {
    echo "❌ ข้อผิดพลาด: " . $e->getMessage() . "\n";
}
?>
