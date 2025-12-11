<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // ดึงสินค้าที่ active เท่านั้น
    $stmt = $db->prepare("SELECT 
        product_id, 
        product_name, 
        product_code, 
        product_price,
        product_stock
        FROM product 
        WHERE product_del = 0 
        ORDER BY product_name ASC");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
