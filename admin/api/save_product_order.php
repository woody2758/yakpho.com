<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $order = $input['order'] ?? [];
    
    if (empty($order) || !is_array($order)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid order data'
        ]);
        exit;
    }
    
    // Update order for each product
    $stmt = $db->prepare("UPDATE product SET product_index = ? WHERE product_id = ?");
    
    foreach ($order as $index => $productId) {
        $stmt->execute([$index + 1, $productId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'บันทึกลำดับเรียบร้อยแล้ว',
        'count' => count($order)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
