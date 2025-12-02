<?php
// Prevent any output before JSON
ob_start();

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/functions/product.php';
    require_once __DIR__ . '/../../includes/functions/attribute.php';
    
    // Clear any previous output
    ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    
    $productId = $_GET['id'] ?? 0;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        exit;
    }
    
    $product = get_product_by_id($productId, 'th');
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Get translations (with error handling)
    $translations = [];
    try {
        $translations = get_product_translations($productId);
        if (!$translations) {
            $translations = [];
        }
    } catch (Exception $e) {
        // If translations fail, continue with empty array
        $translations = [];
    }
    
    // Get attribute sets (with error handling)
    $attributeSets = [];
    try {
        if (function_exists('get_product_attribute_sets')) {
            $attributeSets = get_product_attribute_sets($productId, 'th');
            if (!$attributeSets) {
                $attributeSets = [];
            }
        }
    } catch (Exception $e) {
        // If attribute sets fail, continue with empty array
        $attributeSets = [];
    }
    
    // Get gallery images
    $galleryImages = [];
    try {
        $stmt = $db->prepare("SELECT image_id, image_filename, image_order FROM product_images WHERE product_id = ? ORDER BY image_order ASC");
        $stmt->execute([$productId]);
        $galleryImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $galleryImages = [];
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product,
        'translations' => $translations,
        'attribute_sets' => $attributeSets,
        'gallery_images' => $galleryImages
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Clear any output
    ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    // Catch fatal errors
    ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal error: ' . $e->getMessage(),
        'error' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
