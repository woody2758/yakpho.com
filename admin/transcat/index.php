<?php
$page_title = "บริหารขนส่ง";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<div class="container-fluid p-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold"><i data-lucide="truck" class="me-2"></i>บริหารขนส่ง</h2>
            <p class="text-muted small mb-0 mt-1">จัดการบริษัทขนส่ง ลิงค์ติดตามพัสดุ และการรองรับ COD</p>
        </div>
        <button class="btn btn-primary px-4 fw-bold shadow-sm" onclick="openModal()">
            <i data-lucide="plus" class="me-1" style="width:16px;height:16px;"></i>เพิ่มขนส่ง
        </button>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="d-flex align-items-start gap-3">
            <i data-lucide="info" class="text-info mt-1" style="width:20px;height:20px;flex-shrink:0;"></i>
            <div>
                <strong>วิธีตั้งค่าลิงค์ติดตามพัสดุ:</strong>
                ใส่ URL ของหน้าติดตาม แล้วใช้ <code>xxx</code> เป็น placeholder สำหรับเลขพัสดุ<br>
                <span class="text-muted small">ตัวอย่าง: <code>https://th.kerryexpress.com/th/track/?track=xxx</code>
                หรือ <code>https://track.thailandpost.co.th/</code> (ไม่ต้องใส่ xxx ถ้าลิงค์ไม่รองรับ)</span>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow:hidden;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i data-lucide="search" style="width:16px;height:16px;"></i></span>
                        <input type="search" id="searchInput" onsearch="if(this.value==='') { if(typeof performSearch === 'function') performSearch(); else if(this.form) this.form.submit(); else window.location.search=''; }" class="form-control border-start-0 bg-light" placeholder="ค้นหาขนส่ง...">
                    </div>
                </div>
                <div class="col-md-8 text-end">
                    <span class="text-muted small" id="tableCount"></span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="tableContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">กำลังโหลด...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="transcatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold" id="modalTitle">
                    <i data-lucide="truck" class="me-2" style="width:20px;height:20px;"></i>เพิ่มการจัดส่ง
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <form id="transcatForm">
                    <input type="hidden" id="transcat_id" name="transcat_id">
                    
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mb-4" id="langTabs" role="tablist">
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

                    <div class="tab-content mb-4" id="langTabContent">
                        <!-- Thai -->
                        <div class="tab-pane fade show active" id="th" role="tabpanel">
                            <div class="row">
                                <div class="col-md-7 mb-3">
                                    <label class="form-label fw-bold">ชื่อการจัดส่ง <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="translations[th][transcat_name]" required placeholder="เช่น Kerry Express, ไปรษณีย์ไทย">
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label fw-bold">ชื่อย่อ</label>
                                    <input type="text" class="form-control" name="translations[th][transcat_nshort]" placeholder="เช่น Kerry, EMS">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">รายละเอียด</label>
                                <textarea class="form-control" name="translations[th][transcat_detail]" rows="2" placeholder="รายละเอียดเพิ่มเติม (ไม่บังคับ)"></textarea>
                            </div>
                        </div>
                        
                        <!-- English -->
                        <div class="tab-pane fade" id="en" role="tabpanel">
                            <div class="row">
                                <div class="col-md-7 mb-3">
                                    <label class="form-label fw-bold">Shipping Name</label>
                                    <input type="text" class="form-control" name="translations[en][transcat_name]" placeholder="e.g. Kerry Express, Thailand Post">
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label fw-bold">Short Name</label>
                                    <input type="text" class="form-control" name="translations[en][transcat_nshort]" placeholder="e.g. Kerry, EMS">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Detail</label>
                                <textarea class="form-control" name="translations[en][transcat_detail]" rows="2" placeholder="Optional description"></textarea>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Tracking Link -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i data-lucide="link" class="me-1" style="width:15px;height:15px;"></i>
                            Link ตรวจสอบพัสดุ
                        </label>
                        <input type="text" class="form-control" name="transcat_link" id="transcat_link_input"
                               placeholder="https://th.kerryexpress.com/th/track/?track=xxx"
                               oninput="previewTrackingLink()">
                        <div class="form-text">
                            ใช้ <code>xxx</code> แทนเลขพัสดุ เช่น <code>https://th.kerryexpress.com/th/track/?track=xxx</code><br>
                            ถ้าไม่มี placeholder เลขพัสดุจะถูกต่อท้าย URL โดยอัตโนมัติ
                        </div>
                        <div id="trackingPreview" class="mt-2 p-2 rounded bg-light small d-none">
                            <strong>ตัวอย่าง URL:</strong> <a id="trackingPreviewLink" href="#" target="_blank" class="text-success"></a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">ลำดับการแสดงผล</label>
                            <input type="number" class="form-control" name="transcat_index" value="1" min="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold d-block">รองรับ COD</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="transcat_cod" id="transcat_cod" value="1">
                                <label class="form-check-label" for="transcat_cod">เก็บเงินปลายทาง (COD)</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold d-block">สถานะ</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="transcat_status" id="transcat_status" value="1" checked>
                                <label class="form-check-label" for="transcat_status">เปิดใช้งาน</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary px-4 fw-bold" onclick="saveTranscat()">
                    <i data-lucide="save" class="me-1" style="width:16px;height:16px;"></i>บันทึก
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const ADMIN_URL = '<?= ADMIN_URL ?>';
const ADMIN_ASSETS = '<?= ADMIN_ASSETS ?>';

function previewTrackingLink() {
    const linkInput = document.getElementById('transcat_link_input');
    const preview = document.getElementById('trackingPreview');
    const previewLink = document.getElementById('trackingPreviewLink');
    const val = linkInput.value.trim();

    if (val) {
        const sampleTracking = 'TH123456789';
        let url = val.includes('xxx') ? val.replace('xxx', sampleTracking) : val + sampleTracking;
        previewLink.href = url;
        previewLink.textContent = url;
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }
}
</script>
<script src="<?= ADMIN_ASSETS ?>/js/language-tabs.js"></script>
<script src="<?= ADMIN_ASSETS ?>/js/transcat.js"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
