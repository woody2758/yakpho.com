<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    // 1. Get ALL active Scents from Master Data (Sorted by your drag-drop order)
    $stmt = $db->query("SELECT pa.attribute_id, pat.attribute_name 
                        FROM product_attributes pa
                        JOIN product_attribute_groups pag ON pa.group_id = pag.group_id
                        LEFT JOIN product_attribute_translations pat ON pa.attribute_id = pat.attribute_id AND pat.lang_code = 'th'
                        WHERE pag.group_code = 'scent' AND pa.attribute_status = 1
                        ORDER BY pa.attribute_index ASC, pa.attribute_id ASC");
    $scents_master = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get ALL active Formulas from Master Data
    $stmt = $db->query("SELECT pa.attribute_id, pat.attribute_name 
                        FROM product_attributes pa
                        JOIN product_attribute_groups pag ON pa.group_id = pag.group_id
                        LEFT JOIN product_attribute_translations pat ON pa.attribute_id = pat.attribute_id AND pat.lang_code = 'th'
                        WHERE pag.group_code = 'formula' AND pa.attribute_status = 1
                        ORDER BY pa.attribute_index ASC, pa.attribute_id ASC");
    $formulas_master = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get all production Groups and Sizes defined in Categories
    $stmt = $db->query("SELECT DISTINCT prod_group_name, prod_size 
                        FROM productcat 
                        WHERE is_production = 1 AND productcat_del = 0
                        ORDER BY prod_group_name ASC, prod_size DESC");
    $production_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($production_groups)) {
        echo json_encode(['success' => true, 'matrix' => [], 'message' => 'ยังไม่ได้ตั้งค่าหมวดหมู่สินค้าสั่งผลิต']);
        exit;
    }

    // 4. Initialize the Matrix Structure with 0 values
    $matrix = [];
    $all_sizes = [];
    $all_formulas = [];

    // Map formulas for easy access
    foreach ($formulas_master as $f) {
        $all_formulas[] = $f['attribute_name'] ?: 'ทั่วไป';
    }
    // If no formulas defined in master, add a default one
    if (empty($all_formulas)) $all_formulas[] = 'ทั่วไป';

    foreach ($production_groups as $pg) {
        $group = $pg['prod_group_name'] ?: 'อื่นๆ';
        $size = $pg['prod_size'] ?: 'ไม่ระบุ';
        
        if (!isset($matrix[$group])) $matrix[$group] = [];
        if (!in_array($size, $all_sizes)) $all_sizes[] = $size;

        foreach ($scents_master as $sm) {
            $scent = $sm['attribute_name'] ?: 'ไม่ระบุชื่อ';
            if (!isset($matrix[$group][$scent])) $matrix[$group][$scent] = [];
            
            if (!isset($matrix[$group][$scent][$size])) {
                $matrix[$group][$scent][$size] = [];
            }

            foreach ($all_formulas as $formula_name) {
                // Now each cell is an object to hold both qty and memos
                $matrix[$group][$scent][$size][$formula_name] = [
                    'qty' => 0,
                    'memos' => []
                ];
            }
        }
    }

    // 5. Fetch Actual Pending Quantities AND Memos from Orders
    $sql = "SELECT 
                pc.prod_group_name,
                pc.prod_size,
                st.attribute_name AS scent_name,
                ft.attribute_name AS formula_name,
                SUM(od.ordetail_qty) as total_qty,
                GROUP_CONCAT(DISTINCT od.ordetail_memo SEPARATOR ' | ') as all_memos
            FROM ordetail od
            JOIN product p ON od.product_id = p.product_id
            JOIN productcat pc ON p.productcat_id = pc.productcat_id
            JOIN orders o ON od.orders_id = o.orders_id
            LEFT JOIN product_attribute_translations st ON p.scent_id = st.attribute_id AND st.lang_code = 'th'
            LEFT JOIN product_attribute_translations ft ON p.formula_id = ft.attribute_id AND ft.lang_code = 'th'
            WHERE o.orders_status IN (3, 4)
            AND pc.is_production = 1
            AND p.product_del = 0
            GROUP BY pc.prod_group_name, pc.prod_size, st.attribute_name, ft.attribute_name";
            
    $stmt = $db->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as $row) {
        $g = $row['prod_group_name'] ?: 'อื่นๆ';
        $scent = $row['scent_name'] ?: 'ไม่ระบุกลิ่น';
        $size = $row['prod_size'] ?: 'ไม่ระบุ';
        $formula = $row['formula_name'] ?: 'ทั่วไป';
        $qty = (int)$row['total_qty'];
        $memos = $row['all_memos'] ? explode(' | ', $row['all_memos']) : [];

        // Update Matrix
        if (!isset($matrix[$g][$scent][$size][$formula])) {
            // Handle dynamically added scents not in master
            if (!isset($matrix[$g])) $matrix[$g] = [];
            if (!isset($matrix[$g][$scent])) $matrix[$g][$scent] = [];
            if (!isset($matrix[$g][$scent][$size])) $matrix[$g][$scent][$size] = [];
            $matrix[$g][$scent][$size][$formula] = ['qty' => 0, 'memos' => []];
            
            if (!in_array($size, $all_sizes)) $all_sizes[] = $size;
            if (!in_array($formula, $all_formulas)) $all_formulas[] = $formula;
        }

        $matrix[$g][$scent][$size][$formula]['qty'] = $qty;
        $matrix[$g][$scent][$size][$formula]['memos'] = $memos;
    }

    // Sort sizes naturally (1kg before 40g etc)
    usort($all_sizes, function($a, $b) {
        return strnatcmp($a, $b);
    });

    echo json_encode([
        'success' => true,
        'matrix' => $matrix,
        'all_sizes' => $all_sizes,
        'all_formulas' => $all_formulas,
        'date' => date('d/m/Y')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
