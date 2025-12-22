<?php
/**
 * Save FAQs Category
 * Create or update FAQ category with translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $faqscat_id = $data['faqscat_id'] ?? 0;
    $faqscat_status = isset($data['faqscat_status']) ? (int)$data['faqscat_status'] : 1;
    $translations = $data['translations'] ?? [];
    
    $db->beginTransaction();
    
    if ($faqscat_id > 0) {
        // Update existing category
        $stmt = $db->prepare("
            UPDATE faqscat SET
                faqscat_status = ?,
                faqscat_update = NOW(),
                update_id = ?
            WHERE faqscat_id = ? AND faqscat_del = 0
        ");
        $stmt->execute([$faqscat_status, $_SESSION['admin_id'], $faqscat_id]);
        
    } else {
        // Get next index
        $maxIndex = $db->query("SELECT MAX(CAST(faqscat_index AS UNSIGNED)) FROM faqscat WHERE faqscat_del = 0")->fetchColumn();
        $newIndex = str_pad((int)$maxIndex + 1, 2, '0', STR_PAD_LEFT);
        
        // Insert new category
        $stmt = $db->prepare("
            INSERT INTO faqscat 
            (faqscat_index, faqscat_status, faqscat_date, save_id, faqscat_del)
            VALUES (?, ?, NOW(), ?, 0)
        ");
        $stmt->execute([$newIndex, $faqscat_status, $_SESSION['admin_id']]);
        $faqscat_id = $db->lastInsertId();
    }
    
    // Save translations for all 8 languages
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar', 'he'];
    
    foreach ($languages as $lang) {
        $name = $translations[$lang]['faqscat_name'] ?? '';
        $detail = $translations[$lang]['faqscat_detail'] ?? '';
        
        // Check if translation exists
        $check = $db->prepare("SELECT id FROM faqscat_translations WHERE faqscat_id = ? AND lang_code = ?");
        $check->execute([$faqscat_id, $lang]);
        
        if ($check->fetchColumn()) {
            // Update
            $stmt = $db->prepare("
                UPDATE faqscat_translations SET
                    faqscat_name = ?,
                    faqscat_detail = ?
                WHERE faqscat_id = ? AND lang_code = ?
            ");
            $stmt->execute([$name, $detail, $faqscat_id, $lang]);
        } else {
            // Insert
            $stmt = $db->prepare("
                INSERT INTO faqscat_translations (faqscat_id, lang_code, faqscat_name, faqscat_detail)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$faqscat_id, $lang, $name, $detail]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'FAQ category saved successfully',
        'faqscat_id' => $faqscat_id
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
