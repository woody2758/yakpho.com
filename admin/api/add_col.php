<?php
require 'd:/xampp/htdocs/esther.rest/admin/includes/config.php';
try {
    $db->exec('ALTER TABLE user ADD COLUMN user_google_map VARCHAR(255) DEFAULT NULL AFTER user_note');
    echo 'Success';
} catch(Exception $e) {
    echo $e->getMessage();
}
?>
