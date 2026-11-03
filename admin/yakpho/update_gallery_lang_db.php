<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $db->exec("
    CREATE TABLE IF NOT EXISTS `yakpho_gallery_category_translations` (
        `category_id` INT(11) NOT NULL,
        `lang_code` VARCHAR(10) NOT NULL,
        `category_name` VARCHAR(255) NOT NULL,
        PRIMARY KEY (`category_id`, `lang_code`),
        FOREIGN KEY (`category_id`) REFERENCES `yakpho_gallery_categories`(`category_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Migrate existing names to TH
    $stmt = $db->query("SELECT category_id, category_name FROM yakpho_gallery_categories");
    $cats = $stmt->fetchAll();
    
    $ins = $db->prepare("INSERT IGNORE INTO yakpho_gallery_category_translations (category_id, lang_code, category_name) VALUES (?, 'th', ?)");
    foreach($cats as $cat) {
        $ins->execute([$cat['category_id'], $cat['category_name']]);
    }

    echo "Translation table created and data migrated successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
