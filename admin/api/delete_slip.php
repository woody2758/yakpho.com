<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['slip_id']) || !isset($data['orders_id'])) {
        throw new Exception('Missing parameters');
    }
    
    $slip_id = (int)$data['slip_id'];
    $orders_id = (int)$data['orders_id'];
    
    // 1. Get filename before deleting
    $stmt = $db->prepare("SELECT slip_filename FROM order_slips WHERE slip_id = ? AND orders_id = ?");
    $stmt->execute([$slip_id, $orders_id]);
    $slip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$slip) throw new Exception('ไม่พบสลิปที่ต้องการลบ');
    
    $filename = $slip['slip_filename'];
    
    $db->beginTransaction();
    
    // 2. Delete from database
    $stmt = $db->prepare("DELETE FROM order_slips WHERE slip_id = ?");
    $stmt->execute([$slip_id]);
    
    // 3. Delete file if exists
    $filePath = __DIR__ . '/../../uploads/slips/' . $filename;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // 4. Update orders table slip reference if it was the main one (optional but good for consistency)
    $stmt = $db->prepare("UPDATE orders SET orders_slip = (SELECT slip_filename FROM order_slips WHERE orders_id = ? ORDER BY slip_uploaded_at DESC LIMIT 1) WHERE orders_id = ?");
    $stmt->execute([$orders_id, $orders_id]);

    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ลบสลิปเรียบร้อยแล้ว'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
