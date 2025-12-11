<?php
require_once 'admin/includes/config.php';

try {
    $stmt = $db->query("DESCRIBE user_history");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Schema for 'user_history' table:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Table 'user_history' not found or error: " . $e->getMessage();
}
?>
