<?php
session_start();
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/auth.php";

$page_title            = "Dashboard";
$include_chart         = true;
$include_dashboard_css = true;

// TODO: query จริงจาก DB (ตอนนี้ใส่ mock ไปก่อน)
$todaySales    = 12500;
$monthSales    = 285000;
$totalOrders   = 842;
$totalProducts = 56;
$lowStock      = 3;

$chartLabels  = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];
$chartOrders  = [12, 19, 9, 15, 22, 17, 10];
$chartRevenue = [4500, 8200, 3900, 6100, 12300, 9800, 7200];

$recentOrders = [
    ['id'=>'#10231','customer'=>'John Doe','total'=>850,'status'=>'ชำระเงินแล้ว','date'=>'2025-11-14'],
    ['id'=>'#10230','customer'=>'Sarah','total'=>1250,'status'=>'รอชำระเงิน','date'=>'2025-11-14'],
    ['id'=>'#10229','customer'=>'สมชาย','total'=>590,'status'=>'จัดส่งแล้ว','date'=>'2025-11-13'],
    ['id'=>'#10228','customer'=>'Maria','total'=>1720,'status'=>'ชำระเงินแล้ว','date'=>'2025-11-13'],
    ['id'=>'#10227','customer'=>'Cozy Thai Spa','total'=>3950,'status'=>'กำลังจัดเตรียม','date'=>'2025-11-12'],
];

ob_start();
?>

<h1 class="page-title">Dashboard</h1>

<div class="dashboard-grid">
    <!-- ใช้ card-yakpho + dashboard-card ตาม dashboard.css -->
    <!-- ... (พี่เอาจากเวอร์ชั่นที่เราทำไว้แล้วมาใส่ตรงนี้ได้เลย) ... -->
</div>

<!-- ฯลฯ ส่วนกราฟ + ตาราง -->

<script>
const labels  = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
const orders  = <?= json_encode($chartOrders) ?>;
const revenue = <?= json_encode($chartRevenue) ?>;

// Chart.js เหมือนที่พี่ใช้ก่อนหน้า
</script>

<?php
$content = ob_get_clean();
include __DIR__ . "/includes/layout.php";
