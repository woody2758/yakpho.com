<?php
// Test get_products_grid.php API
require_once __DIR__ . '/admin/includes/config.php';

echo "=== Testing Product Grid API ===\n\n";

// Test 1: Check active products
$stmt = $db->prepare("SELECT COUNT(*) as count FROM product WHERE product_del = 0 AND product_status = 1");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "1. Active products (del=0, status=1): " . $result['count'] . "\n\n";

// Test 2: Check products with translations
$stmt = $db->prepare("
    SELECT p.product_id, p.product_code, pt.product_name 
    FROM product p
    LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = 'th'
    WHERE p.product_del = 0 AND p.product_status = 1
    LIMIT 5
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "2. Sample products:\n";
print_r($products);

// Test 3: Check categories
$stmt = $db->prepare("SELECT COUNT(*) as count FROM productcat WHERE productcat_del = 0");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\n3. Active categories: " . $result['count'] . "\n";

// Test 4: Actually call the API
echo "\n4. API Response:\n";
ob_start();
include __DIR__ . '/admin/api/get_products_grid.php';
$output = ob_get_clean();
echo $output;
