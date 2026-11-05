<?php
require_once "db.php";

try {
    $sql = file_get_contents("sql/create_fb_groups_table.sql");
    $db->exec($sql);
    echo "Database setup successful. Table 'facebook_groups' created.";
} catch(PDOException $e) {
    echo "Critical Error: " . $e->getMessage();
}
?>
