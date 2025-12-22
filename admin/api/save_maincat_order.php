<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['order']) || !is_array($data['order'])) {
        throw new Exception('ไม่พบข้อมูลลำดับ');
    }
    
    $db->beginTransaction();
    
    $stmt = $db->prepare("UPDATE maincat SET maincat_index = ? WHERE maincat_id = ?");
    
    foreach ($data['order'] as $index => $id) {
        $stmt->execute([$index + 1, $id]);
    }
    
    $db->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
