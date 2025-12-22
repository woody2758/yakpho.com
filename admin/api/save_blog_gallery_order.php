<?php
/**
 * Save Blog Gallery Order
 * Updates gallery_order for drag-drop reordering
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $blog_id = isset($data['blog_id']) ? (int)$data['blog_id'] : 0;
    $order = isset($data['order']) ? $data['order'] : [];
    
    if ($blog_id <= 0) {
        throw new Exception('Invalid blog ID');
    }
    
    if (empty($order) || !is_array($order)) {
        throw new Exception('Invalid order data');
    }
    
    // Update each gallery image order
    $stmt = $db->prepare("
        UPDATE blog_gallery 
        SET gallery_order = ? 
        WHERE id = ? AND blog_id = ?
    ");
    
    foreach ($order as $item) {
        if (!isset($item['id']) || !isset($item['order'])) continue;
        
        $gallery_id = (int)$item['id'];
        $gallery_order = (int)$item['order'];
        
        $stmt->execute([$gallery_order, $gallery_id, $blog_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Gallery order updated successfully'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
