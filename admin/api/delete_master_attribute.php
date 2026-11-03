<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

try {
    $attributeId = $_GET['id'] ?? 0;

    if (!$attributeId) {
        throw new Exception('Attribute ID is required');
    }

    // Check if being used (Optional but recommended)
    $stmt = $db->prepare("SELECT COUNT(*) FROM product WHERE scent_id = ? OR formula_id = ?");
    $stmt->execute([$attributeId, $attributeId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('ไม่สามารถลบได้เนื่องจากมีสินค้าใช้งานข้อมูลนี้อยู่');
    }

    // Soft delete by setting status to 0
    $stmt = $db->prepare("UPDATE product_attributes SET attribute_status = 0 WHERE attribute_id = ?");
    $stmt->execute([$attributeId]);

    echo json_encode(['success' => true, 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
