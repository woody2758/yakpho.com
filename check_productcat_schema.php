<?php
require_once __DIR__ . '/includes/config.php';

echo "=== PRODUCTCAT TABLE ===\n";
$stmt = $db->query('DESCRIBE productcat');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-30s %-20s\n", $row['Field'], $row['Type']);
}

echo "\n=== PRODUCTCAT_TRANSLATIONS TABLE ===\n";
$stmt = $db->query('DESCRIBE productcat_translations');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-30s %-20s\n", $row['Field'], $row['Type']);
}
?>
