<?php
require_once "db.php";

try {
    $sql = file_get_contents("sql/create_fb_settings_tables.sql");
    $db->exec($sql);
    echo "Database setup successful. Tables 'fb_keywords' and 'fb_configs' created/updated.";
} catch(PDOException $e) {
    echo "Critical Error: " . $e->getMessage();
}
?>
