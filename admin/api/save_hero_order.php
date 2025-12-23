<?php
/**
 * Save Hero Slides Order
 * Update slide_order after drag-and-drop
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $order = $input['order'] ?? [];
    
    if (empty($order)) {
        throw new Exception('Order data required');
    }
    
    $db->beginTransaction();
    
    $stmt = $db->prepare("UPDATE hero_slides SET slide_order = ? WHERE slide_id = ?");
    
    foreach ($order as $item) {
        $stmt->execute([$item['order'], $item['id']]);
    }
    
    $db->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
