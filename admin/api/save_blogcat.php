<?php
/**
 * Save Blog Category
 * Create or update blog category with translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $blogcat_id = $data['blogcat_id'] ?? 0;
    $blogcat_status = isset($data['blogcat_status']) ? (int)$data['blogcat_status'] : 1;
    $translations = $data['translations'] ?? [];
    
    $db->beginTransaction();
    
    if ($blogcat_id > 0) {
        // Update existing category
        $stmt = $db->prepare("
            UPDATE blogcat SET
                blogcat_status = ?,
                blogcat_update = NOW(),
                update_id = ?
            WHERE blogcat_id = ? AND blogcat_del = 0
        ");
        $stmt->execute([$blogcat_status, $_SESSION['admin_id'], $blogcat_id]);
        
    } else {
        // Get next index
        $maxIndex = $db->query("SELECT MAX(CAST(blogcat_index AS UNSIGNED)) FROM blogcat WHERE blogcat_del = 0")->fetchColumn();
        $newIndex = str_pad((int)$maxIndex + 1, 2, '0', STR_PAD_LEFT);
        
        // Insert new category
        $stmt = $db->prepare("
            INSERT INTO blogcat 
            (blogcat_index, blogcat_status, blogcat_date, save_id, blogcat_del)
            VALUES (?, ?, NOW(), ?, 0)
        ");
        $stmt->execute([$newIndex, $blogcat_status, $_SESSION['admin_id']]);
        $blogcat_id = $db->lastInsertId();
    }
    
    // Save translations for all 8 languages
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    
    foreach ($languages as $lang) {
        $name = $translations[$lang]['blogcat_name'] ?? '';
        $detail = $translations[$lang]['blogcat_detail'] ?? '';
        
        // Check if translation exists
        $check = $db->prepare("SELECT id FROM blogcat_translations WHERE blogcat_id = ? AND lang_code = ?");
        $check->execute([$blogcat_id, $lang]);
        
        if ($check->fetchColumn()) {
            // Update
            $stmt = $db->prepare("
                UPDATE blogcat_translations SET
                    blogcat_name = ?,
                    blogcat_detail = ?
                WHERE blogcat_id = ? AND lang_code = ?
            ");
            $stmt->execute([$name, $detail, $blogcat_id, $lang]);
        } else {
            // Insert
            $stmt = $db->prepare("
                INSERT INTO blogcat_translations (blogcat_id, lang_code, blogcat_name, blogcat_detail)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$blogcat_id, $lang, $name, $detail]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Blog category saved successfully',
        'blogcat_id' => $blogcat_id
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
