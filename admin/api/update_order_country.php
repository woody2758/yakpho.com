<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$orders_id = isset($_POST['orders_id']) ? (int)$_POST['orders_id'] : 0;
$country_id = isset($_POST['country_id']) ? (int)$_POST['country_id'] : 0;

if ($orders_id <= 0 || $country_id < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE orders SET orders_country_id = ? WHERE orders_id = ?");
    $stmt->execute([$country_id, $orders_id]);

    echo json_encode(['success' => true, 'message' => 'อัปเดตประเทศเรียบร้อยแล้ว']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
