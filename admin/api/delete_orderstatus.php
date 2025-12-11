<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check permission (Admin only)
require_role('admin');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$statusId = $input['orsts_id'] ?? 0;

if (empty($statusId)) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบ ID สถานะ'
    ]);
    exit;
}

try {
    // Check if status is used in orders (if you have an orders table, add check here)
    // For now, we assume soft delete is safe
    
    // Soft delete
    $stmt = $db->prepare("UPDATE orsts SET orsts_del = 1, orsts_update = NOW(), update_id = ? WHERE orsts_id = ?");
    $stmt->execute([$_SESSION['admin_id'] ?? 0, $statusId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'ลบสถานะเรียบร้อยแล้ว'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
