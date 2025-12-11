<?php
require_once __DIR__ . '/includes/config.php';

echo "=== LANGUAGES TABLE SCHEMA ===\n";
$stmt = $db->query('DESCRIBE languages');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-20s %-20s\n", $row['Field'], $row['Type']);
}
?>
