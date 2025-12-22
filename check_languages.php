<?php
/**
 * Check Current Language Configuration
 * Displays all languages in the database and their occurrences in translation tables
 */

require_once __DIR__ . '/includes/config.php';

try {
    echo "=== CURRENT LANGUAGE CONFIGURATION ===\n\n";
    
    // 1. Check languages table
    echo "1. Languages Table:\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $db->query('SELECT * FROM languages ORDER BY lang_order');
    
    $dbLanguages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dbLanguages[] = $row['lang_code'];
        echo sprintf(
            "%-5s | %-8s | %-15s | %s | Order: %2d | Status: %d\n",
            $row['lang_id'],
            $row['lang_code'],
            $row['lang_name'],
            $row['lang_flag'],
            $row['lang_order'],
            $row['lang_status']
        );
    }
    
    echo "\nTotal Languages: " . count($dbLanguages) . "\n";
    echo "Codes: " . implode(', ', $dbLanguages) . "\n\n";
    
    // 2. Check translation tables
    echo "2. Translation Tables:\n";
    echo str_repeat("-", 80) . "\n";
    
    $tables = [
        'aboutus_translation',
        'blog_translations',
        'blogcat_translations',
        'faqs_translations',
        'faqscat_translations',
        'product_translations',
        'productcat_translation',
        'orderstatus_translation',
        'paycat_translation',
        'transcat_translation',
        'bank_translation'
    ];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT DISTINCT lang_code FROM {$table} ORDER BY lang_code");
            $langs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $countStmt = $db->query("SELECT COUNT(*) FROM {$table}");
            $count = $countStmt->fetchColumn();
            
            echo sprintf("%-30s: %s (Total: %d)\n", $table, implode(', ', $langs), $count);
        } catch (Exception $e) {
            echo sprintf("%-30s: TABLE NOT FOUND\n", $table);
        }
    }
    
    echo "\n✅ Language check completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
