<?php
require_once '../includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== บาล์มนวด Products in Database ===\n\n";

// Search for balm products
$stmt = $db->prepare("
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
    AND product_name LIKE '%บาล์ม%'
    ORDER BY product_code ASC
    LIMIT 50
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found: " . count($products) . " products\n\n";

if (count($products) > 0) {
    foreach ($products as $p) {
        echo "ID: {$p['product_id']} | Code: {$p['product_code']} | {$p['product_name']}\n";
        echo "  Formula: {$p['formula']} | Scent: {$p['scent']} | Price: {$p['product_price']} | Weight: {$p['product_weight']}\n";
        echo "  Stock: {$p['product_stock']}\n\n";
    }
    
    // Count by formula
    echo "\n=== Count by Formula ===\n";
    $formulaCount = $db->query("
        SELECT formula, COUNT(*) as count 
        FROM product 
        WHERE product_del = 0 AND product_name LIKE '%บาล์ม%'
        GROUP BY formula
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($formulaCount as $fc) {
        echo "{$fc['formula']}: {$fc['count']} products\n";
    }
    
    // Count by scent
    echo "\n=== Count by Scent ===\n";
    $scentCount = $db->query("
        SELECT scent, COUNT(*) as count 
        FROM product 
        WHERE product_del = 0 AND product_name LIKE '%บาล์ม%'
        GROUP BY scent
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($scentCount as $sc) {
        echo "{$sc['scent']}: {$sc['count']} products\n";
    }
} else {
    echo "❌ No balm products found!\n";
    echo "Need to create products first.\n";
}
?>
