<?php
/**
 * Delete Blog Permanently
 * Hard delete blog + remove all images (cover + gallery)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $blog_id = isset($data['blog_id']) ? (int)$data['blog_id'] : 0;
    
    if ($blog_id <= 0) {
        throw new Exception('Invalid blog ID');
    }
    
    // Get blog data first
    $stmt = $db->prepare("SELECT blog_picture FROM blog WHERE blog_id = ? AND blog_del = 1");
    $stmt->execute([$blog_id]);
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$blog) {
        throw new Exception('Blog not found in trash');
    }
    
    // Get gallery images
    $stmt = $db->prepare("SELECT gallery_image FROM blog_gallery WHERE blog_id = ?");
    $stmt->execute([$blog_id]);
    $galleryImages = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Delete from database
        $db->prepare("DELETE FROM blog_gallery WHERE blog_id = ?")->execute([$blog_id]);
        $db->prepare("DELETE FROM blog_translations WHERE blog_id = ?")->execute([$blog_id]);
        $db->prepare("DELETE FROM blog WHERE blog_id = ?")->execute([$blog_id]);
        
        $db->commit();
        
        // Delete cover images (3 sizes)
        if (!empty($blog['blog_picture'])) {
            $coverPath = $blog['blog_picture'];
            
            // Extract basename
            if (preg_match('/original-(.+)\.(jpg|jpeg|png|gif)$/i', $coverPath, $matches)) {
                $basename = $matches[1];
                $ext = $matches[2];
                
                $uploadDir = __DIR__ . '/../../uploads/blog/';
                
                // Delete all 3 sizes
                $filesToDelete = [
                    'original-' . $basename . '.' . $ext,
                    'medium-' . $basename . '.webp',
                    'small-' . $basename . '.webp'
                ];
                
                foreach ($filesToDelete as $file) {
                    $filepath = $uploadDir . $file;
                    if (file_exists($filepath)) {
                        @unlink($filepath);
                    }
                }
            }
        }
        
        // Delete gallery images
        foreach ($galleryImages as $galleryPath) {
            // Extract filename from path
            $filename = basename($galleryPath);
            $filepath = __DIR__ . '/../../' . $galleryPath;
            
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Blog deleted permanently'
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
