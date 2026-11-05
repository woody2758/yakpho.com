<?php
require 'db.php';
$db->exec("ALTER TABLE ai_staff MODIFY COLUMN status ENUM('Active', 'Inactive', 'Deleted') NOT NULL DEFAULT 'Active'");
echo 'Schema updated';
?>
