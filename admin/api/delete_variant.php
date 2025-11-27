<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$variantId = $data['variant_id'] ?? 0;

if (!$variantId) {
    echo json_encode(['success' => false, 'message' => 'Variant ID is required']);
    exit;
}

try {
    global $db;
    $stmt = $db->prepare("DELETE FROM product_variants WHERE variant_id = ?");
    $result = $stmt->execute([$variantId]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Variant deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete variant']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
