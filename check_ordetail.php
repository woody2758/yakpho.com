<?php
require_once 'admin/includes/config.php';

try {
    $stmt = $db->query("DESCRIBE ordetail");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Schema for 'ordetail' table:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
