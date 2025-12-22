<?php
/**
 * Upload Blog Gallery Images
 * Supports both single image ('image') and multiple images ('images[]') upload
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $blog_id = $_POST['blog_id'] ?? 0;
    if ($blog_id <= 0) {
        throw new Exception('Invalid blog ID');
    }
    
    $uploadDir = __DIR__ . '/../../uploads/blog/gallery';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Get current max order
    $stmt = $db->prepare("SELECT COALESCE(MAX(gallery_order), 0) FROM blog_gallery WHERE blog_id = ?");
    $stmt->execute([$blog_id]);
    $maxOrder = $stmt->fetchColumn();
    
    $uploaded = [];
    
    // Check if single image ('image') or multiple ('images[]')
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Single image upload (auto-upload mode)
        $file = $_FILES['image'];
        $result = uploadSingleGalleryImage($file, $blog_id, $maxOrder + 1, $uploadDir, $db);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'gallery_id' => $result['id'],
                'image_url' => ROOT_URL . '/uploads/blog/gallery/' . $result['filename'],
                'filename' => $result['filename']
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Failed to upload image');
        }
        
    } elseif (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        // Multiple images upload (legacy mode)
        $fileCount = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];
                
                $result = uploadSingleGalleryImage($file, $blog_id, $maxOrder + $i + 1, $uploadDir, $db);
                if ($result) {
                    $uploaded[] = $result;
                }
            }
        }
        
        // Get all gallery images for this blog
        $stmt = $db->prepare("
            SELECT id, gallery_image, gallery_order 
            FROM blog_gallery 
            WHERE blog_id = ? 
            ORDER BY gallery_order ASC
        ");
        $stmt->execute([$blog_id]);
        $gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'uploaded' => $uploaded,
            'count' => count($uploaded),
            'gallery' => $gallery
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('No files uploaded');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Upload single gallery image
 */
function uploadSingleGalleryImage($file, $blog_id, $order, $uploadDir, $db) {
    // Validate file type
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return null;
    }
    
    // Load source image
    switch ($imageInfo[2]) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($file['tmp_name']);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($file['tmp_name']);
            break;
        default:
            return null;
    }
    
    if (!$sourceImage) return null;
    
    // Calculate resize dimensions (max 1200px)
    $srcWidth = imagesx($sourceImage);
    $srcHeight = imagesy($sourceImage);
    $maxSize = 1200;
    
    if ($srcWidth > $maxSize || $srcHeight > $maxSize) {
        $ratio = min($maxSize / $srcWidth, $maxSize / $srcHeight);
        $newWidth = (int)($srcWidth * $ratio);
        $newHeight = (int)($srcHeight * $ratio);
    } else {
        $newWidth = $srcWidth;
        $newHeight = $srcHeight;
    }
    
    // Create resized image
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($resizedImage, false);
    imagesavealpha($resizedImage, true);
    
    imagecopyresampled(
        $resizedImage, $sourceImage,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $srcWidth, $srcHeight
    );
    
    // Generate filename
    $filename = 'blog-' . $blog_id . '-gallery-' . time() . '-' . mt_rand(1000, 9999) . '.webp';
    $targetPath = $uploadDir . '/' . $filename;
    
    // Save as WebP
    $success = imagewebp($resizedImage, $targetPath, 85);
    
    imagedestroy($resizedImage);
    imagedestroy($sourceImage);
    
    if (!$success) return null;
    
    // Insert into database
    $stmt = $db->prepare("
        INSERT INTO blog_gallery (blog_id, gallery_image, gallery_order)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$blog_id, 'uploads/blog/gallery/' . $filename, $order]);
    
    return [
        'id' => $db->lastInsertId(),
        'filename' => $filename
    ];
}
