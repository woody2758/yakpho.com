<?php
require_once "db.php";
$stmt = $db->query("SELECT * FROM system_settings");
$rows = $stmt->fetchAll();
echo "<pre>";
print_r($rows);
echo "</pre>";
?>
