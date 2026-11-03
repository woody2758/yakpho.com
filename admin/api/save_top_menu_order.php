<?php
/**
 * Save Top Menu Order and Hierarchy API
 * Updates both order and parent-child relationships
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $order = $data['order'] ?? [];

    if (empty($order)) {
        throw new Exception("No order data received");
    }

    $db->beginTransaction();

    // Update both menu_order and parent_id
    $stmt = $db->prepare("UPDATE top_menus SET menu_order = ?, parent_id = ?, update_id = ? WHERE menu_id = ?");
    
    foreach ($order as $item) {
        $stmt->execute([
            $item['order'], 
            $item['parent_id'], 
            $_SESSION['user_id'] ?? 0, 
            $item['id']
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
