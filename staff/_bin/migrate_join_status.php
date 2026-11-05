<?php
require_once "db.php";
try {
    $db->exec("ALTER TABLE facebook_groups MODIFY COLUMN join_status ENUM('Not Joined', 'Queued', 'Pending', 'Joined', 'Blocked') DEFAULT 'Not Joined'");
    echo "Join Status updated successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
