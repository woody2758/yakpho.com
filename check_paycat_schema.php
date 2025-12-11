<?php
require_once 'admin/includes/config.php';

try {
    $stmt = $db->query("DESCRIBE paycat");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Schema for 'paycat' table:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
    // Also check if paycat_translations exists
    try {
        $stmt = $db->query("DESCRIBE paycat_translations");
        echo "\nSchema for 'paycat_translations' table:\n";
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
    } catch (PDOException $e) {
        echo "\nTable 'paycat_translations' does not exist.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
