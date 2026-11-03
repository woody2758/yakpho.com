<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $db->exec("ALTER TABLE orders ADD COLUMN orders_paid_amount DECIMAL(10,2) DEFAULT 0.00 AFTER orders_grandtotal");
    echo "Column orders_paid_amount added successfully.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
