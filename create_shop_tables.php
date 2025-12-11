<?php
require_once 'admin/includes/config.php';

try {
    // Create shop_info table
    $sql1 = "CREATE TABLE IF NOT EXISTS `shop_info` (
        `shop_id` int(11) NOT NULL AUTO_INCREMENT,
        `shop_phone` varchar(50) DEFAULT NULL,
        `shop_email` varchar(100) DEFAULT NULL,
        `shop_tax_id` varchar(50) DEFAULT NULL,
        `shop_logo` varchar(255) DEFAULT NULL,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`shop_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $db->exec($sql1);
    echo "Created table 'shop_info'.\n";

    // Create shop_info_translations table
    $sql2 = "CREATE TABLE IF NOT EXISTS `shop_info_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `shop_id` int(11) NOT NULL,
        `lang_code` varchar(5) NOT NULL,
        `shop_name` varchar(255) DEFAULT NULL,
        `shop_address` text DEFAULT NULL,
        `official_name` varchar(255) DEFAULT NULL,
        `official_address` text DEFAULT NULL,
        PRIMARY KEY (`translation_id`),
        KEY `shop_id` (`shop_id`),
        KEY `lang_code` (`lang_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $db->exec($sql2);
    echo "Created table 'shop_info_translations'.\n";

    // Insert default row if not exists
    $stmt = $db->query("SELECT COUNT(*) FROM shop_info");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO shop_info (shop_id) VALUES (1)");
        echo "Inserted default shop_info row.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
