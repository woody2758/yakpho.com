<?php
/**
 * Standalone Migration Script for YakPho Hosting
 * Purpose: Update thai_massage_pages table schema for Deep Extraction.
 */
require_once "db.php"; // Assumes db.php is in the same directory

try {
    echo "<h2>YakPho Database Migration</h2>";
    echo "Starting migration on: " . date("Y-m-d H:i:s") . "<br>";

    // 1. Ensure Table Exists
    $sql_table = "CREATE TABLE IF NOT EXISTS thai_massage_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_name VARCHAR(255),
        page_url VARCHAR(255) UNIQUE,
        messenger_link VARCHAR(255),
        province VARCHAR(100),
        followers VARCHAR(50),
        phone VARCHAR(50),
        email VARCHAR(100),
        website VARCHAR(255),
        is_active TINYINT DEFAULT 1,
        last_visited_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $db->exec($sql_table);
    echo "✓ Table 'thai_massage_pages' is ready.<br>";

    // 2. Add New Columns if they don't exist
    $columns_to_add = [
        'phone' => "VARCHAR(50) AFTER followers",
        'email' => "VARCHAR(100) AFTER phone",
        'website' => "VARCHAR(255) AFTER email",
        'last_visited_at' => "DATETIME AFTER website",
        'deep_scraped_at' => "DATETIME NULL DEFAULT NULL AFTER last_visited_at"
    ];

    foreach ($columns_to_add as $col => $definition) {
        $check = $db->query("SHOW COLUMNS FROM thai_massage_pages LIKE '$col'")->rowCount();
        if ($check == 0) {
            $db->exec("ALTER TABLE thai_massage_pages ADD COLUMN $col $definition");
            echo "✓ Column '$col' added.<br>";
        } else {
            echo "• Column '$col' already exists.<br>";
        }
    }

    // 3. Register V6 Bots in bot_processes
    $db->exec("INSERT IGNORE INTO bot_processes (bot_key, script_name, status) VALUES ('fb_search_harvester', 'fb_search_harvester.py', 'idle')");
    $db->exec("INSERT IGNORE INTO bot_processes (bot_key, script_name, status) VALUES ('fb_deep_scraper', 'fb_deep_scraper.py', 'idle')");
    echo "✓ V6.0 AI Staff (Scout & Inspector) Registered successfully.<br>";

    echo "<br><strong style='color:green'>Migration Completed Successfully!</strong><br>";
    echo "You can now delete this file for security.";

} catch (Exception $e) {
    echo "<br><strong style='color:red'>Migration Error:</strong> " . $e->getMessage();
}
?>
