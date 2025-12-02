<?php
// Prevent any output before JSON
ob_start();

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/functions/product.php';
    require_once __DIR__ . '/../../includes/functions/attribute.php';
    require_once __DIR__ . '/../../includes/functions/image.php';
    
    // Clear any previous output
    ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    $productId = $_POST['product_id'] ?? 0;
    $isEdit = !empty($productId);
    
    // Handle product code - convert TH to YP if needed
    $productCode = $_POST['product_code'] ?? '';
    if (strpos($productCode, 'TH') === 0) {
        // If editing, use product_id, otherwise will be set after creation
        if ($isEdit) {
            $productCode = 'YP' . $productId;
        }
    }
    
    // Prepare product data
    $productData = [
        'product_code' => $productCode,
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
        // Update (has its own transaction)
        $productData['update_id'] = $_SESSION['admin_id'] ?? 0;
        update_product($productId, $productData);
    } else {
        // Create (has its own transaction)
        $productData['save_id'] = $_SESSION['admin_id'] ?? 0;
        $productId = create_product($productData);
        
        // Ensure product was created successfully
        if (!$productId) {
            throw new Exception('ไม่สามารถสร้างสินค้าได้');
        }
    }
    
    // Handle main product image (from cropper - Base64)
    if (!empty($_POST['product_image_base64'])) {
        $uploadDir = __DIR__ . '/../../uploads/products';
        
        // Get old filename before updating (for deletion later)
        $oldProductPicture = null;
        if ($isEdit && !empty($_POST['old_product_picture'])) {
            $oldProductPicture = $_POST['old_product_picture'];
        }
        
        // Generate new unique filename
        $filename = generate_unique_filename('product_' . $productId);
        
        // Process and save new image
        $imagePaths = process_product_image($_POST['product_image_base64'], $filename, $uploadDir, true);
        
        if ($imagePaths) {
            // Update database with new image filename (just the base name with .webp)
            $newImageFilename = $filename . '.webp';
            $stmt = $db->prepare("UPDATE product SET product_picture = ? WHERE product_id = ?");
            $stmt->execute([$newImageFilename, $productId]);
            
            // Now delete old images (after successful update)
            if ($oldProductPicture) {
                $oldFilename = pathinfo($oldProductPicture, PATHINFO_FILENAME);
                @unlink($uploadDir . '/small-' . $oldFilename . '.webp');
                @unlink($uploadDir . '/large-' . $oldFilename . '.webp');
                @unlink($uploadDir . '/original-' . $oldFilename . '.webp');
            }
        }
    }
    
    // Handle gallery images (multiple file uploads)
    if (!empty($_FILES['gallery_images']['name'][0])) {
        $uploadDir = __DIR__ . '/../../uploads/products/gallery';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileCount = count($_FILES['gallery_images']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['gallery_images']['tmp_name'][$i];
                $originalName = $_FILES['gallery_images']['name'][$i];
                
                $filename = generate_unique_filename($originalName);
                
                // Process gallery image (resize to 800x800, convert to WebP)
                $sourceImage = load_image($tmpPath);
                
                if ($sourceImage) {
                    $resizedImage = resize_image($sourceImage, 800, 800, true);
                    $outputPath = $uploadDir . '/' . $filename . '.webp';
                    save_as_webp($resizedImage, $outputPath, 90);
                    
                    imagedestroy($sourceImage);
                    imagedestroy($resizedImage);
                    
                    // Get current max order
                    $stmt = $db->prepare("SELECT COALESCE(MAX(image_order), 0) FROM product_images WHERE product_id = ?");
                    $stmt->execute([$productId]);
                    $maxOrder = $stmt->fetchColumn();
                    
                    // Insert into product_images table
                    $stmt = $db->prepare("INSERT INTO product_images (product_id, image_filename, image_order) VALUES (?, ?, ?)");
                    $stmt->execute([$productId, $filename . '.webp', $maxOrder + 1]);
                }
            }
        }
    }
    
    // Save translations - get active languages from database
    $stmt = $db->query("SELECT lang_code FROM languages WHERE lang_status = 1");
    $languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
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
            if (function_exists('assign_attribute_to_product')) {
                assign_attribute_to_product($productId, $groupId);
            }
        }
        
        // Generate variants if requested
        if (isset($_POST['generate_variants']) && function_exists('generate_product_variants')) {
            generate_product_variants($productId, $_POST['product_stock'] ?? 0);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => $isEdit ? 'บันทึกข้อมูลเรียบร้อยแล้ว' : 'เพิ่มสินค้าเรียบร้อยแล้ว',
        'product_id' => $productId
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    // Clear any output
    ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    // Catch fatal errors
    ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
