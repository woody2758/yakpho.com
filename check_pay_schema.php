<?php
require_once 'admin/includes/config.php';
$stmt = $db->query("DESCRIBE pay");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";
?>
