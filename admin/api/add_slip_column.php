<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $db->exec("ALTER TABLE orders ADD COLUMN orders_slip VARCHAR(255) DEFAULT NULL AFTER orders_msg");
    echo "Column orders_slip added successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
