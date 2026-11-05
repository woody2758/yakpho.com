<?php
require_once "d:/xampp/htdocs/yakpho.com/staff/db.php";

try {
    $sql = "ALTER TABLE thai_massage_pages 
            ADD COLUMN IF NOT EXISTS phone VARCHAR(50) AFTER followers,
            ADD COLUMN IF NOT EXISTS email VARCHAR(100) AFTER phone,
            ADD COLUMN IF NOT EXISTS website VARCHAR(255) AFTER email,
            ADD COLUMN IF NOT EXISTS last_visited_at DATETIME AFTER website,
            ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER last_visited_at";
    
    $db->exec($sql);
    echo "Database updated successfully!\n";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
