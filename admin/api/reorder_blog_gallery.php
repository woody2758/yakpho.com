<?php
/**
 * Reorder Blog Gallery Images
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $order = $data['order'] ?? [];
    
    if (empty($order)) {
        throw new Exception('No order data provided');
    }
    
    $db->beginTransaction();
    
    foreach ($order as $index => $id) {
        $stmt = $db->prepare("UPDATE blog_gallery SET gallery_order = ? WHERE id = ?");
        $stmt->execute([$index + 1, $id]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Gallery reordered successfully'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
