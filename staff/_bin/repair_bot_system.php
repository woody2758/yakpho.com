<?php
require_once "db.php";

echo "<h2>YAKPHO Bot System Repair</h2>";

try {
    // 1. Ensure table structure is correct (Basic check)
    $db->exec("CREATE TABLE IF NOT EXISTS bot_processes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bot_key VARCHAR(50) UNIQUE,
        script_name VARCHAR(100),
        status VARCHAR(20) DEFAULT 'idle',
        pid INT DEFAULT 0,
        pending_command VARCHAR(20) DEFAULT NULL,
        last_started DATETIME DEFAULT NULL,
        vps_last_sync DATETIME DEFAULT NULL,
        log_cache TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // 2. Seed/Restore Bot Staff
    $bots = [
        ['yakpho_real_work', 'lead_gen_scraper.py'],
        ['fb_group_scraper', 'fb_group_scraper.py'],
        ['fb_ai_processor',  'ai_processor.py'],
        ['fb_joiner',        'group_joiner.py'],
        ['fb_page_scraper',  'fb_page_scraper.py']
    ];

    foreach ($bots as $b) {
        $stmt = $db->prepare("INSERT INTO bot_processes (bot_key, script_name, status) 
                            VALUES (?, ?, 'idle') 
                            ON DUPLICATE KEY UPDATE script_name = VALUES(script_name)");
        $stmt->execute($b);
        echo "✓ Bot '" . $b[0] . "' is ready.<br>";
    }

    echo "<br><b style='color:green;'>SUCCESS:</b> Bot system has been repaired. Please check your Dashboard now.";

} catch (Exception $e) {
    echo "<b style='color:red;'>ERROR:</b> " . $e->getMessage();
}
?>
