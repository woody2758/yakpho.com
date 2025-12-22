<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$maincatId = $_POST['maincat_id'] ?? 0;

try {
    if (empty($maincatId)) {
        throw new Exception('ไม่พบ ID');
    }
    
    $db->beginTransaction();
    
    // Check if there are sub-categories under this main category
    $stmt = $db->prepare("SELECT COUNT(*) FROM productcat WHERE maincat_id = ? AND productcat_del = 0");
    $stmt->execute([$maincatId]);
    $subcatCount = $stmt->fetchColumn();
    
    // Soft delete (just mark as deleted, don't actually remove)
    // Sub-categories will have their maincat_id set to NULL via foreign key ON DELETE SET NULL
    $stmt = $db->prepare("UPDATE maincat SET maincat_del = 1, maincat_update = NOW(), update_id = ? WHERE maincat_id = ?");
    $stmt->execute([$_SESSION['admin_id'] ?? 0, $maincatId]);
    
    $db->commit();
    
    $message = 'ลบหมวดหมู่หลักสำเร็จ';
    if ($subcatCount > 0) {
        $message .= " (หมวดย่อย $subcatCount รายการจะถูกตั้งเป็นไม่มีหมวดหลัก)";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
