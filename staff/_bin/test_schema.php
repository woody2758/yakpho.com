<?php
require 'db.php';
$cols = $db->query("SHOW COLUMNS FROM creative_contents")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
