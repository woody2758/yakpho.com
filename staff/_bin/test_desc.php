<?php
require_once "db.php";
echo "facebook_groups columns:\n";
print_r($db->query('DESCRIBE facebook_groups')->fetchAll(PDO::FETCH_COLUMN));
echo "\ncreative_post_queue columns:\n";
print_r($db->query('DESCRIBE creative_post_queue')->fetchAll(PDO::FETCH_COLUMN));
