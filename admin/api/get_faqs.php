<?php
/**
 * Get Single FAQ
 * Returns FAQ with all 8 language translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        throw new Exception('Invalid FAQ ID');
    }
    
    // Get main FAQ record
    $stmt = $db->prepare("
        SELECT * FROM faqs 
        WHERE faqs_id = ? AND faqs_del = 0
    ");
    $stmt->execute([$id]);
    $faq = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$faq) {
        throw new Exception('FAQ not found');
    }
    
    // Get all translations
    $stmt = $db->prepare("
        SELECT lang_code, faqs_name, faqs_detail
        FROM faqs_translations
        WHERE faqs_id = ?
    ");
    $stmt->execute([$id]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format translations as object
    $translationsObj = [];
    foreach ($translations as $trans) {
        $translationsObj[$trans['lang_code']] = [
            'faqs_name' => $trans['faqs_name'] ?? '',
            'faqs_detail' => $trans['faqs_detail'] ?? ''
        ];
    }
    
    // Ensure all 8 languages exist
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar', 'he'];
    foreach ($languages as $lang) {
        if (!isset($translationsObj[$lang])) {
            $translationsObj[$lang] = [
                'faqs_name' => '',
                'faqs_detail' => ''
            ];
        }
    }
    
    $faq['translations'] = $translationsObj;
    
    echo json_encode([
        'success' => true,
        'data' => $faq
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
