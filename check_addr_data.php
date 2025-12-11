<?php
require_once 'admin/includes/config.php';

try {
    $stmt = $db->query("SELECT * FROM addr LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
