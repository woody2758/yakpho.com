<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $stmt = $db->query("
        SELECT pc.productcat_id as id, pct.productcat_name as name,
               COUNT(p.product_id) as count
        FROM productcat pc
        LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = 'th'
        LEFT JOIN product p ON pc.productcat_id = p.productcat_id AND p.product_del = 0
        WHERE pc.productcat_del = 0
        GROUP BY pc.productcat_id, pct.productcat_name
        ORDER BY pc.productcat_index ASC, pc.productcat_id ASC
    ");
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'categories' => $categories]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
