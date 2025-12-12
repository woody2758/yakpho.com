<?php
/**
 * Get FAQs Categories Table
 * Returns paginated list of FAQ categories with translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    
    // Base query
    $where = "WHERE fc.faqscat_del = 0";
    $params = [];
    
    // Search filter
    if (!empty($search)) {
        $where .= " AND (fct.faqscat_name LIKE ? OR fct.faqscat_detail LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Get total count
    $countSql = "
        SELECT COUNT(DISTINCT fc.faqscat_id)
        FROM faqscat fc
        LEFT JOIN faqscat_translations fct ON fc.faqscat_id = fct.faqscat_id AND fct.lang_code = 'th'
        $where
    ";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get categories
    $sql = "
        SELECT 
            fc.faqscat_id,
            fc.faqscat_index,
            fc.faqscat_status,
            fct_th.faqscat_name as name_th,
            fct_en.faqscat_name as name_en
        FROM faqscat fc
        LEFT JOIN faqscat_translations fct_th ON fc.faqscat_id = fct_th.faqscat_id AND fct_th.lang_code = 'th'
        LEFT JOIN faqscat_translations fct_en ON fc.faqscat_id = fct_en.faqscat_id AND fct_en.lang_code = 'en'
        $where
        ORDER BY fc.faqscat_index ASC, fc.faqscat_id ASC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $categories,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
