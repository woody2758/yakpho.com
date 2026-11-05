<?php
require_once "db.php";

try {
    $db->exec("CREATE TABLE IF NOT EXISTS creative_contents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ai_strategy VARCHAR(20) NOT NULL COMMENT 'Green, Yellow, Red',
        content_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $db->exec("CREATE TABLE IF NOT EXISTS creative_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_path VARCHAR(255) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $db->exec("CREATE TABLE IF NOT EXISTS creative_post_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target_id INT NOT NULL COMMENT 'Refers to group_id or page_id',
        target_type VARCHAR(20) NOT NULL COMMENT 'group, page',
        content_id INT NULL COMMENT 'Can be null if dynamically generated (e.g. Red)',
        image_id INT NULL COMMENT 'Can be null',
        scheduled_at DATETIME NOT NULL,
        status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, posting, completed, failed',
        bot_staff_id INT NULL,
        post_url VARCHAR(255) NULL,
        error_message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "✅ Database setup successful. Creative tables created!";
} catch(PDOException $e) {
    echo "Critical Error: " . $e->getMessage();
}
?>
