<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$categoryId = $input['productcat_id'] ?? 0;

if (empty($categoryId)) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบ ID หมวดสินค้า'
    ]);
    exit;
}

try {
    // Check if category has products
    $stmt = $db->prepare("SELECT COUNT(*) FROM product WHERE productcat_id = ? AND product_del = 0");
    $stmt->execute([$categoryId]);
    $productCount = $stmt->fetchColumn();
    
    if ($productCount > 0) {
        echo json_encode([
            'success' => false,
            'message' => "ไม่สามารถลบได้ เนื่องจากมีสินค้าในหมวดนี้ $productCount รายการ"
        ]);
        exit;
    }
    
    // Soft delete
    $stmt = $db->prepare("UPDATE productcat SET productcat_del = 1, productcat_update = NOW(), update_id = ? WHERE productcat_id = ?");
    $stmt->execute([$_SESSION['admin_id'] ?? 0, $categoryId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'ลบหมวดสินค้าเรียบร้อยแล้ว'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
