<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

try {
    $order = $_POST['order'] ?? [];

    if (empty($order)) {
        throw new Exception('No order data received');
    }

    $db->beginTransaction();

    $stmt = $db->prepare("UPDATE product_attributes SET attribute_index = ? WHERE attribute_id = ?");
    
    foreach ($order as $index => $id) {
        $stmt->execute([$index, $id]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'บันทึกลำดับเรียบร้อยแล้ว']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
