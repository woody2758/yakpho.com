<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $stmt = $db->query("DESCRIBE pay");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
