<?php
/**
 * Get Products Grid Data - For Order Product Selection
 * Returns products organized by categories with images
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    // Get all active product categories
    $catStmt = $db->prepare("
        SELECT pc.productcat_id, pct.productcat_name
        FROM productcat pc
        LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id 
            AND pct.lang_code = 'th'
        WHERE pc.productcat_del = 0
        ORDER BY pc.productcat_id ASC
    ");
    $catStmt->execute();
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all active products with details
    $prodStmt = $db->prepare("
        SELECT 
            p.product_id,
            p.productcat_id,
            p.product_code,
            p.product_price,
            p.product_picture,
            pt.product_name
        FROM product p
        LEFT JOIN product_translations pt ON p.product_id = pt.product_id 
            AND pt.lang_code = 'th'
        WHERE p.product_del = 0 AND p.product_status = 1
        ORDER BY p.product_id ASC
    ");
    $prodStmt->execute();
    $allProducts = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group products by category
    $result = [];
    foreach ($categories as $cat) {
        $catProducts = array_filter($allProducts, function($p) use ($cat) {
            return $p['productcat_id'] == $cat['productcat_id'];
        });
        
        // Format product images
        $formattedProducts = array_map(function($p) {
            // Build image path
            if (!empty($p['product_picture'])) {
                // Try small version first (thumbnail)
                $imagePath = str_replace('original-', 'small-', $p['product_picture']);
                $imagePath = str_replace(['uploads/products/', '.jpg', '.jpeg', '.png', '.gif'], 
                                        ['uploads/products/', '.webp', '.webp', '.webp', '.webp'], 
                                        $imagePath);
                
                $fullPath = __DIR__ . '/../../' . $imagePath;
                
                // Fallback to original if small doesn't exist
                if (!file_exists($fullPath) && !empty($p['product_picture'])) {
                    $imagePath = $p['product_picture'];
                }
                
                $p['product_image_url'] = ROOT_URL . '/' . $imagePath;
            } else {
                $p['product_image_url'] = ROOT_URL . '/uploads/products/default.png';
            }
            
            unset($p['product_picture']); // Remove file path, keep URL only
            return $p;
        }, array_values($catProducts));
        
        $result[] = [
            'productcat_id' => $cat['productcat_id'],
            'productcat_name' => $cat['productcat_name'] ?: 'ไม่มีหมวดหมู่',
            'products' => $formattedProducts
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $result
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
