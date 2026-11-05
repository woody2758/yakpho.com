<?php
require 'db.php';
$stmt = $db->query("DESCRIBE bot_processes");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
