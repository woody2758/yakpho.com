<?php
require_once __DIR__ . '/includes/config.php';

try {
    echo "=== Product Translations Table Schema ===\n\n";
    $stmt = $db->query('DESCRIBE product_translations');
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-25s %-20s %s\n", 
            $row['Field'], 
            $row['Type'],
            $row['Key'] ? "KEY: {$row['Key']}" : ''
        );
    }
    
    echo "\n=== Sample Data ===\n";
    $sample = $db->query('SELECT * FROM product_translations LIMIT 3');
    while ($row = $sample->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
