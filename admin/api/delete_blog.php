<?php
/**
 * Delete Blog Post
 * Soft delete blog post
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['blog_id'] ?? 0;
    
    if ($id <= 0) {
        throw new Exception('Invalid blog ID');
    }
    
    // Soft delete
    $stmt = $db->prepare("
        UPDATE blog SET 
            blog_del = 1,
            blog_update = NOW(),
            update_id = ?
        WHERE blog_id = ? AND blog_del = 0
    ");
    $stmt->execute([$_SESSION['admin_id'], $id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Blog post not found or already deleted');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Blog post deleted successfully'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
