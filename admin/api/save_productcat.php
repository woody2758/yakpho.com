<?php
require_once __DIR__ . '/../includes/config.php';

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
                              maincat_id = ?,
                              productcat_status = ?,
                              is_production = ?,
                              prod_group_name = ?,
                              prod_size = ?,
                              prod_formula = ?,
                              productcat_update = NOW(),
                              update_id = ?
                              WHERE productcat_id = ?");
        $stmt->execute([
            $_POST['maincat_id'] ?? null,
            isset($_POST['productcat_status']) ? 1 : 0,
            isset($_POST['is_production']) ? 1 : 0,
            $_POST['prod_group_name'] ?? null,
            $_POST['prod_size'] ?? null,
            $_POST['prod_formula'] ?? null,
            $_SESSION['admin_id'] ?? 0,
            $categoryId
        ]);
        
        // Delete old translations
        $stmt = $db->prepare("DELETE FROM productcat_translations WHERE productcat_id = ?");
        $stmt->execute([$categoryId]);
        
    } else {
        // Get max index for new category
        $maxIndex = $db->query("SELECT MAX(productcat_index) FROM productcat")->fetchColumn() ?: 0;
        $nextIndex = $maxIndex + 1;

        // Insert new category
        $stmt = $db->prepare("INSERT INTO productcat (maincat_id, productcat_status, is_production, prod_group_name, prod_size, prod_formula, productcat_index, productcat_date, productcat_del, save_id) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 0, ?)");
        $stmt->execute([
            $_POST['maincat_id'] ?? null,
            isset($_POST['productcat_status']) ? 1 : 0,
            isset($_POST['is_production']) ? 1 : 0,
            $_POST['prod_group_name'] ?? null,
            $_POST['prod_size'] ?? null,
            $_POST['prod_formula'] ?? null,
            $nextIndex,
            $_SESSION['admin_id'] ?? 0
        ]);
        $categoryId = $db->lastInsertId();
    }
    
    // Insert translations for all languages
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar', 'he'];
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
    
    // Log Activity
    logStaffAction($db, $isEdit ? 'UPDATE' : 'CREATE', 'productcat', $categoryId, ($isEdit ? "Updated" : "Created") . " category: " . ($_POST['productcat_name_th'] ?? $categoryId));

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
