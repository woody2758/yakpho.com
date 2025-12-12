<?php
/**
 * Add Japanese and Russian Languages
 * Adds JA and RU to the languages table
 */

require_once __DIR__ . '/includes/config.php';

try {
    echo "=== Adding Japanese and Russian Languages ===\n\n";
    
    $db->beginTransaction();
    
    // Check if Japanese exists
    $check = $db->prepare("SELECT lang_id FROM languages WHERE lang_code = 'ja'");
    $check->execute();
    
    if (!$check->fetchColumn()) {
        echo "Adding Japanese (ja)...\n";
        $stmt = $db->prepare("
            INSERT INTO languages (lang_code, lang_name, lang_flag, lang_order, lang_status, lang_default)
            VALUES ('ja', '日本語', '🇯🇵', 7, 1, 0)
        ");
        $stmt->execute();
        echo "✓ Japanese added successfully\n";
    } else {
        echo "✓ Japanese already exists\n";
    }
    
    // Check if Russian exists
    $check = $db->prepare("SELECT lang_id FROM languages WHERE lang_code = 'ru'");
    $check->execute();
    
    if (!$check->fetchColumn()) {
        echo "Adding Russian (ru)...\n";
        $stmt = $db->prepare("
            INSERT INTO languages (lang_code, lang_name, lang_flag, lang_order, lang_status, lang_default)
            VALUES ('ru', 'Русский', '🇷🇺', 8, 1, 0)
        ");
        $stmt->execute();
        echo "✓ Russian added successfully\n";
    } else {
        echo "✓ Russian already exists\n";
    }
    
    $db->commit();
    
    echo "\n=== Current Languages ===\n";
    $stmt = $db->query("
        SELECT lang_code, lang_name, lang_order, lang_status 
        FROM languages 
        ORDER BY lang_order
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['lang_status'] ? '✓' : '✗';
        echo sprintf("%s %s (%s) - Order: %d\n", 
            $status,
            $row['lang_name'], 
            $row['lang_code'],
            $row['lang_order']
        );
    }
    
    echo "\n✅ Language table updated successfully!\n";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}
