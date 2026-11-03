<?php
require 'includes/config.php';
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
$out = "";
foreach($tables as $table) {
    $out .= "TABLE: $table\n";
    $cols = $db->query("DESCRIBE $table")->fetchAll();
    foreach($cols as $col) {
        $out .= "  - {$col['Field']} ({$col['Type']})\n";
    }
}
file_put_contents('db_schema.txt', $out);
echo "Done";
