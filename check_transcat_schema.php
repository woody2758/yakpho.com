<?php
require_once 'admin/includes/config.php';

try {
    $stmt = $db->query("DESCRIBE transcat");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Schema for 'transcat' table:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
    // Also check current data
    $stmt = $db->query("SELECT * FROM transcat LIMIT 5");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nSample Data:\n";
    print_r($data);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
