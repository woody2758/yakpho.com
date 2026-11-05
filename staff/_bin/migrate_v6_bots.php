<?php
require_once "db.php";
try {
    $db->exec("INSERT IGNORE INTO bot_processes (bot_key, script_name, status) VALUES ('fb_search_harvester', 'fb_search_harvester.py', 'idle')");
    $db->exec("INSERT IGNORE INTO bot_processes (bot_key, script_name, status) VALUES ('fb_deep_scraper', 'fb_deep_scraper.py', 'idle')");
    echo "Bots registered successfully in DB.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
