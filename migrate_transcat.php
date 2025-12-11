<?php
require_once 'admin/includes/config.php';

try {
    echo "Starting Shipping Category Migration...\n";
    echo "--------------------------------\n";

    // 1. Create transcat_translations table
    $sql = "CREATE TABLE IF NOT EXISTS `transcat_translations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `transcat_id` int(11) NOT NULL,
        `lang_code` varchar(10) NOT NULL,
        `transcat_name` varchar(255) DEFAULT NULL,
        `transcat_nshort` varchar(100) DEFAULT NULL,
        `transcat_detail` text DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `transcat_id` (`transcat_id`),
        KEY `lang_code` (`lang_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    
    $db->exec($sql);
    echo "Created table 'transcat_translations'.\n";

    // 2. Get existing data
    $stmt = $db->query("SELECT * FROM transcat");
    $transcats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($transcats) . " existing shipping methods.\n";

    // 3. Migrate data
    $migrated = 0;
    foreach ($transcats as $tc) {
        $id = $tc['transcat_id'];
        
        // Check if already migrated
        $check = $db->prepare("SELECT COUNT(*) FROM transcat_translations WHERE transcat_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            continue;
        }

        // Insert Thai (Original)
        $stmt = $db->prepare("INSERT INTO transcat_translations 
            (transcat_id, lang_code, transcat_name, transcat_nshort, transcat_detail) 
            VALUES (?, 'th', ?, ?, ?)");
        $stmt->execute([
            $id, 
            $tc['transcat_name'], 
            $tc['transcat_nshort'], 
            $tc['transcat_detail']
        ]);

        // Insert English (Copy Thai as placeholder)
        $stmt = $db->prepare("INSERT INTO transcat_translations 
            (transcat_id, lang_code, transcat_name, transcat_nshort, transcat_detail) 
            VALUES (?, 'en', ?, ?, ?)");
        $stmt->execute([
            $id, 
            $tc['transcat_name'], 
            $tc['transcat_nshort'], 
            $tc['transcat_detail']
        ]);

        $migrated++;
    }

    echo "Migrated $migrated records.\n";
    echo "--------------------------------\n";
    echo "Migration Complete.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
