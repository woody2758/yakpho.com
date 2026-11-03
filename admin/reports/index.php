<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$page_title            = "รายงานและวิเคราะห์การขาย";
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

<div class="reports-container pb-5">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="page-title mb-1" style="font-size: 1.75rem; font-weight: 600;"><i data-lucide="bar-chart-3" class="me-2 text-primary" style="vertical-align: middle;"></i>รายงานและสถิติการขาย</h1>
            <p class="text-muted small mb-0">วิเคราะห์ข้อมูลรายได้ สินค้า ช่องทางการขาย และพฤติกรรมการซื้อซ้ำของลูกค้าแบบ Real-time</p>
        </div>
        
        <!-- Filter Controls -->
        <div class="d-flex align-items-center gap-2">
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

            <!-- Google Adsense-style Date Range Selector -->
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

    <!-- KPI Summary Grid Cards (Glassmorphism & Harmonious Gradients) -->
    <div class="row g-3 mb-4">
        <!-- ยอดขายรวม -->
        <div class="col-xl col-md-4 col-sm-6">
            <div class="kpi-card shadow-sm border p-3 rounded-4 d-flex align-items-center gap-3 bg-white position-relative overflow-hidden">
                <div class="kpi-icon-wrapper rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i data-lucide="shopping-bag" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">ยอดขายรวม (Subtotal)</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark" id="kpi-subtotal">0.00</h4>
                </div>
            </div>
        </div>
        <!-- ส่วนลด -->
        <div class="col-xl col-md-4 col-sm-6">
            <div class="kpi-card shadow-sm border p-3 rounded-4 d-flex align-items-center gap-3 bg-white position-relative overflow-hidden">
                <div class="kpi-icon-wrapper rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i data-lucide="tag" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">ส่วนลดสะสม (Discount)</span>
                    <h4 class="mb-0 fw-bold mt-1 text-danger"><span id="kpi-discount">0.00</span> <span class="fs-6 text-muted fw-normal ms-1" id="kpi-discount-pct"></span></h4>
                </div>
            </div>
        </div>
        <!-- กำไรจากค่าส่ง -->
        <div class="col-xl col-md-4 col-sm-6">
            <div class="kpi-card shadow-sm border p-3 rounded-4 d-flex align-items-center gap-3 bg-white position-relative overflow-hidden">
                <div class="kpi-icon-wrapper rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i data-lucide="truck" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">กำไรส่วนต่างค่าส่ง</span>
                    <h4 class="mb-0 fw-bold mt-1 text-info" id="kpi-shipping-margin">0.00</h4>
                </div>
            </div>
        </div>
        <!-- ต้นทุนสินค้า COGS -->
        <div class="col-xl col-md-4 col-sm-6">
            <div class="kpi-card shadow-sm border p-3 rounded-4 d-flex align-items-center gap-3 bg-white position-relative overflow-hidden">
                <div class="kpi-icon-wrapper rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i data-lucide="archive" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">ต้นทุนสินค้า (COGS)</span>
                    <h4 class="mb-0 fw-bold mt-1 text-secondary"><span id="kpi-cogs">0.00</span> <span class="fs-6 text-muted fw-normal ms-1" id="kpi-cogs-pct"></span></h4>
                </div>
            </div>
        </div>
        <!-- กำไรสุทธิคาดการณ์ -->
        <div class="col-xl col-md-4 col-sm-6">
            <div class="kpi-card shadow-sm p-3 rounded-4 d-flex align-items-center gap-3 position-relative overflow-hidden border-2 border-primary" style="background: linear-gradient(135deg, rgba(6, 199, 85, 0.05) 0%, rgba(0, 123, 255, 0.05) 100%);">
                <div class="kpi-icon-wrapper rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                    <i data-lucide="trending-up" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <span class="text-success small d-block fw-bold">กำไรสุทธิ (Net Profit)</span>
                    <h4 class="mb-0 fw-bold mt-1 text-success fs-3"><span id="kpi-net-profit">0.00</span> <span class="fs-6 text-success fw-bold ms-1" id="kpi-net-profit-pct"></span></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Navigation Tabs -->
    <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden position-relative">
        <!-- Top Premium Progress Bar -->
        <div id="topProgressBar" class="progress-loader-bar" style="display: none;"></div>
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs border-0 px-3" id="reportTabs" role="tablist" style="gap: 15px;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3 fw-bold border-0 border-bottom border-3 border-transparent" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-selected="true" style="border-radius: 0;">
                        <i data-lucide="activity" class="me-1" style="width: 16px; height: 16px;"></i> สรุปภาพรวมทางการเงิน
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 border-bottom border-3 border-transparent" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-selected="false" style="border-radius: 0;">
                        <i data-lucide="package-search" class="me-1" style="width: 16px; height: 16px;"></i> สินค้าขายดี & ต้นทุน
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 border-bottom border-3 border-transparent" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff" type="button" role="tab" aria-selected="false" style="border-radius: 0;">
                        <i data-lucide="users" class="me-1" style="width: 16px; height: 16px;"></i> ทีมขาย & ตัวแทนพาร์ทเนอร์
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 border-bottom border-3 border-transparent" id="channels-tab" data-bs-toggle="tab" data-bs-target="#channels" type="button" role="tab" aria-selected="false" style="border-radius: 0;">
                        <i data-lucide="pie-chart" class="me-1" style="width: 16px; height: 16px;"></i> ช่องทางการขาย & ประเทศ
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 border-bottom border-3 border-transparent position-relative" id="rfm-tab" data-bs-toggle="tab" data-bs-target="#rfm" type="button" role="tab" aria-selected="false" style="border-radius: 0;">
                        <i data-lucide="repeat" class="me-1" style="width: 16px; height: 16px;"></i> วิเคราะห์การซื้อซ้ำ & กลุ่มลูกค้า
                        <span class="position-absolute top-1 start-100 translate-middle badge rounded-pill bg-danger d-none" id="alertCountBadge">0</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4 bg-light bg-opacity-25" id="mainLoaderContainer" style="position: relative; min-height: 400px;">
            <!-- Loader Overlay -->
            <div id="reportLoader" class="d-none align-items-center justify-content-center position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75" style="z-index: 10; backdrop-filter: blur(4px);">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted small mt-2 fw-medium">กำลังคำนวณข้อมูล...</div>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="reportTabsContent">
                
                <!-- Tab 1: Financial Overview -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="card shadow-sm border p-3 bg-white rounded-3 h-100">
                                <h6 class="fw-bold mb-3"><i data-lucide="dollar-sign" class="me-1 text-primary"></i> โครงสร้างงบรายได้และกำไรสุทธิ</h6>
                                <div style="height: 300px; position: relative;">
                                    <canvas id="financialTrendChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="card shadow-sm border p-3 bg-white rounded-3 h-100">
                                <h6 class="fw-bold mb-3"><i data-lucide="credit-card" class="me-1 text-primary"></i> สัดส่วนการชำระเงิน (Payment Methods)</h6>
                                <div style="height: 200px; position: relative;" class="d-flex align-items-center justify-content-center">
                                    <canvas id="paymentMethodsChart"></canvas>
                                </div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm align-middle mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ประเภทช่องทาง</th>
                                                <th class="text-center">จำนวนออเดอร์</th>
                                                <th class="text-end">ยอดขาย</th>
                                                <th class="text-end">สัดส่วน (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="paymentMethodsTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Product Performance -->
                <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm border p-3 bg-white rounded-3">
                                <h6 class="fw-bold mb-3"><i data-lucide="star" class="me-1 text-primary"></i> ตารางจัดอันดับรายการสินค้าขายดียอดนิยม</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;" class="text-center">อันดับ</th>
                                                <th style="width: 60px;">รูป</th>
                                                <th>รหัสสินค้า</th>
                                                <th>ชื่อสินค้า</th>
                                                <th class="text-center">จำนวนชิ้นที่ขาย</th>
                                                <th class="text-end">ยอดขายรวม</th>
                                                <th class="text-end">สัดส่วน (%)</th>
                                                <th class="text-end">ต้นทุน (COGS)</th>
                                                <th class="text-end">กำไรขั้นต้น</th>
                                                <th class="text-center">Margin (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="topSellingProductsTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card shadow-sm border p-3 bg-white rounded-3 h-100">
                                <h6 class="fw-bold mb-3"><i data-lucide="tag" class="me-1 text-primary"></i> ยอดขายแยกตามหมวดหมู่สินค้า</h6>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th>หมวดหมู่สินค้า</th>
                                                <th class="text-center">ชิ้นที่ขายได้</th>
                                                <th class="text-end">ยอดขายรวม</th>
                                                <th class="text-end">สัดส่วน (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="categorySalesTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Staff & Agents -->
                <div class="tab-pane fade" id="staff" role="tabpanel" aria-labelledby="staff-tab">
                    <div class="row g-4">
                        <!-- แอดมินผู้คีย์ -->
                        <div class="col-lg-6">
                            <div class="card shadow-sm border p-3 bg-white rounded-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0"><i data-lucide="award" class="me-1 text-primary"></i> ประสิทธิภาพและยอดขายรายแอดมิน (Staff / Admin)</h6>
                                    <span class="badge bg-primary bg-opacity-10 text-primary small">นับจากผู้สร้างออเดอร์</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th>แอดมิน</th>
                                                <th class="text-center">ออเดอร์สะสม</th>
                                                <th class="text-end">ยอดขายรวมสุทธิ</th>
                                                <th class="text-end">สัดส่วน (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="staffPerformanceTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ตัวแทน Therapist -->
                        <div class="col-lg-6">
                            <div class="card shadow-sm border p-3 bg-white rounded-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0"><i data-lucide="shield-check" class="me-1 text-primary"></i> ยอดขายตัวแทน / ร้านนวดพาร์ทเนอร์ (Therapist)</h6>
                                    <span class="badge bg-success bg-opacity-10 text-success small">นับตามรหัสตัวแทน</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ชื่อตัวแทน / ร้านพาร์ทเนอร์</th>
                                                <th class="text-center">ออเดอร์สะสม</th>
                                                <th class="text-end">ยอดรวมที่ตัวแทนขายได้</th>
                                                <th class="text-end">สัดส่วน (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="agentPerformanceTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Channels & Countries -->
                <div class="tab-pane fade" id="channels" role="tabpanel" aria-labelledby="channels-tab">
                    <div class="row g-4">
                        <!-- ช่องทางขาย -->
                        <div class="col-md-6">
                            <div class="card shadow-sm border p-3 bg-white rounded-3 h-100">
                                <h6 class="fw-bold mb-3"><i data-lucide="external-link" class="me-1 text-primary"></i> ยอดขายเปรียบเทียบตามช่องทางการขาย (Sales Channels)</h6>
                                <div style="height: 180px; position: relative;" class="d-flex align-items-center justify-content-center mb-3">
                                    <canvas id="salesChannelsChart"></canvas>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ช่องทางขาย</th>
                                                <th class="text-center">จำนวนออเดอร์</th>
                                                <th class="text-end">ยอดขายรวม</th>
                                                <th class="text-end">สัดส่วน (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="channelsTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ประเทศของลูกค้า -->
                        <div class="col-md-6">
                            <div class="card shadow-sm border p-3 bg-white rounded-3 h-100">
                                <h6 class="fw-bold mb-3"><i data-lucide="globe" class="me-1 text-primary"></i> วิเคราะห์ยอดขายตามประเทศของลูกค้า (Customer Countries)</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ประเทศ</th>
                                                <th class="text-center">รหัสประเทศ</th>
                                                <th class="text-center">จำนวนออเดอร์</th>
                                                <th class="text-end">ยอดรวมทั้งสิ้น</th>
                                                <th class="text-end">สัดส่วน (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="countriesTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 5: RFM & Repeat Purchases -->
                <div class="tab-pane fade" id="rfm" role="tabpanel" aria-labelledby="rfm-tab">
                    
                    <!-- Advanced Repeat Alerts & Segmentation counts -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-4">
                            <div class="card shadow-sm border p-3 bg-white rounded-3 h-100">
                                <h6 class="fw-bold mb-3"><i data-lucide="users" class="me-1 text-primary"></i> สัดส่วนกลุ่มผู้ซื้อ (Customer Segmentation)</h6>
                                <div style="height: 150px; position: relative;" class="d-flex align-items-center justify-content-center mb-3">
                                    <canvas id="customerSegmentationChart"></canvas>
                                </div>
                                <div class="d-flex justify-content-around text-center small mt-3">
                                    <div>
                                        <span class="d-block text-muted">ซื้อซ้ำ (Repeat)</span>
                                        <strong class="text-primary fs-5"><span id="seg-count-repeat">0</span> <span class="fs-6 text-muted fw-normal ms-1" id="seg-pct-repeat"></span></strong>
                                    </div>
                                    <div>
                                        <span class="d-block text-muted">ซื้อครั้งเดียว (One-time)</span>
                                        <strong class="text-secondary fs-5"><span id="seg-count-onetime">0</span> <span class="fs-6 text-muted fw-normal ms-1" id="seg-pct-onetime"></span></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="card shadow-sm border p-3 bg-white rounded-3 border-danger border-opacity-25 h-100 repeat-alerts-card">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold mb-0 text-danger"><i data-lucide="bell" class="me-1 animate-pulse"></i> รายชื่อลูกค้าเลยกำหนดซื้อซ้ำ (Overdue Repeat Buyers)</h6>
                                        <span class="badge bg-danger rounded-pill px-2" style="font-size: 11px;">เฉลี่ยรอบซื้อของร้าน: <span id="system-avg-interval">0</span> วัน</span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger fw-bold" onclick="showAllOverdueCustomers(1)" style="font-size: 12px;">ดูทั้งหมด <i data-lucide="arrow-right" style="width: 12px; height: 12px;"></i></button>
                                </div>
                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0 small">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>ชื่อลูกค้า</th>
                                                <th>เบอร์โทร</th>
                                                <th class="text-center">จำนวนออเดอร์</th>
                                                <th class="text-center">รอบซื้อเฉลี่ย</th>
                                                <th class="text-center">ซื้อล่าสุดผ่านมาแล้ว</th>
                                                <th class="text-center">วันที่ซื้อล่าสุด</th>
                                                <th class="text-center">การกระทำ</th>
                                            </tr>
                                        </thead>
                                        <tbody id="repeatAlertsTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Top Spenders -->
                        <div class="col-md-6">
                            <div class="card shadow-sm border p-3 bg-white rounded-3">
                                <h6 class="fw-bold mb-3"><i data-lucide="crown" class="me-1 text-primary"></i> 15 อันดับลูกค้ายอดซื้อสะสมสูงสุด (Top Spenders)</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ลูกค้า</th>
                                                <th class="text-center">จำนวนคำสั่งซื้อ</th>
                                                <th class="text-end">ยอดซื้อสะสม (LTV)</th>
                                                <th class="text-end">ซื้อเฉลี่ย/ออเดอร์</th>
                                            </tr>
                                        </thead>
                                        <tbody id="topSpendersTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Segmented customer list -->
                        <div class="col-md-6">
                            <div class="card shadow-sm border p-3 bg-white rounded-3">
                                <h6 class="fw-bold mb-3"><i data-lucide="filter" class="me-1 text-primary"></i> สมาชิกจัดหมวดหมู่ลูกค้า</h6>
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0 small">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>ลูกค้า</th>
                                                <th>กลุ่มลูกค้า</th>
                                                <th class="text-center">คำสั่งซื้อ</th>
                                                <th class="text-end">ยอดซื้อรวม</th>
                                            </tr>
                                        </thead>
                                        <tbody id="segmentedUsersTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-1">
                        <!-- Recent Customers -->
                        <div class="col-12">
                            <div class="card shadow-sm border p-3 bg-white rounded-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0"><i data-lucide="clock" class="me-1 text-primary"></i> ลูกค้าที่สั่งซื้อล่าสุด (Recent Customers)</h6>
                                    <button class="btn btn-sm btn-outline-primary fw-bold" onclick="showAllRecentCustomers(1)" style="font-size: 12px;">ดูทั้งหมด <i data-lucide="arrow-right" style="width: 12px; height: 12px;"></i></button>
                                </div>
                                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0 small">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>ลูกค้า</th>
                                                <th>เลขที่ออเดอร์ล่าสุด</th>
                                                <th class="text-center">วันที่สั่งซื้อล่าสุด</th>
                                                <th class="text-end">ยอดสั่งซื้อล่าสุด</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recentCustomersTableBody">
                                            <!-- Render via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- All Recent Customers Modal (Paginated) -->
<div class="modal fade" id="allRecentCustomersModal" tabindex="-1" aria-labelledby="allRecentCustomersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg" style="overflow: hidden;">
            <div class="modal-header border-0 bg-white p-4 pb-0 position-relative">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="allRecentCustomersModalLabel">
                    <i data-lucide="clock" class="text-primary" style="width: 24px; height: 24px;"></i>
                    ลูกค้าที่สั่งซื้อทั้งหมด (ตามช่วงเวลาที่เลือก)
                </h5>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-4 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>ลูกค้า</th>
                                <th>เลขที่ออเดอร์ล่าสุด</th>
                                <th class="text-center">วันที่สั่งซื้อล่าสุด</th>
                                <th class="text-end">ยอดสั่งซื้อล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody id="allRecentCustomersTableBody">
                            <!-- Render via JS -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Controls -->
                <div class="d-flex justify-content-between align-items-center mt-3" id="allRecentCustomersPagination">
                    <!-- Render via JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- All Overdue Customers Modal (Paginated) -->
<div class="modal fade" id="allOverdueCustomersModal" tabindex="-1" aria-labelledby="allOverdueCustomersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg" style="overflow: hidden;">
            <div class="modal-header border-0 bg-white p-4 pb-0 position-relative">
                <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="allOverdueCustomersModalLabel">
                    <i data-lucide="bell" class="text-danger animate-pulse" style="width: 24px; height: 24px;"></i>
                    รายชื่อลูกค้าเลยกำหนดซื้อซ้ำทั้งหมด (อิงตามหมวดหมู่ที่เลือกตลอดช่วงเวลา)
                </h5>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-4 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>ชื่อลูกค้า</th>
                                <th>เบอร์โทร</th>
                                <th class="text-center">จำนวนออเดอร์</th>
                                <th class="text-center">รอบซื้อเฉลี่ย</th>
                                <th class="text-center">ซื้อล่าสุดผ่านมาแล้ว</th>
                                <th class="text-center">วันที่ซื้อล่าสุด</th>
                                <th class="text-center">การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody id="allOverdueCustomersTableBody">
                            <!-- Render via JS -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Controls -->
                <div class="d-flex justify-content-between align-items-center mt-3" id="allOverdueCustomersPagination">
                    <!-- Render via JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customer History Modal -->
<div class="modal fade" id="customerHistoryModal" tabindex="-1" aria-labelledby="customerHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg" style="overflow: hidden;">
            <div class="modal-header border-0 bg-white p-4 pb-0 position-relative">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="customerHistoryModalLabel">
                    <i data-lucide="history" class="text-primary" style="width: 24px; height: 24px;"></i>
                    ประวัติการสั่งซื้อและสถิติซื้อซ้ำอย่างละเอียด
                </h5>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-4 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <!-- Customer Info Card -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start border rounded-3 p-3 mb-4 bg-light bg-opacity-25 gap-3" style="border-color: rgba(0,0,0,0.08) !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle overflow-hidden border border-secondary border-opacity-25 bg-white shadow-sm" style="width: 64px; height: 64px; flex-shrink: 0;">
                            <img id="history-cust-pic" src="<?= ROOT_URL ?>/uploads/profile/default.png" alt="User Avatar" class="w-100 h-100" style="object-fit: cover;">
                        </div>
                        <div>
                            <span class="badge bg-secondary bg-opacity-10 text-muted border mb-2" id="history-cust-id">C000000</span>
                            <h6 class="fw-bold mb-1 text-dark" id="history-cust-name">-</h6>
                            <div class="text-muted small" id="history-cust-contact" style="line-height: 1.6;">-</div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <span class="badge bg-info bg-opacity-10 text-info border" id="history-cust-channel" style="font-size: 11px;">ช่องทาง: -</span>
                                <span class="badge bg-success bg-opacity-10 text-success border" id="history-cust-country" style="font-size: 11px;">ประเทศ: -</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-sm-end align-self-sm-center">
                        <span class="d-block text-muted small">ยอดซื้อสะสมรวม</span>
                        <strong class="fs-4 text-success" id="history-cust-total">0.00 บาท</strong>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-dark"><i data-lucide="list" class="me-1 text-primary"></i> รายการคำสั่งซื้อทั้งหมดที่สำเร็จ (Excluding Cancelled)</h6>
                <div class="table-responsive mb-4" style="max-height: 300px; overflow-y: auto;">
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
                            <!-- Dynamic Rows -->
                        </tbody>
                    </table>
                </div>

                <!-- Profile Picture History -->
                <div class="border-top pt-3 mt-3">
                    <h6 class="fw-bold mb-3 text-dark"><i data-lucide="image" class="me-1 text-primary"></i> ประวัติการเปลี่ยนรูปโปรไฟล์ลูกค้า (Profile Picture History)</h6>
                    <div id="history-profile-pics" class="d-flex flex-wrap gap-3">
                        <!-- Render via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 bg-white p-4 pb-0 position-relative">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i data-lucide="file-text" class="text-primary" style="width: 24px; height: 24px;"></i>
                    ใบรายละเอียดสั่งซื้อ
                </h5>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-4 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-white position-relative">
                <!-- Loading Overlay -->
                <div id="orderDetailLoader" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center" style="background-color: rgba(255, 255, 255, 0.85); z-index: 10; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem;">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                    <div class="mt-3 fw-bold text-dark loader-text">กำลังดึงข้อมูลใบเสร็จ...</div>
                </div>
                <iframe id="orderDetailIframe" src="" style="width:100%; height:80vh; border:none; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem; opacity: 0; transition: opacity 0.3s ease;"></iframe>
            </div>
        </div>
    </div>
</div>

<style>
    /* CSS Styles for Premium Layout & Cards */
    .kpi-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-color: rgba(0, 0, 0, 0.08) !important;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.03) !important;
    }
    .nav-tabs .nav-link {
        color: #6c757d;
        border-bottom: 3px solid transparent !important;
    }
    .nav-tabs .nav-link.active {
        color: var(--bs-primary) !important;
        border-bottom-color: var(--bs-primary) !important;
        background-color: transparent !important;
    }
    .btn-white {
        background-color: #fff;
        color: #333;
    }
    .repeat-alerts-card {
        background-color: #fff !important;
    }
    .table-responsive thead.sticky-top th {
        background-color: #ffffff !important;
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 2px solid rgba(0, 0, 0, 0.05) !important;
    }
    .btn-outline-history {
        color: #0284c7 !important;
        border-color: rgba(2, 132, 199, 0.3) !important;
        background-color: transparent !important;
        transition: all 0.15s ease-in-out;
    }
    .btn-outline-history:hover {
        color: #fff !important;
        background-color: #0284c7 !important;
        border-color: #0284c7 !important;
    }
    .text-history-link {
        color: #0284c7 !important;
        transition: color 0.15s ease-in-out;
    }
    .text-history-link:hover {
        color: #0369a1 !important;
    }
    [data-theme="dark"] .kpi-card,
    [data-theme="dark"] .btn-white {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: #f5f5f4 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-theme="dark"] .card-header {
        background-color: rgba(0,0,0,0.2) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-theme="dark"] .table-light {
        --bs-table-bg: rgba(255, 255, 255, 0.05) !important;
    }
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .5; }
    }
    .progress-loader-bar {
        height: 4px;
        width: 100%;
        background: linear-gradient(90deg, #007bff 0%, #06C755 50%, #007bff 100%);
        background-size: 200% 100%;
        animation: loading-bar-scan 1.5s infinite linear;
        position: absolute;
        top: 0;
        left: 0;
        z-index: 1050;
    }
    @keyframes loading-bar-scan {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Soothing Dark Mode Adjustments to Reduce Eye Strain */
    [data-theme="dark"] .text-primary {
        color: #60a5fa !important; /* Soft royal blue */
    }
    [data-theme="dark"] .text-success {
        color: #34d399 !important; /* Soft emerald green */
    }
    [data-theme="dark"] .text-danger {
        color: #f87171 !important; /* Soft pastel red */
    }
    [data-theme="dark"] .text-info {
        color: #38bdf8 !important; /* Soft sky blue */
    }
    [data-theme="dark"] .text-secondary,
    [data-theme="dark"] .text-muted {
        color: #9ca3af !important; /* Soft cool gray */
    }
    [data-theme="dark"] .text-dark {
        color: #e5e7eb !important; /* Off-white instead of pure white */
    }
    [data-theme="dark"] .bg-primary.bg-opacity-10 {
        background-color: rgba(96, 165, 250, 0.15) !important;
    }
    [data-theme="dark"] .bg-success.bg-opacity-10 {
        background-color: rgba(52, 211, 153, 0.15) !important;
    }
    [data-theme="dark"] .bg-danger.bg-opacity-10 {
        background-color: rgba(248, 113, 113, 0.15) !important;
    }
    [data-theme="dark"] .bg-info.bg-opacity-10 {
        background-color: rgba(56, 189, 248, 0.15) !important;
    }
    [data-theme="dark"] .bg-secondary.bg-opacity-10 {
        background-color: rgba(156, 163, 175, 0.15) !important;
    }
    [data-theme="dark"] .border-primary {
        border-color: rgba(96, 165, 250, 0.3) !important;
    }
    [data-theme="dark"] .border-danger {
        border-color: rgba(248, 113, 113, 0.2) !important;
    }
    [data-theme="dark"] .progress-loader-bar {
        background: linear-gradient(90deg, #3b82f6 0%, #10b981 50%, #3b82f6 100%);
    }
    [data-theme="dark"] code {
        color: #f472b6 !important; /* Soft rose/pink code */
        background-color: rgba(244, 114, 182, 0.08) !important;
        border: 1px solid rgba(244, 114, 182, 0.15) !important;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: inherit;
    }
    [data-theme="dark"] .table-light th,
    [data-theme="dark"] .table-light {
        background-color: rgba(0, 0, 0, 0.15) !important;
        color: #9ca3af !important;
        border-bottom-color: rgba(255, 255, 255, 0.06) !important;
    }
    [data-theme="dark"] .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.02) !important;
    }
    [data-theme="dark"] .nav-tabs .nav-link.active {
        color: #34d399 !important; /* Soft emerald tab focus */
        border-bottom-color: #34d399 !important;
    }
    [data-theme="dark"] .btn-white {
        color: #d1d5db !important;
    }
    [data-theme="dark"] .kpi-card {
        background: rgba(26, 29, 35, 0.4) !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
    }
    [data-theme="dark"] .btn-outline-history {
        color: #38bdf8 !important; /* Pastel sky blue */
        border-color: rgba(56, 189, 248, 0.3) !important;
    }
    [data-theme="dark"] .btn-outline-history:hover {
        color: #0f172a !important;
        background-color: #38bdf8 !important;
        border-color: #38bdf8 !important;
    }
    [data-theme="dark"] .text-history-link {
        color: #38bdf8 !important;
    }
    [data-theme="dark"] .text-history-link:hover {
        color: #7dd3fc !important;
    }
    [data-theme="dark"] .card {
        background-color: rgba(26, 29, 35, 0.4) !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
    }
    [data-theme="dark"] .repeat-alerts-card {
        background: rgba(26, 29, 35, 0.4) !important;
        border-color: rgba(248, 113, 113, 0.15) !important;
    }
    [data-theme="dark"] .table-responsive thead.sticky-top th {
        background-color: #1a1d23 !important; /* matches card dark backdrop perfectly */
        color: #d1d5db !important;
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 2px solid rgba(255, 255, 255, 0.08) !important;
    }
    
    /* Customer History Modal Dark Theme adjustments */
    [data-theme="dark"] #customerHistoryModal .modal-content,
    [data-theme="dark"] #customerHistoryModal .modal-header,
    [data-theme="dark"] #customerHistoryModal .modal-body {
        background-color: #1a1d23 !important;
        color: #e5e7eb !important;
    }
    [data-theme="dark"] #customerHistoryModal .btn-close {
        filter: invert(1) grayscale(1) brightness(2);
    }
    [data-theme="dark"] #customerHistoryModal .bg-light {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }
    [data-theme="dark"] #customerHistoryModal .table-light th,
    [data-theme="dark"] #customerHistoryModal .table-light {
        background-color: rgba(0, 0, 0, 0.25) !important;
        color: #9ca3af !important;
    }
    
    /* Order Detail Loader Dark Theme */
    [data-theme="dark"] #orderDetailLoader {
        background-color: rgba(26, 29, 35, 0.85) !important;
    }
    [data-theme="dark"] #orderDetailLoader .loader-text {
        color: #e5e7eb !important;
    }
</style>

<?php
$content = ob_get_clean();

// Javascript calculations & rendering
ob_start();
?>
<script>
    let currentRange = 'last30days';
    let currentMaincat = 1;
    let finTrendChart = null;
    let payChart = null;
    let chanChart = null;
    let segChart = null;

    function changeMaincat(maincatId, labelText) {
        currentMaincat = maincatId;
        document.getElementById('selectedMaincatLabel').innerText = labelText;
        fetchReportingData();
    }

    document.addEventListener("DOMContentLoaded", function () {
        fetchReportingData();
    });

    function changeRange(range, label) {
        currentRange = range;
        document.getElementById('selectedRangeLabel').innerHTML = label;
        
        // Hide custom range picker if not custom
        if (range !== 'custom') {
            document.getElementById('customDateContainer').classList.remove('d-flex');
            document.getElementById('customDateContainer').classList.add('d-none');
            fetchReportingData();
        }
    }

    function toggleCustomDateInputs() {
        document.getElementById('selectedRangeLabel').innerHTML = 'กำหนดเอง';
        document.getElementById('customDateContainer').classList.remove('d-none');
        document.getElementById('customDateContainer').classList.add('d-flex');
    }

    function applyCustomRange() {
        const start = document.getElementById('startDateInput').value;
        const end = document.getElementById('endDateInput').value;
        if (!start || !end) {
            Swal.fire({
                icon: 'warning',
                title: 'กรอกวันที่ไม่ครบ',
                text: 'กรุณาเลือกวันที่เริ่มต้นและสิ้นสุด'
            });
            return;
        }
        currentRange = 'custom';
        fetchReportingData(start, end);
    }

    function fetchReportingData(customStart = '', customEnd = '') {
        const loader = document.getElementById('reportLoader');
        const progressBar = document.getElementById('topProgressBar');
        
        loader.classList.remove('d-none');
        loader.classList.add('d-flex');
        if (progressBar) progressBar.style.display = 'block';

        let url = `<?= ADMIN_URL ?>/api/get_reporting_data.php?range=${currentRange}&maincat_id=${currentMaincat}`;
        if (currentRange === 'custom') {
            url += `&start_date=${customStart}&end_date=${customEnd}`;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                loader.classList.remove('d-flex');
                loader.classList.add('d-none');
                if (progressBar) progressBar.style.display = 'none';
                
                if (data.success) {
                    renderDashboardData(data);
                } else {
                    Swal.fire('Error', data.message || 'ดึงข้อมูลไม่สำเร็จ', 'error');
                }
            })
            .catch(error => {
                loader.classList.remove('d-flex');
                loader.classList.add('d-none');
                if (progressBar) progressBar.style.display = 'none';
                console.error(error);
                Swal.fire('Error', 'เกิดข้อผิดพลาดทางเทคนิคในการเชื่อมต่อ', 'error');
            });
    }

    function renderDashboardData(data) {
        // ----------------------------------------------------
        // Render KPI Top Cards
        // ----------------------------------------------------
        const subtotalVal = parseFloat(data.financial.total_subtotal);
        const discountVal = parseFloat(data.financial.total_discount);
        const cogsVal = parseFloat(data.financial.total_cogs);
        const netProfitVal = parseFloat(data.financial.net_profit);

        const discountPct = subtotalVal > 0 ? ((discountVal / subtotalVal) * 100).toFixed(1) : '0.0';
        const cogsPct = subtotalVal > 0 ? ((cogsVal / subtotalVal) * 100).toFixed(1) : '0.0';
        const netProfitPct = subtotalVal > 0 ? ((netProfitVal / subtotalVal) * 100).toFixed(1) : '0.0';

        document.getElementById('kpi-subtotal').innerText = subtotalVal.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('kpi-discount').innerText = discountVal.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('kpi-discount-pct').innerText = `(${discountPct}%)`;
        document.getElementById('kpi-shipping-margin').innerText = parseFloat(data.financial.shipping_margin).toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('kpi-cogs').innerText = cogsVal.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('kpi-cogs-pct').innerText = `(${cogsPct}%)`;
        document.getElementById('kpi-net-profit').innerText = netProfitVal.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('kpi-net-profit-pct').innerText = `(${netProfitPct}%)`;

        // ----------------------------------------------------
        // Tab 1: Financial Charts
        // ----------------------------------------------------
        // Trend chart (Structure of revenue)
        if (finTrendChart) finTrendChart.destroy();
        const ctxTrend = document.getElementById('financialTrendChart').getContext('2d');
        finTrendChart = new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: ['ยอดรวมผลิตภัณฑ์', 'ส่วนลดสินค้า', 'ต้นทุนผลิตภัณฑ์ (COGS)', 'กำไรขนส่ง', 'กำไรสุทธิสุทธิ'],
                datasets: [{
                    label: 'ยอดสะสมสะสม (บาท)',
                    data: [
                        data.financial.total_subtotal,
                        -data.financial.total_discount,
                        -data.financial.total_cogs,
                        data.financial.shipping_margin,
                        data.financial.net_profit
                    ],
                    backgroundColor: [
                        'rgba(0, 123, 255, 0.75)',
                        'rgba(220, 53, 69, 0.75)',
                        'rgba(108, 117, 125, 0.75)',
                        'rgba(23, 162, 184, 0.75)',
                        'rgba(40, 167, 69, 0.75)'
                    ],
                    borderRadius: 6
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

        // Payment Methods Pie
        if (payChart) payChart.destroy();
        const payLabels = data.financial.payment_methods.map(p => p.paycat_name);
        const payValues = data.financial.payment_methods.map(p => parseFloat(p.total_sales));
        const totalPaySales = payValues.reduce((a, b) => a + b, 0);
        
        const ctxPay = document.getElementById('paymentMethodsChart').getContext('2d');
        payChart = new Chart(ctxPay, {
            type: 'doughnut',
            data: {
                labels: payLabels,
                datasets: [{
                    data: payValues,
                    backgroundColor: ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return ` ${context.label}: ${value.toLocaleString(undefined, {minimumFractionDigits: 2})} บาท (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Render Payment table
        let payHtml = '';
        data.financial.payment_methods.forEach(p => {
            const payPercent = totalPaySales > 0 ? ((parseFloat(p.total_sales) / totalPaySales) * 100).toFixed(1) : '0.0';
            payHtml += `
                <tr>
                    <td><span class="fw-bold">${p.paycat_name}</span></td>
                    <td class="text-center">${p.orders_count} ออเดอร์</td>
                    <td class="text-end fw-bold">${parseFloat(p.total_sales).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-end text-primary fw-bold">${payPercent}%</td>
                </tr>
            `;
        });
        document.getElementById('paymentMethodsTableBody').innerHTML = payHtml || '<tr><td colspan="4" class="text-center text-muted py-2">ไม่มีข้อมูล</td></tr>';

        // ----------------------------------------------------
        // Tab 2: Products sold
        // ----------------------------------------------------
        let prodHtml = '';
        data.products.top_sellers.forEach((p, idx) => {
            const marginPercent = p.gross_sales > 0 ? ((p.gross_profit / p.gross_sales) * 100).toFixed(1) : 0;
            const sharePercent = subtotalVal > 0 ? ((parseFloat(p.gross_sales) / subtotalVal) * 100).toFixed(1) : '0.0';
            const imgUrl = p.product_img ? `<?= ROOT_URL ?>/uploads/products/small-${p.product_img}` : `<?= ADMIN_ASSETS ?>/images/placeholder.png`;
            prodHtml += `
                <tr>
                    <td class="text-center fw-bold text-muted">${idx+1}</td>
                    <td><img src="${imgUrl}" class="rounded border" style="width: 32px; height: 32px; object-fit: cover;"></td>
                    <td><code class="text-primary">${p.product_code}</code></td>
                    <td><span class="fw-bold">${p.product_name}</span></td>
                    <td class="text-center fw-bold text-dark">${p.qty_sold} ชิ้น</td>
                    <td class="text-end fw-bold">${parseFloat(p.gross_sales).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-end text-primary fw-bold">${sharePercent}%</td>
                    <td class="text-end text-muted">${parseFloat(p.total_cogs).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-end fw-bold text-success">${parseFloat(p.gross_profit).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-center">
                        <span class="badge ${marginPercent > 50 ? 'bg-success' : 'bg-primary'} bg-opacity-10 text-dark border">${marginPercent}%</span>
                    </td>
                </tr>
            `;
        });
        document.getElementById('topSellingProductsTableBody').innerHTML = prodHtml || '<tr><td colspan="10" class="text-center text-muted py-3">ไม่มีข้อมูลการขายในช่วงนี้</td></tr>';

        // Category breakdown
        const totalCatSales = data.products.brands.reduce((sum, b) => sum + parseFloat(b.gross_sales), 0);
        let catHtml = '';
        data.products.brands.forEach(b => {
            const catPercent = totalCatSales > 0 ? ((parseFloat(b.gross_sales) / totalCatSales) * 100).toFixed(1) : '0.0';
            catHtml += `
                <tr>
                    <td><span class="fw-bold text-dark">${b.productcat_name}</span></td>
                    <td class="text-center fw-bold">${b.qty_sold} ชิ้น</td>
                    <td class="text-end fw-bold text-primary">${parseFloat(b.gross_sales).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-end text-primary fw-bold">${catPercent}%</td>
                </tr>
            `;
        });
        document.getElementById('categorySalesTableBody').innerHTML = catHtml || '<tr><td colspan="4" class="text-center text-muted py-2">ไม่มีข้อมูล</td></tr>';

        // ----------------------------------------------------
        // Tab 3: Staff & Agents
        // ----------------------------------------------------
        const totalStaffSales = data.performance.staff.reduce((sum, s) => sum + parseFloat(s.total_sales), 0);
        let staffHtml = '';
        data.performance.staff.forEach(s => {
            const staffPercent = totalStaffSales > 0 ? ((parseFloat(s.total_sales) / totalStaffSales) * 100).toFixed(1) : '0.0';
            staffHtml += `
                <tr>
                    <td><span class="fw-bold text-dark">${s.user_name} ${s.user_lastname}</span></td>
                    <td class="text-center fw-bold text-muted">${s.orders_count} ออเดอร์</td>
                    <td class="text-end fw-bold text-primary">${parseFloat(s.total_sales).toLocaleString(undefined, {minimumFractionDigits: 2})} บาท</td>
                    <td class="text-end text-primary fw-bold">${staffPercent}%</td>
                </tr>
            `;
        });
        document.getElementById('staffPerformanceTableBody').innerHTML = staffHtml || '<tr><td colspan="4" class="text-center text-muted py-2">ยังไม่มีผลงานบันทึกเข้ามา</td></tr>';

        const totalAgentSales = data.performance.agents.reduce((sum, a) => sum + parseFloat(a.total_sales), 0);
        let agentHtml = '';
        data.performance.agents.forEach(a => {
            const agentPercent = totalAgentSales > 0 ? ((parseFloat(a.total_sales) / totalAgentSales) * 100).toFixed(1) : '0.0';
            agentHtml += `
                <tr>
                    <td><span class="fw-bold text-dark">${a.user_name} ${a.user_lastname}</span></td>
                    <td class="text-center fw-bold text-muted">${a.orders_count} ออเดอร์</td>
                    <td class="text-end fw-bold text-success">${parseFloat(a.total_sales).toLocaleString(undefined, {minimumFractionDigits: 2})} บาท</td>
                    <td class="text-end text-success fw-bold">${agentPercent}%</td>
                </tr>
            `;
        });
        document.getElementById('agentPerformanceTableBody').innerHTML = agentHtml || '<tr><td colspan="4" class="text-center text-muted py-2">ไม่มีประวัติยอดขายตัวแทนจำหน่าย</td></tr>';

        // ----------------------------------------------------
        // Tab 4: Channels & Countries
        // ----------------------------------------------------
        if (chanChart) chanChart.destroy();
        const chanLabels = data.distribution.channels.map(c => c.channel_name);
        const chanValues = data.distribution.channels.map(c => parseFloat(c.total_sales));
        const totalChanSales = chanValues.reduce((a, b) => a + b, 0);

        const ctxChan = document.getElementById('salesChannelsChart').getContext('2d');
        chanChart = new Chart(ctxChan, {
            type: 'doughnut',
            data: {
                labels: chanLabels,
                datasets: [{
                    data: chanValues,
                    backgroundColor: ['#06C755', '#1877F2', '#010101', '#101566', '#EE4D2D', '#6f42c1', '#17a2b8', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return ` ${context.label}: ${value.toLocaleString(undefined, {minimumFractionDigits: 2})} บาท (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        let chanHtml = '';
        data.distribution.channels.forEach(c => {
            const chanPercent = totalChanSales > 0 ? ((parseFloat(c.total_sales) / totalChanSales) * 100).toFixed(1) : '0.0';
            let badgeClass = 'bg-secondary';
            if (c.orders_channel === 'line') badgeClass = 'bg-success';
            if (c.orders_channel === 'fb') badgeClass = 'bg-primary';
            if (c.orders_channel === 'shopee') badgeClass = 'bg-warning text-dark';
            if (c.orders_channel === 'lazada') badgeClass = 'bg-info text-white';
            if (c.orders_channel === 'tiktok') badgeClass = 'bg-dark';
            if (c.orders_channel === 'therapist') badgeClass = 'bg-purple';

            chanHtml += `
                <tr>
                    <td>
                        <span class="badge ${badgeClass} bg-opacity-10 text-dark border me-2">${c.orders_channel.toUpperCase()}</span>
                        <span class="fw-bold">${c.channel_name}</span>
                    </td>
                    <td class="text-center fw-medium">${c.orders_count}</td>
                    <td class="text-end fw-bold text-primary">${parseFloat(c.total_sales).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-end text-primary fw-bold">${chanPercent}%</td>
                </tr>
            `;
        });
        document.getElementById('channelsTableBody').innerHTML = chanHtml || '<tr><td colspan="4" class="text-center text-muted py-2">ไม่มีข้อมูล</td></tr>';

        // Countries table
        const totalCounSales = data.distribution.countries.reduce((sum, c) => sum + parseFloat(c.total_sales), 0);
        let counHtml = '';
        data.distribution.countries.forEach(c => {
            const counPercent = totalCounSales > 0 ? ((parseFloat(c.total_sales) / totalCounSales) * 100).toFixed(1) : '0.0';
            const isThailand = c.orders_country_id == 171;
            const flagBadge = isThailand ? 'bg-success bg-opacity-10 text-success' : (c.orders_country_id == 0 ? 'bg-secondary bg-opacity-10 text-muted' : 'bg-warning bg-opacity-10 text-warning-emphasis');
            counHtml += `
                <tr>
                    <td><span class="fw-bold text-dark">${c.country_name}</span></td>
                    <td class="text-center"><span class="badge ${flagBadge} border small">${c.country_code}</span></td>
                    <td class="text-center fw-bold">${c.orders_count} ออเดอร์</td>
                    <td class="text-end fw-bold text-primary">${parseFloat(c.total_sales).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-end text-primary fw-bold">${counPercent}%</td>
                </tr>
            `;
        });
        document.getElementById('countriesTableBody').innerHTML = counHtml || '<tr><td colspan="5" class="text-center text-muted py-2">ไม่มีข้อมูลประเทศ</td></tr>';

        // ----------------------------------------------------
        // Tab 5: RFM & Segmentation / Alerts
        // ----------------------------------------------------
        const segRepeatCount = parseInt(data.rfm.segmentation_counts.repeat);
        const segOnetimeCount = parseInt(data.rfm.segmentation_counts.onetime);
        const segTotalCount = segRepeatCount + segOnetimeCount;
        
        const segRepeatPct = segTotalCount > 0 ? ((segRepeatCount / segTotalCount) * 100).toFixed(1) : '0.0';
        const segOnetimePct = segTotalCount > 0 ? ((segOnetimeCount / segTotalCount) * 100).toFixed(1) : '0.0';

        document.getElementById('seg-count-repeat').innerText = segRepeatCount;
        document.getElementById('seg-count-onetime').innerText = segOnetimeCount;
        document.getElementById('seg-pct-repeat').innerText = `(${segRepeatPct}%)`;
        document.getElementById('seg-pct-onetime').innerText = `(${segOnetimePct}%)`;

        if (segChart) segChart.destroy();
        const ctxSeg = document.getElementById('customerSegmentationChart').getContext('2d');
        segChart = new Chart(ctxSeg, {
            type: 'doughnut',
            data: {
                labels: ['ซื้อซ้ำ (Repeat)', 'ซื้อครั้งเดียว (One-time)'],
                datasets: [{
                    data: [segRepeatCount, segOnetimeCount],
                    backgroundColor: ['#007bff', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return ` ${context.label}: ${value} คน (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Top Spenders
        let spendHtml = '';
        data.rfm.top_spenders.forEach(t => {
            const cid = 'C' + String(t.user_id).padStart(6, '0');
            const imgPath = t.user_picture ? `<?= ROOT_URL ?>/uploads/profile/${t.user_id}/${t.user_picture}` : `<?= ROOT_URL ?>/uploads/profile/default.png`;
            spendHtml += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle overflow-hidden border border-secondary border-opacity-25" style="width: 32px; height: 32px; flex-shrink: 0;">
                                <img src="${imgPath}" alt="User" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='<?= ROOT_URL ?>/uploads/profile/default.png'">
                            </div>
                            <div>
                                <span class="badge bg-secondary bg-opacity-10 text-muted border mb-1" style="font-size: 10px;">${cid}</span>
                                <div>
                                    <a href="javascript:void(0)" class="fw-bold text-dark text-decoration-none" onclick="showCustomerPurchaseHistory(${t.user_id}, this)">${t.user_name} ${t.user_lastname}</a>
                                </div>
                                <small class="text-muted d-block" style="font-size: 10px;">${t.user_tel || t.user_email || '-'}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center fw-bold">${t.total_orders} ออเดอร์</td>
                    <td class="text-end fw-bold text-primary">${parseFloat(t.lifetime_value).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-end text-muted fw-medium">${parseFloat(t.avg_order_value).toLocaleString(undefined, {minimumFractionDigits: 1})}</td>
                </tr>
            `;
        });
        document.getElementById('topSpendersTableBody').innerHTML = spendHtml || '<tr><td colspan="4" class="text-center text-muted py-2">ไม่มีข้อมูล</td></tr>';

        // Segmented Users table
        let segUserHtml = '';
        data.rfm.segmented_users.forEach(s => {
            const isRepeat = s.segment.includes('ซ้ำ');
            const segBadge = isRepeat ? 'bg-primary text-white' : 'bg-secondary text-white';
            const cid = 'C' + String(s.user_id).padStart(6, '0');
            const imgPath = s.user_picture ? `<?= ROOT_URL ?>/uploads/profile/${s.user_id}/${s.user_picture}` : `<?= ROOT_URL ?>/uploads/profile/default.png`;
            segUserHtml += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle overflow-hidden border border-secondary border-opacity-25" style="width: 32px; height: 32px; flex-shrink: 0;">
                                <img src="${imgPath}" alt="User" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='<?= ROOT_URL ?>/uploads/profile/default.png'">
                            </div>
                            <div>
                                <span class="badge bg-secondary bg-opacity-10 text-muted border mb-1" style="font-size: 10px;">${cid}</span>
                                <div>
                                    <a href="javascript:void(0)" class="fw-bold text-dark text-decoration-none" onclick="showCustomerPurchaseHistory(${s.user_id}, this)">${s.name}</a>
                                </div>
                                <small class="text-muted d-block" style="font-size: 10px;">${s.tel || '-'}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge ${segBadge} small fw-bold">${s.segment}</span></td>
                    <td class="text-center fw-bold">${s.total_orders}</td>
                    <td class="text-end fw-bold text-dark">${parseFloat(s.total_spent).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                </tr>
            `;
        });
        document.getElementById('segmentedUsersTableBody').innerHTML = segUserHtml || '<tr><td colspan="4" class="text-center text-muted py-2">ไม่มีข้อมูล</td></tr>';

        // Recent Customers table
        let recentHtml = '';
        if (data.rfm.recent_customers) {
            data.rfm.recent_customers.forEach(r => {
                const cid = 'C' + String(r.user_id).padStart(6, '0');
                const imgPath = r.user_picture ? `<?= ROOT_URL ?>/uploads/profile/${r.user_id}/${r.user_picture}` : `<?= ROOT_URL ?>/uploads/profile/default.png`;
                recentHtml += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle overflow-hidden border border-secondary border-opacity-25" style="width: 32px; height: 32px; flex-shrink: 0;">
                                    <img src="${imgPath}" alt="User" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='<?= ROOT_URL ?>/uploads/profile/default.png'">
                                </div>
                                <div>
                                    <span class="badge bg-secondary bg-opacity-10 text-muted border mb-1" style="font-size: 10px;">${cid}</span>
                                    <div>
                                        <a href="javascript:void(0)" class="fw-bold text-dark text-decoration-none" onclick="showCustomerPurchaseHistory(${r.user_id}, this)">${r.user_name} ${r.user_lastname}</a>
                                    </div>
                                    <small class="text-muted d-block" style="font-size: 10px;">${r.user_tel || r.user_email || '-'}</small>
                                </div>
                            </div>
                        </td>
                        <td><a href="javascript:void(0)" onclick="showOrderDetailModal(${r.orders_id})" class="text-decoration-none"><code class="text-primary fw-bold" style="cursor:pointer;">${r.orders_no}</code></a></td>
                        <td class="text-center text-muted">${r.last_order_date}</td>
                        <td class="text-end fw-bold text-success">${parseFloat(r.orders_grandtotal).toLocaleString(undefined, {minimumFractionDigits: 2})} บาท</td>
                    </tr>
                `;
            });
        }
        document.getElementById('recentCustomersTableBody').innerHTML = recentHtml || '<tr><td colspan="4" class="text-center text-muted py-2">ไม่มีข้อมูลลูกค้าย้อนหลังในกรอบเวลานี้</td></tr>';

        // Repeat Alerts Table
        document.getElementById('system-avg-interval').innerText = data.rfm.system_avg_interval;
        
        let alertHtml = '';
        const alertCount = data.rfm.repeat_alerts.length;
        const alertBadge = document.getElementById('alertCountBadge');
        
        if (alertCount > 0) {
            alertBadge.innerText = alertCount;
            alertBadge.classList.remove('d-none');
        } else {
            alertBadge.classList.add('d-none');
        }

        data.rfm.repeat_alerts.forEach(a => {
            const cid = 'C' + String(a.user_id).padStart(6, '0');
            const imgPath = a.user_picture ? `<?= ROOT_URL ?>/uploads/profile/${a.user_id}/${a.user_picture}` : `<?= ROOT_URL ?>/uploads/profile/default.png`;
            alertHtml += `
                <tr class="table-danger-hover">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle overflow-hidden border border-secondary border-opacity-25" style="width: 32px; height: 32px; flex-shrink: 0;">
                                <img src="${imgPath}" alt="User" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='<?= ROOT_URL ?>/uploads/profile/default.png'">
                            </div>
                            <div>
                                <span class="badge bg-secondary bg-opacity-10 text-muted border mb-1" style="font-size: 10px;">${cid}</span>
                                <div>
                                    <a href="javascript:void(0)" class="fw-bold text-dark text-decoration-none" onclick="showCustomerPurchaseHistory(${a.user_id}, this)">${a.name}</a>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><code>${a.tel || '-'}</code></td>
                    <td class="text-center fw-bold text-primary">${a.total_orders} ออเดอร์</td>
                    <td class="text-center fw-bold text-muted">${a.avg_interval} วัน</td>
                    <td class="text-center fw-bold text-danger animate-pulse">${a.days_since_last} วัน</td>
                    <td class="text-center text-muted">${a.last_order_date}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-history py-1 px-2 rounded fw-bold me-1" onclick="showCustomerPurchaseHistory(${a.user_id}, this)" style="font-size: 10px;">
                            <i data-lucide="eye" style="width: 10px; height: 10px;" class="me-1"></i> ประวัติ
                        </button>
                        <button class="btn btn-xs btn-outline-danger py-1 px-2 rounded fw-bold" onclick="triggerCustomerAlert(${a.user_id}, '${a.name}', '${a.tel}')" style="font-size: 10px;">
                            <i data-lucide="phone-call" style="width: 10px; height: 10px;" class="me-1"></i> ติดตาม
                        </button>
                    </td>
                </tr>
            `;
        });
        document.getElementById('repeatAlertsTableBody').innerHTML = alertHtml || '<tr><td colspan="7" class="text-center text-muted py-3">🎉 ไม่มีลูกค้าที่เลยกำหนดรอบซื้อเฉลี่ยในขณะนี้</td></tr>';
        
        // Refresh Lucide Icons
        if (window.lucide) {
            lucide.createIcons();
        }
    }

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
                    
                    // Name & Nickname
                    let displayName = cust.user_name + ' ' + (cust.user_lastname || '');
                    if (cust.user_nickname) {
                        displayName += ` (${cust.user_nickname})`;
                    }
                    document.getElementById('history-cust-name').innerText = displayName;

                    // Picture
                    const avatarUrl = cust.user_picture ? `<?= ROOT_URL ?>/uploads/profile/${cust.user_id}/${cust.user_picture}` : `<?= ROOT_URL ?>/uploads/profile/default.png`;
                    document.getElementById('history-cust-pic').src = avatarUrl;
                    document.getElementById('history-cust-pic').onerror = function() {
                        this.src = '<?= ROOT_URL ?>/uploads/profile/default.png';
                    };

                    // Contact
                    document.getElementById('history-cust-contact').innerHTML = `
                        <div class="d-flex align-items-center gap-1"><i data-lucide="phone" style="width: 12px; height: 12px;"></i> เบอร์โทร: ${cust.user_mobile || cust.user_tel || '-'}</div>
                        <div class="d-flex align-items-center gap-1 mt-1"><i data-lucide="mail" style="width: 12px; height: 12px;"></i> อีเมล: ${cust.user_email || '-'}</div>
                    `;

                    // Channel and Country
                    document.getElementById('history-cust-channel').innerText = 'ช่องทาง: ' + (cust.last_channel || '-').toUpperCase();
                    document.getElementById('history-cust-country').innerText = 'ประเทศ: ' + (cust.last_country || 'ประเทศไทย');

                    // Fetch Profile Picture History
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
                                <td><a href="javascript:void(0)" onclick="showOrderDetailModal(${o.orders_id})" class="text-decoration-none"><code class="text-primary fw-bold" style="cursor:pointer;">${o.orders_number}</code></a></td>
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

    function showOrderDetailModal(orderId) {
        const iframe = document.getElementById('orderDetailIframe');
        const loader = document.getElementById('orderDetailLoader');
        
        // Show loader and hide iframe
        loader.classList.remove('d-none');
        loader.classList.add('d-flex');
        iframe.style.opacity = '0';
        
        // Handle load completion
        iframe.onload = function() {
            loader.classList.remove('d-flex');
            loader.classList.add('d-none');
            iframe.style.opacity = '1';
        };
        
        // Fetch new order
        iframe.src = `<?= ADMIN_URL ?>/orders/print.php?id=${orderId}&mode=full`;
        
        const modalEl = document.getElementById('orderDetailModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function triggerCustomerAlert(userId, name, tel) {
        const cid = 'C' + String(userId).padStart(6, '0');
        Swal.fire({
            title: 'ติดตามลูกค้ารอบซื้อซ้ำ 📞',
            icon: 'info',
            html: `
                <div class="text-start">
                    <!-- Customer ID copy block -->
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: rgba(0,0,0,0.08) !important;">
                        <div>
                            <strong>รหัสลูกค้า:</strong> 
                            <code class="text-primary fw-bold fs-6 ms-1" id="swal-cust-id">${cid}</code>
                        </div>
                        <button class="btn btn-xs btn-outline-primary py-1 px-2 rounded fw-bold" onclick="navigator.clipboard.writeText('${cid}'); Swal.showValidationMessage('คัดลอกรหัสลูกค้า ${cid} สำเร็จแล้ว!');" style="font-size: 11px;">
                            คัดลอกรหัส
                        </button>
                    </div>

                    <p class="mb-2"><strong>ชื่อลูกค้า:</strong> ${name}</p>
                    
                    <!-- Customer Phone copy block -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong>เบอร์โทรศัพท์:</strong> 
                            <span class="fw-bold text-dark ms-1" id="swal-cust-tel">${tel || 'ยังไม่ได้ระบุ'}</span>
                        </div>
                        ${tel ? `
                        <button class="btn btn-xs btn-outline-secondary py-1 px-2 rounded fw-bold" onclick="navigator.clipboard.writeText('${tel}'); Swal.showValidationMessage('คัดลอกเบอร์โทร ${tel} สำเร็จแล้ว!');" style="font-size: 11px;">
                            คัดลอกเบอร์
                        </button>
                        ` : ''}
                    </div>

                    <div class="alert alert-light border small text-muted text-start mt-2 mb-0" id="swal-alert-tip">
                        ระบบแนะนำให้ฝ่ายขายนำรหัสลูกค้า <code class="text-primary fw-bold">${cid}</code> ไปค้นหาในห้องแชทของระบบ (LINE OA, Facebook, Website) หรือโทรติดต่อเบอร์ <span class="fw-bold text-dark">${tel || '-'}</span> เพื่อเสนอโปรโมชั่นกระตุ้นการตัดสินใจซื้อซ้ำทันทีครับ
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'คัดลอกเบอร์โทร',
            cancelButtonText: 'ปิด',
            confirmButtonColor: '#007bff'
        }).then(result => {
            if (result.isConfirmed && tel) {
                navigator.clipboard.writeText(tel).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'คัดลอกเบอร์สำเร็จ!',
                        timer: 1000,
                        showConfirmButton: false
                    });
                });
            }
        });

        // Dynamic adjustment for dark theme inside Swal HTML
        if (document.documentElement.getAttribute('data-theme') === 'dark') {
            setTimeout(() => {
                const telEl = document.getElementById('swal-cust-tel');
                if (telEl) {
                    telEl.classList.remove('text-dark');
                    telEl.classList.add('text-light');
                }
                const tipEl = document.getElementById('swal-alert-tip');
                if (tipEl) {
                    tipEl.classList.remove('alert-light', 'text-muted');
                    tipEl.style.backgroundColor = 'rgba(255, 255, 255, 0.03)';
                    tipEl.style.color = '#9ca3af';
                    tipEl.style.borderColor = 'rgba(255,255,255,0.08)';
                }
                const tipText = tipEl.querySelector('.text-dark');
                if (tipText) {
                    tipText.classList.remove('text-dark');
                    tipText.classList.add('text-light');
                }
            }, 50);
        }
    }
    function showAllRecentCustomers(page = 1) {
        const progressBar = document.getElementById('topProgressBar');
        if (progressBar) progressBar.style.display = 'block';

        // Show modal immediately with loading state
        const modalEl = document.getElementById('allRecentCustomersModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        if (!modalEl.classList.contains('show')) {
            modal.show();
        }

        document.getElementById('allRecentCustomersTableBody').innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                    <div>กำลังโหลดข้อมูล...</div>
                </td>
            </tr>
        `;
        document.getElementById('allRecentCustomersPagination').innerHTML = '';

        let url = `<?= ADMIN_URL ?>/api/get_all_recent_customers.php?page=${page}&range=${currentRange}&maincat_id=${currentMaincat}`;
        
        // Add date filters if needed
        if (currentRange === 'custom') {
            const start = document.getElementById('startDateInput').value;
            const end = document.getElementById('endDateInput').value;
            if (start && end) url += `&start_date=${start}&end_date=${end}`;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (progressBar) progressBar.style.display = 'none';
                
                if (data.success) {
                    let html = '';
                    if (data.data && data.data.length > 0) {
                        data.data.forEach(r => {
                            const cid = 'C' + String(r.user_id).padStart(6, '0');
                            const imgPath = r.user_picture ? `<?= ROOT_URL ?>/uploads/profile/${r.user_id}/${r.user_picture}` : `<?= ROOT_URL ?>/uploads/profile/default.png`;
                            html += `
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle overflow-hidden border border-secondary border-opacity-25" style="width: 32px; height: 32px; flex-shrink: 0;">
                                                <img src="${imgPath}" alt="User" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='<?= ROOT_URL ?>/uploads/profile/default.png'">
                                            </div>
                                            <div>
                                                <span class="badge bg-secondary bg-opacity-10 text-muted border mb-1" style="font-size: 10px;">${cid}</span>
                                                <div>
                                                    <a href="javascript:void(0)" class="fw-bold text-dark text-decoration-none" onclick="showCustomerPurchaseHistory(${r.user_id}, this)">${r.user_name} ${r.user_lastname}</a>
                                                </div>
                                                <small class="text-muted d-block" style="font-size: 10px;">${r.user_tel || r.user_email || '-'}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><a href="javascript:void(0)" onclick="showOrderDetailModal(${r.orders_id})" class="text-decoration-none"><code class="text-primary fw-bold" style="cursor:pointer;">${r.orders_no}</code></a></td>
                                    <td class="text-center text-muted">${r.last_order_date}</td>
                                    <td class="text-end fw-bold text-success">${parseFloat(r.orders_grandtotal).toLocaleString(undefined, {minimumFractionDigits: 2})} บาท</td>
                                </tr>
                            `;
                        });
                    } else {
                        html = '<tr><td colspan="4" class="text-center text-muted py-4">ไม่มีข้อมูลในกรอบเวลานี้</td></tr>';
                    }
                    
                    document.getElementById('allRecentCustomersTableBody').innerHTML = html;

                    // Build Pagination
                    let pgHtml = '';
                    if (data.pagination.total_pages > 1) {
                        pgHtml += `<span class="text-muted small">แสดง ${data.data.length} จาก ${data.pagination.total_records} รายการ</span>`;
                        pgHtml += `<nav><ul class="pagination pagination-sm mb-0">`;
                        
                        // Prev
                        if (data.pagination.current_page > 1) {
                            pgHtml += `<li class="page-item"><a class="page-link cursor-pointer" onclick="showAllRecentCustomers(${data.pagination.current_page - 1})">ก่อนหน้า</a></li>`;
                        } else {
                            pgHtml += `<li class="page-item disabled"><span class="page-link">ก่อนหน้า</span></li>`;
                        }

                        // Pages
                        let startPage = Math.max(1, data.pagination.current_page - 2);
                        let endPage = Math.min(data.pagination.total_pages, data.pagination.current_page + 2);
                        
                        for (let i = startPage; i <= endPage; i++) {
                            if (i === data.pagination.current_page) {
                                pgHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                            } else {
                                pgHtml += `<li class="page-item"><a class="page-link cursor-pointer" onclick="showAllRecentCustomers(${i})">${i}</a></li>`;
                            }
                        }

                        // Next
                        if (data.pagination.current_page < data.pagination.total_pages) {
                            pgHtml += `<li class="page-item"><a class="page-link cursor-pointer" onclick="showAllRecentCustomers(${data.pagination.current_page + 1})">ถัดไป</a></li>`;
                        } else {
                            pgHtml += `<li class="page-item disabled"><span class="page-link">ถัดไป</span></li>`;
                        }
                        
                        pgHtml += `</ul></nav>`;
                    }
                    document.getElementById('allRecentCustomersPagination').innerHTML = pgHtml;
                    
                    if (document.documentElement.getAttribute('data-theme') === 'dark') {
                        document.querySelectorAll('#allRecentCustomersTableBody .text-dark').forEach(el => {
                            el.classList.remove('text-dark');
                            el.classList.add('text-light');
                        });
                    }
                    
                    setTimeout(() => {
                        if (window.lucide) lucide.createIcons();
                    }, 200);

                } else {
                    Swal.fire('Error', data.message || 'ดึงข้อมูลไม่สำเร็จ', 'error');
                }
            })
            .catch(error => {
                if (progressBar) progressBar.style.display = 'none';
                console.error(error);
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการดึงข้อมูลลูกค้าล่าสุด', 'error');
            });
    }

    function showAllOverdueCustomers(page = 1) {
        const progressBar = document.getElementById('topProgressBar');
        if (progressBar) progressBar.style.display = 'block';

        const modalEl = document.getElementById('allOverdueCustomersModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        if (!modalEl.classList.contains('show')) {
            modal.show();
        }

        document.getElementById('allOverdueCustomersTableBody').innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm text-danger mb-2" role="status"></div>
                    <div>กำลังโหลดข้อมูล...</div>
                </td>
            </tr>
        `;
        document.getElementById('allOverdueCustomersPagination').innerHTML = '';

        let url = `<?= ADMIN_URL ?>/api/get_all_overdue_customers.php?page=${page}&maincat_id=${currentMaincat}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (progressBar) progressBar.style.display = 'none';
                
                if (data.success) {
                    let html = '';
                    if (data.data && data.data.length > 0) {
                        data.data.forEach(r => {
                            const cid = 'C' + String(r.user_id).padStart(6, '0');
                            const imgPath = r.user_picture ? `<?= ROOT_URL ?>/uploads/profile/${r.user_id}/${r.user_picture}` : `<?= ROOT_URL ?>/uploads/profile/default.png`;
                            html += `
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle overflow-hidden border border-secondary border-opacity-25" style="width: 32px; height: 32px; flex-shrink: 0;">
                                                <img src="${imgPath}" alt="User" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='<?= ROOT_URL ?>/uploads/profile/default.png'">
                                            </div>
                                            <div>
                                                <span class="badge bg-secondary bg-opacity-10 text-muted border mb-1" style="font-size: 10px;">${cid}</span>
                                                <div>
                                                    <a href="javascript:void(0)" class="fw-bold text-dark text-decoration-none" onclick="showCustomerPurchaseHistory(${r.user_id}, this)">${r.name}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><small class="text-muted">${r.tel || '-'}</small></td>
                                    <td class="text-center fw-bold text-primary">${r.total_orders} ออเดอร์</td>
                                    <td class="text-center">${r.avg_interval} วัน</td>
                                    <td class="text-center fw-bold text-danger">${r.days_since_last} วัน</td>
                                    <td class="text-center text-muted">${r.last_order_date}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary me-1 px-2 py-1" style="font-size: 11px;" onclick="showCustomerPurchaseHistory(${r.user_id}, this)">
                                            <i data-lucide="eye" style="width: 12px; height: 12px;" class="me-1"></i> ประวัติ
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger px-2 py-1" style="font-size: 11px;" onclick="triggerCustomerAlert(${r.user_id}, '${r.name}', '${r.tel}')">
                                            <i data-lucide="phone" style="width: 12px; height: 12px;" class="me-1"></i> ติดตาม
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        html = '<tr><td colspan="7" class="text-center text-muted py-4">ไม่พบลูกค้ารอบซื้อซ้ำที่เลยกำหนด</td></tr>';
                    }
                    
                    document.getElementById('allOverdueCustomersTableBody').innerHTML = html;

                    // Build Pagination
                    let pgHtml = '';
                    if (data.pagination.total_pages > 1) {
                        pgHtml += `<span class="text-muted small">แสดง ${data.data.length} จาก ${data.pagination.total_records} รายการ</span>`;
                        pgHtml += `<nav><ul class="pagination pagination-sm mb-0">`;
                        
                        if (data.pagination.current_page > 1) {
                            pgHtml += `<li class="page-item"><a class="page-link cursor-pointer" onclick="showAllOverdueCustomers(${data.pagination.current_page - 1})">ก่อนหน้า</a></li>`;
                        } else {
                            pgHtml += `<li class="page-item disabled"><span class="page-link">ก่อนหน้า</span></li>`;
                        }

                        let startPage = Math.max(1, data.pagination.current_page - 2);
                        let endPage = Math.min(data.pagination.total_pages, data.pagination.current_page + 2);
                        
                        for (let i = startPage; i <= endPage; i++) {
                            if (i === data.pagination.current_page) {
                                pgHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                            } else {
                                pgHtml += `<li class="page-item"><a class="page-link cursor-pointer" onclick="showAllOverdueCustomers(${i})">${i}</a></li>`;
                            }
                        }

                        if (data.pagination.current_page < data.pagination.total_pages) {
                            pgHtml += `<li class="page-item"><a class="page-link cursor-pointer" onclick="showAllOverdueCustomers(${data.pagination.current_page + 1})">ถัดไป</a></li>`;
                        } else {
                            pgHtml += `<li class="page-item disabled"><span class="page-link">ถัดไป</span></li>`;
                        }
                        
                        pgHtml += `</ul></nav>`;
                    }
                    document.getElementById('allOverdueCustomersPagination').innerHTML = pgHtml;
                    
                    if (document.documentElement.getAttribute('data-theme') === 'dark') {
                        document.querySelectorAll('#allOverdueCustomersTableBody .text-dark').forEach(el => {
                            el.classList.remove('text-dark');
                            el.classList.add('text-light');
                        });
                    }
                    
                    setTimeout(() => {
                        if (window.lucide) lucide.createIcons();
                    }, 200);

                } else {
                    Swal.fire('Error', data.message || 'ดึงข้อมูลไม่สำเร็จ', 'error');
                }
            })
            .catch(error => {
                if (progressBar) progressBar.style.display = 'none';
                console.error(error);
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการดึงข้อมูลลูกค้ารอบซื้อซ้ำ', 'error');
            });
    }
</script>

<?php
$extra_scripts = ob_get_clean();

include '../includes/layout.php';
?>
