<?php
/**
 * Get Single Blog Category
 * Returns category with all 8 language translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        throw new Exception('Invalid category ID');
    }
    
    // Get main category record
    $stmt = $db->prepare("
        SELECT * FROM blogcat 
        WHERE blogcat_id = ? AND blogcat_del = 0
    ");
    $stmt->execute([$id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        throw new Exception('Category not found');
    }
    
    // Get all translations
    $stmt = $db->prepare("
        SELECT lang_code, blogcat_name, blogcat_detail
        FROM blogcat_translations
        WHERE blogcat_id = ?
    ");
    $stmt->execute([$id]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format translations as object
    $translationsObj = [];
    foreach ($translations as $trans) {
        $translationsObj[$trans['lang_code']] = [
            'blogcat_name' => $trans['blogcat_name'] ?? '',
            'blogcat_detail' => $trans['blogcat_detail'] ?? ''
        ];
    }
    
    // Ensure all 8 languages exist
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    foreach ($languages as $lang) {
        if (!isset($translationsObj[$lang])) {
            $translationsObj[$lang] = [
                'blogcat_name' => '',
                'blogcat_detail' => ''
            ];
        }
    }
    
    $category['translations'] = $translationsObj;
    
    echo json_encode([
        'success' => true,
        'data' => $category
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
