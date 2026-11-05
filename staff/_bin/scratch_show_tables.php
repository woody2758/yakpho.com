<?php
require 'db.php';
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $table) {
    echo "TABLE: $table\n";
    $cols = $db->query("DESCRIBE $table")->fetchAll();
    foreach($cols as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
}
