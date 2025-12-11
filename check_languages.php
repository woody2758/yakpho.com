<?php
require_once __DIR__ . '/includes/config.php';

echo "=== LANGUAGES TABLE ===\n";
$stmt = $db->query("SELECT * FROM languages ORDER BY lang_id");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-5s %-10s %-20s Status: %d\n", 
        $row['lang_id'], 
        $row['lang_code'], 
        $row['lang_name'] ?? 'N/A',
        $row['lang_status']
    );
}
?>
