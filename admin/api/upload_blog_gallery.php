<?php
/**
 * Upload Blog Gallery Images
 * Handle multiple gallery image uploads
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions/image.php';

try {
    $blog_id = $_POST['blog_id'] ?? 0;
    if ($blog_id <= 0) {
        throw new Exception('Invalid blog ID');
    }
    
    if (empty($_FILES['gallery_images'])) {
        throw new Exception('No files uploaded');
    }
    
    $uploadDir = __DIR__ . '/../../uploads/blog/gallery';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploaded = [];
    $fileCount = count($_FILES['gallery_images']['name']);
    
    // Get current max order
    $stmt = $db->prepare("SELECT COALESCE(MAX(gallery_order), 0) FROM blog_gallery WHERE blog_id = ?");
    $stmt->execute([$blog_id]);
    $maxOrder = $stmt->fetchColumn();
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['gallery_images']['tmp_name'][$i];
            
            // Generate filename
            $filename = 'blog_' . $blog_id . '_gallery_' . time() . '_' . $i . '.webp';
            $targetPath = $uploadDir . '/' . $filename;
            
            // Load image
            $sourceImage = load_image($tmpPath);
            if ($sourceImage) {
                // Resize if needed (max 1200px width)
                $resizedImage = resize_image($sourceImage, 1200, 1200, true);
                
                // Save as WebP
                if (imagewebp($resizedImage, $targetPath, 85)) {
                    // Insert into database
                    $stmt = $db->prepare("
                        INSERT INTO blog_gallery (blog_id, gallery_image, gallery_order)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$blog_id, $filename, $maxOrder + $i + 1]);
                    
                    $uploaded[] = [
                        'id' => $db->lastInsertId(),
                        'filename' => $filename,
                        'url' => '/uploads/blog/gallery/' . $filename
                    ];
                }
                
                imagedestroy($resizedImage);
                imagedestroy($sourceImage);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'uploaded' => $uploaded,
        'count' => count($uploaded)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
