<?php
$page_title = "จัดการกลิ่นและสูตร";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

ob_start();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1 fw-bold text-dark">จัดการข้อมูลหลัก</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="../dashboard/" class="text-decoration-none text-primary">Dashboard</a></li>
                    <li class="breadcrumb-item active">จัดการกลิ่นและสูตร</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-4">
        <!-- 1. Manage Scents -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-subtle p-2 rounded-3 me-3">
                            <i data-lucide="wind" class="text-primary" style="width: 20px; height: 20px;"></i>
                        </div>
                        <h2 class="h6 mb-0 fw-bold">จัดการกลิ่น (Scents)</h2>
                    </div>
                    <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModal('scent')">
                        <i data-lucide="plus" class="me-1" style="width: 14px; height: 14px;"></i> เพิ่มกลิ่น
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="scentTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="ps-4"></th>
                                    <th style="width: 60px;">ID</th>
                                    <th>ชื่อกลิ่น (ไทย)</th>
                                    <th>ชื่อกลิ่น (EN)</th>
                                    <th>Code</th>
                                    <th style="width: 120px;" class="text-center pe-4">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center py-5"><div class="spinner-border spinner-border-sm text-primary"></div> กำลังโหลด...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Manage Formulas -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-success-subtle p-2 rounded-3 me-3">
                            <i data-lucide="flask-conical" class="text-success" style="width: 20px; height: 20px;"></i>
                        </div>
                        <h2 class="h6 mb-0 fw-bold">จัดการสูตร (Formulas)</h2>
                    </div>
                    <button class="btn btn-success btn-sm rounded-pill px-3" onclick="openModal('formula')">
                        <i data-lucide="plus" class="me-1" style="width: 14px; height: 14px;"></i> เพิ่มสูตร
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="formulaTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="ps-4"></th>
                                    <th>ชื่อสูตร (ไทย)</th>
                                    <th style="width: 100px;" class="text-center pe-4">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="2" class="text-center py-5"><div class="spinner-border spinner-border-sm text-success"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Fetch all active languages
$stmt = $db->query("SELECT * FROM languages WHERE lang_status = 1 ORDER BY lang_order ASC");
$activeLanguages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Modal for Add/Edit -->
<div class="modal fade" id="attributeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">แก้ไขข้อมูล</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="attributeForm">
                <div class="modal-body py-4">
                    <input type="hidden" id="attrId" name="attribute_id">
                    <input type="hidden" id="groupCode" name="group_code">
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">รหัสอ้างอิง (Code)</label>
                            <input type="text" class="form-control rounded-3" id="attrCode" name="attribute_code" placeholder="เช่น citronella, hot">
                        </div>
                    </div>

                    <!-- Language Tabs -->
                    <div class="card border-0 bg-light rounded-4">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <ul class="nav nav-pills nav-fill" id="langTabs" role="tablist">
                                <?php foreach ($activeLanguages as $index => $lang): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link rounded-pill <?= $index === 0 ? 'active' : '' ?> d-flex align-items-center justify-content-center" 
                                                id="tab-<?= $lang['lang_code'] ?>" 
                                                data-bs-toggle="pill" 
                                                data-bs-target="#pane-<?= $lang['lang_code'] ?>" 
                                                type="button" role="tab">
                                            <img src="<?= ROOT_URL ?>/assets/images/flags/<?= $lang['lang_code'] ?>.png" width="16" class="me-2">
                                            <?= htmlspecialchars($lang['lang_name']) ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="langTabContent">
                                <?php foreach ($activeLanguages as $index => $lang): ?>
                                    <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" 
                                         id="pane-<?= $lang['lang_code'] ?>" role="tabpanel">
                                        <div class="mb-0">
                                            <label class="form-label fw-semibold">ชื่อกลิ่น/สูตร (<?= $lang['lang_name'] ?>)</label>
                                            <input type="text" class="form-control rounded-3" 
                                                   name="names[<?= $lang['lang_code'] ?>]" 
                                                   id="name-<?= $lang['lang_code'] ?>" 
                                                   placeholder="กรอกชื่อในภาษา <?= $lang['lang_name'] ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="saveBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                        <span class="btn-text">บันทึกข้อมูลทั้งหมด</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.cursor-move { cursor: move; }
.cursor-move:hover { color: var(--bs-primary) !important; }
#langTabs .nav-link { font-size: 0.85rem; padding: 0.4rem 1rem; color: #888 !important; }
#langTabs .nav-link.active { background-color: #3498db !important; color: white !important; }

/* บังคับ Dark Mode ให้เข้มแน่นอน ตามหน้า order_status.php */
[data-theme="dark"] .modal-content {
    background-color: #1a1a1a !important;
    color: #f0f0f0 !important;
    border: 1px solid #333 !important;
}
[data-theme="dark"] .modal-content .card.bg-light {
    background-color: #262626 !important;
    border-color: #333 !important;
    color: #f0f0f0 !important;
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
[data-theme="dark"] .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}
[data-theme="dark"] .modal-title {
    color: #3498db !important;
}
</style>

<script src="../assets/js/master_attributes.js"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
