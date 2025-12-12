<?php
/**
 * Get FAQs Table
 * Returns paginated list of FAQs with translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    
    // Base query
    $where = "WHERE f.faqs_del = 0";
    $params = [];
    
    // Category filter
    if ($category > 0) {
        $where .= " AND f.faqscat_id = ?";
        $params[] = $category;
    }
    
    // Search filter
    if (!empty($search)) {
        $where .= " AND (ft.faqs_name LIKE ? OR ft.faqs_detail LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Get total count
    $countSql = "
        SELECT COUNT(DISTINCT f.faqs_id)
        FROM faqs f
        LEFT JOIN faqs_translations ft ON f.faqs_id = ft.faqs_id AND ft.lang_code = 'th'
        $where
    ";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get FAQs
    $sql = "
        SELECT 
            f.faqs_id,
            f.faqscat_id,
            f.faqs_index,
            f.faqs_status,
            f.faqs_view,
            ft_th.faqs_name as name_th,
            ft_en.faqs_name as name_en,
            fct.faqscat_name as category_name
        FROM faqs f
        LEFT JOIN faqs_translations ft_th ON f.faqs_id = ft_th.faqs_id AND ft_th.lang_code = 'th'
        LEFT JOIN faqs_translations ft_en ON f.faqs_id = ft_en.faqs_id AND ft_en.lang_code = 'en'
        LEFT JOIN faqscat fc ON f.faqscat_id = fc.faqscat_id
        LEFT JOIN faqscat_translations fct ON fc.faqscat_id = fct.faqscat_id AND fct.lang_code = 'th'
        $where
        ORDER BY f.faqs_index ASC, f.faqs_id ASC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $faqs,
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
