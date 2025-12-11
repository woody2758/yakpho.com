<?php
require_once __DIR__ . '/includes/config.php';

try {
    $db->beginTransaction();

    // 1. Create orsts_translations table
    $sql = "CREATE TABLE IF NOT EXISTS `orsts_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `orsts_id` int(11) NOT NULL,
        `lang_code` varchar(5) NOT NULL,
        `orsts_name` varchar(255) DEFAULT NULL,
        `orsts_msg` text DEFAULT NULL,
        PRIMARY KEY (`translation_id`),
        KEY `orsts_id` (`orsts_id`),
        KEY `lang_code` (`lang_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "✅ Created table 'orsts_translations'\n";

    // 2. Migrate existing data to Thai (th)
    // Check if data already exists to avoid duplicates
    $stmt = $db->query("SELECT COUNT(*) FROM orsts_translations");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $stmt = $db->query("SELECT * FROM orsts");
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $insertStmt = $db->prepare("INSERT INTO orsts_translations (orsts_id, lang_code, orsts_name, orsts_msg) VALUES (?, 'th', ?, ?)");
        
        foreach ($statuses as $status) {
            $insertStmt->execute([
                $status['orsts_id'],
                $status['orsts_detail'], // Old name field
                $status['orsts_msg']     // Old msg field
            ]);
            echo "   -> Migrated ID {$status['orsts_id']}: {$status['orsts_detail']}\n";
        }
        echo "✅ Migration completed.\n";
    } else {
        echo "ℹ️  Data already exists in 'orsts_translations', skipping migration.\n";
    }

    $db->commit();
    echo "\n=== SUCCESS ===\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
