<?php
/**
 * Get Footer Menus for Frontend
 * Returns active items for a specific column and language
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

try {
    $column = $_GET['column'] ?? '';
    $lang = $_GET['lang'] ?? 'th';
    
    if (empty($column)) {
        throw new Exception("Missing column name");
    }
    
    $stmt = $db->prepare("
        SELECT 
            m.menu_link, 
            m.menu_icon, 
            m.menu_target, 
            t.menu_text 
        FROM footer_menus m
        INNER JOIN footer_menus_translations t ON m.menu_id = t.menu_id AND t.lang_code = ?
        WHERE m.menu_column = ? 
        AND m.menu_status = 'active' 
        AND m.delete_date IS NULL
        ORDER BY m.menu_order ASC
    ");
    
    $stmt->execute([$lang, $column]);
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'menus' => $menus
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
