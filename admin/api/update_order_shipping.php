<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['orders_id']) || !isset($data['transcat_id'])) {
        throw new Exception('Invalid input');
    }

    $orders_id = (int)$data['orders_id'];
    $transcat_id = (int)$data['transcat_id'];
    $tracking = isset($data['orders_tracking']) ? trim($data['orders_tracking']) : '';
    
    // จัดการต้นทุนค่าส่งจริง
    $delicosttrue_sql = "";
    $params = [$transcat_id, $tracking];
    
    if (isset($data['orders_delicosttrue']) && $data['orders_delicosttrue'] !== '') {
        $delicosttrue_sql = ", orders_delicosttrue = ?";
        $params[] = (float)$data['orders_delicosttrue'];
    }

    $status_sql = "";
    if ($tracking !== '') {
        $status_sql = ", orders_status = 5"; // 5 = ที่ต้องได้รับ
    }

    $params[] = $_SESSION['admin_id'] ?? 0;
    $params[] = $orders_id;

    $stmt = $db->prepare("UPDATE orders SET transcat_id = ?, orders_tracking = ? {$delicosttrue_sql} {$status_sql}, update_id = ?, orders_update = NOW() WHERE orders_id = ?");
    $stmt->execute($params);

    // Log Activity
    $logMsg = "Updated shipping info (Transcat: $transcat_id, Tracking: $tracking)";
    if (isset($data['orders_delicosttrue']) && $data['orders_delicosttrue'] !== '') {
        $logMsg .= ", True Cost: " . $data['orders_delicosttrue'];
    }
    if ($tracking !== '') {
        $logMsg .= ", Status auto-updated to 5 (ที่ต้องได้รับ)";
    }
    logStaffAction($db, 'UPDATE', 'orders', $orders_id, $logMsg);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
