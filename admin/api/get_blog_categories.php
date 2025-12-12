<?php
/**
 * Get Blog Categories List with Count
 * Returns categories with blog post counts
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $sql = "
        SELECT 
            bc.blogcat_id,
            bct.blogcat_name,
            COUNT(b.blog_id) as blog_count
        FROM blogcat bc
        LEFT JOIN blogcat_translations bct ON bc.blogcat_id = bct.blogcat_id AND bct.lang_code = 'th'
        LEFT JOIN blog b ON bc.blogcat_id = b.blogcat_id AND b.blog_del = 0
        WHERE bc.blogcat_del = 0 AND bc.blogcat_status = 1
        GROUP BY bc.blogcat_id, bct.blogcat_name
        ORDER BY bc.blogcat_index ASC
    ";
    
    $categories = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
