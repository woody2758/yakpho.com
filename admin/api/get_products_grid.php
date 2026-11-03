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
        WHERE pc.productcat_del = 0 AND pc.productcat_status = 1
        ORDER BY pc.productcat_index ASC, pc.productcat_id ASC
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
            p.vat_type,
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
            $filename = $p['product_picture'];
            
            if (!empty($filename)) {
                // Determine the correct image path
                // Check if it already has pathing info, if not assume uploads/products/
                $basePath = (strpos($filename, 'uploads/products/') !== false) ? '' : 'uploads/products/';
                
                // Try small version first
                $smallFilename = 'small-' . basename($filename);
                $smallPath = $basePath . $smallFilename;
                
                // Fallback to original if small doesn't exist or just use filename as is
                // We'll trust the UI to handle standard pathing
                if (strpos($filename, 'uploads/products/') === false) {
                    $p['product_image_url'] = ROOT_URL . "/uploads/products/small-" . $filename;
                } else {
                    $p['product_image_url'] = ROOT_URL . "/" . $filename;
                }
                
            } else {
                $p['product_image_url'] = ROOT_URL . '/admin/assets/images/placeholder.png';
            }
            
            unset($p['product_picture']); 
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
