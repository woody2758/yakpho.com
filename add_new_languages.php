<?php
require_once __DIR__ . '/includes/config.php';

try {
    $db->beginTransaction();
    
    // Insert new languages
    // code, name, flag (optional), order
    $languages = [
        ['fr', 'Français', 'fr', 4],
        ['zh', '中文', 'cn', 5],
        ['ko', '한국어', 'kr', 6]
    ];
    
    $stmt = $db->prepare("INSERT INTO languages (lang_code, lang_name, lang_flag, lang_order, lang_status, lang_default) VALUES (?, ?, ?, ?, 1, 0)");
    
    foreach ($languages as $lang) {
        // Check if exists first
        $checkStmt = $db->prepare("SELECT lang_id FROM languages WHERE lang_code = ?");
        $checkStmt->execute([$lang[0]]);
        
        if (!$checkStmt->fetch()) {
            $stmt->execute($lang);
            echo "✅ Added: {$lang[0]} - {$lang[1]}\n";
        } else {
            echo "⏭️  Skipped: {$lang[0]} - already exists\n";
        }
    }
    
    $db->commit();
    echo "\n✅ All languages added successfully!\n";
    
    // Show all languages
    echo "\n=== ALL LANGUAGES ===\n";
    $stmt = $db->query("SELECT * FROM languages ORDER BY lang_order ASC");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-5s %-10s %-20s Order: %d Status: %d\n", 
            $row['lang_id'], 
            $row['lang_code'], 
            $row['lang_name'] ?? 'N/A',
            $row['lang_order'],
            $row['lang_status']
        );
    }
    
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
