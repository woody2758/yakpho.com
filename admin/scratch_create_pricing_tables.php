<?php
require 'includes/config.php';

try {
    $queries = [
        "CREATE TABLE IF NOT EXISTS yakpho_pricing_intro (
            intro_id INT(11) PRIMARY KEY AUTO_INCREMENT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        "CREATE TABLE IF NOT EXISTS yakpho_pricing_intro_translations (
            translation_id INT(11) PRIMARY KEY AUTO_INCREMENT,
            intro_id INT(11),
            lang_code VARCHAR(10),
            intro_title VARCHAR(255),
            intro_subtitle VARCHAR(255),
            FOREIGN KEY (intro_id) REFERENCES yakpho_pricing_intro(intro_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS yakpho_pricing_tiers (
            tier_id INT(11) PRIMARY KEY AUTO_INCREMENT,
            tier_order INT(11) DEFAULT 0,
            tier_status TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS yakpho_pricing_tier_translations (
            translation_id INT(11) PRIMARY KEY AUTO_INCREMENT,
            tier_id INT(11),
            lang_code VARCHAR(10),
            tier_volume VARCHAR(255),
            tier_price VARCHAR(255),
            FOREIGN KEY (tier_id) REFERENCES yakpho_pricing_tiers(tier_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS yakpho_pricing_features (
            feature_id INT(11) PRIMARY KEY AUTO_INCREMENT,
            feature_icon VARCHAR(50),
            feature_order INT(11) DEFAULT 0,
            feature_status TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS yakpho_pricing_feature_translations (
            translation_id INT(11) PRIMARY KEY AUTO_INCREMENT,
            feature_id INT(11),
            lang_code VARCHAR(10),
            feature_text VARCHAR(255),
            FOREIGN KEY (feature_id) REFERENCES yakpho_pricing_features(feature_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "INSERT INTO yakpho_pricing_intro (intro_id) VALUES (1) ON DUPLICATE KEY UPDATE intro_id=1;"
    ];

    foreach ($queries as $q) {
        $db->exec($q);
    }
    
    // Seed initial translations for intro
    $stmt = $db->query("SELECT COUNT(*) FROM yakpho_pricing_intro_translations");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO yakpho_pricing_intro_translations (intro_id, lang_code, intro_title, intro_subtitle) VALUES 
            (1, 'th', 'เรทราคาส่ง YakPho Aroma', 'ราคาต่อกิโลกรัม สำหรับทุกสูตรและทุกกลิ่น'),
            (1, 'en', 'YakPho Aroma Wholesale Rates', 'Price per kilogram for all formulas and scents')");
    }

    // Insert dummy tiers
    $stmt = $db->query("SELECT COUNT(*) FROM yakpho_pricing_tiers");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO yakpho_pricing_tiers (tier_order) VALUES (1), (2), (3), (4), (5), (6)");
        $db->exec("INSERT INTO yakpho_pricing_tier_translations (tier_id, lang_code, tier_volume, tier_price) VALUES 
            (1, 'th', '1 กก.', '690 บาท'),
            (2, 'th', '6 กก.', '590 บาท'),
            (3, 'th', '10 กก.', '560 บาท'),
            (4, 'th', '20 กก.', '530 บาท'),
            (5, 'th', '30 กก.', '500 บาท'),
            (6, 'th', '50 กก.', '490 บาท')");
    }

    // Insert dummy features
    $stmt = $db->query("SELECT COUNT(*) FROM yakpho_pricing_features");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO yakpho_pricing_features (feature_icon, feature_order) VALUES ('📦', 1), ('🌿', 2), ('🎨', 3)");
        $db->exec("INSERT INTO yakpho_pricing_feature_translations (feature_id, lang_code, feature_text) VALUES 
            (1, 'th', 'บรรจุถุงซีลขนาด 1 กก. แน่นหนาพร้อมเดินทางไกล'),
            (2, 'th', 'ผลิตสดใหม่ทุกออเดอร์ เพื่อคงคุณภาพและกลิ่นหอม'),
            (3, 'th', 'คละสูตร คละกลิ่นได้ทุกแบบ')");
    }

    echo "Tables created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
