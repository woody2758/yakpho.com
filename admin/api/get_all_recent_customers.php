<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20; // Items per page
    $offset = ($page - 1) * $limit;

    $range = $_GET['range'] ?? 'thismonth';
    $startDate = '';
    $endDate = '';

    switch ($range) {
        case 'today':
            $startDate = date('Y-m-d 00:00:00');
            $endDate = date('Y-m-d 23:59:59');
            break;
        case 'yesterday':
            $startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
            $endDate = date('Y-m-d 23:59:59', strtotime('-1 day'));
            break;
        case 'last7days':
            $startDate = date('Y-m-d 00:00:00', strtotime('-6 days'));
            $endDate = date('Y-m-d 23:59:59');
            break;
        case 'last30days':
            $startDate = date('Y-m-d 00:00:00', strtotime('-29 days'));
            $endDate = date('Y-m-d 23:59:59');
            break;
        case 'thismonth':
            $startDate = date('Y-m-01 00:00:00');
            $endDate = date('Y-m-t 23:59:59');
            break;
        case 'lastmonth':
            $startDate = date('Y-m-01 00:00:00', strtotime('first day of last month'));
            $endDate = date('Y-m-t 23:59:59', strtotime('last day of last month'));
            break;
        case 'last3months':
            $startDate = date('Y-m-01 00:00:00', strtotime('-2 months'));
            $endDate = date('Y-m-t 23:59:59');
            break;
        case 'last6months':
            $startDate = date('Y-m-01 00:00:00', strtotime('-5 months'));
            $endDate = date('Y-m-t 23:59:59');
            break;
        case 'thisyear':
            $startDate = date('Y-01-01 00:00:00');
            $endDate = date('Y-12-31 23:59:59');
            break;
        case 'custom':
            $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] . ' 00:00:00' : date('Y-m-01 00:00:00');
            $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] . ' 23:59:59' : date('Y-m-t 23:59:59');
            break;
        case 'all':
        default:
            $startDate = '2000-01-01 00:00:00';
            $endDate = '2099-12-31 23:59:59';
            break;
    }

    $maincatId = isset($_GET['maincat_id']) ? (int)$_GET['maincat_id'] : 0;
    $maincatWhereOrders = "";
    if ($maincatId > 0) {
        $maincatWhereOrders = " AND EXISTS (
            SELECT 1 FROM ordetail sub_od 
            JOIN product sub_p ON sub_od.product_id = sub_p.product_id
            JOIN productcat sub_pc ON sub_p.productcat_id = sub_pc.productcat_id
            WHERE sub_od.orders_id = o.orders_id 
              AND sub_pc.maincat_id = :maincat_id
        ) ";
    }
    
    $baseWhere = " o.orders_del = 0 AND o.orders_status NOT IN (8, 9) AND o.orders_date BETWEEN :start AND :end " . $maincatWhereOrders;

    // Count total records
    $stmt_count = $db->prepare("
        SELECT COUNT(o.orders_id) as total
        FROM orders o
        JOIN user u ON o.user_id = u.user_id
        WHERE $baseWhere
    ");
    $params = ['start' => $startDate, 'end' => $endDate];
    if ($maincatId > 0) {
        $params['maincat_id'] = $maincatId;
    }
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Fetch paginated records
    $stmt = $db->prepare("
        SELECT 
            u.user_id,
            u.user_name,
            u.user_lastname,
            u.user_email,
            u.user_tel,
            u.user_picture,
            o.orders_id,
            o.orders_no,
            o.orders_date as last_order_date,
            o.orders_grandtotal
        FROM orders o
        JOIN user u ON o.user_id = u.user_id
        WHERE $baseWhere
        ORDER BY o.orders_date DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':start', $startDate, PDO::PARAM_STR);
    $stmt->bindValue(':end', $endDate, PDO::PARAM_STR);
    if ($maincatId > 0) {
        $stmt->bindValue(':maincat_id', $maincatId, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $customers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'limit' => $limit
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
