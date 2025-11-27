<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions/product.php';

header('Content-Type: application/json');

$productId = $_GET['id'] ?? 0;

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

try {
    $product = get_product_by_id($productId, 'th');
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Get translations
    $translations = get_product_translations($productId);
    
    // Get attribute sets
    $attributeSets = get_product_attribute_sets($productId, 'th');
    
    echo json_encode([
        'success' => true,
        'product' => $product,
        'translations' => $translations,
        'attribute_sets' => $attributeSets
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
