<?php
require_once "db.php";
try {
    $db->exec("ALTER TABLE bot_processes 
        ADD COLUMN IF NOT EXISTS pending_command ENUM('start', 'stop', 'restart') DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS log_cache TEXT,
        ADD COLUMN IF NOT EXISTS vps_last_sync DATETIME DEFAULT NULL");
    echo "Phase 7 Database Columns Added Successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
