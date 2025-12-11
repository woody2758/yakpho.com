<?php
require_once 'admin/includes/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `user_history` (
        `history_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `action` varchar(50) NOT NULL,
        `details` text DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`history_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    $db->exec($sql);
    echo "Created table 'user_history'.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
