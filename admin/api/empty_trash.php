<?php
/**
 * Empty Trash - Delete All Trashed Blogs
 * Permanently delete all blogs where blog_del = 1
 * Remove all associated images
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    // Get all trashed blogs
    $stmt = $db->prepare("
        SELECT blog_id, blog_picture 
        FROM blog 
        WHERE blog_del = 1
    ");
    $stmt->execute();
    $trashedBlogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($trashedBlogs)) {
        echo json_encode([
            'success' => true,
            'message' => 'Trash is already empty',
            'deleted_count' => 0
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $deletedCount = 0;
    $uploadDir = __DIR__ . '/../../uploads/blog/';
    $galleryDir = __DIR__ . '/../../uploads/blog/gallery/';
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        foreach ($trashedBlogs as $blog) {
            $blog_id = $blog['blog_id'];
            
            // Get gallery images for this blog
            $galleryStmt = $db->prepare("SELECT gallery_image FROM blog_gallery WHERE blog_id = ?");
            $galleryStmt->execute([$blog_id]);
            $galleryImages = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Delete from database
            $db->prepare("DELETE FROM blog_gallery WHERE blog_id = ?")->execute([$blog_id]);
            $db->prepare("DELETE FROM blog_translations WHERE blog_id = ?")->execute([$blog_id]);
            $db->prepare("DELETE FROM blog WHERE blog_id = ?")->execute([$blog_id]);
            
            // Delete cover images (3 sizes)
            if (!empty($blog['blog_picture'])) {
                $coverPath = $blog['blog_picture'];
                
                if (preg_match('/original-(.+)\.(jpg|jpeg|png|gif)$/i', $coverPath, $matches)) {
                    $basename = $matches[1];
                    $ext = $matches[2];
                    
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
                $filepath = __DIR__ . '/../../' . $galleryPath;
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
            }
            
            $deletedCount++;
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Deleted $deletedCount blog(s) permanently",
            'deleted_count' => $deletedCount
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
