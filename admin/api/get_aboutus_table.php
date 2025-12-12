<?php
/**
 * API: Get About Us Table Data
 * Returns list of all aboutus records with translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

try {
    // Get all aboutus records with Thai and English translations
    $sql = "
        SELECT 
            a.aboutus_id,
            a.aboutus_heading,
            a.aboutus_index,
            a.aboutus_status,
            t_th.title as title_th,
            t_en.title as title_en
        FROM aboutus a
        LEFT JOIN aboutus_translation t_th ON a.aboutus_id = t_th.aboutus_id AND t_th.lang_code = 'th'
        LEFT JOIN aboutus_translation t_en ON a.aboutus_id = t_en.aboutus_id AND t_en.lang_code = 'en'
        WHERE a.aboutus_del = 0
        ORDER BY a.aboutus_index ASC, a.aboutus_id ASC
    ";
    
    $stmt = $db->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data
    foreach ($data as &$row) {
        $row['aboutus_status'] = (int)$row['aboutus_status'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
