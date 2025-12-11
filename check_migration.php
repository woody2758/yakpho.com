<?php
require_once __DIR__ . '/includes/config.php';

echo "=== ORSTS_TRANSLATIONS DATA ===\n";
$stmt = $db->query("SELECT * FROM orsts_translations LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['translation_id']} | ORSTS_ID: {$row['orsts_id']} | Lang: {$row['lang_code']} | Name: {$row['orsts_name']}\n";
}

echo "\nTotal Rows: " . $db->query("SELECT COUNT(*) FROM orsts_translations")->fetchColumn() . "\n";
?>
