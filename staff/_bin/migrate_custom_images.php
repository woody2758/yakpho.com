<?php
require 'db.php';
try {
    $db->query("ALTER TABLE creative_contents ADD COLUMN custom_images TEXT DEFAULT NULL");
    echo "Column custom_images added successfully.<br>";
} catch (Exception $e) {
    echo "Error (or column already exists): " . $e->getMessage() . "<br>";
}
if (!is_dir('uploads/creative/custom')) {
    mkdir('uploads/creative/custom', 0777, true);
    echo "Directory uploads/creative/custom created.<br>";
} else {
    echo "Directory uploads/creative/custom already exists.<br>";
}
echo "Migration Done.";
?>
