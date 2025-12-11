<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $stmt = $db->query("SELECT orders_slip FROM orders LIMIT 1");
    echo "Column exists";
} catch (Exception $e) {
    echo "Column does not exist: " . $e->getMessage();
}
?>
