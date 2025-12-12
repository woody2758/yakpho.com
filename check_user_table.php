<?php
require_once __DIR__ . '/includes/config.php';
$tables = $db->query("SHOW TABLES LIKE '%user%'")->fetchAll(PDO::FETCH_COLUMN);
echo "User-related tables:\n";
foreach($tables as $t) {
    echo "- $t\n";
}
