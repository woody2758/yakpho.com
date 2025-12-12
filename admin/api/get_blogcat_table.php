<?php
/**
 * Get Blog Categories Table
 * Returns paginated list of blog categories with translations
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
    $where = "WHERE bc.blogcat_del = 0";
    $params = [];
    
    // Search filter
    if (!empty($search)) {
        $where .= " AND (bct.blogcat_name LIKE ? OR bct.blogcat_detail LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Get total count
    $countSql = "
        SELECT COUNT(DISTINCT bc.blogcat_id)
        FROM blogcat bc
        LEFT JOIN blogcat_translations bct ON bc.blogcat_id = bct.blogcat_id AND bct.lang_code = 'th'
        $where
    ";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get categories
    $sql = "
        SELECT 
            bc.blogcat_id,
            bc.blogcat_index,
            bc.blogcat_status,
            bc.blogcat_picture,
            bct_th.blogcat_name as name_th,
            bct_en.blogcat_name as name_en
        FROM blogcat bc
        LEFT JOIN blogcat_translations bct_th ON bc.blogcat_id = bct_th.blogcat_id AND bct_th.lang_code = 'th'
        LEFT JOIN blogcat_translations bct_en ON bc.blogcat_id = bct_en.blogcat_id AND bct_en.lang_code = 'en'
        $where
        ORDER BY bc.blogcat_index ASC, bc.blogcat_id ASC
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
