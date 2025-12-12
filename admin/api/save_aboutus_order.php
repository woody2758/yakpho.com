<?php
/**
 * API: Save About Us Sort Order
 * Updates aboutus_index for drag-drop sorting
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $order = $input['order'] ?? [];
    
    if (empty($order)) {
        throw new Exception('Order array is required');
    }
    
    $db->beginTransaction();
    
    foreach ($order as $index => $id) {
        $newIndex = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("UPDATE aboutus SET aboutus_index = ? WHERE aboutus_id = ?");
        $stmt->execute([$newIndex, $id]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order updated successfully'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
