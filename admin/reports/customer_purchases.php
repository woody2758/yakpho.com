<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$page_title = "รายงานการซื้อของลูกค้า (Customer Purchase Report)";

$maincatId = isset($_GET['maincat_id']) ? (int)$_GET['maincat_id'] : 1;

// Fetch Main Categories for the dropdown
$stmt_mc = $db->query("
    SELECT c.maincat_id, ct_th.maincat_name 
    FROM maincat c
    LEFT JOIN maincat_translations ct_th ON c.maincat_id = ct_th.maincat_id AND ct_th.lang_code = 'th'
    WHERE c.maincat_del = 0
    ORDER BY c.maincat_index ASC
");
$maincats = $stmt_mc->fetchAll(PDO::FETCH_ASSOC);

// Prepare maincat filter condition
$maincatWhereOrders = "";
$params = [];
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

// Query for New Customers (1 order)
$sql_new = "
    SELECT u.user_id, u.user_name, u.user_lastname, u.user_nickname, u.user_mobile, u.user_tel, u.user_picture, u.user_status, u.user_stars,
           MAX(o.orders_date) as last_order_date,
           DATEDIFF(NOW(), MAX(o.orders_date)) as days_since_last_order,
           COUNT(o.orders_id) as total_orders,
           SUM(o.orders_grandtotal) as total_spent
    FROM user u
    JOIN orders o ON u.user_id = o.user_id
    WHERE o.orders_del = 0 AND o.orders_status = 6 $maincatWhereOrders
    GROUP BY u.user_id
    HAVING total_orders = 1
    ORDER BY last_order_date DESC
";
$stmt_new = $db->prepare($sql_new);
$stmt_new->execute($params);
$new_customers_base = $stmt_new->fetchAll(PDO::FETCH_ASSOC);

// Query for Returning Customers (>= 2 orders)
$sql_return = "
    SELECT u.user_id, u.user_name, u.user_lastname, u.user_nickname, u.user_mobile, u.user_tel, u.user_picture, u.user_status, u.user_stars,
           MAX(o.orders_date) as last_order_date,
           MIN(o.orders_date) as first_order_date,
           DATEDIFF(NOW(), MAX(o.orders_date)) as days_since_last_order,
           COUNT(o.orders_id) as total_orders,
           SUM(o.orders_grandtotal) as total_spent,
           ROUND(DATEDIFF(MAX(o.orders_date), MIN(o.orders_date)) / (COUNT(o.orders_id) - 1)) as avg_days_between_orders
    FROM user u
    JOIN orders o ON u.user_id = o.user_id
    WHERE o.orders_del = 0 AND o.orders_status = 6 $maincatWhereOrders
    GROUP BY u.user_id
    HAVING total_orders >= 2
    ORDER BY total_orders DESC, total_spent DESC
";
$stmt_return = $db->prepare($sql_return);
$stmt_return->execute($params);
$returning_customers_base = $stmt_return->fetchAll(PDO::FETCH_ASSOC);

// Batch fetch additional details to prevent N+1 and slow subqueries
$all_users = array_merge($new_customers_base, $returning_customers_base);
$userIds = array_column($all_users, 'user_id');

$userExtraData = [];
if (!empty($userIds)) {
    $inPlaceholders = str_repeat('?,', count($userIds) - 1) . '?';

    // 1. Fetch tags
    $stmt_tags = $db->prepare("SELECT m.user_id, GROUP_CONCAT(CONCAT(t.tag_name, '::', IFNULL(t.tag_color, 'secondary')) SEPARATOR '||') as user_tags_str 
                               FROM user_tag_mapping m 
                               JOIN user_tags t ON m.tag_id = t.tag_id 
                               WHERE m.user_id IN ($inPlaceholders) 
                               GROUP BY m.user_id");
    $stmt_tags->execute($userIds);
    while ($row = $stmt_tags->fetch(PDO::FETCH_ASSOC)) {
        $userExtraData[$row['user_id']]['user_tags_str'] = $row['user_tags_str'];
    }

    // 2. Fetch address counts
    $stmt_addr = $db->prepare("SELECT user_id, COUNT(addr_id) as address_count FROM addr WHERE user_id IN ($inPlaceholders) GROUP BY user_id");
    $stmt_addr->execute($userIds);
    while ($row = $stmt_addr->fetch(PDO::FETCH_ASSOC)) {
        $userExtraData[$row['user_id']]['address_count'] = $row['address_count'];
    }

    // 3. Fetch followups
    $stmt_f = $db->prepare("SELECT user_id, COUNT(followup_id) as followup_count, MAX(followup_date) as last_followup_date 
                            FROM customer_followups WHERE user_id IN ($inPlaceholders) GROUP BY user_id");
    $stmt_f->execute($userIds);
    while ($row = $stmt_f->fetch(PDO::FETCH_ASSOC)) {
        $userExtraData[$row['user_id']]['followup_count'] = $row['followup_count'];
        $userExtraData[$row['user_id']]['last_followup_date'] = $row['last_followup_date'];
    }
    
    // 4. Fetch last order details using a window function approach logic but via IN clause
    // To do this fast, we get all orders for these users, sorted, and pick the first per user in PHP
    $stmt_last_order = $db->prepare("SELECT user_id, orders_id, orders_channel, orders_country_id, orders_date 
                                     FROM orders 
                                     WHERE user_id IN ($inPlaceholders) AND orders_del = 0 
                                     ORDER BY orders_date DESC, orders_id DESC");
    $stmt_last_order->execute($userIds);
    $last_orders_fetched = [];
    while ($row = $stmt_last_order->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($last_orders_fetched[$row['user_id']])) {
            $userExtraData[$row['user_id']]['last_order_id'] = $row['orders_id'];
            $userExtraData[$row['user_id']]['last_channel'] = $row['orders_channel'];
            $userExtraData[$row['user_id']]['last_country_id'] = $row['orders_country_id'];
            $last_orders_fetched[$row['user_id']] = true;
        }
    }
}

// Merge back
$new_customers = [];
foreach ($new_customers_base as $c) {
    $uid = $c['user_id'];
    $c['user_tags_str'] = $userExtraData[$uid]['user_tags_str'] ?? '';
    $c['address_count'] = $userExtraData[$uid]['address_count'] ?? 0;
    $c['followup_count'] = $userExtraData[$uid]['followup_count'] ?? 0;
    $c['last_followup_date'] = $userExtraData[$uid]['last_followup_date'] ?? null;
    $c['last_order_id'] = $userExtraData[$uid]['last_order_id'] ?? null;
    $c['last_channel'] = $userExtraData[$uid]['last_channel'] ?? null;
    $c['last_country_id'] = $userExtraData[$uid]['last_country_id'] ?? null;
    $new_customers[] = $c;
}

$returning_customers = [];
foreach ($returning_customers_base as $c) {
    $uid = $c['user_id'];
    $c['user_tags_str'] = $userExtraData[$uid]['user_tags_str'] ?? '';
    $c['address_count'] = $userExtraData[$uid]['address_count'] ?? 0;
    $c['followup_count'] = $userExtraData[$uid]['followup_count'] ?? 0;
    $c['last_followup_date'] = $userExtraData[$uid]['last_followup_date'] ?? null;
    $c['last_order_id'] = $userExtraData[$uid]['last_order_id'] ?? null;
    $c['last_channel'] = $userExtraData[$uid]['last_channel'] ?? null;
    $c['last_country_id'] = $userExtraData[$uid]['last_country_id'] ?? null;
    $returning_customers[] = $c;
}

// Fetch all countries
$countries = [];
try {
    $stmt_c = $db->query("SELECT country_id, country_code, country_name FROM countries");
    while ($row = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
        $countries[$row['country_id']] = $row;
    }
} catch (Exception $e) {}

// Calculate Total Members and Purchaser Percentages
$stmt_members = $db->query("SELECT COUNT(user_id) as total FROM user WHERE user_del = 0 AND role NOT IN ('owner', 'admin', 'manager', 'editor', 'staff', 'packer')");
$total_members = $stmt_members->fetch(PDO::FETCH_ASSOC)['total'];

$total_new = count($new_customers);
$total_returning = count($returning_customers);
$total_purchasers = $total_new + $total_returning;
$purchaser_percentage = $total_members > 0 ? round(($total_purchasers / $total_members) * 100, 2) : 0;
$new_percentage = $total_members > 0 ? round(($total_new / $total_members) * 100, 2) : 0;
$returning_percentage = $total_members > 0 ? round(($total_returning / $total_members) * 100, 2) : 0;

// Determine selected maincat name
$selectedMaincatName = 'ทุกหมวดหมู่ (All)';
if ($maincatId > 0) {
    foreach ($maincats as $mc) {
        if ($mc['maincat_id'] == $maincatId) {
            $selectedMaincatName = $mc['maincat_name'] ?: 'Unknown';
            break;
        }
    }
}

// Handle Print View
if (isset($_GET['print']) && $_GET['print'] == '1') {
    include 'print_customer_purchases_template.php';
    exit;
}

ob_start();
?>
<style>
/* Dark mode overrides for Customer Purchases */
[data-theme="dark"] .kpi-card {
    background-color: rgba(0, 0, 0, 0.25) !important;
    border-color: rgba(255, 255, 255, 0.05) !important;
}
[data-theme="dark"] .kpi-card .text-dark {
    color: #e2e8f0 !important;
}
[data-theme="dark"] .card-header.bg-white {
    background-color: rgba(0, 0, 0, 0.3) !important;
    border-bottom-color: rgba(255, 255, 255, 0.05) !important;
}
[data-theme="dark"] .nav-tabs .nav-link {
    color: #94a3b8 !important;
}
[data-theme="dark"] .nav-tabs .nav-link.active {
    background-color: transparent !important;
    color: #38bdf8 !important;
    border-bottom-color: #38bdf8 !important;
}
[data-theme="dark"] .card-body.bg-light {
    background-color: rgba(0, 0, 0, 0.1) !important;
}
[data-theme="dark"] table.bg-white {
    background-color: transparent !important;
}
[data-theme="dark"] .table-light th {
    background-color: rgba(255, 255, 255, 0.05) !important;
    color: #cbd5e1 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
[data-theme="dark"] .table td {
    border-bottom-color: rgba(255, 255, 255, 0.05) !important;
    color: #e2e8f0;
}
[data-theme="dark"] .table td .text-dark {
    color: #e2e8f0 !important;
}
[data-theme="dark"] .table-hover tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.03) !important;
}
[data-theme="dark"] .modal-content {
    background-color: #1e293b !important;
    color: #e2e8f0;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}
[data-theme="dark"] .modal-header.bg-light {
    background-color: rgba(0, 0, 0, 0.3) !important;
    border-bottom-color: rgba(255, 255, 255, 0.1) !important;
}
[data-theme="dark"] .modal-header .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}
[data-theme="dark"] .input-group-text.bg-white,
[data-theme="dark"] input.form-control,
[data-theme="dark"] textarea.form-control {
    background-color: rgba(0, 0, 0, 0.2) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #e2e8f0 !important;
}
[data-theme="dark"] input.form-control::placeholder,
[data-theme="dark"] textarea.form-control::placeholder {
    color: #64748b !important;
}
[data-theme="dark"] .btn-light {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #e2e8f0 !important;
}
[data-theme="dark"] .btn-light:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
    color: #fff !important;
}
[data-theme="dark"] #addFollowupForm.bg-light {
    background-color: rgba(0, 0, 0, 0.2) !important;
}
[data-theme="dark"] .p-3.bg-white.border-bottom {
    background-color: rgba(0, 0, 0, 0.2) !important;
    border-bottom-color: rgba(255, 255, 255, 0.05) !important;
}
[data-theme="dark"] .btn-outline-primary {
    color: #38bdf8;
    border-color: #38bdf8;
}
[data-theme="dark"] .btn-outline-primary:hover,
[data-theme="dark"] .btn-check:checked + .btn-outline-primary {
    background-color: #38bdf8;
    color: #0f172a;
}
[data-theme="dark"] .text-dark {
    color: #f1f5f9 !important;
}
[data-theme="dark"] .modal-body .card {
    background-color: rgba(255,255,255,0.03) !important;
    border-color: rgba(255,255,255,0.05) !important;
}
.profile-zoom {
    width: 40px;
    height: 40px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
    position: relative;
    z-index: 1;
}
.profile-zoom:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
}
tr.searchable-row:hover td {
    position: relative;
    z-index: 50;
}
.text-nowrap-all th, .text-nowrap-all td:not(.col-name) {
    white-space: nowrap !important;
}

[data-theme="dark"] .modal-body .card .text-dark {
    color: #f1f5f9 !important;
}
[data-theme="dark"] .btn-white {
    background-color: rgba(0, 0, 0, 0.2) !important;
    color: #e2e8f0 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
[data-theme="dark"] .dropdown-menu {
    background-color: #1e293b !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
[data-theme="dark"] .dropdown-item {
    color: #e2e8f0 !important;
}
[data-theme="dark"] .dropdown-item:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
    color: #fff !important;
}
[data-theme="dark"] .dropdown-divider {
    border-top-color: rgba(255, 255, 255, 0.1) !important;
}
</style>

<div class="reports-container pb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="page-title mb-1" style="font-size: 1.75rem; font-weight: 600;">
                <i data-lucide="users-round" class="me-2 text-primary" style="vertical-align: middle;"></i>รายงานพฤติกรรมลูกค้า
            </h1>
            <p class="text-muted small mb-0">วิเคราะห์ข้อมูลลูกค้าใหม่ และลูกค้าเก่าที่ซื้อซ้ำ เพื่อประเมินรอบการซื้อ (RFM)</p>
        </div>
        
        <!-- Filter Controls -->
        <div class="d-flex align-items-center gap-2">
            <!-- Print Button -->
            <a href="?maincat_id=<?= $maincatId ?>&print=1" target="_blank" class="btn btn-outline-secondary shadow-sm fw-bold d-flex align-items-center gap-2 bg-white" title="พิมพ์รายงาน">
                <i data-lucide="printer" style="width: 16px; height: 16px;" class="text-secondary"></i>
                <span class="d-none d-md-inline">พิมพ์รายงาน</span>
            </a>
            
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
        </div>
    </div>

    <!-- KPI Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kpi-card shadow-sm border p-3 rounded-4 d-flex align-items-center gap-3 bg-white position-relative overflow-hidden border-start border-4 border-primary">
                <div class="kpi-icon-wrapper rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i data-lucide="users" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">ลูกค้าทั้งหมด (สมาชิก)</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark"><?= number_format($total_members) ?> <span class="fs-6 text-muted fw-normal ms-1">ราย</span></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card shadow-sm border p-3 rounded-4 d-flex align-items-center gap-3 bg-white position-relative overflow-hidden border-start border-4 border-warning">
                <div class="kpi-icon-wrapper rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i data-lucide="percent" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">สัดส่วนลูกค้าที่ซื้อจริง</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark"><?= $purchaser_percentage ?>% <span class="fs-6 text-muted fw-normal ms-1">(<?= number_format($total_purchasers) ?>)</span></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card shadow-sm border p-3 rounded-4 d-flex align-items-center gap-3 bg-white position-relative overflow-hidden border-start border-4 border-info">
                <div class="kpi-icon-wrapper rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i data-lucide="user-plus" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">ลูกค้าใหม่ (สั่ง 1 ครั้ง)</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark"><?= number_format($total_new) ?> <span class="fs-6 text-muted fw-normal ms-1">ราย (<?= $new_percentage ?>%)</span></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card shadow-sm border p-3 rounded-4 d-flex align-items-center gap-3 bg-white position-relative overflow-hidden border-start border-4 border-success">
                <div class="kpi-icon-wrapper rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i data-lucide="user-check" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <span class="text-muted small d-block fw-medium">ลูกค้าเก่า (ซื้อซ้ำ >= 2 ครั้ง)</span>
                    <h4 class="mb-0 fw-bold mt-1 text-dark"><?= number_format($total_returning) ?> <span class="fs-6 text-muted fw-normal ms-1">ราย (<?= $returning_percentage ?>%)</span></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom p-0 d-flex flex-wrap align-items-center justify-content-between">
            <ul class="nav nav-tabs border-0 px-3" id="customerTabs" role="tablist" style="gap: 15px;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-3 fw-bold border-0 border-bottom border-3 border-transparent" id="new-tab" data-bs-toggle="tab" data-bs-target="#newCust" type="button" role="tab" style="border-radius: 0;">
                        <i data-lucide="sparkles" class="me-1" style="width: 16px; height: 16px;"></i> ลูกค้าใหม่ (<?= number_format(count($new_customers)) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3 fw-bold border-0 border-bottom border-3 border-transparent" id="return-tab" data-bs-toggle="tab" data-bs-target="#returnCust" type="button" role="tab" style="border-radius: 0;">
                        <i data-lucide="repeat" class="me-1" style="width: 16px; height: 16px;"></i> ลูกค้าซื้อซ้ำ (<?= number_format(count($returning_customers)) ?>)
                    </button>
                </li>
            </ul>
            <div class="p-2 px-3">
                <div class="input-group input-group-sm" style="max-width: 300px;">
                    <span class="input-group-text bg-white border-end-0"><i data-lucide="search" style="width:16px;"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="ค้นหาชื่อ, เบอร์โทร, รหัสลูกค้า...">
                </div>
            </div>
        </div>
        
        <div class="card-body p-0 bg-light bg-opacity-25">
            <div class="tab-content" id="customerTabsContent">
                
                <!-- Tab: New Customers -->
                <div class="tab-pane fade show active" id="newCust" role="tabpanel">
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0 bg-white text-nowrap-all" id="newTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="col-name" style="min-width: 250px;">ชื่อลูกค้า</th>
                                    <th>เบอร์โทรศัพท์</th>
                                    <th class="text-center">ที่อยู่</th>
                                    <th class="text-center">ช่องทาง</th>
                                    <th class="text-center">ประเทศ</th>
                                    <th class="text-center sortable-header-new" data-sort-by="lastdate" onclick="sortNew('lastdate', this)" style="cursor: pointer; user-select: none;" title="คลิกเพื่อเรียงลำดับ">วันที่ซื้อ (ล่าสุด) <i data-lucide="arrow-up-down" style="width: 14px; height: 14px; margin-left: 2px; vertical-align: middle;"></i></th>
                                    <th class="text-center sortable-header-new" data-sort-by="days" onclick="sortNew('days', this)" style="cursor: pointer; user-select: none;" title="คลิกเพื่อเรียงลำดับ">ระยะเวลาที่ผ่านไป <i data-lucide="arrow-up-down" style="width: 14px; height: 14px; margin-left: 2px; vertical-align: middle;"></i></th>
                                    <th class="text-end sortable-header-new" data-sort-by="total" onclick="sortNew('total', this)" style="cursor: pointer; user-select: none;" title="คลิกเพื่อเรียงลำดับ">ยอดสั่งซื้อรวม <i data-lucide="arrow-up-down" style="width: 14px; height: 14px; margin-left: 2px; vertical-align: middle;"></i></th>
                                    <th class="text-end pe-3">ติดตาม / ประวัติ</th>
                                </tr>
                            </thead>
                            <tbody id="newTableBody">
                                <?php if(empty($new_customers)): ?>
                                    <tr class="no-data"><td colspan="6" class="text-center py-4 text-muted">ไม่พบข้อมูลลูกค้าใหม่</td></tr>
                                <?php else: ?>
                                    <?php foreach($new_customers as $c): 
                                        $phone = (string)($c['user_mobile'] ?: $c['user_tel']);
                                        $fullname = trim((string)$c['user_name'].' '.(string)$c['user_lastname']);
                                        if (!empty($c['user_nickname'])) $fullname .= ' (' . trim($c['user_nickname']) . ')';
                                        if(empty($fullname)) $fullname = "ลูกค้าไม่มีชื่อ (ID: ".$c['user_id'].")";
                                        $customerCode = "C".str_pad($c['user_id'], 4, '0', STR_PAD_LEFT);
                                        
                                        $img_path = __DIR__ . "/../../uploads/profile/{$c['user_id']}/{$c['user_picture']}";
                                        $img_url  = (!empty($c['user_picture']) && file_exists($img_path)) 
                                                    ? ROOT_URL . "/uploads/profile/{$c['user_id']}/{$c['user_picture']}" 
                                                    : ROOT_URL . "/uploads/profile/default.png";
                                        
                                        $tagsHTML = '';
                                        if (!empty($c['user_tags_str'])) {
                                            $tagPairs = explode('||', $c['user_tags_str']);
                                            foreach ($tagPairs as $pair) {
                                                $parts = explode('::', $pair);
                                                if(count($parts) === 2) {
                                                    $tName = htmlspecialchars($parts[0]);
                                                    $tColor = htmlspecialchars($parts[1]);
                                                    $isHex = strpos($tColor, '#') === 0;
                                                    $badgeStyle = $isHex ? 'background-color: '.$tColor.'; color: #fff; border: 1px solid rgba(0,0,0,0.1);' : '';
                                                    $badgeClass = $isHex ? 'badge fw-normal shadow-sm' : 'badge bg-'.$tColor.' fw-normal shadow-sm';
                                                    $tagsHTML .= '<span class="'.$badgeClass.'" style="font-size: 0.65rem; padding: 4px 8px; border-radius: 6px; '.$badgeStyle.'">'.$tName.'</span>';
                                                }
                                            }
                                        }
                                        $tagsHTML .= '<div class="btn-manage-tags ms-1 d-inline-flex align-items-center justify-content-center rounded border" onclick="promptAddTag(' . $c['user_id'] . ')" title="จัดการ Tag" style="width: 20px; height: 20px; cursor: pointer; vertical-align: middle; background-color: var(--bs-secondary-bg, #f8f9fa);"><i data-lucide="edit-2" style="width:10px;height:10px;color:inherit;"></i></div>';

                                        $channel = !empty($c['last_channel']) ? $c['last_channel'] : '';
                                        $channel_badge = '-';
                                        if ($channel) {
                                            $channel_color = '#6c757d';
                                            $channel_icon = 'tag';
                                            $lower_channel = strtolower($channel);
                                            if (strpos($lower_channel, 'line') !== false) { $channel_color = '#06C755'; $channel_icon = 'message-circle'; }
                                            else if (strpos($lower_channel, 'facebook') !== false || strpos($lower_channel, 'fb') !== false) { $channel_color = '#1877F2'; $channel_icon = 'facebook'; }
                                            else if (strpos($lower_channel, 'tiktok') !== false) { $channel_color = '#010101'; $channel_icon = 'music'; }
                                            else if (strpos($lower_channel, 'shopee') !== false) { $channel_color = '#EE4D2D'; $channel_icon = 'shopping-bag'; }
                                            else if (strpos($lower_channel, 'lazada') !== false) { $channel_color = '#101566'; $channel_icon = 'shopping-cart'; }
                                            else if (strpos($lower_channel, 'website') !== false) { $channel_color = '#0ea5e9'; $channel_icon = 'globe'; }
                                            else if (strpos($lower_channel, 'phone') !== false) { $channel_color = '#0d6efd'; $channel_icon = 'phone'; }
                                            else if (strpos($lower_channel, 'therapist') !== false) { $channel_color = '#6f42c1'; $channel_icon = 'heart'; }
                                            $channel_badge = '<span class="badge shadow-sm-hover text-white" style="background-color: ' . $channel_color . '; font-size: 0.65rem; padding: 2px 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;" onclick="changeCpOrderChannel(' . $c['last_order_id'] . ', \'' . htmlspecialchars(addslashes($channel)) . '\', this)" title="คลิกเพื่อเปลี่ยนช่องทางการขาย"><i data-lucide="' . $channel_icon . '" style="width:10px; height:10px;"></i> ' . htmlspecialchars($channel) . '</span>';
                                        } else if ($c['last_order_id']) {
                                            $channel_badge = '<span class="badge bg-secondary shadow-sm-hover text-white" style="font-size: 0.65rem; padding: 2px 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;" onclick="changeCpOrderChannel(' . $c['last_order_id'] . ', \'\', this)" title="คลิกเพื่อเปลี่ยนช่องทางการขาย"><i data-lucide="help-circle" style="width:10px; height:10px;"></i> ไม่ระบุ</span>';
                                        }

                                        $country_badge = '-';
                                        $cid = $c['last_country_id'] ?? 171;
                                        if ($c['last_order_id']) {
                                            if (isset($countries[$cid])) {
                                                $cdata = $countries[$cid];
                                                $flag = '';
                                                if (!empty($cdata['country_code'])) {
                                                    $flag = '<img src="https://flagcdn.com/16x12/' . strtolower($cdata['country_code']) . '.png" class="me-1" style="vertical-align: text-bottom; margin-bottom: 2px;">';
                                                }
                                                if ($cid == 171) {
                                                    $country_badge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 shadow-sm-hover" style="font-size: 0.65rem; cursor: pointer;" onclick="changeCpOrderCountry(' . $c['last_order_id'] . ', ' . $cid . ', this)" title="คลิกเพื่อเปลี่ยนประเทศ">' . $flag . htmlspecialchars($cdata['country_name']) . '</span>';
                                                } else {
                                                    $country_badge = '<span class="badge border fw-bold shadow-sm-hover" style="font-size: 0.65rem; background-color: #d39e00; color: #fff; border-color: #c69500 !important; cursor: pointer;" onclick="changeCpOrderCountry(' . $c['last_order_id'] . ', ' . $cid . ', this)" title="คลิกเพื่อเปลี่ยนประเทศ">' . $flag . htmlspecialchars($cdata['country_name']) . '</span>';
                                                }
                                            } else {
                                                $country_badge = '<span class="badge bg-secondary shadow-sm-hover text-white" style="font-size: 0.65rem; cursor: pointer;" onclick="changeCpOrderCountry(' . $c['last_order_id'] . ', 0, this)" title="คลิกเพื่อเปลี่ยนประเทศ">ไม่ระบุประเทศ</span>';
                                            }
                                        }

                                        $address_count = (int)$c['address_count'];
                                        $address_badge = '<a href="javascript:void(0);" onclick="openAddressModal(' . $c['user_id'] . ', \'' . htmlspecialchars(addslashes($fullname)) . '\')" class="badge bg-light text-primary border shadow-sm-hover" style="font-size: 0.65rem; text-decoration: none;" title="จัดการที่อยู่"><i data-lucide="map-pin" style="width:10px; height:10px;"></i> ' . $address_count . ' ที่อยู่</a>';

                                        $historyBtnText = "ยังไม่ติดตาม (0)";
                                        $historyIconClass = "text-muted";
                                        $historyBtnClass = "text-muted";
                                        if ($c['followup_count'] > 0) {
                                            $historyIconClass = "text-info";
                                            $historyBtnClass = "text-dark";
                                            $fDate = !empty($c['last_followup_date']) ? date('d/m/y', strtotime($c['last_followup_date'])) : '-';
                                            $historyBtnText = $fDate . ' <span class="text-success fw-bold">(' . $c['followup_count'] . ')</span>';
                                        }

                                        $statusIcon = ($c['user_status'] == 1) ? '<i data-lucide="check" class="text-success" style="width:14px;height:14px;"></i>' : '<i data-lucide="x" class="text-danger" style="width:14px;height:14px;"></i>';
                                        $rowStyle = ($c['user_status'] == 0) ? 'opacity: 0.3;' : '';
                                    ?>
                                    <tr class="searchable-row" id="user-row-<?= $c['user_id'] ?>" style="<?= $rowStyle ?>" data-lastdate="<?= $c['last_order_date'] ?>" data-days="<?= $c['days_since_last_order'] ?>" data-total="<?= $c['total_spent'] ?>" data-search="<?= strtolower(htmlspecialchars($fullname.' '.$phone.' '.$customerCode)) ?>">
                                        <td class="col-name" style="line-height: 1.5; padding-top: 10px; padding-bottom: 10px;">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= $img_url ?>" class="profile-img-<?= $c['user_id'] ?> profile-zoom rounded-circle object-fit-cover shadow-sm border" alt="Profile" onclick="openUserImageModal(<?= $c['user_id'] ?>, '<?= htmlspecialchars(addslashes($c['user_picture'] ?? '')) ?>')" style="cursor: pointer;">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div id="customer-name-<?= $c['user_id'] ?>" class="fw-medium text-primary cursor-pointer d-inline-block" onclick="openEditCustomerModal(<?= $c['user_id'] ?>)" title="คลิกเพื่อแก้ไขข้อมูล: <?= htmlspecialchars($fullname) ?>" style="cursor: pointer;"><?= htmlspecialchars($fullname) ?></div>
                                                        
                                                        <!-- 5-Star Rating System -->
                                                        <?php $user_stars = isset($c['user_stars']) ? (int)$c['user_stars'] : 0; ?>
                                                        <div class="user-stars-container d-flex align-items-center" id="stars-container-cp1-<?= $c['user_id'] ?>">
                                                            <?php for($i=1; $i<=5; $i++): ?>
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" 
                                                                     fill="<?= $i <= $user_stars ? '#ffc107' : 'none' ?>" 
                                                                     stroke="<?= $i <= $user_stars ? '#ffc107' : '#adb5bd' ?>" 
                                                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                                                     class="cursor-pointer star-icon" 
                                                                     onclick="updateUserStars(<?= $c['user_id'] ?>, <?= $i ?>)"
                                                                     style="transition: transform 0.1s; margin: 0 1px;">
                                                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                                                </svg>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                        <small class="text-muted me-1"><?= $customerCode ?></small>
                                                        <span id="tags-wrapper-<?= $c['user_id'] ?>" style="display:contents;"><?= $tagsHTML ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($phone) ?></td>
                                        <td class="text-center"><?= $address_badge ?></td>
                                        <td class="text-center"><?= $channel_badge ?></td>
                                        <td class="text-center"><?= $country_badge ?></td>
                                        <td class="text-center"><?= date('d/m/Y', strtotime($c['last_order_date'])) ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $c['days_since_last_order'] <= 30 ? 'bg-success' : 'bg-warning text-dark' ?> bg-opacity-25 <?= $c['days_since_last_order'] <= 30 ? 'text-success' : '' ?>">
                                                <?= $c['days_since_last_order'] ?> วันที่แล้ว
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-primary">
                                            ฿<?= number_format($c['total_spent'], 2) ?>
                                            <button class="btn btn-sm btn-light border shadow-sm ms-2 px-2 py-0" onclick="openOrderSummaryModal(<?= $c['user_id'] ?>, '<?= htmlspecialchars(addslashes($fullname)) ?>')" style="font-size: 0.75rem;" title="ดูสรุปรายการสินค้า">
                                                🛒 <span class="fw-bold">1</span>
                                            </button>
                                        </td>
                                        <td class="text-end pe-3 align-middle">
                                            <button type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center justify-content-center shadow-sm me-1" onclick="toggleUserStatus(<?= $c['user_id'] ?>, <?= $c['user_status'] ?>)" title="เปลี่ยนสถานะ">
                                                <?= $statusIcon ?>
                                            </button>
                                            <button id="history-btn-<?= $c['user_id'] ?>" type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 shadow-sm" onclick="openCustomerModal(<?= $c['user_id'] ?>)" title="ดูประวัติ">
                                                <i data-lucide="history" class="<?= $historyIconClass ?>" style="width:14px;height:14px;"></i>
                                                <span class="small fw-medium <?= $historyBtnClass ?>"><?= $historyBtnText ?></span>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Returning Customers -->
                <div class="tab-pane fade" id="returnCust" role="tabpanel">
                    <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-muted"><i data-lucide="filter" style="width: 14px;"></i> เรียงลำดับข้อมูล</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="sortReturn" id="sortCount" autocomplete="off" checked onchange="sortReturning('count')">
                            <label class="btn btn-outline-primary fw-bold" for="sortCount">จำนวนออเดอร์</label>

                            <input type="radio" class="btn-check" name="sortReturn" id="sortTotal" autocomplete="off" onchange="sortReturning('total')">
                            <label class="btn btn-outline-primary fw-bold" for="sortTotal">ยอดสั่งซื้อรวม</label>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0 bg-white text-nowrap-all" id="returnTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="col-name" style="min-width: 250px;">ชื่อลูกค้า</th>
                                    <th>เบอร์โทรศัพท์</th>
                                    <th class="text-center">ที่อยู่</th>
                                    <th class="text-center">ช่องทาง</th>
                                    <th class="text-center">ประเทศ</th>
                                    <th class="text-center text-nowrap sortable-header" data-sort-by="count" onclick="sortReturning('count', this)" style="cursor: pointer; user-select: none;" title="คลิกเพื่อเรียงลำดับ">ออเดอร์ <i data-lucide="arrow-up-down" style="width: 14px; height: 14px; margin-left: 2px; vertical-align: middle;"></i></th>
                                    <th class="text-center sortable-header" data-sort-by="lastdate" onclick="sortReturning('lastdate', this)" style="cursor: pointer; user-select: none;" title="คลิกเพื่อเรียงลำดับ">ซื้อล่าสุดเมื่อ <i data-lucide="arrow-up-down" style="width: 14px; height: 14px; margin-left: 2px; vertical-align: middle;"></i></th>
                                    <th class="text-center sortable-header" data-sort-by="avgcycle" onclick="sortReturning('avgcycle', this)" style="cursor: pointer; user-select: none;" title="คลิกเพื่อเรียงลำดับ">รอบซื้อเฉลี่ย <i data-lucide="arrow-up-down" style="width: 14px; height: 14px; margin-left: 2px; vertical-align: middle;"></i></th>
                                    <th class="text-end sortable-header" data-sort-by="total" onclick="sortReturning('total', this)" style="cursor: pointer; user-select: none;" title="คลิกเพื่อเรียงลำดับ">ยอดรวมทั้งสิ้น (LTV) <i data-lucide="arrow-up-down" style="width: 14px; height: 14px; margin-left: 2px; vertical-align: middle;"></i></th>
                                    <th class="text-end pe-3">ติดตาม / ประวัติ</th>
                                </tr>
                            </thead>
                            <tbody id="returnTableBody">
                                <?php if(empty($returning_customers)): ?>
                                    <tr class="no-data"><td colspan="7" class="text-center py-4 text-muted">ไม่พบข้อมูลลูกค้าเก่า</td></tr>
                                <?php else: ?>
                                    <?php foreach($returning_customers as $c): 
                                        $phone = (string)($c['user_mobile'] ?: $c['user_tel']);
                                        $fullname = trim((string)$c['user_name'].' '.(string)$c['user_lastname']);
                                        if (!empty($c['user_nickname'])) $fullname .= ' (' . trim($c['user_nickname']) . ')';
                                        if(empty($fullname)) $fullname = "ลูกค้าไม่มีชื่อ (ID: ".$c['user_id'].")";
                                        $customerCode = "C".str_pad($c['user_id'], 4, '0', STR_PAD_LEFT);
                                        
                                        $img_path = __DIR__ . "/../../uploads/profile/{$c['user_id']}/{$c['user_picture']}";
                                        $img_url  = (!empty($c['user_picture']) && file_exists($img_path)) 
                                                    ? ROOT_URL . "/uploads/profile/{$c['user_id']}/{$c['user_picture']}" 
                                                    : ROOT_URL . "/uploads/profile/default.png";
                                        
                                        $tagsHTML = '';
                                        if (!empty($c['user_tags_str'])) {
                                            $tagPairs = explode('||', $c['user_tags_str']);
                                            foreach ($tagPairs as $pair) {
                                                $parts = explode('::', $pair);
                                                if(count($parts) === 2) {
                                                    $tName = htmlspecialchars($parts[0]);
                                                    $tColor = htmlspecialchars($parts[1]);
                                                    $isHex = strpos($tColor, '#') === 0;
                                                    $badgeStyle = $isHex ? 'background-color: '.$tColor.'; color: #fff; border: 1px solid rgba(0,0,0,0.1);' : '';
                                                    $badgeClass = $isHex ? 'badge fw-normal shadow-sm' : 'badge bg-'.$tColor.' fw-normal shadow-sm';
                                                    $tagsHTML .= '<span class="'.$badgeClass.'" style="font-size: 0.65rem; padding: 4px 8px; border-radius: 6px; '.$badgeStyle.'">'.$tName.'</span>';
                                                }
                                            }
                                        }
                                        $tagsHTML .= '<div class="btn-manage-tags ms-1 d-inline-flex align-items-center justify-content-center rounded border" onclick="promptAddTag(' . $c['user_id'] . ')" title="จัดการ Tag" style="width: 20px; height: 20px; cursor: pointer; vertical-align: middle; background-color: var(--bs-secondary-bg, #f8f9fa);"><i data-lucide="edit-2" style="width:10px;height:10px;color:inherit;"></i></div>';
                                        
                                        $channel = !empty($c['last_channel']) ? $c['last_channel'] : '';
                                        $channel_badge = '-';
                                        if ($channel) {
                                            $channel_color = '#6c757d';
                                            $channel_icon = 'tag';
                                            $lower_channel = strtolower($channel);
                                            if (strpos($lower_channel, 'line') !== false) { $channel_color = '#06C755'; $channel_icon = 'message-circle'; }
                                            else if (strpos($lower_channel, 'facebook') !== false || strpos($lower_channel, 'fb') !== false) { $channel_color = '#1877F2'; $channel_icon = 'facebook'; }
                                            else if (strpos($lower_channel, 'tiktok') !== false) { $channel_color = '#010101'; $channel_icon = 'music'; }
                                            else if (strpos($lower_channel, 'shopee') !== false) { $channel_color = '#EE4D2D'; $channel_icon = 'shopping-bag'; }
                                            else if (strpos($lower_channel, 'lazada') !== false) { $channel_color = '#101566'; $channel_icon = 'shopping-cart'; }
                                            else if (strpos($lower_channel, 'website') !== false) { $channel_color = '#0ea5e9'; $channel_icon = 'globe'; }
                                            else if (strpos($lower_channel, 'phone') !== false) { $channel_color = '#0d6efd'; $channel_icon = 'phone'; }
                                            else if (strpos($lower_channel, 'therapist') !== false) { $channel_color = '#6f42c1'; $channel_icon = 'heart'; }
                                            $channel_badge = '<span class="badge shadow-sm-hover text-white" style="background-color: ' . $channel_color . '; font-size: 0.65rem; padding: 2px 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;" onclick="changeCpOrderChannel(' . $c['last_order_id'] . ', \'' . htmlspecialchars(addslashes($channel)) . '\', this)" title="คลิกเพื่อเปลี่ยนช่องทางการขาย"><i data-lucide="' . $channel_icon . '" style="width:10px; height:10px;"></i> ' . htmlspecialchars($channel) . '</span>';
                                        } else if ($c['last_order_id']) {
                                            $channel_badge = '<span class="badge bg-secondary shadow-sm-hover text-white" style="font-size: 0.65rem; padding: 2px 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;" onclick="changeCpOrderChannel(' . $c['last_order_id'] . ', \'\', this)" title="คลิกเพื่อเปลี่ยนช่องทางการขาย"><i data-lucide="help-circle" style="width:10px; height:10px;"></i> ไม่ระบุ</span>';
                                        }

                                        $country_badge = '-';
                                        $cid = $c['last_country_id'] ?? 171;
                                        if ($c['last_order_id']) {
                                            if (isset($countries[$cid])) {
                                                $cdata = $countries[$cid];
                                                $flag = '';
                                                if (!empty($cdata['country_code'])) {
                                                    $flag = '<img src="https://flagcdn.com/16x12/' . strtolower($cdata['country_code']) . '.png" class="me-1" style="vertical-align: text-bottom; margin-bottom: 2px;">';
                                                }
                                                if ($cid == 171) {
                                                    $country_badge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 shadow-sm-hover" style="font-size: 0.65rem; cursor: pointer;" onclick="changeCpOrderCountry(' . $c['last_order_id'] . ', ' . $cid . ', this)" title="คลิกเพื่อเปลี่ยนประเทศ">' . $flag . htmlspecialchars($cdata['country_name']) . '</span>';
                                                } else {
                                                    $country_badge = '<span class="badge border fw-bold shadow-sm-hover" style="font-size: 0.65rem; background-color: #d39e00; color: #fff; border-color: #c69500 !important; cursor: pointer;" onclick="changeCpOrderCountry(' . $c['last_order_id'] . ', ' . $cid . ', this)" title="คลิกเพื่อเปลี่ยนประเทศ">' . $flag . htmlspecialchars($cdata['country_name']) . '</span>';
                                                }
                                            } else {
                                                $country_badge = '<span class="badge bg-secondary shadow-sm-hover text-white" style="font-size: 0.65rem; cursor: pointer;" onclick="changeCpOrderCountry(' . $c['last_order_id'] . ', 0, this)" title="คลิกเพื่อเปลี่ยนประเทศ">ไม่ระบุประเทศ</span>';
                                            }
                                        }

                                        $address_count = (int)$c['address_count'];
                                        $address_badge = '<a href="javascript:void(0);" onclick="openAddressModal(' . $c['user_id'] . ', \'' . htmlspecialchars(addslashes($fullname)) . '\')" class="badge bg-light text-primary border shadow-sm-hover" style="font-size: 0.65rem; text-decoration: none;" title="จัดการที่อยู่"><i data-lucide="map-pin" style="width:10px; height:10px;"></i> ' . $address_count . ' ที่อยู่</a>';

                                        $historyBtnText = "ยังไม่ติดตาม (0)";
                                        $historyIconClass = "text-muted";
                                        $historyBtnClass = "text-muted";
                                        if ($c['followup_count'] > 0) {
                                            $historyIconClass = "text-info";
                                            $historyBtnClass = "text-dark";
                                            $fDate = !empty($c['last_followup_date']) ? date('d/m/y', strtotime($c['last_followup_date'])) : '-';
                                            $historyBtnText = $fDate . ' <span class="text-success fw-bold">(' . $c['followup_count'] . ')</span>';
                                        }

                                        // ประเมินสถานะ
                                        $statusClass = "success";
                                        if ($c['days_since_last_order'] > ($c['avg_days_between_orders'] + 15)) {
                                            $statusClass = "danger"; // เลยกำหนดซื้อรอบต่อไปมา 15 วันแล้ว
                                        } else if ($c['days_since_last_order'] >= $c['avg_days_between_orders']) {
                                            $statusClass = "warning text-dark"; // ถึงกำหนดซื้อซ้ำพอดี
                                        }

                                        $statusIcon = ($c['user_status'] == 1) ? '<i data-lucide="check" class="text-success" style="width:14px;height:14px;"></i>' : '<i data-lucide="x" class="text-danger" style="width:14px;height:14px;"></i>';
                                        $rowStyle = ($c['user_status'] == 0) ? 'opacity: 0.3;' : '';
                                    ?>
                                    <tr class="searchable-row" id="user-row-<?= $c['user_id'] ?>" style="<?= $rowStyle ?>" data-count="<?= $c['total_orders'] ?>" data-total="<?= $c['total_spent'] ?>" data-lastdate="<?= $c['last_order_date'] ?>" data-avgcycle="<?= $c['avg_days_between_orders'] ?>" data-search="<?= strtolower(htmlspecialchars($fullname.' '.$phone.' '.$customerCode)) ?>">
                                        <td class="col-name" style="line-height: 1.5; padding-top: 10px; padding-bottom: 10px;">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= $img_url ?>" class="profile-img-<?= $c['user_id'] ?> profile-zoom rounded-circle object-fit-cover shadow-sm border" alt="Profile" onclick="openUserImageModal(<?= $c['user_id'] ?>, '<?= htmlspecialchars(addslashes($c['user_picture'] ?? '')) ?>')" style="cursor: pointer;">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div id="customer-name-return-<?= $c['user_id'] ?>" class="fw-medium text-primary cursor-pointer d-inline-block" onclick="openEditCustomerModal(<?= $c['user_id'] ?>)" title="คลิกเพื่อแก้ไขข้อมูล: <?= htmlspecialchars($fullname) ?>" style="cursor: pointer;"><?= htmlspecialchars($fullname) ?></div>
                                                        
                                                        <!-- 5-Star Rating System -->
                                                        <?php $user_stars = isset($c['user_stars']) ? (int)$c['user_stars'] : 0; ?>
                                                        <div class="user-stars-container d-flex align-items-center" id="stars-container-cp2-<?= $c['user_id'] ?>">
                                                            <?php for($i=1; $i<=5; $i++): ?>
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" 
                                                                     fill="<?= $i <= $user_stars ? '#ffc107' : 'none' ?>" 
                                                                     stroke="<?= $i <= $user_stars ? '#ffc107' : '#adb5bd' ?>" 
                                                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                                                     class="cursor-pointer star-icon" 
                                                                     onclick="updateUserStars(<?= $c['user_id'] ?>, <?= $i ?>)"
                                                                     style="transition: transform 0.1s; margin: 0 1px;">
                                                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                                                </svg>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                        <small class="text-muted me-1"><?= $customerCode ?></small>
                                                        <span id="tags-wrapper-return-<?= $c['user_id'] ?>" style="display:contents;"><?= $tagsHTML ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($phone) ?></td>
                                        <td class="text-center"><?= $address_badge ?></td>
                                        <td class="text-center"><?= $channel_badge ?></td>
                                        <td class="text-center"><?= $country_badge ?></td>
                                        <td class="text-center"><span class="badge bg-primary rounded-pill"><?= $c['total_orders'] ?></span></td>
                                        <td class="text-center">
                                            <div class="small fw-bold"><?= date('d/m/y', strtotime($c['last_order_date'])) ?></div>
                                            <span class="badge bg-<?= $statusClass ?> bg-opacity-25 <?= $statusClass == 'success' ? 'text-success' : ($statusClass == 'danger' ? 'text-danger' : '') ?> " style="font-size: 10px;">
                                                <?= $c['days_since_last_order'] ?> วันที่แล้ว
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-dark"><?= $c['avg_days_between_orders'] ?></span> <span class="small text-muted">วัน</span>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            ฿<?= number_format($c['total_spent'], 2) ?>
                                            <button class="btn btn-sm btn-light border shadow-sm ms-2 px-2 py-0" onclick="openOrderSummaryModal(<?= $c['user_id'] ?>, '<?= htmlspecialchars(addslashes($fullname)) ?>')" style="font-size: 0.75rem;" title="ดูสรุปรายการสินค้า">
                                                🛒 <span class="fw-bold"><?= $c['total_orders'] ?></span>
                                            </button>
                                        </td>
                                        <td class="text-end pe-3 align-middle">
                                            <button type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center justify-content-center shadow-sm me-1" onclick="toggleUserStatus(<?= $c['user_id'] ?>, <?= $c['user_status'] ?>)" title="เปลี่ยนสถานะ">
                                                <?= $statusIcon ?>
                                            </button>
                                            <button id="history-btn-<?= $c['user_id'] ?>" type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 shadow-sm" onclick="openCustomerModal(<?= $c['user_id'] ?>)" title="ดูประวัติ">
                                                <i data-lucide="history" class="<?= $historyIconClass ?>" style="width:14px;height:14px;"></i>
                                                <span class="small fw-medium <?= $historyBtnClass ?>"><?= $historyBtnText ?></span>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Order Summary Modal -->
<div class="modal fade" id="orderSummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold">สรุปรายการสินค้า <span id="osmCustomerName" class="text-primary mx-2"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-50" id="osmBody">
                <div class="text-center py-5 text-muted">กำลังโหลด...</div>
            </div>
        </div>
    </div>
</div>

<!-- Customer Details Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="customerModalTitle">ประวัติลูกค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-7">
                        <h6 class="fw-bold mb-3"><i data-lucide="shopping-bag" class="me-2 text-primary" style="width:18px;"></i> ประวัติคำสั่งซื้อ</h6>
                        
                        <!-- Mini Report -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-2 border rounded bg-light text-center shadow-sm h-100">
                                    <div class="small text-muted mb-1">จำนวนออเดอร์</div>
                                    <h5 class="fw-bold mb-0 text-primary" id="mdlTotalOrders">-</h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded bg-light text-center shadow-sm h-100">
                                    <div class="small text-muted mb-1">เฉลี่ยกี่วันในการสั่งซื้อ</div>
                                    <h5 class="fw-bold mb-0 text-success" id="mdlAvgDays">-</h5>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive rounded border">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>วันที่ซื้อ</th>
                                        <th>เลขที่บิล</th>
                                        <th class="text-center">จำนวนชิ้น</th>
                                        <th class="text-end">ยอดสั่งซื้อ</th>
                                        <th class="text-center">ห่างจากบิลก่อน</th>
                                    </tr>
                                </thead>
                                <tbody id="mdlOrdersBody">
                                    <!-- AJAX Content -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-5 mt-4 mt-md-0">
                        <!-- Customer Note -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0"><i data-lucide="user-check" class="me-2 text-info" style="width:18px;"></i> ข้อมูลลูกค้า / หมายเหตุ</h6>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editUserNote()" id="btnEditUserNote" style="padding: 2px 8px; font-size: 0.75rem;">
                                    <i data-lucide="edit-2" style="width:12px;"></i> แก้ไข
                                </button>
                            </div>
                            <div class="card card-body bg-light border-0 shadow-sm" id="userNoteDisplay" style="min-height: 50px; font-size: 0.85rem;">
                                - ไม่มีข้อมูล -
                            </div>
                            <div id="userNoteEditor" style="display: none;">
                                <textarea id="txtUserNote" class="form-control mb-2" rows="3" placeholder="ระบุข้อมูลที่ควรทราบเกี่ยวกับลูกค้ารายนี้..." style="font-size: 0.85rem;"></textarea>
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-light border shadow-sm" onclick="cancelUserNote()">ยกเลิก</button>
                                    <button class="btn btn-sm btn-success shadow-sm" onclick="saveUserNote()">บันทึกข้อมูล</button>
                                </div>
                            </div>
                        </div>

                        <!-- Google Map Link -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0"><i data-lucide="map-pin" class="me-2 text-success" style="width:18px;"></i> ลิงค์ Google Map ลูกค้า</h6>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editUserMap()" id="btnEditUserMap" style="padding: 2px 8px; font-size: 0.75rem;">
                                    <i data-lucide="edit-2" style="width:12px;"></i> แก้ไข
                                </button>
                            </div>
                            <div class="card card-body bg-light border-0 shadow-sm" id="userMapDisplay" style="min-height: 40px; font-size: 0.85rem; word-break: break-all;">
                                - ไม่มีลิงค์แผนที่ -
                            </div>
                            <div id="userMapEditor" style="display: none;">
                                <input type="text" id="txtUserMap" class="form-control mb-2" placeholder="วางลิงค์ Google Map ที่นี่..." style="font-size: 0.85rem;">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-light border shadow-sm" onclick="cancelUserMap()">ยกเลิก</button>
                                    <button class="btn btn-sm btn-success shadow-sm" onclick="saveUserMap()">บันทึกลิงค์</button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i data-lucide="clipboard-list" class="me-2 text-warning" style="width:18px;"></i> บันทึกการติดตาม</h6>
                            <button type="button" class="btn btn-sm btn-primary" onclick="showAddFollowup()">
                                <i data-lucide="plus" style="width:14px;"></i> เพิ่มบันทึก
                            </button>
                        </div>
                        
                        <!-- Add Form (Hidden by default) -->
                        <div id="addFollowupForm" class="card card-body bg-light border-0 mb-3" style="display: none;">
                            <form id="frmAddFollowup" onsubmit="saveFollowup(event)">
                                <input type="hidden" id="fuUserId" name="user_id">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">วันที่ติดต่อ</label>
                                    <input type="date" class="form-control form-control-sm" name="date" required value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">รายละเอียด</label>
                                    <textarea class="form-control form-control-sm" name="detail" rows="2" required placeholder="บันทึกข้อมูลการสนทนา..."></textarea>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button type="button" class="btn btn-sm btn-light" onclick="hideAddFollowup()">ยกเลิก</button>
                                    <button type="submit" class="btn btn-sm btn-success">บันทึก</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Followup List -->
                        <div id="mdlFollowupsList" class="d-flex flex-column gap-2" style="max-height: 400px; overflow-y: auto;">
                            <!-- AJAX Content -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const COUNTRIES_DATA = <?= json_encode($countries) ?>;
let currentUserId = null;
const customerModal = new bootstrap.Modal(document.getElementById('customerModal'));

// 0. State Persistence (localStorage) and Maincat Filter
document.addEventListener("DOMContentLoaded", function() {
    // Check if URL has maincat parameter, if not, check localStorage
    let urlParams = new URLSearchParams(window.location.search);
    let savedMaincat = localStorage.getItem('cp_maincat_id');
    
    if (urlParams.has('maincat_id')) {
        localStorage.setItem('cp_maincat_id', urlParams.get('maincat_id'));
    } else {
        if (!savedMaincat || savedMaincat === '0') {
            savedMaincat = '1';
        }
        window.location.href = '?maincat_id=' + savedMaincat;
    }

    // Restore Tab
    const activeTabId = localStorage.getItem('cp_active_tab');
    if (activeTabId) {
        const tabElement = document.getElementById(activeTabId);
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    }
    
    // Listen for Tab changes to save state
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', event => {
            localStorage.setItem('cp_active_tab', event.target.id);
        });
    });

    // Restore Sorting
    const activeSort = localStorage.getItem('cp_sort_by') || 'count';
    if (activeSort === 'total') {
        if(document.getElementById('sortTotal')) document.getElementById('sortTotal').checked = true;
    } else if (activeSort === 'count') {
        if(document.getElementById('sortCount')) document.getElementById('sortCount').checked = true;
    }
    sortReturning(activeSort);

    const activeSortNew = localStorage.getItem('cp_sort_by_new') || 'lastdate';
    sortNew(activeSortNew);
    
    // Restore Search
    const savedSearch = localStorage.getItem('cp_search_term');
    if (savedSearch) {
        const searchInput = document.getElementById('searchInput');
        searchInput.value = savedSearch;
        searchInput.dispatchEvent(new Event('input'));
    }
    
    if(window.lucide) {
        lucide.createIcons();
    }
});

function changeMaincat(id, name) {
    localStorage.setItem('cp_maincat_id', id);
    window.location.href = '?maincat_id=' + id;
}

// 1. Search Function
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase().trim();
    localStorage.setItem('cp_search_term', term); // Save to cookie/storage
    
    const rows = document.querySelectorAll('.searchable-row');
    rows.forEach(row => {
        const searchData = row.getAttribute('data-search');
        if (searchData.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// 2. Sorting Function
let currentSortDir = localStorage.getItem('cp_sort_dir') || 'desc';
let currentSortDirNew = localStorage.getItem('cp_sort_dir_new') || 'desc';

function sortNew(sortBy, headerEl = null) {
    if (headerEl) {
        const activeSort = localStorage.getItem('cp_sort_by_new');
        if (activeSort === sortBy) {
            currentSortDirNew = currentSortDirNew === 'desc' ? 'asc' : 'desc';
        } else {
            currentSortDirNew = 'desc';
        }
    }

    localStorage.setItem('cp_sort_by_new', sortBy);
    localStorage.setItem('cp_sort_dir_new', currentSortDirNew);

    document.querySelectorAll('.sortable-header-new').forEach(th => {
        const icon = th.querySelector('i');
        if (icon) {
            if (th.getAttribute('data-sort-by') === sortBy) {
                icon.setAttribute('data-lucide', currentSortDirNew === 'desc' ? 'arrow-down' : 'arrow-up');
            } else {
                icon.setAttribute('data-lucide', 'arrow-up-down');
            }
        }
    });
    if(window.lucide) lucide.createIcons();

    const tbody = document.getElementById('newTableBody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr[data-lastdate]'));

    rows.sort((a, b) => {
        let valA = 0, valB = 0;
        if (sortBy === 'total') {
            valA = parseFloat(a.getAttribute('data-total')) || 0;
            valB = parseFloat(b.getAttribute('data-total')) || 0;
        } else if (sortBy === 'lastdate') {
            valA = new Date(a.getAttribute('data-lastdate')).getTime() || 0;
            valB = new Date(b.getAttribute('data-lastdate')).getTime() || 0;
        } else if (sortBy === 'days') {
            valA = parseInt(a.getAttribute('data-days')) || 0;
            valB = parseInt(b.getAttribute('data-days')) || 0;
        }

        if (currentSortDirNew === 'desc') {
            return valB - valA;
        } else {
            return valA - valB;
        }
    });

    const noData = tbody.querySelector('.no-data');
    tbody.innerHTML = '';
    if(noData && rows.length === 0) {
        tbody.appendChild(noData);
    }
    
    rows.forEach(row => {
        tbody.appendChild(row);
    });
}

function sortReturning(sortBy, headerEl = null) {
    if (headerEl) {
        const activeSort = localStorage.getItem('cp_sort_by');
        if (activeSort === sortBy) {
            currentSortDir = currentSortDir === 'desc' ? 'asc' : 'desc';
        } else {
            currentSortDir = 'desc';
        }
    }

    localStorage.setItem('cp_sort_by', sortBy);
    localStorage.setItem('cp_sort_dir', currentSortDir);

    // Update icons in headers
    document.querySelectorAll('.sortable-header').forEach(th => {
        const icon = th.querySelector('i');
        if (icon) {
            if (th.getAttribute('data-sort-by') === sortBy) {
                icon.setAttribute('data-lucide', currentSortDir === 'desc' ? 'arrow-down' : 'arrow-up');
            } else {
                icon.setAttribute('data-lucide', 'arrow-up-down');
            }
        }
    });
    if(window.lucide) lucide.createIcons();

    if (sortBy === 'count' && document.getElementById('sortCount')) {
        document.getElementById('sortCount').checked = true;
    } else if (sortBy === 'total' && document.getElementById('sortTotal')) {
        document.getElementById('sortTotal').checked = true;
    }

    const tbody = document.getElementById('returnTableBody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr[data-count]'));

    rows.sort((a, b) => {
        let valA = 0, valB = 0;
        if (sortBy === 'count') {
            valA = parseInt(a.getAttribute('data-count')) || 0;
            valB = parseInt(b.getAttribute('data-count')) || 0;
        } else if (sortBy === 'total') {
            valA = parseFloat(a.getAttribute('data-total')) || 0;
            valB = parseFloat(b.getAttribute('data-total')) || 0;
        } else if (sortBy === 'lastdate') {
            valA = new Date(a.getAttribute('data-lastdate')).getTime() || 0;
            valB = new Date(b.getAttribute('data-lastdate')).getTime() || 0;
        } else if (sortBy === 'avgcycle') {
            valA = parseInt(a.getAttribute('data-avgcycle')) || 0;
            valB = parseInt(b.getAttribute('data-avgcycle')) || 0;
        }

        if (currentSortDir === 'desc') {
            return valB - valA;
        } else {
            return valA - valB;
        }
    });

    const noData = tbody.querySelector('.no-data');
    tbody.innerHTML = '';
    if(noData && rows.length === 0) {
        tbody.appendChild(noData);
    }
    
    rows.forEach(row => {
        tbody.appendChild(row);
    });
}

// 3. Modals and AJAX Fetching
function openCustomerModal(userId) {
    currentUserId = userId;
    document.getElementById('fuUserId').value = userId;
    hideAddFollowup();
    
    // Reset contents
    document.getElementById('customerModalTitle').innerHTML = `ประวัติลูกค้า <small class="text-muted ms-2">(กำลังโหลด...)</small>`;
    document.getElementById('mdlOrdersBody').innerHTML = '<tr><td colspan="5" class="text-center py-3">กำลังโหลด...</td></tr>';
    document.getElementById('mdlFollowupsList').innerHTML = '<div class="text-center py-3">กำลังโหลด...</div>';
    
    customerModal.show();
    fetchCustomerDetails();
}

function fetchCustomerDetails() {
    const formData = new FormData();
    formData.append('action', 'get_details');
    formData.append('user_id', currentUserId);

    fetch('ajax_customer_details.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            renderOrders(data.orders);
            renderFollowups(data.followups);
            
            // Update table record row button dynamically
            const btn = document.getElementById(`history-btn-${currentUserId}`);
            if (btn) {
                const count = data.followups ? data.followups.length : 0;
                if (count > 0) {
                    const latest = data.followups[0].followup_date;
                    const parts = latest.split(' ')[0].split('-');
                    const shortYear = parts[0].substring(2);
                    const thDateStr = `${parts[2]}/${parts[1]}/${shortYear}`;
                    btn.innerHTML = `<i data-lucide="history" class="text-info" style="width:14px;height:14px;"></i>
                                     <span class="small fw-medium text-dark">${thDateStr} <span class="text-success fw-bold">(${count})</span></span>`;
                } else {
                    btn.innerHTML = `<i data-lucide="history" class="text-muted" style="width:14px;height:14px;"></i>
                                     <span class="small fw-medium text-muted">ยังไม่ติดตาม (0)</span>`;
                }
                if(window.lucide) lucide.createIcons();
            }
            
            // Update Mini Report
            document.getElementById('mdlTotalOrders').innerText = data.stats.total_orders;
            document.getElementById('mdlAvgDays').innerText = data.stats.avg_days > 0 ? data.stats.avg_days + ' วัน' : '-';
            
            // Set name in title
            let fullname = (data.user.user_name || '') + ' ' + (data.user.user_lastname || '');
            fullname = fullname.trim() || 'ลูกค้าไม่มีชื่อ';
            document.getElementById('customerModalTitle').innerHTML = `ประวัติลูกค้า <span class="text-primary mx-2">${fullname}</span> <small class="text-muted fs-6">C${String(currentUserId).padStart(4, '0')}</small>`;
            
            // Set User Note
            const note = data.user.user_note || '';
            document.getElementById('txtUserNote').value = note;
            document.getElementById('userNoteDisplay').innerHTML = note.trim() ? note.replace(/\n/g, '<br>') : '<span class="text-muted">- ไม่มีข้อมูล -</span>';
            cancelUserNote();

            // Set User Map
            const googleMap = data.user.user_google_map || '';
            document.getElementById('txtUserMap').value = googleMap;
            document.getElementById('userMapDisplay').innerHTML = googleMap.trim() ? `<a href="${googleMap}" target="_blank" class="text-primary text-decoration-none"><i data-lucide="external-link" style="width:14px;height:14px;"></i> เปิดแผนที่</a><br><span class="text-muted small">${googleMap}</span>` : '<span class="text-muted">- ไม่มีลิงค์แผนที่ -</span>';
            cancelUserMap();

            
        } else {
            Swal.fire('Error', data.message || 'เกิดข้อผิดพลาด', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
    });
}

function renderOrders(orders) {
    const tbody = document.getElementById('mdlOrdersBody');
    if (!orders || orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">ไม่มีประวัติคำสั่งซื้อ</td></tr>';
        return;
    }
    
    let html = '';
    orders.forEach(o => {
        // Format Date
        const d = new Date(o.orders_date);
        const dateStr = d.toLocaleDateString('th-TH', {day:'2-digit', month:'2-digit', year:'2-digit'});
        
        let daysHtml = o.days_since_prev !== '-' ? `${o.days_since_prev} วัน` : '<span class="text-muted">-</span>';
        
        html += `
            <tr>
                <td>${dateStr}</td>
                <td><small class="text-muted">${o.orders_no}</small></td>
                <td class="text-center"><span class="badge bg-secondary rounded-pill">${o.total_qty}</span></td>
                <td class="text-end fw-bold text-success">฿${parseFloat(o.orders_grandtotal).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                <td class="text-center">${daysHtml}</td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function renderFollowups(followups) {
    const list = document.getElementById('mdlFollowupsList');
    if (!followups || followups.length === 0) {
        list.innerHTML = '<div class="text-center py-4 text-muted small border rounded bg-white">ยังไม่มีบันทึกการติดตาม</div>';
        return;
    }
    
    let html = '';
    followups.forEach(f => {
        const d = new Date(f.followup_date);
        const dateStr = d.toLocaleDateString('th-TH', {day:'2-digit', month:'short', year:'numeric'});
        const adminName = f.admin_name || 'ระบบ';
        
        html += `
            <div class="card border-0 shadow-sm mb-2 border-start border-3 border-warning">
                <div class="card-body p-2 px-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold small text-dark"><i data-lucide="calendar" style="width:12px;"></i> ${dateStr}</span>
                        <span class="text-muted" style="font-size: 0.7rem;"><i data-lucide="user" style="width:12px;"></i> ${adminName}</span>
                    </div>
                    <p class="mb-0 small text-secondary">${f.followup_detail.replace(/\\n/g, '<br>')}</p>
                </div>
            </div>
        `;
    });
    list.innerHTML = html;
    if(window.lucide) lucide.createIcons();
}

function showAddFollowup() {
    document.getElementById('addFollowupForm').style.display = 'block';
}

function editUserNote() {
    document.getElementById('userNoteDisplay').style.display = 'none';
    document.getElementById('userNoteEditor').style.display = 'block';
    document.getElementById('btnEditUserNote').style.display = 'none';
}

function cancelUserNote() {
    document.getElementById('userNoteDisplay').style.display = 'block';
    document.getElementById('userNoteEditor').style.display = 'none';
    document.getElementById('btnEditUserNote').style.display = 'inline-block';
}

function saveUserNote() {
    const note = document.getElementById('txtUserNote').value;
    const fd = new FormData();
    fd.append('action', 'save_user_note');
    fd.append('user_id', currentUserId);
    fd.append('note', note);
    
    fetch('ajax_customer_details.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            document.getElementById('userNoteDisplay').innerHTML = note.trim() ? note.replace(/\n/g, '<br>') : '<span class="text-muted">- ไม่มีข้อมูล -</span>';
            cancelUserNote();
            Swal.fire({icon: 'success', title: 'บันทึกข้อมูลลูกค้าสำเร็จ', timer: 1000, showConfirmButton: false});
        } else {
            Swal.fire('Error', data.message || 'เกิดข้อผิดพลาด', 'error');
        }
    });
}

function editUserMap() {
    document.getElementById('userMapDisplay').style.display = 'none';
    document.getElementById('userMapEditor').style.display = 'block';
    document.getElementById('btnEditUserMap').style.display = 'none';
}

function cancelUserMap() {
    document.getElementById('userMapDisplay').style.display = 'block';
    document.getElementById('userMapEditor').style.display = 'none';
    document.getElementById('btnEditUserMap').style.display = 'inline-block';
}

function saveUserMap() {
    const mapUrl = document.getElementById('txtUserMap').value;
    const fd = new FormData();
    fd.append('action', 'save_user_map');
    fd.append('user_id', currentUserId);
    fd.append('google_map', mapUrl);
    
    fetch('ajax_customer_details.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            document.getElementById('userMapDisplay').innerHTML = mapUrl.trim() ? `<a href="${mapUrl}" target="_blank" class="text-primary text-decoration-none"><i data-lucide="external-link" style="width:14px;height:14px;"></i> เปิดแผนที่</a><br><span class="text-muted small">${mapUrl}</span>` : '<span class="text-muted">- ไม่มีลิงค์แผนที่ -</span>';
            cancelUserMap();
            if(window.lucide) lucide.createIcons();
            Swal.fire({icon: 'success', title: 'บันทึกลิงค์แผนที่สำเร็จ', timer: 1000, showConfirmButton: false});
        } else {
            Swal.fire('Error', data.message || 'เกิดข้อผิดพลาด', 'error');
        }
    });
}

function hideAddFollowup() {
    document.getElementById('addFollowupForm').style.display = 'none';
    document.getElementById('frmAddFollowup').reset();
    document.querySelector('input[name="date"]').value = new Date().toISOString().split('T')[0];
}

function getChannelBadgeHtml(orderId, channel) {
    if (!channel) return `<span class="badge bg-secondary shadow-sm-hover text-white" style="font-size: 0.65rem; padding: 2px 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;" onclick="changeCpOrderChannel(${orderId}, '', this)" title="คลิกเพื่อเปลี่ยนช่องทางการขาย"><i data-lucide="help-circle" style="width:10px; height:10px;"></i> ไม่ระบุ</span>`;
    
    let color = '#6c757d';
    let icon = 'tag';
    const lower = channel.toLowerCase();
    if (lower.includes('line')) { color = '#06C755'; icon = 'message-circle'; }
    else if (lower.includes('facebook') || lower.includes('fb')) { color = '#1877F2'; icon = 'facebook'; }
    else if (lower.includes('tiktok')) { color = '#010101'; icon = 'music'; }
    else if (lower.includes('shopee')) { color = '#EE4D2D'; icon = 'shopping-bag'; }
    else if (lower.includes('lazada')) { color = '#101566'; icon = 'shopping-cart'; }
    else if (lower.includes('website')) { color = '#0ea5e9'; icon = 'globe'; }
    else if (lower.includes('phone')) { color = '#0d6efd'; icon = 'phone'; }
    else if (lower.includes('therapist')) { color = '#6f42c1'; icon = 'heart'; }
    
    return `<span class="badge shadow-sm-hover text-white" style="background-color: ${color}; font-size: 0.65rem; padding: 2px 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;" onclick="changeCpOrderChannel(${orderId}, '${channel.replace(/'/g, "\\'")}', this)" title="คลิกเพื่อเปลี่ยนช่องทางการขาย"><i data-lucide="${icon}" style="width:10px; height:10px;"></i> ${channel}</span>`;
}

function getCountryBadgeHtml(orderId, countryId) {
    const cid = parseInt(countryId);
    if(cid === 0 || !COUNTRIES_DATA[cid]) {
        return `<span class="badge bg-secondary shadow-sm-hover text-white" style="font-size: 0.65rem; cursor: pointer;" onclick="changeCpOrderCountry(${orderId}, 0, this)">ไม่ระบุประเทศ</span>`;
    }
    const cdata = COUNTRIES_DATA[cid];
    let flag = cdata.country_code ? `<img src="https://flagcdn.com/16x12/${cdata.country_code.toLowerCase()}.png" class="me-1" style="vertical-align: text-bottom; margin-bottom: 2px;">` : '';
    if(cid === 171) {
        return `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 shadow-sm-hover" style="font-size: 0.65rem; cursor: pointer;" onclick="changeCpOrderCountry(${orderId}, ${cid}, this)" title="คลิกเพื่อเปลี่ยนประเทศ">${flag}${cdata.country_name}</span>`;
    } else {
        return `<span class="badge border fw-bold shadow-sm-hover" style="font-size: 0.65rem; background-color: #d39e00; color: #fff; border-color: #c69500 !important; cursor: pointer;" onclick="changeCpOrderCountry(${orderId}, ${cid}, this)" title="คลิกเพื่อเปลี่ยนประเทศ">${flag}${cdata.country_name}</span>`;
    }
}

async function changeCpOrderChannel(orderId, currentChannelCode, el) {
    try {
        Swal.fire({ title: 'กำลังโหลด...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const response = await fetch(`../api/get_active_sales_channels.php`);
        const data = await response.json();
        if (!data.success) throw new Error('Cannot load channels');
        
        let optionsHtml = '<select id="swal-input-channel" class="form-select">';
        optionsHtml += '<option value="">-- ไม่ระบุ --</option>';
        data.channels.forEach(c => {
            const isSelected = c.channel_code === currentChannelCode ? 'selected' : '';
            optionsHtml += `<option value="${c.channel_code}" ${isSelected}>${c.channel_name}</option>`;
        });
        optionsHtml += '</select>';
        
        Swal.close();
        const { value: selectedChannel, isConfirmed } = await Swal.fire({
            title: 'เปลี่ยนช่องทางการขาย',
            html: optionsHtml,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                const selected = document.getElementById('swal-input-channel').value;
                if (selected === currentChannelCode) return false;
                
                const fd = new FormData();
                fd.append('orders_id', orderId);
                fd.append('channel_code', selected);
                
                try {
                    const res = await fetch(`../api/update_order_channel.php`, { method: 'POST', body: fd });
                    const result = await res.json();
                    if (!result.success) throw new Error(result.message);
                    return selected;
                } catch (error) {
                    Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error}`);
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        });
        
        if (isConfirmed && selectedChannel !== false) {
            el.outerHTML = getChannelBadgeHtml(orderId, selectedChannel);
            if(window.lucide) lucide.createIcons();
            Swal.fire({icon: 'success', title: 'สำเร็จ', timer: 1000, showConfirmButton: false});
        }
    } catch(e) {
        Swal.fire('Error', e.message, 'error');
    }
}

async function changeCpOrderCountry(orderId, currentCountryId, el) {
    try {
        Swal.fire({ title: 'กำลังโหลด...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const response = await fetch(`../api/get_countries.php`);
        const data = await response.json();
        if (!data.success) throw new Error('Cannot load countries');
        
        window.COUNTRIES_DATA = {};
        let optionsHtml = '<select id="swal-input-country" class="form-select">';
        optionsHtml += '<option value="0">-- ไม่ระบุประเทศ --</option>';
        data.countries.forEach(c => {
            window.COUNTRIES_DATA[c.country_id] = c;
            const isSelected = parseInt(c.country_id) === parseInt(currentCountryId) ? 'selected' : '';
            optionsHtml += `<option value="${c.country_id}" ${isSelected}>${c.country_name}</option>`;
        });
        optionsHtml += '</select>';
        
        Swal.close();
        const { value: selectedCountry, isConfirmed } = await Swal.fire({
            title: 'เปลี่ยนประเทศ',
            html: optionsHtml,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                const selected = document.getElementById('swal-input-country').value;
                if (parseInt(selected) === parseInt(currentCountryId)) return false;
                
                const fd = new FormData();
                fd.append('orders_id', orderId);
                fd.append('country_id', selected);
                
                try {
                    const res = await fetch(`../api/update_order_country.php`, { method: 'POST', body: fd });
                    const result = await res.json();
                    if (!result.success) throw new Error(result.message);
                    return selected;
                } catch (error) {
                    Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error}`);
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        });
        
        if (isConfirmed && selectedCountry !== false) {
             el.outerHTML = getCountryBadgeHtml(orderId, selectedCountry);
             if(window.lucide) lucide.createIcons();
             Swal.fire({icon: 'success', title: 'สำเร็จ', timer: 1000, showConfirmButton: false});
        }
    } catch(e) {
        Swal.fire('Error', e.message, 'error');
    }
}

function saveFollowup(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    // Show spinner
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> กำลังบันทึก...';
    
    const formData = new FormData(form);
    formData.append('action', 'save_followup');
    
    fetch('ajax_customer_details.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        // Restore button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
        if (data.status === 'success') {
            hideAddFollowup();
            fetchCustomerDetails(); // refresh lists
        } else {
            Swal.fire('Error', data.message || 'บันทึกไม่สำเร็จ', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        // Restore button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        Swal.fire('Error', 'เกิดข้อผิดพลาด', 'error');
    });
}

function viewProfileImage(imgUrl, name) {
    Swal.fire({
        title: name,
        html: `
            <div id="swalImgLoader" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <img src="${imgUrl}" class="rounded shadow img-fluid mt-2" style="display: none; max-height: 70vh; margin: 0 auto;" onload="
                document.getElementById('swalImgLoader').style.display = 'none';
                this.style.display = 'block';
            " onerror="
                document.getElementById('swalImgLoader').innerHTML = '<span class=\\'text-danger\\'>ไม่สามารถโหลดรูปภาพได้</span>';
            ">
        `,
        showConfirmButton: false,
        showCloseButton: true,
        width: 'auto',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454',
        customClass: {
            title: 'fs-5'
        }
    });
}
const orderSummaryModal = new bootstrap.Modal(document.getElementById('orderSummaryModal'));

function openOrderSummaryModal(userId, customerName) {
    document.getElementById('osmCustomerName').innerText = customerName;
    document.getElementById('osmBody').innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">กำลังโหลด...</div></div>';
    orderSummaryModal.show();

    const fd = new FormData();
    fd.append('action', 'get_order_summary');
    fd.append('user_id', userId);

    fetch('ajax_customer_details.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            let html = '';
            if (data.orders.length === 0) {
                html = '<div class="text-center py-4 text-muted">ไม่พบข้อมูลออเดอร์</div>';
            } else {
                data.orders.forEach(o => {
                    const d = new Date(o.orders_date);
                    const dateStr = d.toLocaleDateString('th-TH', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'});
                    
                    html += `
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <span class="fw-bold fs-6 text-dark">#${o.orders_no}</span> 
                                    <small class="text-muted ms-2">OrdersID: ${o.orders_id}</small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">${dateStr}</small>
                                    <span class="fw-bold text-success">฿${parseFloat(o.orders_grandtotal).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</span>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex flex-wrap gap-2">
                    `;
                    
                    if (o.products && o.products.length > 0) {
                        o.products.forEach(p => {
                            const img = p.product_image_url || '/uploads/products/default.png';
                            html += `
                                <div class="border rounded p-1 text-center bg-white" style="width: 80px; position: relative;">
                                    <img src="${img}" class="img-fluid rounded" style="height: 60px; object-fit: contain;" title="${p.product_name}" onerror="this.src='/uploads/products/default.png'">
                                    <span class="badge bg-secondary position-absolute top-0 start-100 translate-middle rounded-pill" style="font-size: 0.6rem;">x${p.ordetail_qty}</span>
                                </div>
                            `;
                        });
                    } else {
                        html += '<span class="text-muted small">ไม่มีรายการสินค้า</span>';
                    }
                    
                    html += `
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            document.getElementById('osmBody').innerHTML = html;
        } else {
            document.getElementById('osmBody').innerHTML = `<div class="text-center py-4 text-danger">${data.message || 'เกิดข้อผิดพลาด'}</div>`;
        }
    })
    .catch(err => {
        document.getElementById('osmBody').innerHTML = `<div class="text-center py-4 text-danger">ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้</div>`;
    });
}

function toggleUserStatus(userId, currentStatus) {
    const newStatus = currentStatus == 1 ? 0 : 1;
    const statusText = newStatus === 1 ? 'ใช้งานปกติ' : 'เพิกเฉย / โปร่งใส';
    
    Swal.fire({
        title: 'ยืนยันการเปลี่ยนสถานะ?',
        text: `คุณต้องการเปลี่ยนสถานะลูกค้ารายนี้เป็น "${statusText}" ใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, เปลี่ยนเลย',
        cancelButtonText: 'ยกเลิก',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${ADMIN_URL}/api/update_status.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: 'เปลี่ยนสถานะเรียบร้อยแล้ว',
                        timer: 1500,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    });

                    // Update UI without reload
                    document.querySelectorAll(`button[onclick="toggleUserStatus(${userId}, ${currentStatus})"]`).forEach(btn => {
                        btn.setAttribute('onclick', `toggleUserStatus(${userId}, ${newStatus})`);
                        if (newStatus === 1) {
                            btn.innerHTML = '<i data-lucide="check" class="text-success" style="width:14px;height:14px;"></i>';
                        } else {
                            btn.innerHTML = '<i data-lucide="x" class="text-danger" style="width:14px;height:14px;"></i>';
                        }
                    });
                    
                    document.querySelectorAll(`tr#user-row-${userId}`).forEach(row => {
                        row.style.opacity = newStatus === 1 ? '1' : '0.3';
                    });
                    if (typeof lucide !== 'undefined') lucide.createIcons();

                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message || 'ไม่สามารถเปลี่ยนสถานะได้' });
                }
            })
            .catch(error => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' });
            });
        }
    });
}

let cachedTags = [];
async function refreshCachedTags() {
    try {
        const r = await fetch(ADMIN_URL + '/api/get_all_tags.php');
        const d = await r.json();
        if (d.success) cachedTags = d.tags;
    } catch (e) {}
}
refreshCachedTags();

function renderTagsInTable(userId, tagsArray) {
    let html = '';
    tagsArray.forEach(tagName => {
        let tagData = cachedTags.find(t => t.tag_name === tagName);
        let tColor = tagData ? tagData.tag_color : 'secondary';
        let isHex = tColor.startsWith('#');
        let badgeStyle = isHex ? `background-color: ${tColor}; color: #fff; border: 1px solid rgba(0,0,0,0.1);` : '';
        let badgeClass = isHex ? 'badge fw-normal shadow-sm' : `badge bg-${tColor} fw-normal shadow-sm`;
        html += `<span class="${badgeClass}" style="font-size: 0.65rem; padding: 4px 8px; border-radius: 6px; ${badgeStyle}">${tagName}</span>`;
    });
    
    html += `<div class="btn-manage-tags ms-1 d-inline-flex align-items-center justify-content-center rounded border" onclick="promptAddTag(${userId})" title="จัดการ Tag" style="width: 20px; height: 20px; cursor: pointer; vertical-align: middle; background-color: var(--bs-secondary-bg, #f8f9fa);"><i data-lucide="edit-2" style="width:10px;height:10px;color:inherit;"></i></div>`;
    
    const wrapper1 = document.getElementById(`tags-wrapper-${userId}`);
    if (wrapper1) { wrapper1.innerHTML = html; }
    const wrapper2 = document.getElementById(`tags-wrapper-return-${userId}`);
    if (wrapper2) { wrapper2.innerHTML = html; }
    
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function promptAddTag(userId) {
    let modalCurrentTags = [];
    let pendingTagUpdates = 0;

    function updateFinishButtonState() {
        const btn = Swal.getConfirmButton();
        if (!btn) return;
        if (pendingTagUpdates > 0) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';
        } else {
            btn.disabled = false;
            btn.innerHTML = 'ปิดหน้าต่าง';
        }
    }

    function renderTagModalContent(currentTags, isLoading = false) {
        if (isLoading) return '<div class="text-center py-5"><div class="spinner-border text-success"></div><p class="mt-2 small text-muted">กำลังโหลดข้อมูลแท็ก...</p></div>';
        
        let tagsHtml = currentTags.map(tag => `
            <span class="tag-badge-inline">
                ${tag} 
                <span class="remove-tag-btn" onclick="optimisticRemoveTag(${userId}, '${tag}')" title="ลบแท็กนี้">&times;</span>
            </span>
        `).join('');

        let suggestions = cachedTags.filter(t => !currentTags.includes(t.tag_name)).map(t => `
            <span class="suggestion-chip" onclick="optimisticAddTag(${userId}, '${t.tag_name}')">
                <i class="fas fa-plus-circle me-1 opacity-50"></i>${t.tag_name}
            </span>
        `).join('');

        return `
            <div class="text-start">
                <style>
                    .tag-input-container {
                        border: 2px solid #e5e7eb; border-radius: 12px; padding: 10px; display: flex; flex-wrap: wrap; gap: 8px;
                        background: var(--tag-input-bg, #fff); color: var(--tag-input-color, #111); min-height: 110px; align-content: flex-start;
                    }
                    .tag-badge-inline {
                        background: #065f46; color: #fff; padding: 5px 8px 5px 12px; border-radius: 8px; font-size: 0.75rem; display: flex; align-items: center; font-weight: 500;
                    }
                    .remove-tag-btn {
                        cursor: pointer; display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; background: rgba(255,255,255,0.2); margin-left: 8px; font-size: 14px; transition: all 0.2s;
                    }
                    .remove-tag-btn:hover { background: #ef4444; color: #fff; transform: rotate(90deg); }
                    .modal-tag-input-field { border: none !important; outline: none !important; flex: 1; min-width: 150px; padding: 5px; font-size: 0.85rem; background: transparent; color: inherit; }
                    .suggestion-chip { background: #f3f4f6; color: #374151; padding: 7px 14px; border-radius: 20px; font-size: 0.75rem; cursor: pointer; border: 1px solid #e5e7eb; transition: all 0.2s; margin-right: 8px; margin-bottom: 10px; display: inline-block; }
                    .suggestion-chip:hover { background: #10b981; color: #fff; border-color: #10b981; }
                </style>
                <div class="mb-4">
                    <label class="small fw-bold text-muted mb-2 d-block">จัดการแท็กของสมาชิก:</label>
                    <div class="tag-input-container" onclick="document.getElementById('modal-tag-input').focus()">
                        ${tagsHtml}
                        <input type="text" id="modal-tag-input" class="modal-tag-input-field" placeholder="พิมพ์ชื่อแท็กใหม่แล้วกด Enter...">
                    </div>
                </div>
                <label class="small fw-bold text-muted mb-2 d-block">แท็กแนะนำที่มีในระบบ:</label>
                <div class="d-flex flex-wrap" style="max-height: 150px; overflow-y: auto; padding: 2px;">
                    ${suggestions || '<span class="text-muted small">ยังไม่มีแท็กอื่นในระบบ</span>'}
                </div>
            </div>`;
    }

    window.optimisticAddTag = (uid, tagName) => {
        if (modalCurrentTags.includes(tagName)) return;
        modalCurrentTags.push(tagName);
        pendingTagUpdates++;
        updateModalUI(uid);
        updateFinishButtonState();
        fetch(ADMIN_URL + '/api/update_user_tags.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: uid, tag_name: tagName, action: 'add' })
        }).then(r => r.json()).then(d => {
            pendingTagUpdates--;
            updateFinishButtonState();
            if (d.success) refreshCachedTags();
            else { modalCurrentTags = modalCurrentTags.filter(t => t !== tagName); updateModalUI(uid); Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: d.message, toast: true, position: 'top-end', timer: 3000 }); }
        }).catch(() => { pendingTagUpdates--; updateFinishButtonState(); });
    };

    window.optimisticRemoveTag = (uid, tagName) => {
        modalCurrentTags = modalCurrentTags.filter(t => t !== tagName);
        pendingTagUpdates++;
        updateModalUI(uid);
        updateFinishButtonState();
        fetch(ADMIN_URL + '/api/update_user_tags.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: uid, tag_name: tagName, action: 'remove' })
        }).then(r => r.json()).then(d => {
            pendingTagUpdates--;
            updateFinishButtonState();
            if (!d.success) { modalCurrentTags.push(tagName); updateModalUI(uid); Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถลบได้', toast: true, position: 'top-end', timer: 3000 }); }
        }).catch(() => { pendingTagUpdates--; updateFinishButtonState(); });
    };

    function updateModalUI(uid) {
        const cont = Swal.getHtmlContainer();
        if (cont) {
            cont.innerHTML = renderTagModalContent(modalCurrentTags);
            const input = document.getElementById('modal-tag-input');
            if (input) {
                input.focus();
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        const val = input.value.trim();
                        if (val) optimisticAddTag(uid, val);
                    }
                });
            }
        }
    }

    Swal.fire({
        title: 'จัดการแท็กกลุ่มลูกค้า',
        html: renderTagModalContent([], true),
        showConfirmButton: true,
        confirmButtonText: 'ปิดหน้าต่าง',
        confirmButtonColor: '#6c757d',
        width: '550px',
        didOpen: () => {
            fetch(ADMIN_URL + '/api/get_user_tags.php?user_id=' + userId)
                .then(r => r.json())
                .then(d => {
                    modalCurrentTags = d.success ? d.tags : [];
                    updateModalUI(userId);
                });
        }
    }).then(() => {
        // Instead of reload, just update UI
        renderTagsInTable(userId, modalCurrentTags);
    });
}

// No need to create icons here since we do it in DOMContentLoaded above
</script>

<!-- Address Management Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-lucide="map-pin" style="width:20px;height:20px;"></i>
                    <span id="addressModalTitle">ที่อยู่ของลูกค้า</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Address Button -->
                <div class="mb-3">
                    <button type="button" class="btn btn-success btn-sm" onclick="showAddressForm()">
                        <i data-lucide="plus" style="width:16px;height:16px;"></i>
                        เพิ่มที่อยู่ใหม่
                    </button>
                </div>

                <!-- Address Form (Hidden by default) -->
                <div id="addressFormContainer" style="display:none;">
                    <div class="card mb-3 border-primary">
                        <div class="card-header bg-primary bg-opacity-10">
                            <h6 class="mb-0" id="formTitle">เพิ่มที่อยู่ใหม่</h6>
                        </div>
                        <div class="card-body">
                            <form id="addressForm">
                                <input type="hidden" id="addr_id" name="addr_id">
                                <input type="hidden" id="user_id" name="user_id">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">ชื่อผู้รับ <span class="text-danger">*</span></label>
                                        <input type="text" id="addr_name" name="addr_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                        <input type="text" id="addr_mobile" name="addr_mobile" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">ที่อยู่บรรทัด 1 <span class="text-danger">*</span></label>
                                        <input type="text" id="addr_detail" name="addr_detail" class="form-control" placeholder="เลขที่ หมู่บ้าน/คอนโด ชั้น ห้อง ถนน ซอย" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">ที่อยู่บรรทัด 2</label>
                                        <input type="text" id="addr_detail2" name="addr_detail2" class="form-control" placeholder="ตำบล/แขวง อำเภอ/เขต">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                                        <select id="provinces_id" name="provinces_id" class="form-select" required>
                                            <option value="">-- เลือกจังหวัด --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">รหัสไปรษณีย์</label>
                                        <input type="text" id="addr_postcode" name="addr_postcode" class="form-control" maxlength="5">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ประเภทที่อยู่ <span class="text-danger">*</span></label>
                                        <select id="addr_type" name="addr_type" class="form-select" required>
                                            <option value="1">🏠 ที่บ้าน</option>
                                            <option value="2">🏢 ที่ทำงาน</option>
                                            <option value="3">📄 ที่อยู่สำหรับออกบิล/ใบกำกับภาษี</option>
                                            <option value="4">📦 ใช้เป็นผู้ส่งในนาม</option>
                                            <option value="5">👤 ผู้รับแทน</option>
                                            <option value="6">🏕️ ที่อยู่รับสินค้าชั่วคราว</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">หมายเหตุ</label>
                                        <input type="text" id="addr_forword" name="addr_forword" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="mt-3 d-flex gap-2">
                                    <button type="button" class="btn btn-primary" onclick="saveAddress()">
                                        <i data-lucide="save" style="width:16px;height:16px;"></i>
                                        บันทึก
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="cancelAddressForm()">
                                        ยกเลิก
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Address List -->
                <div id="addressList">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Image Modal -->
<div class="modal fade" id="userImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">อัปเดตรูปโปรไฟล์ลูกค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <form id="userImageForm">
                    <input type="hidden" id="user_image_id" name="user_id">
                    <div class="mb-3">
                        <img id="current_user_image" src="" class="rounded-circle border mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <div class="mb-3">
                        <input type="file" class="form-control" name="user_picture" accept="image/*" required>
                    </div>
                </form>

                <div class="mt-4 border-top pt-3 text-start">
                    <h6 class="small fw-bold mb-2"><i data-lucide="history" class="me-1" style="width:12px; height:12px;"></i> ประวัติรูปภาพ</h6>
                    <div id="user_image_history" class="d-flex gap-2 overflow-auto pb-2" style="min-height: 80px;">
                        <!-- History images will be loaded here -->
                        <div class="text-muted small">กำลังโหลดประวัติ...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" id="btnSaveUserImage" class="btn btn-primary" onclick="saveUserImage()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Customer Edit Modal -->
<div class="modal fade" id="editCustomerModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขข้อมูลลูกค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editCustomerForm">
                    <input type="hidden" id="editCustomerUserId" name="user_id">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ (First Name)</label>
                        <input type="text" class="form-control" id="editCustomerName" name="user_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">นามสกุล (Last Name)</label>
                        <input type="text" class="form-control" id="editCustomerLastname" name="user_lastname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อเล่น (Nickname)</label>
                        <input type="text" class="form-control" id="editCustomerNickname" name="user_nickname">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เบอร์โทร (Phone)</label>
                        <input type="text" class="form-control" id="editCustomerMobile" name="user_mobile">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อีเมล (Email)</label>
                        <input type="email" class="form-control" id="editCustomerEmail" name="user_email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-info"><i data-lucide="user-check" class="me-1" style="width: 14px; height: 14px;"></i> ข้อมูลลูกค้า / หมายเหตุ</label>
                        <textarea class="form-control" id="editCustomerNote" name="user_note" rows="3" placeholder="ระบุข้อมูลที่ควรทราบเกี่ยวกับลูกค้ารายนี้..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-success"><i data-lucide="map-pin" class="me-1" style="width: 14px; height: 14px;"></i> ลิงค์ Google Map ลูกค้า</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="editCustomerGoogleMap" name="user_google_map" placeholder="วางลิงค์ Google Map ที่นี่">
                            <a href="#" target="_blank" class="btn btn-outline-success" id="btnOpenEditMap" style="display:none;"><i data-lucide="external-link" style="width: 14px; height: 14px;"></i> ดูแผนที่</a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveEditCustomerInfo()" id="btnSaveEditCustomer">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof ADMIN_URL === 'undefined') {
        window.ADMIN_URL = '<?= ADMIN_URL ?>';
    }
    if (typeof ROOT_URL === 'undefined') {
        window.ROOT_URL = '<?= ROOT_URL ?>';
    }

function openUserImageModal(userId, currentImage) {
    // Reset form
    document.getElementById('userImageForm').reset();
    
    document.getElementById('user_image_id').value = userId;
    const img = document.getElementById('current_user_image');
    if (currentImage) {
        img.src = `${window.ROOT_URL}/uploads/profile/${userId}/${currentImage}`;
    } else {
        img.src = `../assets/images/placeholder.png`;
    }

    const historyContainer = document.getElementById('user_image_history');
    historyContainer.innerHTML = '<div class="text-muted small">กำลังโหลดประวัติ...</div>';
    
    fetch(`${window.ADMIN_URL}/api/get_user_image_history.php?user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.images.length > 0) {
                let html = '';
                data.images.forEach(img => {
                    const isActive = img.filename === currentImage ? 'border-primary' : 'opacity-75';
                    html += `
                        <div class="position-relative cursor-pointer flex-shrink-0" 
                             onclick="selectHistoryImage(${userId}, '${img.filename}')" 
                             title="เลือกรูปนี้">
                            <img src="${img.url}" 
                                 class="rounded border ${isActive}" 
                                 style="width: 60px; height: 60px; object-fit: cover; transition: all 0.2s;"
                                 onmouseover="this.style.opacity='1'; this.style.transform='scale(1.05)'"
                                 onmouseout="if(!this.classList.contains('border-primary')) this.style.opacity='0.75'; this.style.transform='scale(1)'">
                        </div>`;
                });
                historyContainer.innerHTML = html;
            } else {
                historyContainer.innerHTML = '<div class="text-muted small italic">ไม่มีประวัติรูปภาพ</div>';
            }
        })
        .catch(err => {
            console.error(err);
            historyContainer.innerHTML = '<div class="text-danger small">โหลดล้มเหลว</div>';
        });

    new bootstrap.Modal(document.getElementById('userImageModal')).show();
}

function selectHistoryImage(userId, filename) {
    Swal.fire({
        title: 'ยืนยันการเปลี่ยนรูป',
        text: 'คุณต้องการใช้รูปที่เคยอัปโหลดไว้แล้วใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ใช้รูปนี้',
        cancelButtonText: 'ยกเลิก',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`${window.ADMIN_URL}/api/set_user_picture_from_history.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, filename: filename })
            })
            .then(response => {
                if (!response.ok) throw new Error(response.statusText);
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'เปลี่ยนรูปสำเร็จ',
                    showConfirmButton: false,
                    timer: 800
                });
                const modal = bootstrap.Modal.getInstance(document.getElementById('userImageModal'));
                if (modal) modal.hide();
                
                const newImgUrl = `${window.ROOT_URL}/uploads/profile/${userId}/${filename}`;
                document.querySelectorAll(`.profile-img-${userId}`).forEach(img => {
                    img.src = newImgUrl;
                    img.setAttribute('onclick', `openUserImageModal(${userId}, '${filename}')`);
                });
            } else {
                Swal.fire('Error', result.value.message, 'error');
            }
        }
    });
}

function saveUserImage() {
    const form = document.getElementById('userImageForm');
    const formData = new FormData(form);
    const btn = document.getElementById('btnSaveUserImage');
    const originalHtml = btn.innerHTML;
    const userId = document.getElementById('user_image_id').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> กำลังบันทึก...';

    fetch(`${window.ADMIN_URL}/api/update_user_picture.php`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    showConfirmButton: false,
                    timer: 800
                });
                const modal = bootstrap.Modal.getInstance(document.getElementById('userImageModal'));
                if (modal) modal.hide();
                
                const newImgUrl = `${window.ROOT_URL}/uploads/profile/${userId}/${data.filename}`;
                document.querySelectorAll(`.profile-img-${userId}`).forEach(img => {
                    img.src = newImgUrl;
                    img.setAttribute('onclick', `openUserImageModal(${userId}, '${data.filename}')`);
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            Swal.fire('Error', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
        });
}

function openEditCustomerModal(userId) {
    const modalEl = document.getElementById('editCustomerModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) {
        modal = new bootstrap.Modal(modalEl);
    }

    document.getElementById('editCustomerForm').reset();

    fetch(`${window.ADMIN_URL}/api/get_user_mini.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.data;
                document.getElementById('editCustomerUserId').value = user.user_id;
                document.getElementById('editCustomerName').value = user.user_name || '';
                document.getElementById('editCustomerLastname').value = user.user_lastname || '';
                
                const elNickname = document.getElementById('editCustomerNickname');
                if(elNickname) elNickname.value = user.user_nickname || '';
                
                document.getElementById('editCustomerMobile').value = user.user_mobile || '';
                document.getElementById('editCustomerEmail').value = user.user_email || '';
                
                const elNote = document.getElementById('editCustomerNote');
                if(elNote) elNote.value = user.user_note || '';
                
                const elMap = document.getElementById('editCustomerGoogleMap');
                if(elMap) elMap.value = user.user_google_map || '';

                const btnOpenMap = document.getElementById('btnOpenEditMap');
                if (btnOpenMap) {
                    if (user.user_google_map && user.user_google_map.trim() !== '') {
                        btnOpenMap.href = user.user_google_map;
                        btnOpenMap.style.display = 'inline-flex';
                        btnOpenMap.style.alignItems = 'center';
                    } else {
                        btnOpenMap.style.display = 'none';
                        btnOpenMap.href = '#';
                    }
                }
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }

                modal.show();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'ไม่สามารถโหลดข้อมูลลูกค้าได้', 'error');
        });
}

function saveEditCustomerInfo() {
    const form = document.getElementById('editCustomerForm');
    const formData = new FormData(form);
    const btn = document.getElementById('btnSaveEditCustomer');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> กำลังบันทึก...';

    fetch(`${window.ADMIN_URL}/api/save_user_mini.php`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (data.success) {
                const modalEl = document.getElementById('editCustomerModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();

                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    showConfirmButton: false,
                    timer: 800
                });
                
                // Update DOM directly without reload
                const userId = document.getElementById('editCustomerUserId').value;
                const name = document.getElementById('editCustomerName').value;
                const lastname = document.getElementById('editCustomerLastname').value;
                const fullname = (name + ' ' + lastname).trim();
                
                document.querySelectorAll('#customer-name-' + userId).forEach(el => {
                    el.textContent = fullname;
                    el.title = "คลิกเพื่อแก้ไขข้อมูล: " + fullname;
                });
                
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error('Error:', error);
            Swal.fire('Error', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
        });
}
</script>
<script src="../assets/js/addresses.js?v=<?= time() ?>"></script>

<?php
$extra_scripts = ob_get_clean();
$content = ob_get_clean();

// Add missing include layout
include '../includes/layout.php';
?>
