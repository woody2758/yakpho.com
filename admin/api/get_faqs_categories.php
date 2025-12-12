<?php
/**
 * Get FAQ Categories List
 * Returns simple list for dropdown
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $sql = "
        SELECT 
            fc.faqscat_id,
            fct.faqscat_name,
            COUNT(f.faqs_id) as faqs_count
        FROM faqscat fc
        LEFT JOIN faqscat_translations fct ON fc.faqscat_id = fct.faqscat_id AND fct.lang_code = 'th'
        LEFT JOIN faqs f ON fc.faqscat_id = f.faqscat_id AND f.faqs_del = 0
        WHERE fc.faqscat_del = 0 AND fc.faqscat_status = 1
        GROUP BY fc.faqscat_id, fct.faqscat_name
        ORDER BY fc.faqscat_index ASC
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
