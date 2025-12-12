<?php
/**
 * Get Single Blog Post
 * Returns blog with all 8 language translations and gallery images
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        throw new Exception('Invalid blog ID');
    }
    
    // Get main blog record
    $stmt = $db->prepare("
        SELECT * FROM blog 
        WHERE blog_id = ? AND blog_del = 0
    ");
    $stmt->execute([$id]);
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$blog) {
        throw new Exception('Blog post not found');
    }
    
    // Get all translations
    $stmt = $db->prepare("
        SELECT lang_code, blog_name, blog_excerpt, blog_detail, blog_tag
        FROM blog_translations
        WHERE blog_id = ?
    ");
    $stmt->execute([$id]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format translations as object
    $translationsObj = [];
    foreach ($translations as $trans) {
        $translationsObj[$trans['lang_code']] = [
            'blog_name' => $trans['blog_name'] ?? '',
            'blog_excerpt' => $trans['blog_excerpt'] ?? '',
            'blog_detail' => $trans['blog_detail'] ?? '',
            'blog_tag' => $trans['blog_tag'] ?? ''
        ];
    }
    
    // Ensure all 8 languages exist
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    foreach ($languages as $lang) {
        if (!isset($translationsObj[$lang])) {
            $translationsObj[$lang] = [
                'blog_name' => '',
                'blog_excerpt' => '',
                'blog_detail' => '',
                'blog_tag' => ''
            ];
        }
    }
    
    // Get gallery images
    $stmt = $db->prepare("
        SELECT id, gallery_image, gallery_order
        FROM blog_gallery
        WHERE blog_id = ?
        ORDER BY gallery_order ASC, id ASC
    ");
    $stmt->execute([$id]);
    $gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $blog['translations'] = $translationsObj;
    $blog['gallery'] = $gallery;
    
    echo json_encode([
        'success' => true,
        'data' => $blog
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
