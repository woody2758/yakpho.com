<?php
session_start();
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/auth.php";

$page_title            = "Dashboard";
$include_chart         = true;
$include_dashboard_css = true;

// Helper function to get simple counts
function getDashCount($db, $sql, $params = []) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() ?: 0;
}

// Calculate dates in PHP timezone
$todayStart = date('Y-m-d 00:00:00');
$todayEnd = date('Y-m-d 23:59:59');
$monthStart = date('Y-m-01 00:00:00');
$monthEnd = date('Y-m-t 23:59:59');

// 1. Today's Sales
$todaySales = getDashCount($db, "SELECT SUM(orders_grandtotal) FROM orders WHERE orders_date BETWEEN ? AND ? AND orders_del = 0 AND orders_status NOT IN (8, 9)", [$todayStart, $todayEnd]);

// 2. Month's Sales
$monthSales = getDashCount($db, "SELECT SUM(orders_grandtotal) FROM orders WHERE orders_date BETWEEN ? AND ? AND orders_del = 0 AND orders_status NOT IN (8, 9)", [$monthStart, $monthEnd]);

// 3. Total Orders
$totalOrders = getDashCount($db, "SELECT COUNT(*) FROM orders WHERE orders_del = 0 AND orders_status NOT IN (8, 9)");

// 4. Products Low Stock
try {
    $lowStock = getDashCount($db, "SELECT COUNT(*) FROM product WHERE product_del = 0 AND stock_alert_enabled = 1 AND product_stock <= stock_alert_level");
} catch(Exception $e) {
    try {
        $lowStock = getDashCount($db, "SELECT COUNT(*) FROM product WHERE product_stock <= 5 AND product_del = 0");
    } catch(Exception $e2) {
        $lowStock = getDashCount($db, "SELECT COUNT(*) FROM product WHERE product_del = 0");
    }
}

// 5. Chart Data (Dynamic Period)
$period = $_GET['period'] ?? '7';
$chartLabels = [];
$chartRevenue = [];
$chartTitle = "สถิติยอดขาย 7 วันย้อนหลัง";

if ($period === 'month') {
    $chartTitle = "สถิติยอดขายเดือนนี้";
    $daysInMonth = date('t');
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $date = date("Y-m-") . str_pad($i, 2, '0', STR_PAD_LEFT);
        $chartLabels[] = date('d/m', strtotime($date));
        $dayStart = $date . ' 00:00:00';
        $dayEnd = $date . ' 23:59:59';
        $dailySales = getDashCount($db, "SELECT SUM(orders_grandtotal) FROM orders WHERE orders_date BETWEEN ? AND ? AND orders_del = 0 AND orders_status NOT IN (8, 9)", [$dayStart, $dayEnd]);
        $chartRevenue[] = (float)$dailySales;
    }
} elseif ($period === '30') {
    $chartTitle = "สถิติยอดขาย 30 วันย้อนหลัง";
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('d/m', strtotime($date));
        $dayStart = $date . ' 00:00:00';
        $dayEnd = $date . ' 23:59:59';
        $dailySales = getDashCount($db, "SELECT SUM(orders_grandtotal) FROM orders WHERE orders_date BETWEEN ? AND ? AND orders_del = 0 AND orders_status NOT IN (8, 9)", [$dayStart, $dayEnd]);
        $chartRevenue[] = (float)$dailySales;
    }
} else {
    // Default 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('d/m', strtotime($date));
        $dayStart = $date . ' 00:00:00';
        $dayEnd = $date . ' 23:59:59';
        $dailySales = getDashCount($db, "SELECT SUM(orders_grandtotal) FROM orders WHERE orders_date BETWEEN ? AND ? AND orders_del = 0 AND orders_status NOT IN (8, 9)", [$dayStart, $dayEnd]);
        $chartRevenue[] = (float)$dailySales;
    }
}

// 5.5 CRM & Loyalty Metrics
$dashTotalMembers = (int)getDashCount($db, "SELECT COUNT(*) FROM user WHERE user_del = 0");
$dashVipSpa       = (int)getDashCount($db, "SELECT COUNT(*) FROM user WHERE member_tier = 'vip_spa' AND user_del = 0");
$dashGeneral      = (int)getDashCount($db, "SELECT COUNT(*) FROM user WHERE (member_tier = 'general' OR member_tier IS NULL) AND user_del = 0");
$dashPartners     = (int)getDashCount($db, "SELECT COUNT(*) FROM affiliates WHERE status = 'active'");
$dashTotalPoints  = (int)getDashCount($db, "SELECT SUM(m_stamp_points) FROM user WHERE user_del = 0");
$dashLineLinked   = (int)getDashCount($db, "SELECT COUNT(*) FROM user WHERE line_user_id IS NOT NULL AND line_user_id != '' AND user_del = 0");
$dashUnpaidComm   = (float)getDashCount($db, "SELECT SUM(amount) FROM affiliate_commissions WHERE status = 'unpaid'");

// 6. Recent Orders
$stmt = $db->query("
    SELECT o.orders_no as id, CONCAT(u.user_name, ' ', u.user_lastname) as customer, 
           o.orders_grandtotal as total, 
           COALESCE(st.orsts_name, os.orsts_detail) as status, 
           os.orsts_color as color,
           o.orders_date as date
    FROM orders o
    LEFT JOIN user u ON o.user_id = u.user_id
    LEFT JOIN orsts os ON o.orders_status = os.orsts_id
    LEFT JOIN orsts_translations st ON os.orsts_id = st.orsts_id AND st.lang_code = 'th'
    WHERE o.orders_del = 0
    ORDER BY o.orders_date DESC
    LIMIT 5
");
$recentOrders = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

ob_start();
?>

<div class="container-fluid px-1 px-md-3 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 text-primary fw-bold d-flex align-items-center">
                <i data-lucide="layout-dashboard" class="me-2 text-primary icon-md"></i> ภาพรวมระบบ (Dashboard)
            </h3>
            <p class="text-muted small mb-0 mt-1 ms-4">ข้อมูลสรุปและสถิติการใช้งานระบบทั้งหมด</p>
        </div>
        <div>
            <button class="btn btn-sm btn-outline-primary" onclick="window.location.reload();">
                <i data-lucide="refresh-cw" class="me-1 icon-sm"></i> อัปเดตข้อมูล
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">ยอดขายวันนี้</p>
                            <h3 class="mb-0 fw-bold text-success">฿<?= number_format($todaySales) ?></h3>
                        </div>
                        <div class="p-2 bg-success bg-opacity-10 rounded-3">
                            <i data-lucide="banknote" class="text-success icon-md"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        <span class="text-success fw-bold"><i data-lucide="trending-up" class="icon-sm"></i> +12.5%</span> เทียบกับเมื่อวาน
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">คำสั่งซื้อทั้งหมด</p>
                            <h3 class="mb-0 fw-bold text-primary"><?= number_format($totalOrders) ?></h3>
                        </div>
                        <div class="p-2 bg-primary bg-opacity-10 rounded-3">
                            <i data-lucide="shopping-bag" class="text-primary icon-md"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        <span class="text-success fw-bold"><i data-lucide="trending-up" class="icon-sm"></i> +5.2%</span> เทียบกับเดือนที่แล้ว
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">ยอดขายเดือนนี้</p>
                            <h3 class="mb-0 fw-bold text-info">฿<?= number_format($monthSales) ?></h3>
                        </div>
                        <div class="p-2 bg-info bg-opacity-10 rounded-3">
                            <i data-lucide="wallet" class="text-info icon-md"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        รอสรุปยอดสิ้นเดือน
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">สินค้าใกล้หมดสต๊อก</p>
                            <h3 class="mb-0 fw-bold text-danger"><?= number_format($lowStock) ?></h3>
                        </div>
                        <div class="p-2 bg-danger bg-opacity-10 rounded-3">
                            <i data-lucide="alert-triangle" class="text-danger icon-md"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small">
                        <a href="#" class="text-danger text-decoration-none">คลิกเพื่อดูรายละเอียด <i data-lucide="arrow-right" class="icon-xs"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Area -->
    <div class="row g-3">
        <!-- Sales Chart -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><?= $chartTitle ?></h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <?php
                            if ($period === '30') echo '30 วันล่าสุด';
                            elseif ($period === 'month') echo 'เดือนนี้';
                            else echo '7 วันล่าสุด';
                            ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 small">
                            <li><a class="dropdown-item" href="?period=7">7 วันล่าสุด</a></li>
                            <li><a class="dropdown-item" href="?period=30">30 วันล่าสุด</a></li>
                            <li><a class="dropdown-item" href="?period=month">เดือนนี้</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">คำสั่งซื้อล่าสุด</h6>
                    <a href="<?= ADMIN_URL ?>/orders/index.php" class="text-primary text-decoration-none small">ดูทั้งหมด</a>
                </div>
                <div class="card-body p-0 mt-3">
                    <div class="list-group list-group-flush border-0">
                        <?php foreach($recentOrders as $order): 
                            $statusColor = $order['color'] ?: '#6c757d';
                        ?>
                        <div class="list-group-item border-0 px-4 py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold mb-1"><?= $order['id'] ?> <span class="badge bg-light text-dark border ms-2"><?= htmlspecialchars($order['status'] ?: 'ไม่ระบุสถานะ') ?></span></div>
                                <div class="small text-muted"><?= $order['customer'] ?> • <?= $order['date'] ?></div>
                            </div>
                            <div class="fw-bold text-success">
                                ฿<?= number_format($order['total']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide) {
        lucide.createIcons();
    }

    const labels  = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
    const revenue = <?= json_encode($chartRevenue) ?>;

    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Create gradient
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(52, 152, 219, 0.5)');   
    gradient.addColorStop(1, 'rgba(52, 152, 219, 0.05)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'ยอดขาย (บาท)',
                data: revenue,
                borderColor: '#3498db',
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#3498db',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 13, family: 'Inter' },
                    bodyFont: { size: 14, family: 'Inter', weight: 'bold' },
                    callbacks: {
                        label: function(context) {
                            return ' ฿' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . "/includes/layout.php";
