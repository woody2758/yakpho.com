<?php
require 'db.php';
$stmt = $db->query("SELECT * FROM bot_processes");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
