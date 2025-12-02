<?php
/**
 * Image Processing Helper Functions
 * Handles image resizing, watermarking, and WebP conversion
 */

/**
 * Convert any image to WebP format
 * 
 * @param resource $image GD image resource
 * @param string $outputPath Output file path (should end with .webp)
 * @param int $quality Quality (0-100)
 * @return bool Success status
 */
function save_as_webp($image, $outputPath, $quality = 90) {
    return imagewebp($image, $outputPath, $quality);
}

/**
 * Load image from file (supports JPG, PNG, GIF, WebP)
 * 
 * @param string $filePath Path to image file
 * @return resource|false GD image resource or false on failure
 */
function load_image($filePath) {
    $imageInfo = getimagesize($filePath);
    
    if (!$imageInfo) {
        return false;
    }
    
    $mimeType = $imageInfo['mime'];
    
    switch ($mimeType) {
        case 'image/jpeg':
            return imagecreatefromjpeg($filePath);
        case 'image/png':
            return imagecreatefrompng($filePath);
        case 'image/gif':
            return imagecreatefromgif($filePath);
        case 'image/webp':
            return imagecreatefromwebp($filePath);
        default:
            return false;
    }
}

/**
 * Load image from Base64 string
 * 
 * @param string $base64String Base64 encoded image data
 * @return resource|false GD image resource or false on failure
 */
function load_image_from_base64($base64String) {
    // Remove data URI prefix if present
    if (strpos($base64String, 'data:image') === 0) {
        $base64String = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
    }
    
    $imageData = base64_decode($base64String);
    
    if ($imageData === false) {
        return false;
    }
    
    return imagecreatefromstring($imageData);
}

/**
 * Resize image to specified dimensions (maintains aspect ratio if only one dimension given)
 * 
 * @param resource $sourceImage Source GD image resource
 * @param int $width Target width
 * @param int $height Target height
 * @param bool $crop Whether to crop to exact dimensions
 * @return resource Resized GD image resource
 */
function resize_image($sourceImage, $width, $height = null, $crop = false) {
    $srcWidth = imagesx($sourceImage);
    $srcHeight = imagesy($sourceImage);
    
    // If height not specified, maintain aspect ratio
    if ($height === null) {
        $height = ($width / $srcWidth) * $srcHeight;
    }
    
    if ($crop) {
        // Crop to exact dimensions (center crop)
        $srcAspect = $srcWidth / $srcHeight;
        $dstAspect = $width / $height;
        
        if ($srcAspect > $dstAspect) {
            // Source is wider
            $newSrcWidth = $srcHeight * $dstAspect;
            $newSrcHeight = $srcHeight;
            $srcX = ($srcWidth - $newSrcWidth) / 2;
            $srcY = 0;
        } else {
            // Source is taller
            $newSrcWidth = $srcWidth;
            $newSrcHeight = $srcWidth / $dstAspect;
            $srcX = 0;
            $srcY = ($srcHeight - $newSrcHeight) / 2;
        }
        
        $destImage = imagecreatetruecolor($width, $height);
        
        // Preserve transparency
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        
        imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, $srcX, $srcY,
            $width, $height, $newSrcWidth, $newSrcHeight
        );
    } else {
        // Simple resize
        $destImage = imagecreatetruecolor($width, $height);
        
        // Preserve transparency
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        
        imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, 0, 0,
            $width, $height, $srcWidth, $srcHeight
        );
    }
    
    return $destImage;
}

/**
 * Apply watermark to image
 * 
 * @param resource $baseImage Base GD image resource
 * @param string $watermarkPath Path to watermark image (PNG with transparency)
 * @param string $position Position: 'top-left', 'top-right', 'center', 'bottom-left', 'bottom-right'
 * @param int $opacity Opacity (0-100)
 * @param int $padding Padding from edges in pixels
 * @return resource Image with watermark applied
 */
function apply_watermark($baseImage, $watermarkPath, $position = 'bottom-right', $opacity = 80, $padding = 20) {
    if (!file_exists($watermarkPath)) {
        return $baseImage; // Return original if watermark not found
    }
    
    $watermark = load_image($watermarkPath);
    
    if (!$watermark) {
        return $baseImage;
    }
    
    $baseWidth = imagesx($baseImage);
    $baseHeight = imagesy($baseImage);
    $watermarkWidth = imagesx($watermark);
    $watermarkHeight = imagesy($watermark);
    
    // Calculate position
    switch ($position) {
        case 'top-left':
            $destX = $padding;
            $destY = $padding;
            break;
        case 'top-right':
            $destX = $baseWidth - $watermarkWidth - $padding;
            $destY = $padding;
            break;
        case 'center':
            $destX = ($baseWidth - $watermarkWidth) / 2;
            $destY = ($baseHeight - $watermarkHeight) / 2;
            break;
        case 'bottom-left':
            $destX = $padding;
            $destY = $baseHeight - $watermarkHeight - $padding;
            break;
        case 'bottom-right':
        default:
            $destX = $baseWidth - $watermarkWidth - $padding;
            $destY = $baseHeight - $watermarkHeight - $padding;
            break;
    }
    
    // Apply watermark with opacity
    imagecopymerge(
        $baseImage, $watermark,
        $destX, $destY,
        0, 0,
        $watermarkWidth, $watermarkHeight,
        $opacity
    );
    
    imagedestroy($watermark);
    
    return $baseImage;
}

/**
 * Process and save product image in multiple sizes
 * 
 * @param string $sourceImagePath Path to source image or Base64 string
 * @param string $filename Desired filename (without extension)
 * @param string $uploadDir Upload directory path
 * @param bool $isBase64 Whether source is Base64 encoded
 * @return array|false Array with paths to created files or false on failure
 */
function process_product_image($sourceImagePath, $filename, $uploadDir, $isBase64 = false) {
    global $db;
    
    // Load source image
    if ($isBase64) {
        $sourceImage = load_image_from_base64($sourceImagePath);
    } else {
        $sourceImage = load_image($sourceImagePath);
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $results = [];
    
    // 1. Small version (300x300)
    $smallImage = resize_image($sourceImage, 300, 300, true);
    $smallPath = $uploadDir . '/small-' . $filename . '.webp';
    save_as_webp($smallImage, $smallPath, 85);
    imagedestroy($smallImage);
    $results['small'] = $smallPath;
    
    // 2. Large version (800x800) with watermark
    $largeImage = resize_image($sourceImage, 800, 800, true);
    
    // Get watermark settings
    try {
        $stmt = $db->prepare("SELECT setting_name, setting_value FROM sitesettings WHERE setting_name LIKE 'watermark_%'");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (!empty($settings['watermark_enabled']) && $settings['watermark_enabled'] == '1' && !empty($settings['watermark_image'])) {
            $watermarkPath = __DIR__ . '/../../uploads/watermarks/' . $settings['watermark_image'];
            $largeImage = apply_watermark(
                $largeImage,
                $watermarkPath,
                $settings['watermark_position'] ?? 'bottom-right',
                intval($settings['watermark_opacity'] ?? 80),
                intval($settings['watermark_padding'] ?? 20)
            );
        }
    } catch (PDOException $e) {
        // Continue without watermark if settings can't be loaded
    }
    
    $largePath = $uploadDir . '/large-' . $filename . '.webp';
    save_as_webp($largeImage, $largePath, 90);
    imagedestroy($largeImage);
    $results['large'] = $largePath;
    
    // 3. Original size (converted to WebP)
    $originalPath = $uploadDir . '/original-' . $filename . '.webp';
    save_as_webp($sourceImage, $originalPath, 95);
    $results['original'] = $originalPath;
    
    imagedestroy($sourceImage);
    
    return $results;
}

/**
 * Generate unique filename
 * 
 * @param string $originalName Original filename
 * @return string Unique filename without extension
 */
function generate_unique_filename($originalName = '') {
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    
    if ($originalName) {
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($originalName, PATHINFO_FILENAME));
        return $cleanName . '_' . $timestamp . '_' . $random;
    }
    
    return 'product_' . $timestamp . '_' . $random;
}
