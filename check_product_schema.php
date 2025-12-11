<?php
require_once 'admin/includes/config.php';

try {
    $stmt = $db->query("DESCRIBE product");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Schema for 'product' table:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
