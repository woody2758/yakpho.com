<?php
/**
 * Cleanup products with productcat_id = 0
 */

require_once __DIR__ . '/includes/config.php';

echo "=== Cleanup Products with productcat_id = 0 ===\n\n";

try {
    $db->beginTransaction();
    
    // Get products with productcat_id = 0
    $sql = "SELECT product_id, product_picture 
            FROM product 
            WHERE productcat_id = 0 
            AND product_del = 0";
    
    $stmt = $db->query($sql);
    $productsToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $productIds = array_column($productsToDelete, 'product_id');
    $productCount = count($productIds);
    
    echo "Found $productCount products with productcat_id = 0\n\n";
    
    if ($productCount === 0) {
        echo "No products to delete. Exiting.\n";
        $db->rollBack();
        exit;
    }
    
    // Delete related data
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
    
    // Delete product_promotions
    try {
        $stmt = $db->prepare("DELETE FROM product_promotions WHERE product_id IN ($productIdsPlaceholders)");
        $stmt->execute($productIds);
        echo "- Deleted " . $stmt->rowCount() . " promotions\n";
    } catch (Exception $e) {
        echo "- Promotions table not found (skipped)\n";
    }
    
    // Delete products
    $stmt = $db->prepare("DELETE FROM product WHERE product_id IN ($productIdsPlaceholders)");
    $stmt->execute($productIds);
    echo "- Deleted " . $stmt->rowCount() . " products\n\n";
    
    // Commit database changes
    $db->commit();
    echo "✓ Database cleanup completed successfully!\n\n";
    
    // Delete image files
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
    echo "\n✓ All cleanup tasks completed successfully!\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Database changes have been rolled back.\n";
}
