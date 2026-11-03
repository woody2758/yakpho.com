<?php
require_once __DIR__ . '/../includes/config.php';
try {
    // 1. Create order_notes table
    $db->exec("CREATE TABLE IF NOT EXISTS `order_notes` (
        `note_id` int(11) NOT NULL AUTO_INCREMENT,
        `orders_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `note_text` text NOT NULL,
        `note_image` varchar(255) DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`note_id`),
        KEY `orders_id` (`orders_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "Table 'order_notes' created or already exists.\n";
    
    // 2. Hide or deactivate status 'รอส่ง สินค้าหมด' (ID 11) from dashboard
    // We will do this via logic in the PHP APIs later
    
    echo "Status cleanup plan initialized.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
