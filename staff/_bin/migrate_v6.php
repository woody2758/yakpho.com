<?php
require_once "db.php";
try {
    $db->exec("ALTER TABLE thai_massage_pages ADD COLUMN deep_scraped_at DATETIME NULL DEFAULT NULL");
    echo "Migration Success";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
         echo "Migration Success (Already exists)";
    } else {
         echo "Error: " . $e->getMessage();
    }
}
