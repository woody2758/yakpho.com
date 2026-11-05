<?php
require 'd:/xampp/htdocs/yakpho.com/staff/db.php';

try {
    $db->exec("ALTER TABLE creative_images ADD COLUMN target_zone VARCHAR(50) DEFAULT 'All' AFTER is_active");
    echo "Added target_zone to creative_images\n";
} catch (Exception $e) {
    echo "Error creative_images: " . $e->getMessage() . "\n";
}

try {
    $db->exec("ALTER TABLE creative_contents ADD COLUMN target_zone VARCHAR(50) DEFAULT 'All' AFTER content_text");
    echo "Added target_zone to creative_contents\n";
} catch (Exception $e) {
    echo "Error creative_contents: " . $e->getMessage() . "\n";
}

// Update existing records to 'All'
$db->exec("UPDATE creative_images SET target_zone = 'All' WHERE target_zone IS NULL");
$db->exec("UPDATE creative_contents SET target_zone = 'All' WHERE target_zone IS NULL");
?>
