<?php
/**
 * Get Menu Table Data
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $column = $_GET['column'] ?? 'all';
    
    $query = "
        SELECT 
            m.*, 
            t.menu_text 
        FROM footer_menus m
        LEFT JOIN footer_menus_translations t ON m.menu_id = t.menu_id AND t.lang_code = 'th'
        WHERE m.delete_date IS NULL
    ";
    
    $params = [];
    if ($column !== 'all') {
        $query .= " AND m.menu_column = ? ";
        $params[] = $column;
    }
    
    // Sort by column then by order
    $query .= " ORDER BY m.menu_column ASC, m.menu_order ASC ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
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
