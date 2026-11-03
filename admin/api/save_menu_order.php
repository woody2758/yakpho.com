<?php
/**
 * Save Menu Sort Order
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $itemIds = $input['itemIds'] ?? [];
    
    if (empty($itemIds)) {
        throw new Exception("No items to sort");
    }
    
    $db->beginTransaction();
    
    foreach ($itemIds as $index => $id) {
        $order = $index + 1;
        $stmt = $db->prepare("UPDATE footer_menus SET menu_order = ? WHERE menu_id = ?");
        $stmt->execute([$order, $id]);
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'บันทึกลำดับเรียบร้อย']);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
