<?php
require_once '../includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Product Index Migration ===\n\n";

try {
    // 1. Add product_index column if not exists
    echo "Step 1: Adding product_index column...\n";
    
    $db->exec("
        ALTER TABLE product 
        ADD COLUMN IF NOT EXISTS product_index INT DEFAULT 0 COMMENT 'Sort order within category'
    ");
    
    echo "✅ Column added\n\n";
    
    // 2. Set initial index based on product_id within each category
    echo "Step 2: Setting initial index values...\n";
    
    // Get all categories
    $categories = $db->query("SELECT productcat_id FROM productcat WHERE productcat_del = 0")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($categories as $catId) {
        // Get products in this category ordered by product_id
        $stmt = $db->prepare("
            SELECT product_id 
            FROM product 
            WHERE productcat_id = ? AND product_del = 0 
            ORDER BY product_id ASC
        ");
        $stmt->execute([$catId]);
        $products = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Set index for each product
        $updateStmt = $db->prepare("UPDATE product SET product_index = ? WHERE product_id = ?");
        foreach ($products as $index => $productId) {
            $updateStmt->execute([$index + 1, $productId]);
        }
        
        echo "  ✓ Category ID {$catId}: " . count($products) . " products indexed\n";
    }
    
    // Handle products without category
    $stmt = $db->query("
        SELECT product_id 
        FROM product 
        WHERE (productcat_id IS NULL OR productcat_id = 0) AND product_del = 0 
        ORDER BY product_id ASC
    ");
    $noCatProducts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($noCatProducts) > 0) {
        $updateStmt = $db->prepare("UPDATE product SET product_index = ? WHERE product_id = ?");
        foreach ($noCatProducts as $index => $productId) {
            $updateStmt->execute([$index + 1, $productId]);
        }
        echo "  ✓ No category: " . count($noCatProducts) . " products indexed\n";
    }
    
    echo "\n✅ Initial indexing complete\n\n";
    
    // 3. Verify
    echo "Step 3: Verification...\n";
    $result = $db->query("
        SELECT COUNT(*) as total, 
               COUNT(CASE WHEN product_index > 0 THEN 1 END) as indexed
        FROM product 
        WHERE product_del = 0
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "Total products: {$result['total']}\n";
    echo "Indexed products: {$result['indexed']}\n";
    
    if ($result['total'] == $result['indexed']) {
        echo "\n✅ All products have been indexed!\n";
    } else {
        echo "\n⚠️  Some products still have index = 0\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
