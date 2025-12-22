<?php
/**
 * Save FAQ
 * Create or update FAQ with translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $faqs_id = $data['faqs_id'] ?? 0;
    $faqscat_id = $data['faqscat_id'] ?? 0;
    $faqs_status = isset($data['faqs_status']) ? (int)$data['faqs_status'] : 1;
    $translations = $data['translations'] ?? [];
    
    if ($faqscat_id <= 0) {
        throw new Exception('Please select a category');
    }
    
    $db->beginTransaction();
    
    if ($faqs_id > 0) {
        // Update existing FAQ
        $stmt = $db->prepare("
            UPDATE faqs SET
                faqscat_id = ?,
                faqs_status = ?,
                faqs_update = NOW(),
                update_id = ?
            WHERE faqs_id = ? AND faqs_del = 0
        ");
        $stmt->execute([$faqscat_id, $faqs_status, $_SESSION['admin_id'], $faqs_id]);
        
    } else {
        // Get next index
        $maxIndex = $db->query("SELECT MAX(CAST(faqs_index AS UNSIGNED)) FROM faqs WHERE faqs_del = 0")->fetchColumn();
        $newIndex = str_pad((int)$maxIndex + 1, 2, '0', STR_PAD_LEFT);
        
        // Insert new FAQ
        $stmt = $db->prepare("
            INSERT INTO faqs 
            (faqscat_id, faqs_index, faqs_status, faqs_date, save_id, faqs_del, faqs_view)
            VALUES (?, ?, ?, NOW(), ?, 0, 0)
        ");
        $stmt->execute([$faqscat_id, $newIndex, $faqs_status, $_SESSION['admin_id']]);
        $faqs_id = $db->lastInsertId();
    }
    
    // Save translations for all 8 languages
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar', 'he'];
    
    foreach ($languages as $lang) {
        $name = $translations[$lang]['faqs_name'] ?? '';
        $detail = $translations[$lang]['faqs_detail'] ?? '';
        
        // Check if translation exists
        $check = $db->prepare("SELECT id FROM faqs_translations WHERE faqs_id = ? AND lang_code = ?");
        $check->execute([$faqs_id, $lang]);
        
        if ($check->fetchColumn()) {
            // Update
            $stmt = $db->prepare("
                UPDATE faqs_translations SET
                    faqs_name = ?,
                    faqs_detail = ?
                WHERE faqs_id = ? AND lang_code = ?
            ");
            $stmt->execute([$name, $detail, $faqs_id, $lang]);
        } else {
            // Insert
            $stmt = $db->prepare("
                INSERT INTO faqs_translations (faqs_id, lang_code, faqs_name, faqs_detail)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$faqs_id, $lang, $name, $detail]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'FAQ saved successfully',
        'faqs_id' => $faqs_id
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
