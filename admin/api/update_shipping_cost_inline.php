<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$orders_id = isset($_POST['orders_id']) ? (int)$_POST['orders_id'] : 0;
$delicosttrue = isset($_POST['delicosttrue']) && $_POST['delicosttrue'] !== '' ? (float)$_POST['delicosttrue'] : null;

if ($orders_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE orders SET orders_delicosttrue = ?, update_id = ?, orders_update = NOW() WHERE orders_id = ?");
    $stmt->execute([$delicosttrue, $_SESSION['admin_id'], $orders_id]);

    // Log Activity
    $logMsg = "Updated actual shipping cost (True Cost: " . ($delicosttrue !== null ? $delicosttrue : 'null') . ")";
    logStaffAction($db, 'UPDATE', 'orders', $orders_id, $logMsg);

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
