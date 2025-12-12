<?php
require_once __DIR__ . '/includes/config.php';

// Check aboutus table structure
try {
    $stmt = $db->query("SHOW TABLES LIKE '%about%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "=== Tables containing 'about' ===\n";
    if (empty($tables)) {
        echo "No tables found!\n\n";
    } else {
        foreach ($tables as $table) {
            echo "Table: $table\n";
            
            // Show structure
            $struct = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
            echo "Columns:\n";
            foreach ($struct as $col) {
                echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
            }
            
            // Show sample data
            $sample = $db->query("SELECT * FROM $table LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            echo "Sample data (" . count($sample) . " rows):\n";
            print_r($sample);
            echo "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
