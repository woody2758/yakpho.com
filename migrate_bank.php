<?php
require_once __DIR__ . '/admin/includes/config.php';

try {
    $db->beginTransaction();

    // 1. Create bank_translations table
    $sql = "CREATE TABLE IF NOT EXISTS `bank_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `bank_id` int(11) NOT NULL,
        `lang_code` varchar(5) NOT NULL,
        `bank_bankname` varchar(255) DEFAULT NULL,
        `bank_accountname` varchar(255) DEFAULT NULL,
        `bank_accounttype` varchar(255) DEFAULT NULL,
        `bank_accountbranch` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`translation_id`),
        KEY `bank_id` (`bank_id`),
        KEY `lang_code` (`lang_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "✅ Created table 'bank_translations'\n";

    // 2. Migrate existing data to Thai (th)
    // Check if data already exists to avoid duplicates
    $stmt = $db->query("SELECT COUNT(*) FROM bank_translations");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $stmt = $db->query("SELECT * FROM bank");
        $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $insertStmt = $db->prepare("INSERT INTO bank_translations (bank_id, lang_code, bank_bankname, bank_accountname, bank_accounttype, bank_accountbranch) VALUES (?, 'th', ?, ?, ?, ?)");
        
        foreach ($banks as $bank) {
            $insertStmt->execute([
                $bank['bank_id'],
                $bank['bank_bankname'],
                $bank['bank_accountname'],
                $bank['bank_accounttype'],
                $bank['bank_accountbranch']
            ]);
            echo "   -> Migrated Bank ID {$bank['bank_id']}: {$bank['bank_bankname']}\n";
        }
        echo "✅ Migration completed.\n";
    } else {
        echo "ℹ️  Data already exists in 'bank_translations', skipping migration.\n";
    }

    $db->commit();
    echo "\n=== SUCCESS ===\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
