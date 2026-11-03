<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $include_disabled = isset($_GET['all']) && $_GET['all'] == 1;
    $status_condition = $include_disabled ? "" : " AND o.orsts_status = 1";
    
    $sql = "SELECT o.*, t.orsts_name as orsts_name_en 
            FROM orsts o 
            LEFT JOIN orsts_translations t ON o.orsts_id = t.orsts_id AND t.lang_code = 'en'
            WHERE o.orsts_del = 0 $status_condition 
            ORDER BY o.orsts_index ASC";
            
    $stmt = $db->query($sql);
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'statuses' => $statuses]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
