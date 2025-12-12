<?php
/**
 * Check primary key types for foreign key compatibility
 */

require_once __DIR__ . '/includes/config.php';

echo "=== Checking Primary Key Types ===\n\n";

$tables = ['blog', 'blogcat', 'faqs', 'faqscat'];

foreach ($tables as $table) {
    $stmt = $db->query("SHOW CREATE TABLE $table");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "$table:\n";
    
    // Extract primary key info
    $createTable = $result['Create Table'];
    if (preg_match('/`' . $table . '_id`\s+(\w+\([^\)]+\)|[\w\s]+)/i', $createTable, $matches)) {
        echo "  ID Type: " . trim($matches[1]) . "\n";
    }
    
    // Check for AUTO_INCREMENT
    if (preg_match('/AUTO_INCREMENT=(\d+)/i', $createTable, $matches)) {
        echo "  AUTO_INCREMENT: " . $matches[1] . "\n";
    }
    
    echo "\n";
}
