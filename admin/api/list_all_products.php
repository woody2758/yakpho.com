<?php
require_once '../includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== All Products in Database ===\n\n";

// Get all active products
$stmt = $db->query("
    SELECT 
        product_id,
        product_code,
        product_name,
        formula,
        scent,
        product_price,
        product_weight,
        product_stock
    FROM product 
    WHERE product_del = 0 
    ORDER BY product_id ASC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total Products: " . count($products) . "\n\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($products as $i => $p) {
    echo ($i + 1) . ". ID: {$p['product_id']} | Code: {$p['product_code']}\n";
    echo "   Name: {$p['product_name']}\n";
    echo "   Formula: " . ($p['formula'] ?: '-') . " | Scent: " . ($p['scent'] ?: '-') . "\n";
    echo "   Price: {$p['product_price']} บาท | Weight: {$p['product_weight']} | Stock: {$p['product_stock']}\n";
    echo str_repeat("-", 80) . "\n";
}

echo "\n=== Summary ===\n";
echo "Total: " . count($products) . " products\n";

// Show products with formula/scent
$withFormula = $db->query("SELECT COUNT(*) FROM product WHERE product_del = 0 AND formula IS NOT NULL AND formula != ''")->fetchColumn();
$withScent = $db->query("SELECT COUNT(*) FROM product WHERE product_del = 0 AND scent IS NOT NULL AND scent != ''")->fetchColumn();

echo "With Formula: $withFormula\n";
echo "With Scent: $withScent\n";
?>
