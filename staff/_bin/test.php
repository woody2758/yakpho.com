<?php
require 'db.php';
$stmt = $db->query('SHOW CREATE TABLE fb_campaign_schedules');
print_r($stmt->fetch());
?>
