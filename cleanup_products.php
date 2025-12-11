<?php
/**
 * Product Cleanup Script
 * Removes products not in specified categories and their related data/images
 */

require_once __DIR__ . '/includes/config.php';

// Categories to KEEP
$keepCategories = [32, 38, 40, 41, 42, 46];

echo "=== Product Cleanup Script ===\n\n";

try {
    $db->beginTransaction();
    
    // Step 1: Get products to DELETE (not in keep categories or NULL category)
    $placeholders = implode(',', array_fill(0, count($keepCategories), '?'));
    $sql = "SELECT product_id, product_picture 
            FROM product 
            WHERE (productcat_id NOT IN ($placeholders) OR productcat_id IS NULL)
            AND product_del = 0";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($keepCategories);
    $productsToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $productIds = array_column($productsToDelete, 'product_id');
    $productCount = count($productIds);
    
    echo "Found $productCount products to delete\n\n";
    
    if ($productCount === 0) {
        echo "No products to delete. Exiting.\n";
        $db->rollBack();
        exit;
    }
    
    // Step 2: Delete related data
    echo "Deleting related data...\n";
    
    $productIdsPlaceholders = implode(',', array_fill(0, count($productIds), '?'));
    
    // Delete product_translations
    $stmt = $db->prepare("DELETE FROM product_translations WHERE product_id IN ($productIdsPlaceholders)");
    $stmt->execute($productIds);
    echo "- Deleted " . $stmt->rowCount() . " product translations\n";
    
    // Delete product_variants
    $stmt = $db->prepare("DELETE FROM product_variants WHERE product_id IN ($productIdsPlaceholders)");
    $stmt->execute($productIds);
    echo "- Deleted " . $stmt->rowCount() . " product variants\n";
    
    // Delete product_images
    $stmt = $db->prepare("DELETE FROM product_images WHERE product_id IN ($productIdsPlaceholders)");
    $stmt->execute($productIds);
    echo "- Deleted " . $stmt->rowCount() . " product images\n";
    
    // Delete product_stock_log
    $stmt = $db->prepare("DELETE FROM product_stock_log WHERE product_id IN ($productIdsPlaceholders)");
    $stmt->execute($productIds);
    echo "- Deleted " . $stmt->rowCount() . " stock logs\n";
    
    // Delete product_attribute_sets
    $stmt = $db->prepare("DELETE FROM product_attribute_sets WHERE product_id IN ($productIdsPlaceholders)");
    $stmt->execute($productIds);
    echo "- Deleted " . $stmt->rowCount() . " attribute sets\n";
    
    // Delete product_promotions (if exists)
    try {
        $stmt = $db->prepare("DELETE FROM product_promotions WHERE product_id IN ($productIdsPlaceholders)");
        $stmt->execute($productIds);
        echo "- Deleted " . $stmt->rowCount() . " promotions\n";
    } catch (Exception $e) {
        echo "- Promotions table not found (skipped)\n";
    }
    
    // Step 3: Delete products
    $stmt = $db->prepare("DELETE FROM product WHERE product_id IN ($productIdsPlaceholders)");
    $stmt->execute($productIds);
    echo "- Deleted " . $stmt->rowCount() . " products\n\n";
    
    // Step 4: Delete unused categories
    echo "Deleting unused categories...\n";
    
    // Delete category translations
    $stmt = $db->prepare("DELETE FROM productcat_translations WHERE productcat_id NOT IN ($placeholders)");
    $stmt->execute($keepCategories);
    echo "- Deleted " . $stmt->rowCount() . " category translations\n";
    
    // Delete categories
    $stmt = $db->prepare("DELETE FROM productcat WHERE productcat_id NOT IN ($placeholders)");
    $stmt->execute($keepCategories);
    echo "- Deleted " . $stmt->rowCount() . " categories\n\n";
    
    // Commit database changes
    $db->commit();
    echo "✓ Database cleanup completed successfully!\n\n";
    
    // Step 5: Delete image files
    echo "Deleting image files...\n";
    $uploadsPath = __DIR__ . '/uploads/products';
    $deletedImages = 0;
    $missingImages = 0;
    
    foreach ($productsToDelete as $product) {
        if (empty($product['product_picture'])) {
            continue;
        }
        
        $filename = $product['product_picture'];
        $files = [
            $uploadsPath . '/' . $filename,
            $uploadsPath . '/large-' . $filename,
            $uploadsPath . '/small-' . $filename
        ];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                if (unlink($file)) {
                    $deletedImages++;
                } else {
                    echo "  ! Failed to delete: $file\n";
                }
            } else {
                $missingImages++;
            }
        }
    }
    
    echo "- Deleted $deletedImages image files\n";
    echo "- $missingImages files were already missing\n\n";
    
    echo "=== Cleanup Summary ===\n";
    echo "Products deleted: $productCount\n";
    echo "Images deleted: $deletedImages\n";
    echo "Categories kept: " . implode(', ', $keepCategories) . "\n";
    echo "\n✓ All cleanup tasks completed successfully!\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Database changes have been rolled back.\n";
}
