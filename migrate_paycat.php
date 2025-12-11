<?php
require_once __DIR__ . '/admin/includes/config.php';

try {
    $db->beginTransaction();

    // 1. Create paycat_translations table
    $sql = "CREATE TABLE IF NOT EXISTS `paycat_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `paycat_id` int(11) NOT NULL,
        `lang_code` varchar(5) NOT NULL,
        `paycat_name` varchar(255) DEFAULT NULL,
        `paycat_details` text DEFAULT NULL,
        PRIMARY KEY (`translation_id`),
        KEY `paycat_id` (`paycat_id`),
        KEY `lang_code` (`lang_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "✅ Created table 'paycat_translations'\n";

    // 2. Migrate existing data to Thai (th)
    // Check if data already exists to avoid duplicates
    $stmt = $db->query("SELECT COUNT(*) FROM paycat_translations");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $stmt = $db->query("SELECT * FROM paycat");
        $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $insertStmt = $db->prepare("INSERT INTO paycat_translations (paycat_id, lang_code, paycat_name, paycat_details) VALUES (?, 'th', ?, ?)");
        
        foreach ($cats as $cat) {
            $insertStmt->execute([
                $cat['paycat_id'],
                $cat['paycat_name'], // Old name field
                '' // Initialize details as empty
            ]);
            echo "   -> Migrated ID {$cat['paycat_id']}: {$cat['paycat_name']}\n";
        }
        echo "✅ Migration completed.\n";
    } else {
        echo "ℹ️  Data already exists in 'paycat_translations', skipping migration.\n";
    }

    $db->commit();
    echo "\n=== SUCCESS ===\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
