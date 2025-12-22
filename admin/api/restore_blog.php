<?php
/**
 * Restore Blog from Trash
 * Set blog_del = 0 to restore blog
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
    
    // Restore - set blog_del = 0
    $stmt = $db->prepare("
        UPDATE blog 
        SET blog_del = 0, blog_update = NOW() 
        WHERE blog_id = ? AND blog_del = 1
    ");
    $stmt->execute([$blog_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Blog not found in trash');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Blog restored successfully'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
