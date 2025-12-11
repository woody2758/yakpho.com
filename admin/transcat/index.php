<?php
$page_title = "จัดการการจัดส่ง";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="truck" class="me-2"></i>จัดการการจัดส่ง</h2>
        <button class="btn btn-primary" onclick="openModal()">
            <i data-lucide="plus" class="me-2"></i>เพิ่มการจัดส่ง
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาการจัดส่ง...">
                </div>
            </div>
            <div id="tableContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="transcatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มการจัดส่ง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="transcatForm">
                    <input type="hidden" id="transcat_id" name="transcat_id">
                    
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mb-3" id="langTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="th-tab" data-bs-toggle="tab" data-bs-target="#th" type="button" role="tab">
                                <img src="<?= ADMIN_ASSETS ?>/images/th.png" alt="TH" width="20" class="me-1"> ภาษาไทย
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="en-tab" data-bs-toggle="tab" data-bs-target="#en" type="button" role="tab">
                                <img src="<?= ADMIN_ASSETS ?>/images/en.png" alt="EN" width="20" class="me-1"> English
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mb-3" id="langTabContent">
                        <!-- Thai -->
                        <div class="tab-pane fade show active" id="th" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">ชื่อการจัดส่ง <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="translations[th][transcat_name]" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ชื่อย่อ</label>
                                <input type="text" class="form-control" name="translations[th][transcat_nshort]">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">รายละเอียด</label>
                                <textarea class="form-control" name="translations[th][transcat_detail]" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <!-- English -->
                        <div class="tab-pane fade" id="en" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Shipping Name</label>
                                <input type="text" class="form-control" name="translations[en][transcat_name]">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Short Name</label>
                                <input type="text" class="form-control" name="translations[en][transcat_nshort]">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Detail</label>
                                <textarea class="form-control" name="translations[en][transcat_detail]" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ลำดับการแสดงผล</label>
                            <input type="number" class="form-control" name="transcat_index" value="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Link ตรวจสอบพัสดุ</label>
                            <input type="url" class="form-control" name="transcat_link" placeholder="https://...">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="transcat_cod" id="transcat_cod" value="1">
                                <label class="form-check-label" for="transcat_cod">รองรับเก็บเงินปลายทาง (COD)</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="transcat_status" id="transcat_status" value="1" checked>
                                <label class="form-check-label" for="transcat_status">เปิดใช้งาน</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveTranscat()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<script>
const ADMIN_URL = '<?= ADMIN_URL ?>';
</script>
<script src="<?= ADMIN_ASSETS ?>/js/language-tabs.js"></script>
<script src="<?= ADMIN_ASSETS ?>/js/transcat.js"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
