<?php
require_once __DIR__ . "/../includes/config.php";
$stmt = $db->query("SELECT orsts_id, orsts_detail, orsts_msg FROM orsts ORDER BY orsts_id ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "DATA_START\n";
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
echo "\nDATA_END";
