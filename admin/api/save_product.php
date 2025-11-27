<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions/product.php';
require_once __DIR__ . '/../../includes/functions/attribute.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db->beginTransaction();
    
    $productId = $_POST['product_id'] ?? 0;
    $isEdit = !empty($productId);
    
    // Prepare product data
    $productData = [
        'product_code' => $_POST['product_code'] ?? '',
        'productcat_id' => $_POST['productcat_id'] ?? null,
        'product_slug' => $_POST['product_slug'] ?? null,
        'product_price' => $_POST['product_price'] ?? 0,
        'product_nprice' => $_POST['product_nprice'] ?? 0,
        'product_cprice' => $_POST['product_cprice'] ?? 0,
        'product_weight' => $_POST['product_weight'] ?? 0,
        'price_tier_id' => $_POST['price_tier_id'] ?? null,
        'product_stock' => $_POST['product_stock'] ?? 0,
        'stock_alert_enabled' => isset($_POST['stock_alert_enabled']) ? 1 : 0,
        'stock_alert_level' => $_POST['stock_alert_level'] ?? 10,
        'product_status' => isset($_POST['product_status']) ? 1 : 0,
    ];
    
    if ($isEdit) {
        // Update
        $productData['update_id'] = $_SESSION['admin_id'] ?? 0;
        update_product($productId, $productData);
    } else {
        // Create
        $productData['save_id'] = $_SESSION['admin_id'] ?? 0;
        $productId = create_product($productData);
    }
    
    // Save translations
    $languages = ['th', 'en', 'de'];
    foreach ($languages as $lang) {
        if (!empty($_POST["product_name_{$lang}"])) {
            save_product_translation($productId, $lang, [
                'product_name' => $_POST["product_name_{$lang}"] ?? '',
                'product_excerpt' => $_POST["product_excerpt_{$lang}"] ?? '',
                'product_detail' => $_POST["product_detail_{$lang}"] ?? '',
                'product_unit' => $_POST["product_unit_{$lang}"] ?? '',
                'product_tag' => $_POST["product_tag_{$lang}"] ?? '',
                'seo_title' => $_POST["seo_title_{$lang}"] ?? '',
                'seo_description' => $_POST["seo_description_{$lang}"] ?? ''
            ]);
        }
    }
    
    // Handle attributes (only for new products or if changed)
    if (!$isEdit && !empty($_POST['attribute_groups'])) {
        // Clear existing
        $db->prepare("DELETE FROM product_attribute_sets WHERE product_id = ?")->execute([$productId]);
        
        // Add new
        foreach ($_POST['attribute_groups'] as $groupId) {
            assign_attribute_to_product($productId, $groupId);
        }
        
        // Generate variants if requested
        if (isset($_POST['generate_variants'])) {
            generate_product_variants($productId, $_POST['product_stock'] ?? 0);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $isEdit ? 'Product updated successfully' : 'Product created successfully',
        'product_id' => $productId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
