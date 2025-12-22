<?php
// Simple API test without headers
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/includes/config.php';

try {
    // Test the actual query
    $catStmt = $db->prepare("
        SELECT pc.productcat_id, pct.productcat_name
        FROM productcat pc
        LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id 
            AND pct.lang_code = 'th'
        WHERE pc.productcat_del = 0
        ORDER BY pc.productcat_order ASC, pc.productcat_id ASC
    ");
    $catStmt->execute();
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Categories: " . count($categories) . "\n";
    print_r($categories);
    
    echo "\n\nNow testing products...\n";
    
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
        ORDER BY p.product_order ASC, p.product_id ASC
        LIMIT 3
    ");
    $prodStmt->execute();
    $allProducts = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Products: " . count($allProducts) . "\n";
    print_r($allProducts);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
