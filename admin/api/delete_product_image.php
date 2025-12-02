<?php
/**
 * Delete Product Image API
 * Removes an image from the product gallery
 */

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    $imageId = $_POST['image_id'] ?? 0;
    
    if (!$imageId) {
        echo json_encode(['success' => false, 'message' => 'Image ID required']);
        exit;
    }
    
    // Get image filename before deleting
    $stmt = $db->prepare("SELECT image_filename FROM product_images WHERE image_id = ?");
    $stmt->execute([$imageId]);
    $filename = $stmt->fetchColumn();
    
    if ($filename) {
        // Delete from database
        $stmt = $db->prepare("DELETE FROM product_images WHERE image_id = ?");
        $stmt->execute([$imageId]);
        
        // Delete physical file
        $filePath = __DIR__ . '/../../uploads/products/gallery/' . $filename;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Image deleted successfully'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Image not found'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
