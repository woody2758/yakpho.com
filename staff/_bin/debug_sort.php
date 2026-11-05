<?php
require_once "db.php";
$stmt = $db->query("SELECT id, timestamp FROM massage_leads ORDER BY timestamp DESC, id DESC LIMIT 5");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
?>
