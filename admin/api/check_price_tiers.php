<?php
require_once '../includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Checking for price_tiers table ===\n\n";

try {
    $stmt = $db->query("DESCRIBE price_tiers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ price_tiers table EXISTS!\n\n";
    echo "Structure:\n";
    foreach ($columns as $col) {
        echo "  {$col['Field']} ({$col['Type']}) - Null: {$col['Null']} - Default: {$col['Default']}\n";
    }
    
    echo "\n\n=== Current Data ===\n";
    $data = $db->query("SELECT * FROM price_tiers ORDER BY tier_min_kg ASC")->fetchAll(PDO::FETCH_ASSOC);
    if ($data) {
        foreach ($data as $row) {
            echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "No data found\n";
    }
} catch (PDOException $e) {
    echo "❌ price_tiers table DOES NOT EXIST\n";
    echo "Error: " . $e->getMessage() . "\n";
    
    echo "\n\n=== Need to create price_tiers table ===\n";
}
?>
