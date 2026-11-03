<?php
/**
 * Get Top Menu Table API
 * Returns list of top menu items with parent info and TH translations
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $lang = $_GET['lang'] ?? 'th';
    $parents_only = isset($_GET['parents_only']) && $_GET['parents_only'] == '1';

    $where = "WHERE m.delete_date IS NULL";
    if ($parents_only) {
        $where .= " AND m.parent_id = 0";
    }

    $query = "
        SELECT 
            m.*, 
            t.menu_text,
            pt.menu_text as parent_text
        FROM top_menus m
        LEFT JOIN top_menus_translations t ON m.menu_id = t.menu_id AND t.lang_code = ?
        LEFT JOIN top_menus_translations pt ON m.parent_id = pt.menu_id AND pt.lang_code = ?
        $where
        ORDER BY m.parent_id ASC, m.menu_order ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([$lang, $lang]);
    $all_menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build hierarchy
    $menu_tree = [];
    $lookup = [];

    foreach ($all_menus as $menu) {
        $menu['children'] = [];
        $lookup[$menu['menu_id']] = $menu;
    }

    foreach ($lookup as $id => &$menu) {
        if ($menu['parent_id'] == 0) {
            $menu_tree[] = &$menu;
        } else {
            if (isset($lookup[$menu['parent_id']])) {
                $lookup[$menu['parent_id']]['children'][] = &$menu;
            } else {
                // If parent not found (shouldn't happen with delete_date), treat as top level
                $menu_tree[] = &$menu;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'menus' => $menu_tree
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
