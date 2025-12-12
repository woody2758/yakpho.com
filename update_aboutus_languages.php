<?php
/**
 * Update About Us Language Codes
 * Change ja -> de and ru -> fr in aboutus_translation table
 */

require_once __DIR__ . '/includes/config.php';

try {
    echo "=== Updating About Us Language Codes ===\n\n";
    
    $db->beginTransaction();
    
    // Update Japanese (ja) to German (de)
    $stmt = $db->prepare("
        UPDATE aboutus_translation 
        SET lang_code = 'de' 
        WHERE lang_code = 'ja'
    ");
    $stmt->execute();
    $jaCount = $stmt->rowCount();
    echo "Updated $jaCount records from 'ja' to 'de'\n";
    
    // Update Russian (ru) to French (fr)
    $stmt = $db->prepare("
        UPDATE aboutus_translation 
        SET lang_code = 'fr' 
        WHERE lang_code = 'ru'
    ");
    $stmt->execute();
    $ruCount = $stmt->rowCount();
    echo "Updated $ruCount records from 'ru' to 'fr'\n";
    
    // Check for any missing language codes and insert empty records
    $aboutusRecords = $db->query("SELECT aboutus_id FROM aboutus WHERE aboutus_del = 0")->fetchAll(PDO::FETCH_COLUMN);
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko'];
    
    $insertedCount = 0;
    foreach ($aboutusRecords as $aboutusId) {
        foreach ($languages as $lang) {
            // Check if translation exists
            $check = $db->prepare("SELECT id FROM aboutus_translation WHERE aboutus_id = ? AND lang_code = ?");
            $check->execute([$aboutusId, $lang]);
            
            if (!$check->fetchColumn()) {
                // Insert empty translation
                $insert = $db->prepare("
                    INSERT INTO aboutus_translation (aboutus_id, lang_code, title, subtitle, content)
                    VALUES (?, ?, '', '', '')
                ");
                $insert->execute([$aboutusId, $lang]);
                $insertedCount++;
                echo "Added empty translation for aboutus_id=$aboutusId, lang=$lang\n";
            }
        }
    }
    
    $db->commit();
    
    echo "\n=== Summary ===\n";
    echo "- Changed ja->de: $jaCount records\n";
    echo "- Changed ru->fr: $ruCount records\n";
    echo "- Added empty translations: $insertedCount records\n";
    
    // Show current language distribution
    echo "\n=== Current Language Distribution ===\n";
    $stmt = $db->query("
        SELECT lang_code, COUNT(*) as cnt 
        FROM aboutus_translation 
        GROUP BY lang_code 
        ORDER BY lang_code
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['lang_code']}: {$row['cnt']} records\n";
    }
    
    echo "\n✅ Update completed successfully!\n";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}
