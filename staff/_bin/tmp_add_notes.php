<?php
require 'db.php';
try {
    $db->exec("ALTER TABLE facebook_groups ADD COLUMN IF NOT EXISTS notes TEXT DEFAULT NULL");
    echo "Column added successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
