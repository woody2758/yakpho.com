<?php
/**
 * Upload Blog Cover Image
 * Handle cover image upload with WebP conversion
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions/image.php';

try {
    if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }
    
    $blog_id = $_POST['blog_id'] ?? 0;
    if ($blog_id <= 0) {
        throw new Exception('Invalid blog ID');
    }
    
    $uploadDir = __DIR__ . '/../../uploads/blog/cover';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Get old cover to delete later
    $stmt = $db->prepare("SELECT blog_picture FROM blog WHERE blog_id = ? AND blog_del = 0");
    $stmt->execute([$blog_id]);
    $oldCover = $stmt->fetchColumn();
    
    // Generate filename
    $filename = 'blog_' . $blog_id . '_' . time() . '.webp';
    $targetPath = $uploadDir . '/' . $filename;
    
    // Load and convert to WebP
    $sourceImage = load_image($_FILES['cover_image']['tmp_name']);
    if (!$sourceImage) {
        throw new Exception('ไม่สามารถอ่านไฟล์รูปภาพได้');
    }
    
    // Save as WebP
    if (!imagewebp($sourceImage, $targetPath, 85)) {
        throw new Exception('ไม่สามารถบันทึกรูปภาพได้');
    }
    
    imagedestroy($sourceImage);
    
    // Update database
    $stmt = $db->prepare("UPDATE blog SET blog_picture = ?, blog_update = NOW() WHERE blog_id = ?");
    $stmt->execute([$filename, $blog_id]);
    
    // Delete old cover
    if ($oldCover && file_exists($uploadDir . '/' . $oldCover)) {
        @unlink($uploadDir . '/' . $oldCover);
    }
    
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'url' => '/uploads/blog/cover/' . $filename
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
