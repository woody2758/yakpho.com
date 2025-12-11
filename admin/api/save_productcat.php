<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$categoryId = $_POST['productcat_id'] ?? 0;
$isEdit = !empty($categoryId);
$quickUpdate = isset($_POST['quick_update']); // For status toggle only

try {
    $db->beginTransaction();
    
    if ($quickUpdate) {
        // Quick status update only
        $status = $_POST['productcat_status'] ?? 0;
        $stmt = $db->prepare("UPDATE productcat SET productcat_status = ?, productcat_update = NOW(), update_id = ? WHERE productcat_id = ?");
        $stmt->execute([$status, $_SESSION['admin_id'] ?? 0, $categoryId]);
        
        $db->commit();
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Validate required field (Thai name)
    if (empty($_POST['productcat_name_th'])) {
        throw new Exception('กรุณากรอกชื่อหมวดสินค้า (ภาษาไทย)');
    }
    
    if ($isEdit) {
        // Update category
        $stmt = $db->prepare("UPDATE productcat SET 
                              productcat_status = ?,
                              productcat_update = NOW(),
                              update_id = ?
                              WHERE productcat_id = ?");
        $stmt->execute([
            isset($_POST['productcat_status']) ? 1 : 0,
            $_SESSION['admin_id'] ?? 0,
            $categoryId
        ]);
        
        // Delete old translations
        $stmt = $db->prepare("DELETE FROM productcat_translations WHERE productcat_id = ?");
        $stmt->execute([$categoryId]);
        
    } else {
        // Insert new category
        $stmt = $db->prepare("INSERT INTO productcat (productcat_status, productcat_date, productcat_del, save_id) 
                              VALUES (?, NOW(), 0, ?)");
        $stmt->execute([
            isset($_POST['productcat_status']) ? 1 : 0,
            $_SESSION['admin_id'] ?? 0
        ]);
        $categoryId = $db->lastInsertId();
    }
    
    // Insert translations for all languages
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko'];
    $stmt = $db->prepare("INSERT INTO productcat_translations (productcat_id, lang_code, productcat_name, productcat_detail) 
                          VALUES (?, ?, ?, ?)");
    
    foreach ($languages as $lang) {
        $name = $_POST["productcat_name_$lang"] ?? '';
        $detail = $_POST["productcat_detail_$lang"] ?? '';
        
        // Only insert if name is not empty
        if (!empty($name)) {
            $stmt->execute([$categoryId, $lang, $name, $detail]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $isEdit ? 'บันทึกการแก้ไขเรียบร้อย' : 'เพิ่มหมวดสินค้าเรียบร้อย',
        'category_id' => $categoryId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
