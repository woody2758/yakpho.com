<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20; // Items per page
    $offset = ($page - 1) * $limit;

    $maincatId = isset($_GET['maincat_id']) ? (int)$_GET['maincat_id'] : 0;
    $maincatWhereOrders = "";
    $paramsNoDate = [];
    
    if ($maincatId > 0) {
        $maincatWhereOrders = " AND EXISTS (
            SELECT 1 FROM ordetail sub_od 
            JOIN product sub_p ON sub_od.product_id = sub_p.product_id
            JOIN productcat sub_pc ON sub_p.productcat_id = sub_pc.productcat_id
            WHERE sub_od.orders_id = o.orders_id 
              AND sub_pc.maincat_id = :maincat_id
        ) ";
        $paramsNoDate['maincat_id'] = $maincatId;
    }

    $stmt_orders = $db->prepare("
        SELECT 
            o.user_id,
            u.user_name,
            u.user_lastname,
            u.user_tel,
            u.user_picture,
            o.orders_date,
            o.orders_grandtotal
        FROM orders o
        JOIN user u ON o.user_id = u.user_id
        WHERE o.orders_del = 0 AND o.orders_status NOT IN (8, 9) $maincatWhereOrders
        ORDER BY o.user_id, o.orders_date ASC
    ");
    $stmt_orders->execute($paramsNoDate);
    $all_orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

    $user_orders = [];
    foreach ($all_orders as $ord) {
        $uid = $ord['user_id'];
        if (!isset($user_orders[$uid])) {
            $user_orders[$uid] = [
                'name' => $ord['user_name'] . ' ' . $ord['user_lastname'],
                'tel' => $ord['user_tel'],
                'user_picture' => $ord['user_picture'],
                'dates' => []
            ];
        }
        $user_orders[$uid]['dates'][] = strtotime($ord['orders_date']);
    }

    $stmt_yakpho_users = $db->prepare("
        SELECT DISTINCT o.user_id
        FROM orders o
        JOIN ordetail od ON o.orders_id = od.orders_id
        JOIN product p ON od.product_id = p.product_id
        JOIN productcat pc ON p.productcat_id = pc.productcat_id
        WHERE o.orders_del = 0 AND o.orders_status NOT IN (8, 9) $maincatWhereOrders
          AND (pc.productcat_id IN (32, 38, 40, 49, 50, 51) OR pc.productcat_name LIKE '%ยักษ์โพธิ์%' OR pc.productcat_name LIKE '%บาล์ม%')
    ");
    $stmt_yakpho_users->execute($paramsNoDate);
    $yakpho_user_ids = $stmt_yakpho_users->fetchAll(PDO::FETCH_COLUMN);
    $yakpho_user_lookup = array_flip($yakpho_user_ids);

    $repeat_alerts = [];

    foreach ($user_orders as $uid => $data) {
        if (!isset($yakpho_user_lookup[$uid])) continue;

        $dates = $data['dates'];
        $count = count($dates);
        if ($count < 2) continue; // Only repeat buyers

        $intervals = [];
        for ($i = 1; $i < $count; $i++) {
            $diff = ($dates[$i] - $dates[$i - 1]) / 86400; // in days
            $intervals[] = $diff;
        }

        $avg_interval = array_sum($intervals) / count($intervals);

        $last_date = end($dates);
        $days_since_last = (time() - $last_date) / 86400;

        if ($days_since_last > ($avg_interval + 10)) {
            $repeat_alerts[] = [
                'user_id' => $uid,
                'name' => $data['name'],
                'tel' => $data['tel'],
                'user_picture' => $data['user_picture'],
                'total_orders' => $count,
                'avg_interval' => round($avg_interval, 0),
                'days_since_last' => round($days_since_last, 0),
                'last_order_date' => date('Y-m-d', $last_date)
            ];
        }
    }

    usort($repeat_alerts, function($a, $b) {
        $overdueA = $a['days_since_last'] - $a['avg_interval'];
        $overdueB = $b['days_since_last'] - $b['avg_interval'];
        if (abs($overdueA - $overdueB) < 0.001) return 0;
        return ($overdueB > $overdueA) ? 1 : -1;
    });

    // Pagination
    $total_records = count($repeat_alerts);
    $total_pages = ceil($total_records / $limit);
    $paginated_alerts = array_slice($repeat_alerts, $offset, $limit);

    echo json_encode([
        'success' => true,
        'data' => $paginated_alerts,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
