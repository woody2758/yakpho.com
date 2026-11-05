<?php
require 'db.php';
$stmt = $db->query("SELECT * FROM fb_link_tracking LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
