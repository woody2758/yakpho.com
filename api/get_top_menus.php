<?php
/**
 * Get Top Menus Public API
 * Returns nested menu structure for header navigation
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'th';

    // Fetch all active menus
    $query = "
        SELECT m.menu_id, m.parent_id, m.menu_link, m.menu_icon, t.menu_text
        FROM top_menus m
        JOIN top_menus_translations t ON m.menu_id = t.menu_id AND t.lang_code = ?
        WHERE m.menu_status = 'active' AND m.delete_date IS NULL
        ORDER BY m.menu_order ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([$lang]);
    $all_menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize into tree
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
