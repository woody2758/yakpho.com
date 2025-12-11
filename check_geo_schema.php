<?php
require_once 'admin/includes/config.php';

try {
    echo "Schema for 'provinces':\n";
    $stmt = $db->query("DESCRIBE provinces");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) echo $col['Field'] . " - " . $col['Type'] . "\n";

    echo "\nSchema for 'amphures':\n";
    $stmt = $db->query("DESCRIBE amphures");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) echo $col['Field'] . " - " . $col['Type'] . "\n";

    echo "\nSchema for 'districts':\n";
    $stmt = $db->query("DESCRIBE districts");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) echo $col['Field'] . " - " . $col['Type'] . "\n";

    echo "\nSample data from 'addr' (first 1 row):\n";
    $stmt = $db->query("SELECT * FROM addr LIMIT 1");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
