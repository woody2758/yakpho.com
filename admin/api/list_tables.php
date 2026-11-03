<?php
require_once __DIR__ . '/../includes/config.php';
$stmt = $db->query("SHOW TABLES");
echo "<h3>ตารางในฐานข้อมูล</h3><ul>";
while($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo "<li>{$row[0]}</li>";
}
echo "</ul>";
