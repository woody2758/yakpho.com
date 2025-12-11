<?php
require_once '../includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Current Database Tables ===\n\n";
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "- $table\n";
}

echo "\n\n=== Checking for Price Tier Table ===\n";
$hasPriceTier = in_array('price_tiers', $tables);
echo $hasPriceTier ? "✅ price_tiers table EXISTS" : "❌ price_tiers table DOES NOT EXIST";

echo "\n\n=== Current Product Table Structure ===\n";
$stmt = $db->query("DESCRIBE product");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "{$col['Field']} ({$col['Type']}) - {$col['Null']} - Default: {$col['Default']}\n";
}
?>
