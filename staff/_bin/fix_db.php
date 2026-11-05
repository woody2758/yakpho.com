<?php
require_once "db.php";
try {
    $db->exec("ALTER TABLE facebook_groups ADD COLUMN campaign_tag VARCHAR(255) NULL");
    echo "Added campaign_tag to facebook_groups.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
