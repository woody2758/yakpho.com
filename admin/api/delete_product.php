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
$productId = $data['product_id'] ?? 0;

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

try {
    $adminId = $_SESSION['admin_id'] ?? 0;
    $result = delete_product($productId, $adminId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
