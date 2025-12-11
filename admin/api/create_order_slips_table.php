<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS order_slips (
        slip_id INT AUTO_INCREMENT PRIMARY KEY,
        orders_id INT NOT NULL,
        slip_filename VARCHAR(255) NOT NULL,
        slip_uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        uploaded_by INT DEFAULT 0,
        INDEX (orders_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "Table order_slips created successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
