<?php
require_once 'admin/includes/config.php';

try {
    $stmt = $db->query("DESCRIBE bank");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Schema for 'bank' table:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
    // Check if bank_translations exists (unlikely but good to check)
    try {
        $stmt = $db->query("DESCRIBE bank_translations");
        echo "\nSchema for 'bank_translations' table:\n";
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
    } catch (PDOException $e) {
        echo "\nTable 'bank_translations' does not exist.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
