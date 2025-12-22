<?php
/**
 * Add Arabic and Hebrew Languages (The Perfect 10)
 * Adds AR and HE to the languages table to complete 10-language support
 */

require_once __DIR__ . '/includes/config.php';

try {
    echo "=== Adding Arabic and Hebrew Languages (The Perfect 10) ===\n\n";
    
    $db->beginTransaction();
    
    // Check if Arabic exists
    $check = $db->prepare("SELECT lang_id FROM languages WHERE lang_code = 'ar'");
    $check->execute();
    
    if (!$check->fetchColumn()) {
        echo "Adding Arabic (ar)...\n";
        $stmt = $db->prepare("
            INSERT INTO languages (lang_code, lang_name, lang_flag, lang_order, lang_status, lang_default)
            VALUES ('ar', 'العربية', '🇦🇪', 9, 1, 0)
        ");
        $stmt->execute();
        echo "✓ Arabic added successfully\n";
    } else {
        echo "✓ Arabic already exists\n";
    }
    
    // Check if Hebrew exists
    $check = $db->prepare("SELECT lang_id FROM languages WHERE lang_code = 'he'");
    $check->execute();
    
    if (!$check->fetchColumn()) {
        echo "Adding Hebrew (he)...\n";
        $stmt = $db->prepare("
            INSERT INTO languages (lang_code, lang_name, lang_flag, lang_order, lang_status, lang_default)
            VALUES ('he', 'עברית', '🇮🇱', 10, 1, 0)
        ");
        $stmt->execute();
        echo "✓ Hebrew added successfully\n";
    } else {
        echo "✓ Hebrew already exists\n";
    }
    
    $db->commit();
    
    echo "\n=== Current Languages (The Perfect 10) ===\n";
    $stmt = $db->query("
        SELECT lang_code, lang_name, lang_flag, lang_order, lang_status 
        FROM languages 
        ORDER BY lang_order
    ");
    
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['lang_status'] ? '✓' : '✗';
        echo sprintf("%s %s %s (%s) - Order: %d\n", 
            $status,
            $row['lang_flag'],
            $row['lang_name'], 
            $row['lang_code'],
            $row['lang_order']
        );
        $count++;
    }
    
    echo "\n✅ Total Languages: $count";
    if ($count === 10) {
        echo " - THE PERFECT 10 ACHIEVED! 🎉\n";
    } else {
        echo " - Warning: Expected 10 languages\n";
    }
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}
