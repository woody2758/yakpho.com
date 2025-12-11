<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$statusId = $_POST['orsts_id'] ?? 0;
$isEdit = !empty($statusId);
$quickUpdate = isset($_POST['quick_update']); // For status toggle only

try {
    $db->beginTransaction();
    
    if ($quickUpdate) {
        // Quick status update only
        $status = $_POST['orsts_status'] ?? 0;
        $stmt = $db->prepare("UPDATE orsts SET orsts_status = ?, orsts_update = NOW(), update_id = ? WHERE orsts_id = ?");
        $stmt->execute([$status, $_SESSION['admin_id'] ?? 0, $statusId]);
        
        $db->commit();
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Validate required fields
    if (empty($_POST['orsts_name_th'])) {
        throw new Exception('กรุณากรอกชื่อสถานะ (ภาษาไทย)');
    }
    if (empty($_POST['orsts_code'])) {
        throw new Exception('กรุณากรอกรหัสสถานะ (Code)');
    }
    
    // Prepare common data
    $code = strtoupper(trim($_POST['orsts_code']));
    $color = $_POST['orsts_color'] ?? '#000000';
    $index = (int)($_POST['orsts_index'] ?? 0);
    $userVisible = isset($_POST['orsts_user']) ? 1 : 0;
    $status = isset($_POST['orsts_status']) ? 1 : 0;
    
    // Check duplicate code
    $checkSql = "SELECT orsts_id FROM orsts WHERE orsts_code = ? AND orsts_del = 0";
    $checkParams = [$code];
    if ($isEdit) {
        $checkSql .= " AND orsts_id != ?";
        $checkParams[] = $statusId;
    }
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute($checkParams);
    if ($checkStmt->fetch()) {
        throw new Exception("รหัสสถานะ '$code' มีอยู่แล้วในระบบ");
    }
    
    if ($isEdit) {
        // Update status
        $stmt = $db->prepare("UPDATE orsts SET 
                              orsts_code = ?,
                              orsts_color = ?,
                              orsts_index = ?,
                              orsts_user = ?,
                              orsts_status = ?,
                              orsts_update = NOW(),
                              update_id = ?
                              WHERE orsts_id = ?");
        $stmt->execute([
            $code, $color, $index, $userVisible, $status,
            $_SESSION['admin_id'] ?? 0,
            $statusId
        ]);
        
        // Delete old translations
        $stmt = $db->prepare("DELETE FROM orsts_translations WHERE orsts_id = ?");
        $stmt->execute([$statusId]);
        
    } else {
        // Insert new status
        $stmt = $db->prepare("INSERT INTO orsts (orsts_code, orsts_color, orsts_index, orsts_user, orsts_status, orsts_date, orsts_del, save_id) 
                              VALUES (?, ?, ?, ?, ?, NOW(), 0, ?)");
        $stmt->execute([
            $code, $color, $index, $userVisible, $status,
            $_SESSION['admin_id'] ?? 0
        ]);
        $statusId = $db->lastInsertId();
    }
    
    // Insert translations for all languages
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko'];
    $stmt = $db->prepare("INSERT INTO orsts_translations (orsts_id, lang_code, orsts_name, orsts_msg) 
                          VALUES (?, ?, ?, ?)");
    
    foreach ($languages as $lang) {
        $name = $_POST["orsts_name_$lang"] ?? '';
        $msg = $_POST["orsts_msg_$lang"] ?? '';
        
        // Only insert if name is not empty
        if (!empty($name)) {
            $stmt->execute([$statusId, $lang, $name, $msg]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $isEdit ? 'บันทึกการแก้ไขเรียบร้อย' : 'เพิ่มสถานะเรียบร้อย',
        'orsts_id' => $statusId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
