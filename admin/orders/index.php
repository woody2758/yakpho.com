<?php
$page_title = "จัดการคำสั่งซื้อ";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

$channels = $db->query("SELECT * FROM sales_channels WHERE channel_status = 1 ORDER BY channel_id ASC")->fetchAll(PDO::FETCH_ASSOC);
$countries = $db->query("SELECT * FROM countries ORDER BY country_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$agents = $db->query("SELECT user_id, user_name, user_lastname, role FROM user WHERE user_del = 0 AND role IN ('admin', 'manager', 'owner', 'staff') ORDER BY user_name ASC")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<style>
/* บังคับ Dark Mode ให้เข้มแน่นอน สำหรับทุก Modal ในหน้าจัดการคำสั่งซื้อ */
[data-theme="dark"] .modal-content {
    background-color: #1a1a1a !important;
    color: #f0f0f0 !important;
    border: 1px solid #333 !important;
}
[data-theme="dark"] .modal-content .card {
    background-color: #262626 !important;
    border-color: #333 !important;
    color: #f0f0f0 !important;
}
[data-theme="dark"] .modal-content .card-header {
    background-color: #1e1e1e !important;
    border-bottom: 1px solid #333 !important;
    color: #fff !important;
}
[data-theme="dark"] .form-control, [data-theme="dark"] .form-select {
    background-color: #262626 !important;
    border-color: #444 !important;
    color: #fff !important;
}
[data-theme="dark"] .form-control:focus, [data-theme="dark"] .form-select:focus {
    background-color: #2d2d2d !important;
    color: #fff !important;
}
[data-theme="dark"] .modal-header, [data-theme="dark"] .modal-footer {
    border-color: #333 !important;
}
[data-theme="dark"] .modal-header.bg-light, [data-theme="dark"] .modal-footer.bg-light {
    background-color: #1e1e1e !important;
}
[data-theme="dark"] .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}
[data-theme="dark"] .modal-title {
    color: #3498db !important;
}
[data-theme="dark"] #modalLoadingOverlay {
    background-color: rgba(26, 26, 26, 0.9) !important;
}

/* ปรับแต่งตาราง (Table) ภายใน Modal */
[data-theme="dark"] .modal-content .table {
    color: #e0e0e0 !important;
    background-color: #1a1a1a !important;
}
[data-theme="dark"] .modal-content .table td, [data-theme="dark"] .modal-content .table th {
    border-color: #333 !important;
    color: #e0e0e0 !important;
}
[data-theme="dark"] .modal-content .table thead.table-light th,
[data-theme="dark"] .modal-content .table thead th,
[data-theme="dark"] .modal-content .table tfoot tr.table-light td {
    background-color: #222 !important;
    color: #fff !important;
    border-color: #333 !important;
}
[data-theme="dark"] .modal-content .table-light {
    background-color: #222 !important;
    color: #fff !important;
}
[data-theme="dark"] .modal-content .table-info {
    background-color: #1e3a5f !important;
    color: #fff !important;
}
[data-theme="dark"] .modal-content .table-warning {
    background-color: #3a321d !important;
    color: #fff !important;
}
[data-theme="dark"] .modal-content .table-success {
    background-color: #1b4d3e !important;
    color: #fff !important;
}

/* ปรับแต่ง List Group (ที่อยู่จัดส่ง) ภายใน Modal */
[data-theme="dark"] .modal-content .list-group-item {
    background-color: #262626 !important;
    color: #f0f0f0 !important;
    border-color: #333 !important;
}
[data-theme="dark"] .modal-content .list-group-item:hover {
    background-color: #2d2d2d !important;
}
[data-theme="dark"] .modal-content .list-group-item.active {
    background-color: #3498db !important;
    border-color: #3498db !important;
    color: #fff !important;
}

/* ปรับแต่งแท็บ (Tabs) ภายใน Modal เช่น แท็บแท็บเลือกสินค้า */
[data-theme="dark"] .modal-content .nav-tabs {
    border-bottom: 1px solid #333 !important;
}
[data-theme="dark"] .modal-content .nav-tabs .nav-link {
    color: #888 !important;
    border: none !important;
    background: transparent !important;
}
[data-theme="dark"] .modal-content .nav-tabs .nav-link.active {
    color: #fff !important;
    background-color: #3498db !important;
    border-radius: 5px !important;
}

/* ปรับแต่งหน้ากากเตือน (Alerts) ภายใน Modal */
[data-theme="dark"] .modal-content .alert-warning {
    background-color: #3a321d !important;
    color: #ffe066 !important;
    border: 1px solid #ffe06622 !important;
}
[data-theme="dark"] .modal-content .alert-warning h6,
[data-theme="dark"] .modal-content .alert-warning p,
[data-theme="dark"] .modal-content .alert-warning strong {
    color: #ffe066 !important;
}

/* ปรับแต่งฟอร์มในส่วนสร้างออเดอร์ใหม่ */
[data-theme="dark"] #selectedCustomerInfo, [data-theme="dark"] #selectedAddressInfo {
    color: #e0e0e0 !important;
}
[data-theme="dark"] .cursor-pointer {
    background-color: #2d2d2d !important;
    border-color: #444 !important;
}

/* Hide default search clear button */
input[type="search"]::-webkit-search-decoration,
input[type="search"]::-webkit-search-cancel-button,
input[type="search"]::-webkit-search-results-button,
input[type="search"]::-webkit-search-results-decoration {
  display: none;
}
</style>

<div class="container-fluid px-1 px-md-3 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0 fs-5 fs-md-3 fw-bold"><i data-lucide="shopping-bag" class="me-1 me-md-2 text-primary" style="width:20px; height:20px;"></i>จัดการคำสั่งซื้อ</h3>
        <div class="d-flex gap-1 gap-md-2">
            <a href="<?= ADMIN_URL ?>/orders/print_batch.php" target="_blank" class="btn btn-sm btn-primary fw-bold px-2 px-md-3">
                <i data-lucide="printer" class="me-1 me-md-2" style="width:14px; height:14px;"></i>
                <span class="d-none d-md-inline">พิมพ์ออเดอร์เป็นชุด (A5)</span>
                <span class="d-inline d-md-none">พิมพ์เป็นชุด</span>
            </a>
            <button onclick="createNewOrder()" class="btn btn-sm btn-success px-2 px-md-3 fw-bold">
                <i data-lucide="plus" class="me-1 me-md-2" style="width:14px; height:14px;"></i>
                <span class="d-none d-md-inline">ออเดอร์ใหม่</span>
                <span class="d-inline d-md-none">ใหม่</span>
            </button>
        </div>
    </div>

    <!-- Status Dashboard -->
    <div id="statusDashboard" class="row g-2 mb-3">
        <!-- Status cards will be loaded here -->
        <div class="col-12 text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Search Card -->
    <div class="row g-2 mb-3">
        <div class="col-12">
            <div class="position-relative shadow-sm rounded bg-white">
                <input type="search" id="searchInput" onsearch="if(this.value==='') { if(typeof performSearch === 'function') performSearch(); else if(this.form) this.form.submit(); else window.location.search=''; }" class="form-control border-0 rounded px-3" placeholder="ค้นหาเลขที่ออเดอร์, ชื่อลูกค้า..." style="font-size: 12px; height: 42px;">
                <button type="button" id="clearSearch" onclick="clearSearch()" class="btn border-0 position-absolute end-0 top-50 translate-middle-y d-none text-muted" style="z-index: 4;">
                    <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="row g-2">
        <div class="col-12">
            <div id="tableContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="col-12">
            <div class="bg-white py-3 rounded shadow-sm" id="paginationContainer">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderModal" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="modalTitle">รายละเอียดคำสั่งซื้อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="modalContent" class="p-4">
                    <!-- Content will be loaded here -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <div class="d-flex align-items-center me-auto">
                    <span class="me-2 fw-bold">เปลี่ยนสถานะ:</span>
                    <select id="statusSelect" class="form-select form-select-sm" style="width: 200px;">
                        <!-- Status options will be loaded here -->
                    </select>
                    <div class="form-check ms-3">
                        <input class="form-check-input" type="checkbox" id="notifyEmail">
                        <label class="form-check-label small fw-bold" for="notifyEmail">แจ้งเตือนอีเมล</label>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                <button id="modalSaveBtn" onclick="updateStatus(this)" class="btn btn-success px-4 fw-bold">
                    <i data-lucide="check" class="me-1" style="width:18px;height:18px;"></i> บันทึกข้อมูล
                </button>
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

<!-- Shipping Modal -->
<div class="modal fade" id="shippingModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">จัดการการจัดส่ง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="shippingForm">
                    <input type="hidden" id="shipping_order_id" name="orders_id">
                    <div class="mb-3">
                        <label class="form-label">เลือกขนส่ง</label>
                        <select class="form-select" id="shipping_transcat_id" name="transcat_id" required>
                            <option value="">-- เลือกขนส่ง --</option>
                            <!-- Options will be loaded via JS -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เลขพัสดุ (Tracking Number)</label>
                        <input type="text" class="form-control" id="shipping_tracking" name="orders_tracking" placeholder="ระบุเลขพัสดุ">
                        <div class="form-text">กรณีมีหลายเลข ให้คั่นด้วยเครื่องหมายคอมม่า (,)</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-danger">ต้นทุนค่าส่งที่จ่ายจริง (บาท) <i data-lucide="info" style="width: 14px; height: 14px;" title="ระบบจะนำค่านี้ไปคำนวณกำไร-ขาดทุน หากไม่ระบุจะยังไม่นำไปคิดกำไร"></i></label>
                        <input type="number" step="0.01" class="form-control" id="shipping_delicosttrue" name="orders_delicosttrue" placeholder="0.00">
                        <div class="form-text text-danger" style="font-size: 0.75rem;">* ต้นทุนส่วนนี้ใช้คำนวณหลังบ้าน ไม่แสดงหน้าบิลลูกค้า</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" id="btnSaveShipping" class="btn btn-primary" onclick="saveShipping()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Customer Edit Modal -->
<div class="modal fade" id="customerModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content position-relative">
            <!-- Loading Overlay -->
            <div id="customerModalLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white d-flex flex-column justify-content-center align-items-center z-3 d-none" style="border-radius: 0.3rem; opacity: 0.9;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                <div class="mt-3 text-primary fw-bold fs-5">กำลังโหลดข้อมูล...</div>
            </div>
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขข้อมูลลูกค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="customerForm">
                    <input type="hidden" id="customerUserId" name="user_id">
                    <input type="hidden" id="customerOrderId" name="orders_id">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ (First Name)</label>
                        <input type="text" class="form-control" id="customerName" name="user_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">นามสกุล (Last Name)</label>
                        <input type="text" class="form-control" id="customerLastname" name="user_lastname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อเล่น (Nickname)</label>
                        <input type="text" class="form-control" id="customerNickname" name="user_nickname">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เบอร์โทร (Phone)</label>
                        <input type="text" class="form-control" id="customerMobile" name="user_mobile">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อีเมล (Email)</label>
                        <input type="email" class="form-control" id="customerEmail" name="user_email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-primary"><i data-lucide="globe" class="me-1" style="width: 14px; height: 14px;"></i> เว็บไซต์ (Website)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="customerWebsite" name="user_website" placeholder="ระบุเว็บไซต์หรือลิงก์">
                            <a href="#" target="_blank" class="btn btn-outline-primary" id="btnOpenWebsite" style="display:none;"><i data-lucide="external-link" style="width: 14px; height: 14px;"></i> เปิดลิงก์</a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-info"><i data-lucide="user-check" class="me-1" style="width: 14px; height: 14px;"></i> ข้อมูลลูกค้า / หมายเหตุ</label>
                        <textarea class="form-control" id="customerNote" name="user_note" rows="3" placeholder="ระบุข้อมูลที่ควรทราบเกี่ยวกับลูกค้ารายนี้..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-success"><i data-lucide="map-pin" class="me-1" style="width: 14px; height: 14px;"></i> ลิงค์ Google Map ลูกค้า</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="customerGoogleMap" name="user_google_map" placeholder="วางลิงค์ Google Map ที่นี่">
                            <a href="#" target="_blank" class="btn btn-outline-success" id="btnOpenMap" style="display:none;"><i data-lucide="external-link" style="width: 14px; height: 14px;"></i> ดูแผนที่</a>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveCustomerInfo()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Address Selection Modal -->
<div class="modal fade" id="addressSelectModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressSelectTitle">เลือกที่อยู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addressList" class="list-group">
                    <!-- Addresses will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Order Modal -->
<div class="modal fade" id="createOrderModal" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i data-lucide="shopping-cart" class="me-2"></i>สร้างออเดอร์ใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                
                <!-- Customer Info Section -->
                <div id="customerInfoSection" class="card mb-3">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i data-lucide="user" class="me-2"></i>ข้อมูลลูกค้า</h6>
                    </div>
                    <div class="card-body" id="selectedCustomerInfo">
                        <div class="text-center text-muted py-3">
                            <i data-lucide="user-plus" class="mb-2" style="width:32px;height:32px;"></i>
                            <p class="mb-0">กรุณาเลือกลูกค้า</p>
                        </div>
                    </div>
                </div>

                <!-- Address Section -->
                <div id="addressSection" class="card mb-3 d-none">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i data-lucide="map-pin" class="me-2"></i>ที่อยู่จัดส่ง</h6>
                    </div>
                    <div class="card-body" id="selectedAddressInfo">
                        <!-- Address will be shown here -->
                    </div>
                </div>

                <!-- Cart Section -->
                <div id="cartSection" class="card mb-3 d-none">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i data-lucide="shopping-bag" class="me-2"></i>รายการสินค้า</h6>
                        <button onclick="addProductToNewOrder()" class="btn btn-sm btn-success" id="addProductBtn">
                            <i data-lucide="plus" style="width:14px;height:14px;"></i> เพิ่มสินค้า
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div id="newOrderCartItems" class="table-responsive">
                            <div class="text-center text-muted py-4">
                                <i data-lucide="shopping-cart" style="width:32px;height:32px;"></i>
                                <p class="mb-0 mt-2">ยังไม่มีสินค้าในตะกร้า</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Settings Section -->
                <div id="settingsSection" class="card d-none">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i data-lucide="settings" class="me-2"></i>ตั้งค่าเพิ่มเติม</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">วิธีการชำระเงิน</label>
                                <select id="newOrderPaycat" class="form-select" onchange="updateNewOrderTotal()">
                                    <option value="1">โอนเงิน / เงินสด</option>
                                    <option value="2">เก็บเงินปลายทาง (COD 3%)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">ช่องทางการขาย</label>
                                <select id="newOrderChannel" class="form-select">
                                    <?php foreach ($channels as $c): ?>
                                        <option value="<?= htmlspecialchars($c['channel_code']) ?>"><?= htmlspecialchars($c['channel_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">ประเทศลูกค้า</label>
                                <select id="newOrderCountry" class="form-select">
                                    <option value="0">-- ยังไม่ได้ระบุ --</option>
                                    <?php foreach ($countries as $ct): ?>
                                        <option value="<?= $ct['country_id'] ?>" <?= $ct['country_id'] == 171 ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ct['country_name']) ?> (<?= htmlspecialchars($ct['country_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">ผู้แนะนำ / ตัวแทน / Therapist</label>
                                <div class="input-group">
                                    <select id="newOrderAgent" class="form-select">
                                        <option value="">-- ไม่ระบุ --</option>
                                        <?php foreach ($agents as $a): ?>
                                            <option value="<?= $a['user_id'] ?>">
                                                <?= htmlspecialchars($a['user_name'] . ' ' . $a['user_lastname']) ?> (<?= htmlspecialchars($a['role']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-secondary" type="button" onclick="searchOtherAgent()" title="ค้นหาตัวแทนอื่น...">
                                        <i data-lucide="search" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">ค่าจัดส่ง (บาท)</label>
                                <input type="number" id="newOrderShipping" class="form-control" 
                                       value="60" min="0" step="0.01" 
                                       onchange="updateNewOrderTotal()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">ส่วนลด (บาท)</label>
                                <input type="number" id="newOrderDiscount" class="form-control" 
                                       value="0" min="0" step="0.01" 
                                       onchange="updateNewOrderTotal()">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch card-body py-2 border rounded w-100 mb-0">
                                    <input class="form-check-input" type="checkbox" id="newOrderVat" onchange="updateNewOrderTotal()">
                                    <label class="form-check-label fw-bold" for="newOrderVat">
                                        <i data-lucide="file-text" class="me-1" style="width:14px;height:14px;"></i> คิด VAT 7%
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">หมายเหตุ (พิมพ์ในบิล)</label>
                                <input type="text" id="newOrderNotes" class="form-control" 
                                       placeholder="ข้อความถึงลูกค้า เช่น ขอบคุณที่อุดหนุน...">
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="modal-footer bg-light">
                <div class="me-auto">
                    <div class="d-flex align-items-center">
                        <span class="me-2">ยอดรวม:</span>
                        <h4 class="mb-0 text-success fw-bold" id="newOrderGrandTotal">0.00 บาท</h4>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button onclick="submitNewOrder()" class="btn btn-success" id="submitOrderBtn">
                    <i data-lucide="check" class="me-2"></i>สร้างออเดอร์
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Product Grid Modal -->
<div class="modal fade" id="productGridModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i data-lucide="package" class="me-2"></i>เลือกสินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <!-- Category Tabs -->
                <ul class="nav nav-tabs mb-3" id="categoryTabs" role="tablist">
                    <!-- Tabs will be loaded here -->
                </ul>
                
                <!-- Product Grid Content -->
                <div class="tab-content" id="productGridContent">
                    <!-- Product cards will be loaded here -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center bg-light">
                <div class="fw-bold text-primary">
                    <i data-lucide="shopping-cart" class="me-1"></i> สินค้าในออเดอร์: 
                    <span id="productGridCartSummaryCount" class="badge bg-danger rounded-pill fs-6">0</span>
                </div>
                <button type="button" class="btn btn-success px-4 fw-bold" data-bs-dismiss="modal">
                    <i data-lucide="check-circle" class="me-1"></i> เสร็จสิ้น
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Change Status Modal -->
<div class="modal fade" id="statusChangeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0 justify-content-center">
                <h5 class="modal-title fw-bold text-success text-center w-100" id="statusModalTitle">เปลี่ยนสถานะ #</h5>
                <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4">
                <form id="statusChangeForm">
                    <input type="hidden" id="statusOrderId" name="order_id">
                    
                    <div class="row mb-3">
                        <div class="col-3 text-end fw-bold pt-1" style="font-size: 0.85rem;">สถานะ:</div>
                        <div class="col-9" id="statusRadioContainer" style="font-size: 0.85rem;">
                            <div class="text-center text-muted py-2"><div class="spinner-border spinner-border-sm"></div> กำลังโหลด...</div>
                        </div>
                    </div>
                    
                    <hr class="border-dark opacity-10 my-3">
                    
                    <div id="customerSmsSection" class="mb-3 text-center" style="font-size: 0.8rem;">
                        <!-- SMS Checkbox will be populated here -->
                    </div>
                    
                    <div class="d-flex justify-content-center gap-2 mt-4 mb-2">
                        <button type="button" class="btn btn-primary px-4 fw-bold" style="background-color: #6610f2; border-color: #6610f2;" onclick="saveStatusChange()">บันทึก</button>
                        <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Define global variables
const ADMIN_URL = '<?= ADMIN_URL ?>';
const ROOT_URL = '<?= ROOT_URL ?>';
const ADMIN_ASSETS = '<?= ADMIN_ASSETS ?>';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Parse URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    let initialSearch = urlParams.get('search') || '';
    let initialStatus = urlParams.get('status');
    
    if (initialSearch) {
        document.getElementById('searchInput').value = initialSearch;
        document.getElementById('clearSearch').classList.remove('d-none');
        document.getElementById('clearSearch').classList.add('d-flex');
        if (typeof currentSearchQuery !== 'undefined') {
            currentSearchQuery = initialSearch;
        }
    }
    
    if (initialStatus === 'all') {
        if (typeof currentStatusFilter !== 'undefined') currentStatusFilter = 0;
    } else if (initialStatus !== null) {
        if (typeof currentStatusFilter !== 'undefined') currentStatusFilter = parseInt(initialStatus) || 1;
    }

    // Load initial table
    if (typeof loadOrderTable === 'function') {
        loadOrderTable(1, initialSearch);
    } else {
        console.error('loadOrderTable is not defined');
    }
    
    // Setup search with debounce
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        const value = this.value.trim();
        const clearBtn = document.getElementById('clearSearch');
        
        // Show/hide clear button
        if (value) {
            clearBtn.classList.remove('d-none');
            clearBtn.classList.add('d-flex');
        } else {
            clearBtn.classList.add('d-none');
            clearBtn.classList.remove('d-flex');
        }
        
        // Debounce search
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadOrderTable(1, value);
        }, 300);
    });
});

function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';
    const clearBtn = document.getElementById('clearSearch');
    if (clearBtn) {
        clearBtn.classList.remove('d-flex');
        clearBtn.classList.add('d-none');
    }
    currentUserIdFilter = 0; // Clear customer filter
    if (typeof currentSearchQuery !== 'undefined') currentSearchQuery = '';
    loadOrderTable(1, '');
}
</script>

<!-- Load external JS file -->
<script src="<?= ADMIN_ASSETS ?>/js/orders.js?v=<?= time() ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
