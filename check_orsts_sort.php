<?php
require_once 'admin/includes/config.php';

try {
    $stmt = $db->query("SELECT orsts_id, orsts_detail, orsts_index FROM orsts WHERE orsts_del = 0 ORDER BY orsts_index ASC");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Order Statuses (Sorted by Index):\n";
    foreach ($statuses as $s) {
        echo "ID: " . $s['orsts_id'] . " | Index: " . $s['orsts_index'] . " | Name: " . $s['orsts_detail'] . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
