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
        } catch (Exception $e) {}
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
    // 1. Financial KPI metrics
    $stmt_fin = $db->prepare("
        SELECT 
            COUNT(o.orders_id) AS total_orders,
            SUM(o.orders_total) AS total_subtotal,
            SUM(o.orders_discount) AS total_discount,
            SUM(o.orders_delicost) AS total_delicost,
            SUM(o.orders_delicosttrue) AS total_delicosttrue,
            SUM(o.orders_grandtotal) AS total_grandtotal
        FROM orders o
        WHERE $baseWhere
    ");
    $stmt_fin->execute($params);
    $fin = $stmt_fin->fetch(PDO::FETCH_ASSOC);

    $total_orders = (int)($fin['total_orders'] ?? 0);
    $total_subtotal = (float)($fin['total_subtotal'] ?? 0);
    $total_discount = (float)($fin['total_discount'] ?? 0);
    $total_delicost = (float)($fin['total_delicost'] ?? 0);
    $total_delicosttrue = (float)($fin['total_delicosttrue'] ?? 0);
    $total_grandtotal = (float)($fin['total_grandtotal'] ?? 0);

    // Total COGS
    $stmt_cogs = $db->prepare("
        SELECT SUM(od.item_cost * od.ordetail_qty) AS total_cogs
        FROM ordetail od
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE $baseWhere
    ");
    $stmt_cogs->execute($params);
    $total_cogs = (float)($stmt_cogs->fetchColumn() ?? 0);

    // Profit Calculations
    $shipping_paid = $total_delicosttrue > 0 ? $total_delicosttrue : $total_delicost;
    $shipping_margin = $total_delicost - $shipping_paid;
    $gross_profit = $total_subtotal - $total_cogs - $total_discount;
    $net_profit = $gross_profit + $shipping_margin;
    $aov = $total_orders > 0 ? ($total_grandtotal / $total_orders) : 0;

    $kpis = [
        'total_orders' => $total_orders,
        'gross_revenue' => $total_grandtotal,
        'total_cogs' => $total_cogs,
        'gross_profit' => $gross_profit,
        'net_profit' => $net_profit,
        'aov' => $aov,
        'shipping_profit' => $shipping_margin
    ];

    // 2. Trends: Grouped by day or month
    $groupBy = "DATE(o.orders_date)";
    $dateFormat = "%Y-%m-%d";
    
    if (in_array($range, ['last3months', 'last6months', 'thisyear', 'all'])) {
        $groupBy = "DATE_FORMAT(o.orders_date, '%Y-%m')";
        $dateFormat = "%Y-%m";
    }

    // Daily/Monthly Sales Trend
    $stmt_trend = $db->prepare("
        SELECT 
            DATE_FORMAT(o.orders_date, '$dateFormat') AS period,
            SUM(o.orders_grandtotal) AS revenue,
            SUM(o.orders_total) AS subtotal,
            SUM(o.orders_delicost) AS delicost,
            SUM(o.orders_delicosttrue) AS delicosttrue,
            SUM(o.orders_discount) AS discount
        FROM orders o
        WHERE $baseWhere
        GROUP BY $groupBy
        ORDER BY period ASC
    ");
    $stmt_trend->execute($params);
    $trend_sales = $stmt_trend->fetchAll(PDO::FETCH_ASSOC);

    // Daily/Monthly COGS Trend
    $stmt_trend_cogs = $db->prepare("
        SELECT 
            DATE_FORMAT(o.orders_date, '$dateFormat') AS period,
            SUM(od.item_cost * od.ordetail_qty) AS cogs
        FROM ordetail od
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE $baseWhere
        GROUP BY $groupBy
    ");
    $stmt_trend_cogs->execute($params);
    $trend_cogs = $stmt_trend_cogs->fetchAll(PDO::FETCH_KEY_PAIR);

    $trends = [];
    foreach ($trend_sales as $ts) {
        $period = $ts['period'];
        $rev = (float)$ts['revenue'];
        $sub = (float)$ts['subtotal'];
        $cogs = (float)($trend_cogs[$period] ?? 0);
        $disc = (float)$ts['discount'];
        
        $dc = (float)$ts['delicost'];
        $dct = (float)$ts['delicosttrue'];
        $dc_paid = $dct > 0 ? $dct : $dc;
        $ship_margin = $dc - $dc_paid;
        
        $profit = ($sub - $cogs - $disc) + $ship_margin;

        $trends[] = [
            'period' => $period,
            'revenue' => $rev,
            'profit' => $profit
        ];
    }

    // 3. Channels Distribution
    $stmt_chan = $db->prepare("
        SELECT 
            COALESCE(sc.channel_name, 'ไม่ระบุ') AS name,
            SUM(o.orders_grandtotal) AS value
        FROM orders o
        LEFT JOIN sales_channels sc ON o.orders_channel = sc.channel_code
        WHERE $baseWhere
        GROUP BY o.orders_channel
        ORDER BY value DESC
    ");
    $stmt_chan->execute($params);
    $channels = $stmt_chan->fetchAll(PDO::FETCH_ASSOC);

    // 4. Main Category Distribution
    $stmt_cat = $db->prepare("
        SELECT 
            COALESCE(mct.maincat_name, mc.maincat_slug, 'ไม่ระบุ') AS name,
            SUM(od.ordetail_qty * od.product_price) AS value
        FROM ordetail od
        JOIN product p ON od.product_id = p.product_id
        JOIN productcat pc ON p.productcat_id = pc.productcat_id
        JOIN maincat mc ON pc.maincat_id = mc.maincat_id
        LEFT JOIN maincat_translations mct ON mc.maincat_id = mct.maincat_id AND mct.lang_code = 'th'
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE $baseWhere
        GROUP BY mc.maincat_id
        ORDER BY value DESC
    ");
    $stmt_cat->execute($params);
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // 5. Top Products
    $stmt_prod = $db->prepare("
        SELECT 
            p.product_name AS name,
            SUM(od.ordetail_qty * od.product_price) AS value
        FROM ordetail od
        JOIN product p ON od.product_id = p.product_id
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE $baseWhere
        GROUP BY p.product_id
        ORDER BY value DESC
        LIMIT 10
    ");
    $stmt_prod->execute($params);
    $top_products = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

    // 6. Countries Distribution
    $stmt_coun = $db->prepare("
        SELECT 
            CASE WHEN o.orders_country_id = 0 THEN 'ยังไม่ได้ระบุ' ELSE COALESCE(c.country_name, 'ยังไม่ได้ระบุ') END AS name,
            SUM(o.orders_grandtotal) AS value
        FROM orders o
        LEFT JOIN countries c ON o.orders_country_id = c.country_id
        WHERE $baseWhere
        GROUP BY o.orders_country_id
        ORDER BY value DESC
        LIMIT 10
    ");
    $stmt_coun->execute($params);
    $countries = $stmt_coun->fetchAll(PDO::FETCH_ASSOC);

    // 7. Matrices: Staff Matrix
    $stmt_staff_orders = $db->prepare("
        SELECT 
            o.save_id,
            CONCAT(u.user_name, ' ', COALESCE(u.user_lastname, '')) AS name,
            u.user_nickname,
            u.user_picture,
            COUNT(o.orders_id) AS orders_count,
            SUM(o.orders_grandtotal) AS total_sales
        FROM orders o
        JOIN user u ON o.save_id = u.user_id
        WHERE $baseWhere
        GROUP BY o.save_id
    ");
    $stmt_staff_orders->execute($params);
    $staff_orders = $stmt_staff_orders->fetchAll(PDO::FETCH_ASSOC);

    $stmt_staff_cogs = $db->prepare("
        SELECT 
            o.save_id,
            SUM(od.ordetail_qty * od.item_cost) AS total_cogs,
            SUM(od.ordetail_qty * od.product_price) AS subtotal
        FROM ordetail od
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE $baseWhere
        GROUP BY o.save_id
    ");
    $stmt_staff_cogs->execute($params);
    $staff_cogs = $stmt_staff_cogs->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    $matrix_staff = [];
    foreach ($staff_orders as $so) {
        $sid = $so['save_id'];
        $orders_count = (int)$so['orders_count'];
        $sales = (float)$so['total_sales'];
        $aov_staff = $orders_count > 0 ? ($sales / $orders_count) : 0;
        
        $scogs = (float)($staff_cogs[$sid]['total_cogs'] ?? 0);
        $ssub = (float)($staff_cogs[$sid]['subtotal'] ?? 0);
        $profit_staff = $ssub - $scogs;

        $matrix_staff[] = [
            'name' => $so['name'] . ($so['user_nickname'] ? ' (' . $so['user_nickname'] . ')' : ''),
            'picture' => $so['user_picture'],
            'user_id' => $sid,
            'orders' => $orders_count,
            'sales' => $sales,
            'aov' => $aov_staff,
            'profit' => $profit_staff
        ];
    }
    
    usort($matrix_staff, function($a, $b) {
        return $b['sales'] <=> $a['sales'];
    });

    // 8. Matrices: Shipping Margin Matrix
    $stmt_ship_matrix = $db->prepare("
        SELECT 
            o.orders_channel,
            COALESCE(sc.channel_name, 'ไม่ระบุ') AS channel_name,
            SUM(o.orders_delicost) AS total_delicost,
            SUM(o.orders_delicosttrue) AS total_delicosttrue,
            COUNT(o.orders_id) AS orders_count
        FROM orders o
        LEFT JOIN sales_channels sc ON o.orders_channel = sc.channel_code
        WHERE $baseWhere
        GROUP BY o.orders_channel
        ORDER BY total_delicost DESC
    ");
    $stmt_ship_matrix->execute($params);
    $matrix_shipping = $stmt_ship_matrix->fetchAll(PDO::FETCH_ASSOC);

    // 9. Matrices: Product Sub-cat Matrix
    $stmt_prod_matrix = $db->prepare("
        SELECT 
            pc.productcat_name AS subcat_name,
            p.product_name,
            p.product_code,
            SUM(od.ordetail_qty) AS qty_sold,
            SUM(od.ordetail_qty * od.product_price) AS gross_sales,
            SUM(od.ordetail_qty * od.item_cost) AS total_cogs
        FROM ordetail od
        JOIN product p ON od.product_id = p.product_id
        JOIN productcat pc ON p.productcat_id = pc.productcat_id
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE $baseWhere
        GROUP BY p.product_id
        ORDER BY qty_sold DESC
        LIMIT 50
    ");
    $stmt_prod_matrix->execute($params);
    $matrix_products = $stmt_prod_matrix->fetchAll(PDO::FETCH_ASSOC);

    // 10. Matrices: Monthly Channel Pivot Matrix
    $stmt_monthly_chan = $db->prepare("
        SELECT 
            DATE_FORMAT(o.orders_date, '%Y-%m') AS month_period,
            o.orders_channel,
            COALESCE(sc.channel_name, 'ไม่ระบุ') AS channel_name,
            SUM(o.orders_grandtotal) AS monthly_sales
        FROM orders o
        LEFT JOIN sales_channels sc ON o.orders_channel = sc.channel_code
        WHERE $baseWhere
        GROUP BY DATE_FORMAT(o.orders_date, '%Y-%m'), o.orders_channel
        ORDER BY month_period ASC, monthly_sales DESC
    ");
    $stmt_monthly_chan->execute($params);
    $monthly_raw = $stmt_monthly_chan->fetchAll(PDO::FETCH_ASSOC);

    $months = [];
    $channels_data = [];
    foreach ($monthly_raw as $row) {
        $m = $row['month_period'];
        $ch_name = $row['channel_name'];
        if (!in_array($m, $months)) {
            $months[] = $m;
        }
        if (!isset($channels_data[$ch_name])) {
            $channels_data[$ch_name] = [];
        }
        $channels_data[$ch_name][$m] = (float)$row['monthly_sales'];
    }
    sort($months);

    $matrix_monthly_channels = [
        'months' => $months,
        'channels' => []
    ];
    foreach ($channels_data as $ch_name => $m_data) {
        $sales_by_month = [];
        $total_row_sales = 0.0;
        foreach ($months as $m) {
            $val = $m_data[$m] ?? 0.0;
            $sales_by_month[] = $val;
            $total_row_sales += $val;
        }
        $matrix_monthly_channels['channels'][] = [
            'name' => $ch_name,
            'sales' => $sales_by_month,
            'total' => $total_row_sales
        ];
    }
    
    usort($matrix_monthly_channels['channels'], function($a, $b) {
        return $b['total'] <=> $a['total'];
    });

    // 11. Matrices: Monthly KPIs Pivot Matrix
    $stmt_monthly_kpis = $db->prepare("
        SELECT 
            DATE_FORMAT(o.orders_date, '%Y-%m') AS month_period,
            COUNT(o.orders_id) AS total_orders,
            SUM(o.orders_grandtotal) AS gross_revenue,
            SUM(o.orders_total) AS subtotal,
            SUM(o.orders_discount) AS discount,
            SUM(o.orders_delicost) AS delicost,
            SUM(o.orders_delicosttrue) AS delicosttrue
        FROM orders o
        WHERE $baseWhere
        GROUP BY month_period
        ORDER BY month_period ASC
    ");
    $stmt_monthly_kpis->execute($params);
    $monthly_kpis_raw = $stmt_monthly_kpis->fetchAll(PDO::FETCH_ASSOC);

    // We also need COGS by month for KPIs
    $stmt_monthly_cogs = $db->prepare("
        SELECT 
            DATE_FORMAT(o.orders_date, '%Y-%m') AS month_period,
            SUM(od.item_cost * od.ordetail_qty) AS cogs
        FROM ordetail od
        JOIN orders o ON od.orders_id = o.orders_id
        WHERE $baseWhere
        GROUP BY month_period
    ");
    $stmt_monthly_cogs->execute($params);
    $monthly_cogs_raw = $stmt_monthly_cogs->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $matrix_monthly_kpis = [
        'months' => [],
        'data' => [
            'total_orders' => [],
            'gross_revenue' => [],
            'total_cogs' => [],
            'gross_profit' => [],
            'aov' => [],
            'shipping_profit' => []
        ],
        'totals' => [
            'total_orders' => 0,
            'gross_revenue' => 0,
            'total_cogs' => 0,
            'gross_profit' => 0,
            'aov' => 0,
            'shipping_profit' => 0
        ]
    ];
    
    foreach ($monthly_kpis_raw as $row) {
        $m = $row['month_period'];
        $matrix_monthly_kpis['months'][] = $m;
        
        $orders = (int)$row['total_orders'];
        $rev = (float)$row['gross_revenue'];
        $sub = (float)$row['subtotal'];
        $disc = (float)$row['discount'];
        $dc = (float)$row['delicost'];
        $dct = (float)$row['delicosttrue'];
        
        $cogs = (float)($monthly_cogs_raw[$m] ?? 0);
        
        $dc_paid = $dct > 0 ? $dct : $dc;
        $ship_margin = $dc - $dc_paid;
        $gp = $sub - $cogs - $disc;
        $aov = $orders > 0 ? $rev / $orders : 0;
        
        $matrix_monthly_kpis['data']['total_orders'][] = $orders;
        $matrix_monthly_kpis['data']['gross_revenue'][] = $rev;
        $matrix_monthly_kpis['data']['total_cogs'][] = $cogs;
        $matrix_monthly_kpis['data']['gross_profit'][] = $gp;
        $matrix_monthly_kpis['data']['aov'][] = $aov;
        $matrix_monthly_kpis['data']['shipping_profit'][] = $ship_margin;
        
        $matrix_monthly_kpis['totals']['total_orders'] += $orders;
        $matrix_monthly_kpis['totals']['gross_revenue'] += $rev;
        $matrix_monthly_kpis['totals']['total_cogs'] += $cogs;
        $matrix_monthly_kpis['totals']['gross_profit'] += $gp;
        $matrix_monthly_kpis['totals']['shipping_profit'] += $ship_margin;
    }
    if ($matrix_monthly_kpis['totals']['total_orders'] > 0) {
        $matrix_monthly_kpis['totals']['aov'] = $matrix_monthly_kpis['totals']['gross_revenue'] / $matrix_monthly_kpis['totals']['total_orders'];
    }

    echo json_encode([
        'success' => true,
        'kpis' => $kpis,
        'trends' => $trends,
        'channels' => $channels,
        'categories' => $categories,
        'top_products' => $top_products,
        'countries' => $countries,
        'matrix_staff' => $matrix_staff,
        'matrix_shipping' => $matrix_shipping,
        'matrix_products' => $matrix_products,
        'matrix_monthly_channels' => $matrix_monthly_channels,
        'matrix_monthly_kpis' => $matrix_monthly_kpis
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
