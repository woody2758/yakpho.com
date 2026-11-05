<?php
require_once "db.php";

try {
    echo "Checking bot_processes table...\n";
    $stmt = $db->query("DESCRIBE bot_processes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);

    echo "\nChecking bot_processes content...\n";
    $stmt = $db->query("SELECT * FROM bot_processes");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
