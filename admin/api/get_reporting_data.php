<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Ensure database schema parity for actual shipping cost
if (!function_exists('ensureShippingCostColumn')) {
    function ensureShippingCostColumn($db) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM `orders` LIKE 'orders_delicosttrue'");
            if ($stmt->rowCount() === 0) {
                $db->exec("ALTER TABLE `orders` ADD COLUMN `orders_delicosttrue` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'ค่าส่งจ่ายจริงให้ขนส่ง';");
            }
        } catch (Exception $e) {
            // Silently catch or handle
        }
    }
}
ensureShippingCostColumn($db);

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
$params = ['start' => $startDate, 'end' => $endDate];

if ($maincatId > 0) {
    $maincatWhereOrders = " AND EXISTS (
        SELECT 1 FROM ordetail sub_od 
        JOIN product sub_p ON sub_od.product_id = sub_p.product_id
        JOIN productcat sub_pc ON sub_p.productcat_id = sub_pc.productcat_id
        WHERE sub_od.orders_id = o.orders_id 
          AND sub_pc.maincat_id = :maincat_id
    ) ";
    $params['maincat_id'] = $maincatId;
}

$baseWhere = " o.orders_del = 0 AND o.orders_status NOT IN (8, 9) AND o.orders_date BETWEEN :start AND :end " . $maincatWhereOrders;

try {
    // ----------------------------------------------------
    // 1. FINANCIAL SUMMARY
    // ----------------------------------------------------
    $stmt_fin = $db->prepare("
        SELECT 
            COUNT(o.orders_id) AS total_orders,
            SUM(o.orders_total) AS total_subtotal,
            SUM(o.orders_discount) AS total_discount,
            SUM(o.orders_delicost) AS total_delicost,
            SUM(o.orders_delicosttrue) AS total_delicosttrue,
            SUM(o.orders_cod) AS total_cod,
            SUM(o.orders_grandtotal) AS total_grandtotal
        FROM orders o
        WHERE $baseWhere
    ");
    $stmt_fin->execute($params);
    $fin = $stmt_fin->fetch(PDO::FETCH_ASSOC);

    // Default null values to 0
    $total_orders = (int)($fin['total_orders'] ?? 0);
    $total_subtotal = (float)($fin['total_subtotal'] ?? 0);
    $total_discount = (float)($fin['total_discount'] ?? 0);
    $total_delicost = (float)($fin['total_delicost'] ?? 0);
    $total_delicosttrue = (float)($fin['total_delicosttrue'] ?? 0);
    $total_cod = (float)($fin['total_cod'] ?? 0);
    $total_grandtotal = (float)($fin['total_grandtotal'] ?? 0);

    // Fetch Total COGS
    $stmt_cogs = $db->prepare("
        SELECT SUM(od.item_cost * od.ordetail_qty) AS total_cogs
        FROM ordetail od
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE $baseWhere
    ");
    $stmt_cogs->execute($params);
    $total_cogs = (float)($stmt_cogs->fetchColumn() ?? 0);

    // Calculate Net Profit
    // Shipping Margin = Shipping Revenue - Shipping Paid.
    $shipping_paid = $total_delicosttrue;
    $shipping_margin = $total_delicost - $shipping_paid;
    $net_profit = ($total_subtotal - $total_cogs - $total_discount) + $shipping_margin;

    // payment methods distribution
    $stmt_pay = $db->prepare("
        SELECT 
            o.paycat_id,
            COALESCE(p.paycat_name, 'ไม่ระบุ') AS paycat_name,
            COUNT(o.orders_id) AS orders_count,
            SUM(o.orders_grandtotal) AS total_sales
        FROM orders o
        LEFT JOIN paycat p ON o.paycat_id = p.paycat_id
        WHERE $baseWhere
        GROUP BY o.paycat_id
        ORDER BY total_sales DESC
    ");
    $stmt_pay->execute($params);
    $payment_methods = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);

    // ----------------------------------------------------
    // 2. PRODUCT PERFORMANCE
    // ----------------------------------------------------
    $stmt_prod = $db->prepare("
        SELECT 
            p.product_id,
            p.product_code,
            COALESCE(pt.product_name, p.product_name) AS product_name,
            p.product_img,
            SUM(od.ordetail_qty) AS qty_sold,
            SUM(od.ordetail_qty * od.product_price) AS gross_sales,
            SUM(od.ordetail_qty * od.item_cost) AS total_cogs,
            SUM(od.ordetail_qty * od.product_price) - SUM(od.ordetail_qty * od.item_cost) AS gross_profit
        FROM ordetail od
        JOIN product p ON od.product_id = p.product_id
        LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = 'th'
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE $baseWhere
        GROUP BY p.product_id
        ORDER BY qty_sold DESC
        LIMIT 15
    ");
    $stmt_prod->execute($params);
    $products = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

    // Brand / Category breakdown
    $stmt_brand = $db->prepare("
        SELECT 
            pc.productcat_id,
            COALESCE(pct.productcat_name, pc.productcat_name) AS productcat_name,
            SUM(od.ordetail_qty) AS qty_sold,
            SUM(od.ordetail_qty * od.product_price) AS gross_sales
        FROM ordetail od
        JOIN product p ON od.product_id = p.product_id
        JOIN productcat pc ON p.productcat_id = pc.productcat_id
        LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = 'th'
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE $baseWhere
        GROUP BY pc.productcat_id
        ORDER BY gross_sales DESC
    ");
    $stmt_brand->execute($params);
    $brands = $stmt_brand->fetchAll(PDO::FETCH_ASSOC);

    // ----------------------------------------------------
    // 3. STAFF & AGENT PERFORMANCE
    // ----------------------------------------------------
    $stmt_staff = $db->prepare("
        SELECT 
            u.user_id,
            u.user_name,
            u.user_lastname,
            COUNT(o.orders_id) AS orders_count,
            SUM(o.orders_grandtotal) AS total_sales
        FROM orders o
        JOIN user u ON o.save_id = u.user_id
        WHERE $baseWhere
        GROUP BY o.save_id
        ORDER BY total_sales DESC
    ");
    $stmt_staff->execute($params);
    $staff_performance = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);

    $stmt_agent = $db->prepare("
        SELECT 
            u.user_id,
            u.user_name,
            u.user_lastname,
            COUNT(o.orders_id) AS orders_count,
            SUM(o.orders_grandtotal) AS total_sales
        FROM orders o
        JOIN user u ON o.agent_user_id = u.user_id
        WHERE $baseWhere
        GROUP BY o.agent_user_id
        ORDER BY total_sales DESC
    ");
    $stmt_agent->execute($params);
    $agent_performance = $stmt_agent->fetchAll(PDO::FETCH_ASSOC);

    // ----------------------------------------------------
    // 4. CHANNELS & COUNTRIES
    // ----------------------------------------------------
    $stmt_chan = $db->prepare("
        SELECT 
            o.orders_channel,
            COALESCE(sc.channel_name, 'ไม่ระบุ') AS channel_name,
            COUNT(o.orders_id) AS orders_count,
            SUM(o.orders_grandtotal) AS total_sales
        FROM orders o
        LEFT JOIN sales_channels sc ON o.orders_channel = sc.channel_code
        WHERE $baseWhere
        GROUP BY o.orders_channel
        ORDER BY total_sales DESC
    ");
    $stmt_chan->execute($params);
    $channels = $stmt_chan->fetchAll(PDO::FETCH_ASSOC);

    $stmt_coun = $db->prepare("
        SELECT 
            o.orders_country_id,
            CASE WHEN o.orders_country_id = 0 THEN 'ยังไม่ได้ระบุ' ELSE COALESCE(c.country_name, 'ยังไม่ได้ระบุ') END AS country_name,
            COALESCE(c.country_code, '-') AS country_code,
            COUNT(o.orders_id) AS orders_count,
            SUM(o.orders_grandtotal) AS total_sales
        FROM orders o
        LEFT JOIN countries c ON o.orders_country_id = c.country_id
        WHERE $baseWhere
        GROUP BY o.orders_country_id
        ORDER BY total_sales DESC
    ");
    $stmt_coun->execute($params);
    $countries = $stmt_coun->fetchAll(PDO::FETCH_ASSOC);

    // ----------------------------------------------------
    // 5. CUSTOMER BEHAVIOR & REPEAT PURCHASES
    // ----------------------------------------------------
    // Top Spenders
    $stmt_spend = $db->prepare("
        SELECT 
            u.user_id,
            u.user_name,
            u.user_lastname,
            u.user_email,
            u.user_tel,
            u.user_picture,
            COUNT(o.orders_id) AS total_orders,
            SUM(o.orders_grandtotal) AS lifetime_value,
            AVG(o.orders_grandtotal) AS avg_order_value
        FROM orders o
        JOIN user u ON o.user_id = u.user_id
        WHERE $baseWhere
        GROUP BY o.user_id
        ORDER BY lifetime_value DESC
        LIMIT 15
    ");
    $stmt_spend->execute($params);
    $top_spenders = $stmt_spend->fetchAll(PDO::FETCH_ASSOC);

    // Recent Customers
    $stmt_recent = $db->prepare("
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
        LIMIT 15
    ");
    $stmt_recent->execute($params);
    $recent_customers = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

    // Customer Segmentation (ลูกค้ายาหม่องยักษ์โพธิ์ vs ลูกค้าสมุนไพรเดิม)
    $stmt_seg = $db->prepare("
        SELECT 
            u.user_id,
            u.user_name,
            u.user_lastname,
            u.user_email,
            u.user_tel,
            u.user_picture,
            COUNT(DISTINCT o.orders_id) AS total_orders,
            SUM(o.orders_grandtotal) AS total_spent,
            SUM(CASE WHEN pc.productcat_id IN (32, 38, 40, 49, 50, 51) OR pc.productcat_name LIKE '%ยักษ์โพธิ์%' OR pc.productcat_name LIKE '%บาล์ม%' THEN od.ordetail_qty ELSE 0 END) AS yakpho_balm_qty
        FROM user u
        JOIN orders o ON u.user_id = o.user_id
        JOIN ordetail od ON o.orders_id = od.orders_id
        JOIN product p ON od.product_id = p.product_id
        JOIN productcat pc ON p.productcat_id = pc.productcat_id
        WHERE $baseWhere
        GROUP BY u.user_id
        ORDER BY total_spent DESC
        LIMIT 100
    ");
    $stmt_seg->execute($params);
    $seg_list = $stmt_seg->fetchAll(PDO::FETCH_ASSOC);

    $segmentation_counts = [
        'repeat' => 0,
        'onetime' => 0
    ];
    $segmented_users = [];
    foreach ($seg_list as $user) {
        $is_yakpho = (int)$user['yakpho_balm_qty'] > 0;
        
        // Skip users who have NEVER bought Yakpho Balm
        if (!$is_yakpho) continue;
        
        $total_orders = (int)$user['total_orders'];
        $is_repeat = $total_orders > 1;
        $seg_name = $is_repeat ? 'ซื้อซ้ำ (Repeat)' : 'ซื้อครั้งเดียว (One-time)';
        
        if ($is_repeat) {
            $segmentation_counts['repeat']++;
        } else {
            $segmentation_counts['onetime']++;
        }

        $segmented_users[] = [
            'user_id' => $user['user_id'],
            'name' => $user['user_name'] . ' ' . $user['user_lastname'],
            'email' => $user['user_email'],
            'tel' => $user['user_tel'],
            'user_picture' => $user['user_picture'],
            'total_orders' => $total_orders,
            'total_spent' => (float)$user['total_spent'],
            'segment' => $seg_name
        ];
    }

    // Repeat Purchase Intervals and Alerts
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
    
    // For queries without :start/:end, we need a separate params array
    $paramsNoDate = [];
    if ($maincatId > 0) $paramsNoDate['maincat_id'] = $maincatId;
    
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

    // Get a list of ALL user_ids who have ever bought Yakpho Balm (Optimized)
    $prodStmt = $db->query("SELECT p.product_id FROM product p 
        JOIN productcat pc ON p.productcat_id = pc.productcat_id 
        WHERE pc.productcat_id IN (32, 38, 40, 49, 50, 51) 
           OR pc.productcat_name LIKE '%ยักษ์โพธิ์%' 
           OR pc.productcat_name LIKE '%บาล์ม%'");
    $prodIds = $prodStmt->fetchAll(PDO::FETCH_COLUMN);

    $yakpho_user_ids = [];
    if (!empty($prodIds)) {
        $inProds = implode(',', array_map('intval', $prodIds));
        $stmt_yakpho_users = $db->prepare("
            SELECT DISTINCT o.user_id
            FROM ordetail od
            JOIN orders o ON od.orders_id = o.orders_id
            WHERE od.product_id IN ($inProds)
              AND o.orders_del = 0 AND o.orders_status NOT IN (8, 9) $maincatWhereOrders
        ");
        $stmt_yakpho_users->execute($paramsNoDate);
        $yakpho_user_ids = $stmt_yakpho_users->fetchAll(PDO::FETCH_COLUMN);
    }
    $yakpho_user_lookup = array_flip($yakpho_user_ids);

    $repeat_alerts = [];
    $total_intervals = 0;
    $interval_count = 0;

    foreach ($user_orders as $uid => $data) {
        // Skip users who have NEVER bought Yakpho Balm
        if (!isset($yakpho_user_lookup[$uid])) continue;

        $dates = $data['dates'];
        $count = count($dates);
        if ($count < 2) continue; // Only repeat buyers

        // Calculate consecutive intervals in days
        $intervals = [];
        for ($i = 1; $i < $count; $i++) {
            $diff = ($dates[$i] - $dates[$i - 1]) / 86400; // in days
            $intervals[] = $diff;
        }

        $avg_interval = array_sum($intervals) / count($intervals);
        $total_intervals += $avg_interval;
        $interval_count++;

        // Calculate days since most recent purchase
        $last_date = end($dates);
        $days_since_last = (time() - $last_date) / 86400;

        // If days since last order exceeds their average interval + 10 days, flag as overdue
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

    // Sort repeat alerts so that customers with the highest overdue gap (days_since_last - avg_interval) appear first
    usort($repeat_alerts, function($a, $b) {
        $overdueA = $a['days_since_last'] - $a['avg_interval'];
        $overdueB = $b['days_since_last'] - $b['avg_interval'];
        if (abs($overdueA - $overdueB) < 0.001) return 0;
        return ($overdueB > $overdueA) ? 1 : -1;
    });

    $system_avg_interval = $interval_count > 0 ? round($total_intervals / $interval_count, 0) : 0;

    // Send complete JSON response
    echo json_encode([
        'success' => true,
        'date_range' => [
            'start' => substr($startDate, 0, 10),
            'end' => substr($endDate, 0, 10)
        ],
        'financial' => [
            'total_orders' => $total_orders,
            'total_subtotal' => $total_subtotal,
            'total_discount' => $total_discount,
            'total_delicost' => $total_delicost,
            'total_delicosttrue' => $total_delicosttrue,
            'total_cod' => $total_cod,
            'total_grandtotal' => $total_grandtotal,
            'total_cogs' => $total_cogs,
            'shipping_margin' => $shipping_margin,
            'net_profit' => $net_profit,
            'payment_methods' => $payment_methods
        ],
        'products' => [
            'top_sellers' => $products,
            'brands' => $brands
        ],
        'performance' => [
            'staff' => $staff_performance,
            'agents' => $agent_performance
        ],
        'distribution' => [
            'channels' => $channels,
            'countries' => $countries
        ],
        'rfm' => [
            'top_spenders' => $top_spenders,
            'recent_customers' => $recent_customers,
            'segmentation_counts' => $segmentation_counts,
            'segmented_users' => array_slice($segmented_users, 0, 20), // Show top 20 segmented
            'system_avg_interval' => $system_avg_interval,
            'repeat_alerts' => array_slice($repeat_alerts, 0, 20) // Show top 20 alerts
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
