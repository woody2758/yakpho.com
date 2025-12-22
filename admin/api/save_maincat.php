<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$maincatId = $_POST['maincat_id'] ?? 0;
$isEdit = !empty($maincatId);
$quickUpdate = isset($_POST['quick_update']); // For status toggle only

try {
    $db->beginTransaction();
    
    if ($quickUpdate) {
        // Quick status update only
        $status = $_POST['maincat_status'] ?? 0;
        $stmt = $db->prepare("UPDATE maincat SET maincat_status = ?, maincat_update = NOW(), update_id = ? WHERE maincat_id = ?");
        $stmt->execute([$status, $_SESSION['admin_id'] ?? 0, $maincatId]);
        
        $db->commit();
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Validate required fields
    if (empty($_POST['maincat_name_th'])) {
        throw new Exception('กรุณากรอกชื่อหมวดหมู่หลัก (ภาษาไทย)');
    }
    
    if (empty($_POST['maincat_slug'])) {
        throw new Exception('กรุณากรอก URL Slug');
    }
    
    // Validate slug format (lowercase alphanumeric and hyphens only)
    if (!preg_match('/^[a-z0-9-]+$/', $_POST['maincat_slug'])) {
        throw new Exception('URL Slug ต้องเป็นตัวอักษรภาษาอังกฤษตัวเล็ก ตัวเลข และขีด (-) เท่านั้น');
    }
    
    // Check duplicate slug (excluding current ID if editing)
    $checkSql = "SELECT maincat_id FROM maincat WHERE maincat_slug = ? AND maincat_del = 0";
    $checkParams = [$_POST['maincat_slug']];
    if ($isEdit) {
        $checkSql .= " AND maincat_id != ?";
        $checkParams[] = $maincatId;
    }
    $stmt = $db->prepare($checkSql);
    $stmt->execute($checkParams);
    if ($stmt->rowCount() > 0) {
        throw new Exception('URL Slug นี้ถูกใช้ไปแล้ว');
    }
    
    if ($isEdit) {
        // Update main category
        $stmt = $db->prepare("UPDATE maincat SET 
                              maincat_slug = ?,
                              maincat_icon = ?,
                              maincat_status = ?,
                              maincat_update = NOW(),
                              update_id = ?
                              WHERE maincat_id = ?");
        $stmt->execute([
            $_POST['maincat_slug'],
            $_POST['maincat_icon'] ?? null,
            isset($_POST['maincat_status']) ? 1 : 0,
            $_SESSION['admin_id'] ?? 0,
            $maincatId
        ]);
        
        // Delete old translations
        $stmt = $db->prepare("DELETE FROM maincat_translations WHERE maincat_id = ?");
        $stmt->execute([$maincatId]);
        
    } else {
        // Insert new main category
        $stmt = $db->prepare("INSERT INTO maincat (maincat_slug, maincat_icon, maincat_status, maincat_date, maincat_del, save_id) 
                              VALUES (?, ?, ?, NOW(), 0, ?)");
        $stmt->execute([
            $_POST['maincat_slug'],
            $_POST['maincat_icon'] ?? null,
            isset($_POST['maincat_status']) ? 1 : 0,
            $_SESSION['admin_id'] ?? 0
        ]);
        $maincatId = $db->lastInsertId();
    }
    
    // Insert translations for all languages
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar', 'he'];
    $stmt = $db->prepare("INSERT INTO maincat_translations (maincat_id, lang_code, maincat_name, maincat_detail) 
                          VALUES (?, ?, ?, ?)");
    
    foreach ($languages as $lang) {
        $name = $_POST["maincat_name_$lang"] ?? '';
        $detail = $_POST["maincat_detail_$lang"] ?? '';
        
        // Only insert if name is not empty
        if (!empty($name)) {
            $stmt->execute([$maincatId, $lang, $name, $detail]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $isEdit ? 'บันทึกการแก้ไขเรียบร้อย' : 'เพิ่มหมวดหมู่หลักเรียบร้อย',
        'maincat_id' => $maincatId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
