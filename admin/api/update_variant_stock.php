<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions/product.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$variantId = $data['variant_id'] ?? 0;
$newStock = $data['stock'] ?? 0;

if (!$variantId) {
    echo json_encode(['success' => false, 'message' => 'Variant ID is required']);
    exit;
}

try {
    $adminId = $_SESSION['admin_id'] ?? 0;
    update_variant_stock($variantId, $newStock, 'ปรับสต๊อก', null, $adminId);
    echo json_encode(['success' => true, 'message' => 'Stock updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
