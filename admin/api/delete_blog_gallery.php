<?php
/**
 * Delete Blog Gallery Image
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if ($id <= 0) {
        throw new Exception('Invalid gallery ID');
    }
    
    // Get image info
    $stmt = $db->prepare("SELECT blog_id, gallery_image FROM blog_gallery WHERE id = ?");
    $stmt->execute([$id]);
    $gallery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gallery) {
        throw new Exception('Gallery image not found');
    }
    
    // Delete from database
    $stmt = $db->prepare("DELETE FROM blog_gallery WHERE id = ?");
    $stmt->execute([$id]);
    
    // Delete file
    $filePath = __DIR__ . '/../../uploads/blog/gallery/' . $gallery['gallery_image'];
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Image deleted successfully'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
