<?php
/**
 * Delete Blog Category
 * Soft delete blog category
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['blogcat_id'] ?? 0;
    
    if ($id <= 0) {
        throw new Exception('Invalid category ID');
    }
    
    // Soft delete
    $stmt = $db->prepare("
        UPDATE blogcat SET 
            blogcat_del = 1,
            blogcat_update = NOW(),
            update_id = ?
        WHERE blogcat_id = ? AND blogcat_del = 0
    ");
    $stmt->execute([$_SESSION['admin_id'], $id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Category not found or already deleted');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Blog category deleted successfully'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
