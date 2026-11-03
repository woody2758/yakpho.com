<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = $input['product_id'] ?? 0;
    $field = $input['field'] ?? '';
    $value = $input['value'] ?? 0;

    // Validate field name to prevent SQL injection
    $allowedFields = ['product_price', 'product_nprice', 'product_cprice', 'product_stock', 'product_status'];
    if (!in_array($field, $allowedFields)) {
        throw new Exception("Invalid field");
    }

    if (!$productId) {
        throw new Exception("Product ID required");
    }

    $stmt = $db->prepare("UPDATE product SET $field = ?, updated_at = NOW() WHERE product_id = ?");
    $stmt->execute([$value, $productId]);

    echo json_encode(['success' => true, 'message' => 'Updated successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
