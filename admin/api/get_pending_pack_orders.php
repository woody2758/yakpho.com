<?php
require_once __DIR__ . '/../includes/config.php';
$is_api_call = true;
require_once __DIR__ . '/../includes/scanner_auth.php';

header('Content-Type: application/json');

try {
    $stmt = $db->prepare("SELECT orders_no, orders_date FROM orders WHERE orders_status IN (3, 4) AND orders_del = 0 ORDER BY orders_date ASC");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates for better display
    foreach ($orders as &$order) {
        $order['orders_date'] = date('d/m/Y H:i', strtotime($order['orders_date']));
    }

    echo json_encode(['status' => 'success', 'data' => $orders]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
