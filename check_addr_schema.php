<?php
require_once 'admin/includes/config.php';

try {
    $stmt = $db->query("DESCRIBE addr");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Schema for 'addr' table:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
