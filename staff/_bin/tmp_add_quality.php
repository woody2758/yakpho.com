<?php
require_once "db.php";
$db->exec("ALTER TABLE facebook_groups ADD COLUMN post_quality VARCHAR(20) NOT NULL DEFAULT 'Good'");
echo "Column post_quality added!";
?>
