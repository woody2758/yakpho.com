<?php
/**
 * Upload Blog Cover Image - Optimized 3 Sizes
 * Original: Keep source format (archive/download)
 * Medium & Small: WebP only (display/thumbnail)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

/**
 * Resize and save image
 * Original: Keep source format
 * Small & Medium: WebP only
 */
function resizeAndSaveImage($sourceImage, $basename, $uploadDir, $sourceFormat = 'jpg') {
    $sizes = [
        'small' => ['width' => 300, 'height' => 200, 'quality' => 75],
        'medium' => ['width' => 800, 'height' => 533, 'quality' => 85],
        'original' => ['width' => 1200, 'height' => 800, 'quality' => 90]
    ];
    
    $paths = [];
    $srcWidth = imagesx($sourceImage);
    $srcHeight = imagesy($sourceImage);
    
    foreach ($sizes as $sizeName => $config) {
        // Calculate dimensions maintaining aspect ratio
        $targetWidth = $config['width'];
        $targetHeight = $config['height'];
        
        $srcRatio = $srcWidth / $srcHeight;
        $targetRatio = $targetWidth / $targetHeight;
        
        if ($srcRatio > $targetRatio) {
            $newWidth = $targetWidth;
            $newHeight = (int)($targetWidth / $srcRatio);
        } else {
            $newHeight = $targetHeight;
            $newWidth = (int)($targetHeight * $srcRatio);
        }
        
        // Create resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        
        // Resize
        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $srcWidth, $srcHeight
        );
        
        if ($sizeName === 'original') {
            // Original: Keep source format
            $ext = strtolower($sourceFormat);
            $filename = 'original-' . $basename . '.' . $ext;
            $filepath = $uploadDir . '/' . $filename;
            
            switch ($ext) {
                case 'png':
                    imagepng($resizedImage, $filepath, 9); // Max compression for PNG
                    break;
                case 'gif':
                    imagegif($resizedImage, $filepath);
                    break;
                default: // jpg/jpeg
                    imagejpeg($resizedImage, $filepath, $config['quality']);
                    $ext = 'jpg';
                    $filename = 'original-' . $basename . '.jpg';
            }
            
            $paths[$sizeName] = [
                'path' => 'uploads/blog/' . $filename,
                'size' => filesize($filepath),
                'format' => $ext
            ];
        } else {
            // Small & Medium: WebP only
            $filename = $sizeName . '-' . $basename . '.webp';
            $filepath = $uploadDir . '/' . $filename;
            
            if (!imagewebp($resizedImage, $filepath, $config['quality'])) {
                imagedestroy($resizedImage);
                throw new Exception("Cannot save $sizeName image as WebP");
            }
            
            $paths[$sizeName] = [
                'path' => 'uploads/blog/' . $filename,
                'size' => filesize($filepath),
                'format' => 'webp'
            ];
        }
        
        imagedestroy($resizedImage);
    }
    
    return $paths;
}

try {
    $uploadDir = __DIR__ . '/../../uploads/blog';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $blog_id = 0;
    $sourceImage = null;
    $sourceFormat = 'jpg'; // Default
    
    // Check if it's Base64 data (from cropper)
    $input = file_get_contents('php://input');
    if ($input) {
        $data = json_decode($input, true);
        if (isset($data['base64_image'])) {
            $blog_id = $data['blog_id'] ?? 0;
            $base64Data = $data['base64_image'];
            
            // Detect format from Base64
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $sourceFormat = strtolower($type[1]);
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            }
            
            $imageData = base64_decode($base64Data);
            if ($imageData === false) {
                throw new Exception('Invalid Base64 data');
            }
            
            $sourceImage = imagecreatefromstring($imageData);
            if (!$sourceImage) {
                throw new Exception('Cannot create image from Base64 data');
            }
        }
    }
    
    // If not Base64, check for file upload
    if (!$sourceImage) {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }
        
        $blog_id = $_POST['blog_id'] ?? 0;
        
        $imageInfo = getimagesize($_FILES['image']['tmp_name']);
        if (!$imageInfo) {
            throw new Exception('Invalid image file');
        }
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                $sourceFormat = 'jpg';
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($_FILES['image']['tmp_name']);
                $sourceFormat = 'png';
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($_FILES['image']['tmp_name']);
                $sourceFormat = 'gif';
                break;
            default:
                throw new Exception('Unsupported image format');
        }
        
        if (!$sourceImage) {
            throw new Exception('Cannot load image file');
        }
    }
    
    if ($blog_id < 0) {
        if ($sourceImage) imagedestroy($sourceImage);
        throw new Exception('Invalid blog ID');
    }
    
    // Get old cover to delete
    $oldCover = null;
    if ($blog_id > 0) {
        $stmt = $db->prepare("SELECT blog_picture FROM blog WHERE blog_id = ? AND blog_del = 0");
        $stmt->execute([$blog_id]);
        $oldCover = $stmt->fetchColumn();
    }
    
    // Generate basename
    if ($blog_id > 0) {
        $basename = 'blog-' . $blog_id . '-' . time();
    } else {
        $basename = 'blog-temp-' . time() . '-' . mt_rand(1000, 9999);
    }
    
    // Generate 3 sizes
    $paths = resizeAndSaveImage($sourceImage, $basename, $uploadDir, $sourceFormat);
    imagedestroy($sourceImage);
    
    // Use original as main image URL
    $imageUrl = $paths['original']['path'];
    
    // Update database
    if ($blog_id > 0) {
        $check = $db->prepare("SELECT blog_id FROM blog WHERE blog_id = ? AND blog_del = 0");
        $check->execute([$blog_id]);
        if ($check->fetchColumn()) {
            $stmt = $db->prepare("UPDATE blog SET blog_picture = ?, blog_update = NOW() WHERE blog_id = ?");
            $stmt->execute([$imageUrl, $blog_id]);
        }
    }
    
    // Delete old images
    if ($oldCover && $oldCover != $imageUrl) {
        $oldBasename = preg_replace('/^uploads\/blog\/(original|medium|small)-/', '', $oldCover);
        $oldBasename = preg_replace('/\.(jpg|png|gif|webp)$/', '', $oldBasename);
        
        // Delete all variants
        $patterns = [
            'original-' . $oldBasename . '.jpg',
            'original-' . $oldBasename . '.png',
            'original-' . $oldBasename . '.gif',
            'medium-' . $oldBasename . '.webp',
            'small-' . $oldBasename . '.webp'
        ];
        
        foreach ($patterns as $pattern) {
            $oldFile = $uploadDir . '/' . $pattern;
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'image_url' => $imageUrl,
        'paths' => $paths,
        'full_path' => ROOT_URL . '/' . $imageUrl,
        'basename' => $basename
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
