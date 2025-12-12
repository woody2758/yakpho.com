<?php
/**
 * Migration Script: Migrate About Us to Multi-Language
 * Migrates existing aboutus data to aboutus_translation table
 */

require_once __DIR__ . '/includes/config.php';

echo "=== About Us Migration Script ===\n\n";

try {
    $db->beginTransaction();
    
    // Step 1: Check current data
    echo "Step 1: Checking existing data...\n";
    $existing = $db->query("SELECT * FROM aboutus WHERE aboutus_del = 0")->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($existing) . " aboutus records\n\n";
    
    // Step 2: Check translation table
    echo "Step 2: Checking translation table...\n";
    $translations = $db->query("SELECT COUNT(*) FROM aboutus_translation")->fetchColumn();
    echo "Current translations: $translations\n\n";
    
    if ($translations > 0) {
        echo "⚠️ Warning: Translation table already has data!\n";
        echo "Do you want to continue? This will ADD new translations (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) != 'y') {
            echo "Migration cancelled.\n";
            exit;
        }
    }
    
    // Step 3: Migrate each record
    echo "\nStep 3: Migrating to translation table...\n";
    
    foreach ($existing as $record) {
        $aboutus_id = $record['aboutus_id'];
        $title = $record['aboutus_metatitle'] ?? $record['aboutus_heading'] ?? '';
        $subtitle = $record['aboutus_description'] ?? '';
        $content = $record['aboutus_detail'] ?? '';
        
        echo "Migrating ID: $aboutus_id\n";
        echo "  Title: " . substr($title, 0, 50) . "...\n";
        
        // Delete existing translations first (for re-migration)
        $db->prepare("DELETE FROM aboutus_translation WHERE aboutus_id = ?")->execute([$aboutus_id]);
        
        // Insert Thai translation (default)
        $stmt = $db->prepare("
            INSERT INTO aboutus_translation (aboutus_id, lang_code, title, subtitle, content)
            VALUES (?, 'th', ?, ?, ?)
        ");
        
        $stmt->execute([$aboutus_id, $title, $subtitle, $content]);
        echo "  ✓ Created Thai translation\n";
        
        // Create empty translations for other languages
        $languages = ['en', 'zh', 'ja', 'ko', 'ru'];
        foreach ($languages as $lang) {
            $stmt = $db->prepare("
                INSERT INTO aboutus_translation (aboutus_id, lang_code, title, subtitle, content)
                VALUES (?, ?, '', '', '')
            ");
            $stmt->execute([$aboutus_id, $lang]);
            echo "  ✓ Created empty $lang translation\n";
        }
    }
    
    // Step 4: Add indexes for performance
    echo "\nStep 4: Adding indexes...\n";
    
    try {
        $db->exec("ALTER TABLE aboutus_translation ADD INDEX idx_aboutus_lang (aboutus_id, lang_code)");
        echo "  ✓ Added composite index\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "  - Index already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Step 5: Verify
    echo "\nStep 5: Verifying migration...\n";
    $total = $db->query("SELECT COUNT(*) FROM aboutus_translation")->fetchColumn();
    echo "Total translations: $total\n";
    
    $byLang = $db->query("
        SELECT lang_code, COUNT(*) as cnt 
        FROM aboutus_translation 
        GROUP BY lang_code
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Breakdown by language:\n";
    foreach ($byLang as $row) {
        echo "  - {$row['lang_code']}: {$row['cnt']} records\n";
    }
    
    $db->commit();
    echo "\n✅ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Migration rolled back.\n";
}
