<?php
require 'db.php';
$stmt = $db->query('SELECT * FROM fb_configs');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
