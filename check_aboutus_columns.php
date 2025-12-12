<?php
require_once __DIR__ . '/includes/config.php';
$cols = $db->query("SHOW COLUMNS FROM aboutus")->fetchAll(PDO::FETCH_ASSOC);
echo "Columns in aboutus table:\n";
foreach ($cols as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}
