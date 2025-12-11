<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $stmt = $db->query("SELECT * FROM orsts WHERE orsts_del = 0 ORDER BY CAST(orsts_index AS UNSIGNED) ASC");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'statuses' => $statuses]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
