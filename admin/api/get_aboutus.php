<?php
/**
 * API: Get Single About Us Record
 * Returns aboutus with all language translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

try {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ID is required');
    }
    
    // Get main record
    $stmt = $db->prepare("
        SELECT * FROM aboutus 
        WHERE aboutus_id = ? AND aboutus_del = 0
    ");
    $stmt->execute([$id]);
    $aboutus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aboutus) {
        throw new Exception('About Us not found');
    }
    
    // Get all translations
    $stmt = $db->prepare("
        SELECT lang_code, title, subtitle, content
        FROM aboutus_translation
        WHERE aboutus_id = ?
    ");
    $stmt->execute([$id]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format translations as object
    $translationsObj = [];
    foreach ($translations as $trans) {
        $translationsObj[$trans['lang_code']] = [
            'title' => $trans['title'] ?? '',
            'subtitle' => $trans['subtitle'] ?? '',
            'content' => $trans['content'] ?? ''
        ];
    }
    
    // Ensure all languages exist
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    foreach ($languages as $lang) {
        if (!isset($translationsObj[$lang])) {
            $translationsObj[$lang] = [
                'title' => '',
                'subtitle' => '',
                'content' => ''
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'aboutus_id' => $aboutus['aboutus_id'],
            'aboutus_heading' => $aboutus['aboutus_heading'] ?? '',
            'aboutus_status' => (int)$aboutus['aboutus_status'],
            'aboutus_index' => $aboutus['aboutus_index'],
            'translations' => $translationsObj
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
