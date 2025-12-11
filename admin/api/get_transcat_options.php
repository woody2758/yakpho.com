<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $stmt = $db->query("SELECT transcat_id, transcat_name FROM transcat WHERE transcat_del = 0 AND transcat_status = 1 ORDER BY CAST(transcat_index AS UNSIGNED) ASC");
    $transcats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $transcats]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
