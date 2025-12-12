<?php
/**
 * Get Blog Posts Table
 * Returns paginated list of blog posts with translations
 * ORDER BY blog_update DESC (latest updated first)
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
    $where = "WHERE b.blog_del = 0";
    $params = [];
    
    // Category filter
    if ($category > 0) {
        $where .= " AND b.blogcat_id = ?";
        $params[] = $category;
    }
    
    // Search filter
    if (!empty($search)) {
        $where .= " AND (bt.blog_name LIKE ? OR bt.blog_excerpt LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Get total count
    $countSql = "
        SELECT COUNT(DISTINCT b.blog_id)
        FROM blog b
        LEFT JOIN blog_translations bt ON b.blog_id = bt.blog_id AND bt.lang_code = 'th'
        $where
    ";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get blog posts
    $sql = "
        SELECT 
            b.blog_id,
            b.blogcat_id,
            b.blog_picture,
            b.blog_date,
            b.blog_update,
            b.blog_view,
            b.blog_status,
            bt_th.blog_name as name_th,
            bt_en.blog_name as name_en,
            bct.blogcat_name as category_name
        FROM blog b
        LEFT JOIN blog_translations bt_th ON b.blog_id = bt_th.blog_id AND bt_th.lang_code = 'th'
        LEFT JOIN blog_translations bt_en ON b.blog_id = bt_en.blog_id AND bt_en.lang_code = 'en'
        LEFT JOIN blogcat bc ON b.blogcat_id = bc.blogcat_id
        LEFT JOIN blogcat_translations bct ON bc.blogcat_id = bct.blogcat_id AND bct.lang_code = 'th'
        $where
        ORDER BY b.blog_update DESC, b.blog_id DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $blogs,
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
