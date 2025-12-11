<?php
require_once __DIR__ . '/includes/config.php';

echo "=== ORSTS TABLE ===\n";
try {
    $stmt = $db->query('DESCRIBE orsts');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-30s %-20s\n", $row['Field'], $row['Type']);
    }
} catch (Exception $e) {
    echo "Table 'orsts' not found or error: " . $e->getMessage() . "\n";
}

echo "\n=== ORSTS_TRANSLATIONS TABLE (Check if exists) ===\n";
try {
    $stmt = $db->query('DESCRIBE orsts_translations');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-30s %-20s\n", $row['Field'], $row['Type']);
    }
} catch (Exception $e) {
    echo "Table 'orsts_translations' does not exist.\n";
}
?>
