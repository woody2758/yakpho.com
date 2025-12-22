<?php
/**
 * Save Blog Post
 * Create or update blog post with translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $blog_id = $data['blog_id'] ?? 0;
    $blogcat_id = $data['blogcat_id'] ?? 0;
    $blog_url = trim($data['blog_url'] ?? '');
    $blog_picture = trim($data['blog_picture'] ?? '');
    $blog_status = isset($data['blog_status']) ? (int)$data['blog_status'] : 1;
    $blog_date = $data['blog_date'] ?? date('Y-m-d H:i:s');
    $translations = $data['translations'] ?? [];
    
    if ($blogcat_id <= 0) {
        throw new Exception('Please select a category');
    }
    
    // Generate URL slug if empty (from Thai title)
    if (empty($blog_url) && !empty($translations['th']['blog_name'])) {
        $blog_url = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', 
            transliterate($translations['th']['blog_name']))));
    }
    
    $db->beginTransaction();
    
    if ($blog_id > 0) {
        // Update existing blog
        $stmt = $db->prepare("
            UPDATE blog SET
                blogcat_id = ?,
                blog_url = ?,
                blog_picture = ?,
                blog_date = ?,
                blog_status = ?,
                blog_update = NOW(),
                update_id = ?
            WHERE blog_id = ? AND blog_del = 0
        ");
        $stmt->execute([
            $blogcat_id,
            $blog_url,
            $blog_picture,
            $blog_date,
            $blog_status,
            $_SESSION['admin_id'],
            $blog_id
        ]);
        
    } else {
        // Insert new blog
        $stmt = $db->prepare("
            INSERT INTO blog 
            (blogcat_id, blog_url, blog_picture, blog_date, blog_status, blog_view, blog_del, save_id, update_id)
            VALUES (?, ?, ?, ?, ?, 0, 0, ?, ?)
        ");
        $stmt->execute([
            $blogcat_id,
            $blog_url,
            $blog_picture,
            $blog_date,
            $blog_status,
            $_SESSION['admin_id'],
            $_SESSION['admin_id']
        ]);
        $blog_id = $db->lastInsertId();
    }
    
    // Save translations for all 8 languages
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar', 'he'];
    
    foreach ($languages as $lang) {
        $name = $translations[$lang]['blog_name'] ?? '';
        $excerpt = $translations[$lang]['blog_excerpt'] ?? '';
        $detail = $translations[$lang]['blog_detail'] ?? '';
        $tag = $translations[$lang]['blog_tag'] ?? '';
        
        // Check if translation exists
        $check = $db->prepare("SELECT id FROM blog_translations WHERE blog_id = ? AND lang_code = ?");
        $check->execute([$blog_id, $lang]);
        
        if ($check->fetchColumn()) {
            // Update
            $stmt = $db->prepare("
                UPDATE blog_translations SET
                    blog_name = ?,
                    blog_excerpt = ?,
                    blog_detail = ?,
                    blog_tag = ?
                WHERE blog_id = ? AND lang_code = ?
            ");
            $stmt->execute([$name, $excerpt, $detail, $tag, $blog_id, $lang]);
        } else {
            // Insert
            $stmt = $db->prepare("
                INSERT INTO blog_translations (blog_id, lang_code, blog_name, blog_excerpt, blog_detail, blog_tag)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$blog_id, $lang, $name, $excerpt, $detail, $tag]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Blog post saved successfully',
        'blog_id' => $blog_id
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

/**
 * Simple transliteration function
 */
function transliterate($text) {
    // Thai to Latin transliteration (basic)
    $thai = ['ก','ข','ฃ','ค','ฅ','ฆ','ง','จ','ฉ','ช','ซ','ฌ','ญ','ฎ','ฏ','ฐ','ฑ','ฒ','ณ','ด','ต','ถ','ท','ธ','น','บ','ป','ผ','ฝ','พ','ฟ','ภ','ม','ย','ร','ล','ว','ศ','ษ','ส','ห','ฬ','อ','ฮ'];
    $latin = ['k','kh','kh','kh','kh','kh','ng','ch','ch','ch','s','ch','y','d','t','th','th','th','n','d','t','th','th','th','n','b','p','ph','f','ph','f','ph','m','y','r','l','w','s','s','s','h','l','o','h'];
    
    $text = str_replace($thai, $latin, $text);
    return $text;
}
