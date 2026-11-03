<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$page_title            = "รายงานสถิติการขาย";
$include_chart         = true;
$include_dashboard_css = false;

// Fetch Main Categories
$stmt_mc = $db->query("
    SELECT c.maincat_id, ct_th.maincat_name 
    FROM maincat c
    LEFT JOIN maincat_translations ct_th ON c.maincat_id = ct_th.maincat_id AND ct_th.lang_code = 'th'
    WHERE c.maincat_del = 0
    ORDER BY c.maincat_index ASC
");
$maincats = $stmt_mc->fetchAll(PDO::FETCH_ASSOC);

// Determine selected maincat name (Default 1)
$defaultMaincatId = 1;
$selectedMaincatName = 'ทุกหมวดหมู่ (All)';
foreach ($maincats as $mc) {
    if ($mc['maincat_id'] == $defaultMaincatId) {
        $selectedMaincatName = $mc['maincat_name'] ?: 'Unknown';
        break;
    }
}

ob_start();
?>

<div class="statistics-container pb-5" style="position: relative;">

    <!-- Statistics Loading Overlay -->
    <div id="statsLoadingOverlay" style="
        display: none;
        position: absolute;
        inset: 0;
        z-index: 100;
        background: rgba(255, 255, 255, 0.60);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        border-radius: 16px;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        gap: 12px;
    ">
        <div class="stats-spinner"></div>
        <span class="text-muted fw-semibold small" id="statsLoadingLabel">กำลังโหลดสถิติ...</span>
    </div>
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="page-title mb-1" style="font-size: 1.75rem; font-weight: 600;">
                <i data-lucide="trending-up" class="me-2 text-primary" style="vertical-align: middle;"></i>รายงานสถิติการขาย
            </h1>
            <p class="text-muted small mb-0">วิเคราะห์ผลประกอบการ กำไรสุทธิ ผลงานทีมขาย และประสิทธิภาพช่องทางแบบเชิงลึก</p>
        </div>
        
        <!-- Filter Controls -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Main Category Selector -->
            <div class="dropdown">
                <button class="btn btn-white border dropdown-toggle shadow-sm fw-bold d-flex align-items-center gap-2" type="button" id="maincatBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i data-lucide="layers" style="width: 16px; height: 16px;" class="text-primary"></i>
                    <span id="selectedMaincatLabel"><?= htmlspecialchars($selectedMaincatName) ?></span>
                </button>
                <ul class="dropdown-menu shadow border-0" aria-labelledby="maincatBtn" style="border-radius: 12px; min-width: 200px;">
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeMaincat(0, 'ทุกหมวดหมู่ (All)')">ทุกหมวดหมู่ (All)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php foreach ($maincats as $mc): ?>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeMaincat(<?= $mc['maincat_id'] ?>, '<?= htmlspecialchars($mc['maincat_name'], ENT_QUOTES) ?>')"><?= htmlspecialchars($mc['maincat_name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Date Range Selector -->
            <div class="dropdown">
                <button class="btn btn-white border dropdown-toggle shadow-sm fw-bold d-flex align-items-center gap-2" type="button" id="dateRangeBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i data-lucide="calendar" style="width: 16px; height: 16px;"></i>
                    <span id="selectedRangeLabel">30 วันล่าสุด</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="dateRangeBtn" style="border-radius: 12px; min-width: 200px;">
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeRange('today', 'วันนี้')">วันนี้</a></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeRange('yesterday', 'เมื่อวาน')">เมื่อวาน</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeRange('last7days', '7 วันล่าสุด')">7 วันล่าสุด</a></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeRange('last30days', '30 วันล่าสุด')">30 วันล่าสุด</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeRange('thismonth', 'เดือนนี้')">เดือนนี้</a></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeRange('lastmonth', 'เดือนที่แล้ว')">เดือนที่แล้ว</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeRange('last3months', '3 เดือนล่าสุด')">3 เดือนล่าสุด</a></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeRange('last6months', '6 เดือนล่าสุด')">6 เดือนล่าสุด</a></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeRange('thisyear', 'ปีนี้')">ปีนี้</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="changeRange('all', 'ยอดสะสมทั้งหมด')">ยอดสะสมทั้งหมด</a></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="javascript:void(0)" onclick="toggleCustomDateInputs()">กำหนดช่วงเวลาเอง...</a></li>
                </ul>
            </div>
            
            <div id="customDateContainer" class="d-none align-items-center gap-2 border rounded p-1 bg-white shadow-sm">
                <input type="date" id="startDateInput" class="form-control form-control-sm border-0" style="width: 130px;">
                <span class="text-muted small">ถึง</span>
                <input type="date" id="endDateInput" class="form-control form-control-sm border-0" style="width: 130px;">
                <button class="btn btn-sm btn-primary rounded" onclick="applyCustomRange()">กรอง</button>
            </div>
        </div>
    </div>

    <!-- 1. KPI Cards Row (Glassmorphism design) -->
    <div class="row g-3 mb-4">
        <!-- จำนวนออเดอร์ (Total Orders) -->
        <div class="col-lg col-md-4 col-sm-6">
            <div class="kpi-card shadow-sm border p-3 rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="kpi-icon-wrapper rounded-circle bg-dark bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                    <i data-lucide="shopping-cart" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">จำนวนออเดอร์</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark" id="kpi-orders">0</h4>
                </div>
            </div>
        </div>
        <!-- ยอดขายรวม (Gross Revenue) -->
        <div class="col-lg col-md-4 col-sm-6">
            <div class="kpi-card shadow-sm border p-3 rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="kpi-icon-wrapper rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                    <i data-lucide="shopping-bag" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">ยอดขายรวม</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark" id="kpi-revenue">0.00</h4>
                </div>
            </div>
        </div>
        <!-- ต้นทุนสินค้า (Total COGS) -->
        <div class="col-lg col-md-4 col-sm-6">
            <div class="kpi-card shadow-sm border p-3 rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="kpi-icon-wrapper rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                    <i data-lucide="wallet" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium" id="kpi-cogs-label">ต้นทุนขาย (COGS)</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark" id="kpi-cogs">0.00</h4>
                </div>
            </div>
        </div>
        <!-- กำไรจากสินค้า (Gross Profit) -->
        <div class="col-lg col-md-4 col-sm-6">
            <div class="kpi-card shadow-sm border p-3 rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="kpi-icon-wrapper rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                    <i data-lucide="trending-up" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium" id="kpi-gp-label">กำไรขั้นต้น (GP)</span>
                    <h4 class="mb-0 fw-bold mt-1 text-success" id="kpi-gross-profit">0.00</h4>
                </div>
            </div>
        </div>
        <!-- ยอดสั่งซื้อเฉลี่ย (AOV) -->
        <div class="col-lg col-md-6 col-sm-6">
            <div class="kpi-card shadow-sm border p-3 rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="kpi-icon-wrapper rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                    <i data-lucide="calculator" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">เฉลี่ยต่อออเดอร์ (AOV)</span>
                    <h4 class="mb-0 fw-bold mt-1 text-info" id="kpi-aov">0.00</h4>
                </div>
            </div>
        </div>
        <!-- ส่วนต่างกำไรค่าส่ง (Shipping Margin) -->
        <div class="col-lg col-md-6 col-sm-6">
            <div class="kpi-card shadow-sm border p-3 rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="kpi-icon-wrapper rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                    <i data-lucide="truck" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">กำไรจากค่าจัดส่ง</span>
                    <h4 class="mb-0 fw-bold mt-1 text-warning" id="kpi-shipping-profit">0.00</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Charts Section -->
    <!-- Row 1: Line Chart (Sales & Profit) & Channels Doughnut -->
    <div class="row g-4 mb-4">
        <!-- Sales & Profit Trend -->
        <div class="col-xl-8 col-lg-7">
            <div class="card border shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">แนวโน้มยอดขายและกำไรสะสม</h5>
                        <p class="text-muted small mb-0">เปรียบเทียบยอดขายรวม (Revenue) และกำไรสุทธิ (Net Profit) ตามช่วงเวลา</p>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <div style="height: 320px; position: relative;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sales by Channel -->
        <div class="col-xl-4 col-lg-5">
            <div class="card border shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-1 text-dark">สัดส่วนช่องทางการขาย</h5>
                    <p class="text-muted small mb-0">โครงสร้างสัดส่วนรายได้แยกตามช่องทางการขาย</p>
                </div>
                <div class="card-body px-4 pb-4 d-flex flex-column justify-content-center">
                    <div style="height: 220px; position: relative;" class="mb-3">
                        <canvas id="channelChart"></canvas>
                    </div>
                    <div id="channelLegend" class="d-flex flex-wrap justify-content-center gap-2 small"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Categories, Top Products, Countries (3 Columns) -->
    <div class="row g-4 mb-4">
        <!-- Categories -->
        <div class="col-lg-4 col-md-6">
            <div class="card border shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-1 text-dark">สัดส่วนยอดขายตามหมวดหมู่หลัก</h5>
                    <p class="text-muted small mb-0">เปรียบเทียบความนิยมของแบรนด์/กลุ่มสินค้า</p>
                </div>
                <div class="card-body px-4 pb-4 d-flex flex-column justify-content-center">
                    <div style="height: 200px; position: relative;" class="mb-3">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div id="categoryLegend" class="d-flex flex-wrap justify-content-center gap-2 small"></div>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-lg-4 col-md-6">
            <div class="card border shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-1 text-dark">10 อันดับสินค้าขายดี</h5>
                    <p class="text-muted small mb-0">เรียงตามยอดขายสะสมสุทธิ</p>
                </div>
                <div class="card-body px-4 pb-4">
                    <div style="height: 250px; position: relative;">
                        <canvas id="productsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Countries -->
        <div class="col-lg-4 col-md-12">
            <div class="card border shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-1 text-dark">ยอดขายรายประเทศ</h5>
                    <p class="text-muted small mb-0">วิเคราะห์การกระจายตัวตลาดลูกค้าทั่วโลก</p>
                </div>
                <div class="card-body px-4 pb-4 d-flex flex-column justify-content-center">
                    <div style="height: 200px; position: relative;" class="mb-3">
                        <canvas id="countryChart"></canvas>
                    </div>
                    <div id="countryLegend" class="d-flex flex-wrap justify-content-center gap-2 small"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Matrix & Analytics Tables Tabs Section -->
    <div class="card border shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-transparent border-0 p-0">
            <ul class="nav nav-tabs nav-justified border-bottom" id="matrixTabs" role="tablist" style="background: rgba(0, 0, 0, 0.02);">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3 fw-bold border-0 text-dark d-flex align-items-center justify-content-center gap-2" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff-pane" type="button" role="tab" aria-controls="staff-pane" aria-selected="true">
                        <i data-lucide="users" style="width: 18px; height: 18px;" class="text-primary"></i>
                        ผลงานพนักงานขาย
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 text-dark d-flex align-items-center justify-content-center gap-2" id="products-tab" data-bs-toggle="tab" data-bs-target="#products-pane" type="button" role="tab" aria-controls="products-pane" aria-selected="false">
                        <i data-lucide="box" style="width: 18px; height: 18px;" class="text-success"></i>
                        วิเคราะห์อัตรากำไรสินค้า
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 text-dark d-flex align-items-center justify-content-center gap-2" id="kpis-monthly-tab" data-bs-toggle="tab" data-bs-target="#kpis-monthly-pane" type="button" role="tab" aria-controls="kpis-monthly-pane" aria-selected="false">
                        <i data-lucide="bar-chart-2" style="width: 18px; height: 18px;" class="text-primary"></i>
                        สถิติภาพรวม 12 เดือน
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 text-dark d-flex align-items-center justify-content-center gap-2" id="channel-tab" data-bs-toggle="tab" data-bs-target="#channel-pane" type="button" role="tab" aria-controls="channel-pane" aria-selected="false">
                        <i data-lucide="calendar" style="width: 18px; height: 18px;" class="text-info"></i>
                        ยอดขายช่องทางรายเดือน
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 text-dark d-flex align-items-center justify-content-center gap-2" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping-pane" type="button" role="tab" aria-controls="shipping-pane" aria-selected="false">
                        <i data-lucide="truck" style="width: 18px; height: 18px;" class="text-warning"></i>
                        ส่วนต่างค่าจัดส่ง
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body p-0">
            <div class="tab-content" id="matrixTabContent">
                <!-- Tab 1: Staff Performance -->
                <div class="tab-pane fade show active" id="staff-pane" role="tabpanel" aria-labelledby="staff-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">พนักงานขาย / แอดมิน</th>
                                    <th class="text-center">จำนวนออเดอร์</th>
                                    <th class="text-end">ยอดขายรวมสุทธิ</th>
                                    <th class="text-end">ยอดเฉลี่ยต่อออเดอร์ (AOV)</th>
                                    <th class="text-end pe-4">ประมาณการกำไรขั้นต้น (GP)</th>
                                </tr>
                            </thead>
                            <tbody id="matrixStaffTableBody">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: Products GP Matrix -->
                <div class="tab-pane fade" id="products-pane" role="tabpanel" aria-labelledby="products-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="min-width: 900px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">หมวดหมู่ย่อย</th>
                                    <th>ชื่อสินค้า / รหัสสินค้า</th>
                                    <th class="text-center">จำนวนที่ขายได้ (ชิ้น)</th>
                                    <th class="text-end">ยอดขายรวมสุทธิ</th>
                                    <th class="text-end">ต้นทุนรวม (COGS)</th>
                                    <th class="text-end">กำไรสุทธิ</th>
                                    <th class="text-end pe-4">อัตรากำไร (GP %)</th>
                                </tr>
                            </thead>
                            <tbody id="matrixProductsTableBody">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: Monthly KPIs Pivot -->
                <div class="tab-pane fade" id="kpis-monthly-pane" role="tabpanel" aria-labelledby="kpis-monthly-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="matrixMonthlyKpisTable" style="min-width: 800px;">
                            <thead class="table-light">
                                <tr id="matrixMonthlyKpisHeaderRow">
                                    <th class="ps-4">รายการ</th>
                                    <!-- Dynamic month headers -->
                                    <th class="text-end pe-4">รวมสะสม</th>
                                </tr>
                            </thead>
                            <tbody id="matrixMonthlyKpisTableBody">
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 4: Monthly Channel Pivot -->
                <div class="tab-pane fade" id="channel-pane" role="tabpanel" aria-labelledby="channel-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="matrixMonthlyChannelTable" style="min-width: 800px;">
                            <thead class="table-light">
                                <tr id="matrixMonthlyHeaderRow">
                                    <th class="ps-4">ช่องทางจัดจำหน่าย</th>
                                    <!-- Dynamic month headers -->
                                    <th class="text-end pe-4">ยอดขายรวมสะสม</th>
                                </tr>
                            </thead>
                            <tbody id="matrixMonthlyChannelTableBody">
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 4: Shipping Optimization -->
                <div class="tab-pane fade" id="shipping-pane" role="tabpanel" aria-labelledby="shipping-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ช่องทาง</th>
                                    <th class="text-center">จำนวนออเดอร์</th>
                                    <th class="text-end">ค่าจัดส่งเก็บจากลูกค้า</th>
                                    <th class="text-end">ค่าส่งจ่ายจริงให้ขนส่ง</th>
                                    <th class="text-end">ส่วนต่างกำไร/ขาดทุน</th>
                                    <th class="text-end pe-4">สถานะความคุ้มทุน</th>
                                </tr>
                            </thead>
                            <tbody id="matrixShippingTableBody">
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Customer Profile Details (Reused for consistency) -->
<div class="modal fade" id="customerHistoryModal" tabindex="-1" aria-labelledby="customerHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0 px-4">
                <!-- Customer info card -->
                <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-4 mb-4 pb-4 border-bottom">
                    <div class="rounded-circle overflow-hidden border border-secondary border-opacity-25 shadow bg-white" style="width: 110px; height: 110px; flex-shrink:0;">
                        <img id="history-cust-pic" src="" alt="Customer" class="w-100 h-100" style="object-fit: cover;">
                    </div>
                    <div class="flex-grow-1 text-center text-sm-start">
                        <span class="badge bg-primary bg-opacity-10 text-primary border mb-2 fw-bold" style="font-size: 11px;" id="history-cust-id">C000000</span>
                        <h4 class="fw-bold mb-2 text-dark" id="history-cust-name">-</h4>
                        <div class="text-muted small" id="history-cust-contact">
                            <!-- Populated dynamically -->
                        </div>
                        <div class="d-flex flex-wrap justify-content-center justify-content-sm-start gap-2 mt-3">
                            <span class="badge bg-secondary bg-opacity-10 text-dark border small py-1.5 px-3 fw-bold" id="history-cust-channel">ช่องทาง: -</span>
                            <span class="badge bg-success bg-opacity-10 text-success border small py-1.5 px-3 fw-bold" id="history-cust-country">ประเทศ: -</span>
                        </div>
                    </div>
                    <div class="text-center text-sm-end style-ltv bg-light p-3 rounded-4 border" style="min-width: 160px;">
                        <span class="text-muted small d-block">ยอดซื้อสะสมรวม</span>
                        <span class="fs-4 fw-bold text-success mt-1 d-block" id="history-cust-total">0.00 บาท</span>
                    </div>
                </div>

                <!-- Orders history list -->
                <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2"><i data-lucide="list" style="width: 18px; height: 18px;" class="text-primary"></i> รายการคำสั่งซื้อทั้งหมดที่สำเร็จ (Excluding Cancelled)</h5>
                <div class="table-responsive border rounded-3 mb-4" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>เลขที่ออเดอร์</th>
                                <th class="text-center">วันที่สั่งซื้อ</th>
                                <th>ช่องทาง</th>
                                <th>รายการสินค้า</th>
                                <th class="text-end">ยอดสั่งซื้อ</th>
                            </tr>
                        </thead>
                        <tbody id="historyOrdersTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">ไม่มีข้อมูลประวัติ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Profile Pic History feed -->
                <h5 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2"><i data-lucide="image" style="width: 18px; height: 18px;" class="text-primary"></i> ประวัติการเปลี่ยนรูปโปรไฟล์ลูกค้า (Profile Picture History)</h5>
                <div class="d-flex align-items-start gap-3 overflow-x-auto py-2 px-1 mb-2 border rounded-3 bg-light" id="history-profile-pics" style="min-height: 110px;">
                    <span class="text-muted small">ไม่มีประวัติรูปโปรไฟล์อื่นๆ</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Style enhancements */
.kpi-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.06) !important;
}
.btn-white {
    background-color: #fff;
    color: #374151;
}
.cursor-pointer {
    cursor: pointer;
}
.nav-tabs .nav-link {
    border-radius: 0;
    transition: background-color 0.2s, border-color 0.2s;
}
.nav-tabs .nav-link:hover {
    background-color: rgba(0, 0, 0, 0.04);
}
.nav-tabs .nav-link.active {
    background-color: #fff;
    border-bottom: 2px solid var(--bs-primary) !important;
}
[data-theme="dark"] .kpi-card,
[data-theme="dark"] .card,
[data-theme="dark"] .btn-white {
    background-color: #1f2937 !important;
    color: #f3f4f6 !important;
    border-color: #374151 !important;
}
[data-theme="dark"] .kpi-card .text-dark,
[data-theme="dark"] .card-header .text-dark,
[data-theme="dark"] .nav-tabs .nav-link,
[data-theme="dark"] .modal-content,
[data-theme="dark"] .modal-body .text-dark {
    color: #f3f4f6 !important;
}
[data-theme="dark"] .nav-tabs .nav-link.active {
    background-color: #1f2937 !important;
    color: #fff !important;
}
[data-theme="dark"] .nav-tabs .nav-link:hover:not(.active) {
    background-color: rgba(255, 255, 255, 0.05);
}
[data-theme="dark"] .table-light,
[data-theme="dark"] .table-light th {
    background-color: #374151 !important;
    color: #f3f4f6 !important;
    border-color: #4b5563 !important;
}
[data-theme="dark"] .table-hover tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.02) !important;
}
[data-theme="dark"] .bg-light,
[data-theme="dark"] .style-ltv {
    background-color: #111827 !important;
    border-color: #374151 !important;
}

/* Loading Overlay Spinner */
.stats-spinner {
    width: 44px;
    height: 44px;
    border: 4px solid rgba(0, 123, 255, 0.15);
    border-top-color: #007bff;
    border-radius: 50%;
    animation: statsSpinnerAnim 0.75s linear infinite;
}
@keyframes statsSpinnerAnim {
    to { transform: rotate(360deg); }
}
#statsLoadingOverlay {
    transition: opacity 0.2s ease;
}
[data-theme="dark"] #statsLoadingOverlay {
    background: rgba(17, 24, 39, 0.65) !important;
}
[data-theme="dark"] #statsLoadingOverlay .text-muted {
    color: #9ca3af !important;
}
[data-theme="dark"] .stats-spinner {
    border-color: rgba(99, 179, 237, 0.15);
    border-top-color: #60a5fa;
}
</style>

<?php
$content = ob_get_clean();

ob_start();
?>
<script>
    let currentRange = 'last30days';
    let currentMaincat = 1;
    
    // Chart References
    let trendChart = null;
    let channelChart = null;
    let categoryChart = null;
    let productsChart = null;
    let countryChart = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadStatistics();
        
        // Listen to theme toggles if any
        const switchBtn = document.querySelector('.btn-theme-switch');
        if (switchBtn) {
            switchBtn.addEventListener('click', () => {
                setTimeout(() => {
                    loadStatistics(); // Redraw with new theme colors
                }, 100);
            });
        }
    });

    function getChartColors() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        return {
            text: isDark ? '#d1d5db' : '#374151',
            grid: isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)',
            cardBg: isDark ? '#1f2937' : '#ffffff',
            lineColor: '#007bff',
            profitColor: '#28a745',
            palette: ['#007bff', '#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6610f2', '#e83e8c', '#6c757d']
        };
    }

    function changeRange(range, label) {
        currentRange = range;
        document.getElementById('selectedRangeLabel').innerText = label;
        
        if (range !== 'custom') {
            document.getElementById('customDateContainer').classList.remove('d-flex');
            document.getElementById('customDateContainer').classList.add('d-none');
            loadStatistics('กำลังโหลด: ' + label + '...');
        }
    }

    function toggleCustomDateInputs() {
        document.getElementById('selectedRangeLabel').innerText = 'กำหนดเอง...';
        document.getElementById('customDateContainer').classList.remove('d-none');
        document.getElementById('customDateContainer').classList.add('d-flex');
    }

    function applyCustomRange() {
        currentRange = 'custom';
        loadStatistics('กำลังกรองช่วงเวลาที่กำหนด...');
    }

    function changeMaincat(id, label) {
        currentMaincat = id;
        document.getElementById('selectedMaincatLabel').innerText = label;
        loadStatistics('กำลังโหลด: ' + label + '...');
    }

    function showStatsLoading(label = 'กำลังโหลดสถิติ...') {
        const overlay = document.getElementById('statsLoadingOverlay');
        const labelEl = document.getElementById('statsLoadingLabel');
        if (overlay) {
            if (labelEl) labelEl.innerText = label;
            overlay.style.display = 'flex';
        }
        const progressBar = document.getElementById('topProgressBar');
        if (progressBar) progressBar.style.display = 'block';
    }

    function hideStatsLoading() {
        const overlay = document.getElementById('statsLoadingOverlay');
        if (overlay) overlay.style.display = 'none';
        const progressBar = document.getElementById('topProgressBar');
        if (progressBar) progressBar.style.display = 'none';
    }

    function loadStatistics(label = 'กำลังโหลดสถิติ...') {
        showStatsLoading(label);

        let url = `<?= ADMIN_URL ?>/api/get_statistics_report.php?range=${currentRange}&maincat_id=${currentMaincat}`;
        if (currentRange === 'custom') {
            const start = document.getElementById('startDateInput').value;
            const end = document.getElementById('endDateInput').value;
            if (start && end) {
                url += `&start_date=${start}&end_date=${end}`;
            }
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                hideStatsLoading();
                if (!data.success) {
                    Swal.fire('Error', data.message || 'ดึงสถิติไม่สำเร็จ', 'error');
                    return;
                }
                
                // 1. Populate KPIs
                let orders = parseInt(data.kpis.total_orders) || 0;
                let rev = parseFloat(data.kpis.gross_revenue) || 0;
                let cogs = parseFloat(data.kpis.total_cogs) || 0;
                let gp = parseFloat(data.kpis.gross_profit) || 0;
                
                let cogsPct = rev > 0 ? ((cogs / rev) * 100).toFixed(1) + '%' : '0.0%';
                let gpPct = rev > 0 ? ((gp / rev) * 100).toFixed(1) + '%' : '0.0%';

                document.getElementById('kpi-orders').innerText = orders.toLocaleString();
                document.getElementById('kpi-revenue').innerText = rev.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿';
                
                document.getElementById('kpi-cogs-label').innerText = 'ต้นทุนขาย (COGS) (' + cogsPct + ')';
                document.getElementById('kpi-cogs').innerText = cogs.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿';
                
                document.getElementById('kpi-gp-label').innerText = 'กำไรขั้นต้น (GP) (' + gpPct + ')';
                const gpEl = document.getElementById('kpi-gross-profit');
                gpEl.innerText = gp.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿';
                if (gp < 0) {
                    gpEl.className = 'mb-0 fw-bold mt-1 text-danger';
                } else {
                    gpEl.className = 'mb-0 fw-bold mt-1 text-success';
                }
                
                document.getElementById('kpi-aov').innerText = parseFloat(data.kpis.aov).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿';
                
                const shipProfitEl = document.getElementById('kpi-shipping-profit');
                shipProfitEl.innerText = parseFloat(data.kpis.shipping_profit).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿';
                if (data.kpis.shipping_profit < 0) {
                    shipProfitEl.className = 'mb-0 fw-bold mt-1 text-danger';
                } else {
                    shipProfitEl.className = 'mb-0 fw-bold mt-1 text-warning';
                }

                // 2. Draw Charts
                drawCharts(data);

                // 3. Build Matrices
                buildStaffMatrix(data.matrix_staff);
                buildProductsMatrix(data.matrix_products);
                if (data.matrix_monthly_kpis) buildMonthlyKpisPivot(data.matrix_monthly_kpis);
                buildMonthlyChannelPivot(data.matrix_monthly_channels);
                buildShippingMatrix(data.matrix_shipping);

                if (window.lucide) lucide.createIcons();
            })
            .catch(err => {
                hideStatsLoading();
                console.error(err);
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อดึงข้อมูล', 'error');
            });
    }

    function drawCharts(data) {
        const colors = getChartColors();
        
        // ----------------------------------------------------
        // Chart 1: Sales & Profit Trend (Line Chart)
        // ----------------------------------------------------
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        if (trendChart) trendChart.destroy();
        
        const labels = data.trends.map(t => t.period);
        const revenues = data.trends.map(t => t.revenue);
        const profits = data.trends.map(t => t.profit);

        trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'ยอดขายรวม (Revenue)',
                        data: revenues,
                        borderColor: colors.lineColor,
                        backgroundColor: 'rgba(0, 123, 255, 0.05)',
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'กำไรสุทธิ (Net Profit)',
                        data: profits,
                        borderColor: colors.profitColor,
                        backgroundColor: 'rgba(40, 167, 69, 0.05)',
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: colors.text, font: { weight: 'bold' } }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: { color: colors.grid },
                        ticks: { color: colors.text }
                    },
                    y: {
                        grid: { color: colors.grid },
                        ticks: { color: colors.text }
                    }
                }
            }
        });

        // ----------------------------------------------------
        // Chart 2: Channels (Doughnut)
        // ----------------------------------------------------
        const chanCtx = document.getElementById('channelChart').getContext('2d');
        if (channelChart) channelChart.destroy();

        const chanLabels = data.channels.map(c => c.name);
        const chanValues = data.channels.map(c => c.value);

        channelChart = new Chart(chanCtx, {
            type: 'doughnut',
            data: {
                labels: chanLabels,
                datasets: [{
                    data: chanValues,
                    backgroundColor: colors.palette,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '65%'
            }
        });

        // Channel Legend Build
        const chanLegend = document.getElementById('channelLegend');
        let chanLegHtml = '';
        data.channels.forEach((c, idx) => {
            const pct = data.kpis.gross_revenue > 0 ? ((c.value / data.kpis.gross_revenue) * 100).toFixed(1) : 0;
            const col = colors.palette[idx % colors.palette.length];
            chanLegHtml += `
                <div class="d-flex align-items-center gap-1.5 border rounded-pill px-2.5 py-1" style="background: rgba(0,0,0,0.02);">
                    <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background: ${col};"></span>
                    <strong>${c.name}:</strong> 
                    <span class="text-muted">${parseFloat(c.value).toLocaleString()} ฿ (${pct}%)</span>
                </div>
            `;
        });
        chanLegend.innerHTML = chanLegHtml || '<span class="text-muted small">ไม่มีข้อมูลในช่วงเวลานี้</span>';

        // ----------------------------------------------------
        // Chart 3: Categories (Doughnut)
        // ----------------------------------------------------
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        if (categoryChart) categoryChart.destroy();

        const catLabels = data.categories.map(c => c.name);
        const catValues = data.categories.map(c => c.value);

        categoryChart = new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catValues,
                    backgroundColor: colors.palette.slice().reverse(),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '65%'
            }
        });

        // Category Legend Build
        const catLegend = document.getElementById('categoryLegend');
        let catLegHtml = '';
        const totalCatVal = catValues.reduce((a,b) => a + b, 0);
        data.categories.forEach((c, idx) => {
            const pct = totalCatVal > 0 ? ((c.value / totalCatVal) * 100).toFixed(1) : 0;
            const col = colors.palette.slice().reverse()[idx % colors.palette.length];
            catLegHtml += `
                <div class="d-flex align-items-center gap-1.5 border rounded-pill px-2.5 py-1" style="background: rgba(0,0,0,0.02);">
                    <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background: ${col};"></span>
                    <strong>${c.name}:</strong> 
                    <span class="text-muted">${parseFloat(c.value).toLocaleString()} ฿ (${pct}%)</span>
                </div>
            `;
        });
        catLegend.innerHTML = catLegHtml || '<span class="text-muted small">ไม่มีข้อมูลในช่วงเวลานี้</span>';

        // ----------------------------------------------------
        // Chart 4: Top 10 Products (Horizontal Bar Chart)
        // ----------------------------------------------------
        const prodCtx = document.getElementById('productsChart').getContext('2d');
        if (productsChart) productsChart.destroy();

        const prodLabels = data.top_products.map(p => p.name.length > 15 ? p.name.substring(0, 15) + '...' : p.name);
        const prodValues = data.top_products.map(p => p.value);

        productsChart = new Chart(prodCtx, {
            type: 'bar',
            data: {
                labels: prodLabels,
                datasets: [{
                    label: 'ยอดขายสะสม (฿)',
                    data: prodValues,
                    backgroundColor: 'rgba(40, 167, 69, 0.85)',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: colors.grid },
                        ticks: { color: colors.text }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: colors.text }
                    }
                }
            }
        });

        // ----------------------------------------------------
        // Chart 5: Countries (Doughnut/Pie)
        // ----------------------------------------------------
        const counCtx = document.getElementById('countryChart').getContext('2d');
        if (countryChart) countryChart.destroy();

        const counLabels = data.countries.map(c => c.name);
        const counValues = data.countries.map(c => c.value);

        countryChart = new Chart(counCtx, {
            type: 'pie',
            data: {
                labels: counLabels,
                datasets: [{
                    data: counValues,
                    backgroundColor: colors.palette,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Country Legend
        const counLegend = document.getElementById('countryLegend');
        let counLegHtml = '';
        data.countries.forEach((c, idx) => {
            const pct = data.kpis.gross_revenue > 0 ? ((c.value / data.kpis.gross_revenue) * 100).toFixed(1) : 0;
            const col = colors.palette[idx % colors.palette.length];
            counLegHtml += `
                <div class="d-flex align-items-center gap-1.5 border rounded-pill px-2.5 py-1" style="background: rgba(0,0,0,0.02);">
                    <span class="rounded-circle d-inline-block" style="width: 8px; height: 8px; background: ${col};"></span>
                    <strong>${c.name}:</strong> 
                    <span class="text-muted">${parseFloat(c.value).toLocaleString()} ฿ (${pct}%)</span>
                </div>
            `;
        });
        counLegend.innerHTML = counLegHtml || '<span class="text-muted small">ไม่มีข้อมูลในช่วงเวลานี้</span>';
    }

    function buildStaffMatrix(staffList) {
        let html = '';
        if (staffList && staffList.length > 0) {
            staffList.forEach(s => {
                const avatar = s.picture ? `<?= ROOT_URL ?>/uploads/profile/${s.user_id}/${s.picture}` : `<?= ROOT_URL ?>/uploads/profile/default.png`;
                html += `
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle overflow-hidden border border-secondary border-opacity-25" style="width: 32px; height: 32px; flex-shrink:0;">
                                    <img src="${avatar}" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='<?= ROOT_URL ?>/uploads/profile/default.png'">
                                </div>
                                <span class="fw-bold text-dark">${s.name}</span>
                            </div>
                        </td>
                        <td class="text-center fw-bold">${s.orders.toLocaleString()} ออเดอร์</td>
                        <td class="text-end fw-bold text-primary">${parseFloat(s.sales).toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                        <td class="text-end text-muted">${parseFloat(s.aov).toLocaleString(undefined, {minimumFractionDigits: 1})} ฿</td>
                        <td class="text-end pe-4 fw-bold ${s.profit < 0 ? 'text-danger' : 'text-success'}">${parseFloat(s.profit).toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                    </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="5" class="text-center py-4 text-muted">ไม่มีข้อมูลพนักงานขายในช่วงเวลานี้</td></tr>';
        }
        document.getElementById('matrixStaffTableBody').innerHTML = html;
        
        // Dark theme adjust
        if (document.documentElement.getAttribute('data-theme') === 'dark') {
            document.querySelectorAll('#matrixStaffTableBody .text-dark').forEach(el => {
                el.classList.remove('text-dark');
                el.classList.add('text-light');
            });
        }
    }

    function buildProductsMatrix(prodList) {
        let html = '';
        if (prodList && prodList.length > 0) {
            prodList.forEach(p => {
                const profit = parseFloat(p.gross_sales) - parseFloat(p.total_cogs);
                const margin = p.gross_sales > 0 ? ((profit / p.gross_sales) * 100).toFixed(1) : '0';
                html += `
                    <tr>
                        <td class="ps-4 text-muted small">${p.subcat_name}</td>
                        <td>
                            <div><strong>${p.product_name}</strong></div>
                            <code class="text-secondary" style="font-size: 10px;">${p.product_code || '-'}</code>
                        </td>
                        <td class="text-center fw-bold">${parseInt(p.qty_sold).toLocaleString()}</td>
                        <td class="text-end fw-bold">${parseFloat(p.gross_sales).toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                        <td class="text-end text-muted">${parseFloat(p.total_cogs).toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                        <td class="text-end fw-bold ${profit < 0 ? 'text-danger' : 'text-success'}">${profit.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                        <td class="text-end pe-4"><span class="badge ${profit < 0 ? 'bg-danger' : 'bg-success'} bg-opacity-10 ${profit < 0 ? 'text-danger' : 'text-success'} border">${margin}%</span></td>
                    </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="7" class="text-center py-4 text-muted">ไม่มีข้อมูลสินค้าขายดีในช่วงเวลานี้</td></tr>';
        }
        document.getElementById('matrixProductsTableBody').innerHTML = html;
    }

    function buildMonthlyKpisPivot(kpisData) {
        const headerRow = document.getElementById('matrixMonthlyKpisHeaderRow');
        const tbody = document.getElementById('matrixMonthlyKpisTableBody');

        if (!kpisData || !kpisData.months || kpisData.months.length === 0) {
            headerRow.innerHTML = `<th class="ps-4">รายการ</th><th class="text-end pe-4">รวมสะสม</th>`;
            tbody.innerHTML = '<tr><td colspan="2" class="text-center py-4 text-muted">ไม่มีข้อมูลจัดสรรรายเดือนในช่วงเวลานี้</td></tr>';
            return;
        }

        // Build headers: Month 1 | Month 2 | ... | Total
        let headerHtml = `<th class="ps-4">รายการ</th>`;
        kpisData.months.forEach(m => {
            const parts = m.split('-');
            const yearStr = parseInt(parts[0]) + 543;
            const monthIndex = parseInt(parts[1]) - 1;
            const monthNamesTh = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            const readableMonth = monthNamesTh[monthIndex] + ' ' + String(yearStr).substring(2);
            headerHtml += `<th class="text-end">${readableMonth}</th>`;
        });
        headerHtml += `<th class="text-end pe-4">รวมสะสม</th>`;
        headerRow.innerHTML = headerHtml;

        // Build rows
        let bodyHtml = '';
        
        const rowLabels = {
            'total_orders': 'จำนวนออเดอร์',
            'gross_revenue': 'ยอดขายรวมสุทธิ',
            'total_cogs': 'ต้นทุนรวม (COGS)',
            'gross_profit': 'กำไรขั้นต้น (GP)',
            'aov': 'เฉลี่ยต่อออเดอร์ (AOV)',
            'shipping_profit': 'ส่วนต่างกำไรค่าส่ง'
        };

        const keys = ['total_orders', 'gross_revenue', 'total_cogs', 'gross_profit', 'aov', 'shipping_profit'];
        
        keys.forEach(key => {
            bodyHtml += `<tr>`;
            bodyHtml += `<td class="ps-4 fw-bold text-dark">${rowLabels[key]}</td>`;
            
            kpisData.data[key].forEach(val => {
                if (key === 'total_orders') {
                    bodyHtml += `<td class="text-end">${val > 0 ? parseInt(val).toLocaleString() : '-'}</td>`;
                } else {
                    bodyHtml += `<td class="text-end">${val !== 0 ? parseFloat(val).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿' : '-'}</td>`;
                }
            });
            
            const totalVal = kpisData.totals[key];
            if (key === 'total_orders') {
                bodyHtml += `<td class="text-end pe-4 fw-bold text-primary">${totalVal > 0 ? parseInt(totalVal).toLocaleString() : '-'}</td>`;
            } else {
                bodyHtml += `<td class="text-end pe-4 fw-bold text-primary">${totalVal !== 0 ? parseFloat(totalVal).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿' : '-'}</td>`;
            }
            bodyHtml += `</tr>`;
        });
        
        tbody.innerHTML = bodyHtml;
        
        // Dark theme adjust
        if (document.documentElement.getAttribute('data-theme') === 'dark') {
            document.querySelectorAll('#matrixMonthlyKpisTableBody .text-dark').forEach(el => {
                el.classList.remove('text-dark');
                el.classList.add('text-light');
            });
        }
    }

    function buildMonthlyChannelPivot(monthlyData) {
        const headerRow = document.getElementById('matrixMonthlyHeaderRow');
        const tbody = document.getElementById('matrixMonthlyChannelTableBody');

        if (!monthlyData || !monthlyData.months || monthlyData.months.length === 0) {
            headerRow.innerHTML = `<th class="ps-4">ช่องทางจัดจำหน่าย</th><th class="text-end pe-4">ยอดขายรวมสะสม</th>`;
            tbody.innerHTML = '<tr><td colspan="2" class="text-center py-4 text-muted">ไม่มีข้อมูลจัดสรรรายเดือนในช่วงเวลานี้</td></tr>';
            return;
        }

        // Build headers: Channel Name | Month 1 | Month 2 | ... | Total
        let headerHtml = `<th class="ps-4">ช่องทางจัดจำหน่าย</th>`;
        monthlyData.months.forEach(m => {
            // Convert '2026-05' to readable Thai Month label 'พ.ค. 69'
            const parts = m.split('-');
            const yearStr = parseInt(parts[0]) + 543;
            const monthIndex = parseInt(parts[1]) - 1;
            const monthNamesTh = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            const readableMonth = monthNamesTh[monthIndex] + ' ' + String(yearStr).substring(2);
            headerHtml += `<th class="text-end">${readableMonth}</th>`;
        });
        headerHtml += `<th class="text-end pe-4">ยอดขายรวมสะสม</th>`;
        headerRow.innerHTML = headerHtml;

        // Build rows
        let bodyHtml = '';
        monthlyData.channels.forEach(ch => {
            bodyHtml += `<tr>`;
            bodyHtml += `<td class="ps-4 fw-bold text-dark">${ch.name}</td>`;
            ch.sales.forEach(val => {
                bodyHtml += `<td class="text-end">${val > 0 ? parseFloat(val).toLocaleString(undefined, {minimumFractionDigits: 1}) + ' ฿' : '-'}</td>`;
            });
            bodyHtml += `<td class="text-end pe-4 fw-bold text-primary">${parseFloat(ch.total).toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>`;
            bodyHtml += `</tr>`;
        });
        tbody.innerHTML = bodyHtml;
        
        // Dark theme adjust
        if (document.documentElement.getAttribute('data-theme') === 'dark') {
            document.querySelectorAll('#matrixMonthlyChannelTableBody .text-dark').forEach(el => {
                el.classList.remove('text-dark');
                el.classList.add('text-light');
            });
        }
    }

    function buildShippingMatrix(shipList) {
        let html = '';
        if (shipList && shipList.length > 0) {
            shipList.forEach(s => {
                const delicost = parseFloat(s.total_delicost);
                const delicosttrue = parseFloat(s.total_delicosttrue);
                const profit = delicost - delicosttrue;
                let statusBadge = '';
                
                if (profit > 0) {
                    statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border">คุ้มทุน / มีกำไร</span>';
                } else if (profit < 0) {
                    statusBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border">ขาดทุนค่าส่ง</span>';
                } else {
                    statusBadge = '<span class="badge bg-secondary bg-opacity-10 text-muted border">เท่าทุน</span>';
                }
                
                html += `
                    <tr>
                        <td class="ps-4 fw-bold">${s.channel_name}</td>
                        <td class="text-center">${parseInt(s.orders_count).toLocaleString()} ออเดอร์</td>
                        <td class="text-end fw-bold">${delicost.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                        <td class="text-end text-muted">${delicosttrue.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                        <td class="text-end fw-bold ${profit < 0 ? 'text-danger' : 'text-success'}">${profit.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                        <td class="text-end pe-4">${statusBadge}</td>
                    </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="6" class="text-center py-4 text-muted">ไม่มีข้อมูลส่วนต่างค่าส่งในช่วงเวลานี้</td></tr>';
        }
        document.getElementById('matrixShippingTableBody').innerHTML = html;
    }

    // Modal Display Customer Purchase History (Copy-pasted from main reports for complete feature parity)
    function showCustomerPurchaseHistory(userId, btnElement = null) {
        let originalContent = '';
        if (btnElement) {
            originalContent = btnElement.innerHTML;
            btnElement.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="width: 14px; height: 14px;"></span>';
            btnElement.classList.add('disabled');
            btnElement.style.pointerEvents = 'none';
        }

        const progressBar = document.getElementById('topProgressBar');
        if (progressBar) progressBar.style.display = 'block';

        fetch(`<?= ADMIN_URL ?>/api/get_customer_purchase_history.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if (progressBar) progressBar.style.display = 'none';
                if (btnElement) {
                    btnElement.innerHTML = originalContent;
                    btnElement.classList.remove('disabled');
                    btnElement.style.pointerEvents = 'auto';
                }

                if (data.success) {
                    const cust = data.customer;
                    const orders = data.orders;

                    document.getElementById('history-cust-id').innerText = cust.formatted_id;
                    
                    let displayName = cust.user_name + ' ' + (cust.user_lastname || '');
                    if (cust.user_nickname) {
                        displayName += ` (${cust.user_nickname})`;
                    }
                    document.getElementById('history-cust-name').innerText = displayName;

                    const avatarUrl = cust.user_picture ? `<?= ROOT_URL ?>/uploads/profile/${cust.user_id}/${cust.user_picture}` : `<?= ROOT_URL ?>/uploads/profile/default.png`;
                    document.getElementById('history-cust-pic').src = avatarUrl;
                    document.getElementById('history-cust-pic').onerror = function() {
                        this.src = '<?= ROOT_URL ?>/uploads/profile/default.png';
                    };

                    document.getElementById('history-cust-contact').innerHTML = `
                        <div class="d-flex align-items-center gap-1"><i data-lucide="phone" style="width: 12px; height: 12px;"></i> เบอร์โทร: ${cust.user_mobile || cust.user_tel || '-'}</div>
                        <div class="d-flex align-items-center gap-1 mt-1"><i data-lucide="mail" style="width: 12px; height: 12px;"></i> อีเมล: ${cust.user_email || '-'}</div>
                    `;

                    document.getElementById('history-cust-channel').innerText = 'ช่องทาง: ' + (cust.last_channel || '-').toUpperCase();
                    document.getElementById('history-cust-country').innerText = 'ประเทศ: ' + (cust.last_country || 'ประเทศไทย');

                    const picHistoryContainer = document.getElementById('history-profile-pics');
                    picHistoryContainer.innerHTML = '<span class="text-muted small">กำลังโหลดประวัติรูปโปรไฟล์...</span>';
                    
                    fetch(`<?= ADMIN_URL ?>/api/get_user_image_history.php?user_id=${cust.user_id}`)
                        .then(res => res.json())
                        .then(historyData => {
                            if (historyData.success && historyData.images && historyData.images.length > 0) {
                                let picHtml = '';
                                historyData.images.forEach(img => {
                                    const dateStr = new Date(img.time * 1000).toLocaleDateString('th-TH', {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });
                                    picHtml += `
                                        <div class="text-center">
                                            <div class="rounded-3 border border-secondary border-opacity-25 overflow-hidden shadow-sm bg-white" style="width: 80px; height: 80px;">
                                                <a href="${img.url}" target="_blank">
                                                    <img src="${img.url}" alt="Profile Picture" class="w-100 h-100" style="object-fit: cover;">
                                                </a>
                                            </div>
                                            <small class="text-muted d-block mt-1" style="font-size: 10px; max-width: 90px; overflow-wrap: break-word;">${dateStr}</small>
                                        </div>
                                    `;
                                });
                                picHistoryContainer.innerHTML = picHtml;
                            } else {
                                picHistoryContainer.innerHTML = '<span class="text-muted small">ไม่มีประวัติรูปโปรไฟล์อื่นๆ</span>';
                            }
                        })
                        .catch(err => {
                            picHistoryContainer.innerHTML = '<span class="text-muted small">ไม่สามารถดึงประวัติรูปโปรไฟล์ได้</span>';
                        });
                    
                    let totalSpent = 0;
                    let ordersHtml = '';

                    orders.forEach(o => {
                        totalSpent += parseFloat(o.orders_grandtotal);
                        
                        let itemsList = '';
                        if (o.items && o.items.length > 0) {
                            itemsList = o.items.map(i => `<div class="mb-1 fw-bold text-dark d-flex justify-content-between" style="font-size: 11px;"><span class="text-secondary">• ${i.product_name}</span> <span class="ms-2">x${i.ordetail_qty}</span></div>`).join('');
                        } else {
                            itemsList = '<span class="text-muted">-</span>';
                        }

                        let channelBadge = `<span class="badge bg-secondary bg-opacity-10 text-dark border small">${(o.orders_channel || 'DIRECT').toUpperCase()}</span>`;
                        
                        ordersHtml += `
                            <tr>
                                <td><code class="text-primary fw-bold">${o.orders_number}</code></td>
                                <td class="text-center">${o.orders_date}</td>
                                <td>${channelBadge}</td>
                                <td>${itemsList}</td>
                                <td class="text-end fw-bold text-success">${parseFloat(o.orders_grandtotal).toLocaleString(undefined, {minimumFractionDigits: 2})} บาท</td>
                            </tr>
                        `;
                    });

                    document.getElementById('history-cust-total').innerText = totalSpent.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' บาท';
                    document.getElementById('historyOrdersTableBody').innerHTML = ordersHtml || '<tr><td colspan="5" class="text-center text-muted py-3">ไม่มีประวัติการสั่งซื้อ</td></tr>';

                    if (document.documentElement.getAttribute('data-theme') === 'dark') {
                        document.querySelectorAll('#historyOrdersTableBody .text-dark').forEach(el => {
                            el.classList.remove('text-dark');
                            el.classList.add('text-light');
                        });
                    }

                    const modal = new bootstrap.Modal(document.getElementById('customerHistoryModal'));
                    modal.show();
                    
                    setTimeout(() => {
                        if (window.lucide) lucide.createIcons();
                    }, 200);

                } else {
                    Swal.fire('Error', data.message || 'ดึงประวัติไม่สำเร็จ', 'error');
                }
            })
            .catch(error => {
                if (progressBar) progressBar.style.display = 'none';
                if (btnElement) {
                    btnElement.innerHTML = originalContent;
                    btnElement.classList.remove('disabled');
                    btnElement.style.pointerEvents = 'auto';
                }
                console.error(error);
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อดึงประวัติ', 'error');
            });
    }
</script>
<?php
$extra_scripts = ob_get_clean();

include '../includes/layout.php';
?>
