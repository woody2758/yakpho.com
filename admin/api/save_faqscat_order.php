<?php
/**
 * Save FAQs Category Order
 * Update display order for drag-and-drop sorting
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
        $newIndex = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("UPDATE faqscat SET faqscat_index = ? WHERE faqscat_id = ?");
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
