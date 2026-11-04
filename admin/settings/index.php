<?php
$page_title = "ตั้งค่าร้านค้า";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="settings" class="me-2"></i>ตั้งค่าร้านค้า</h2>
    </div>

    <form id="shopSettingsForm" enctype="multipart/form-data">
        <input type="hidden" name="shop_id" value="1">
        
        <div class="row g-4">
            <!-- Left Column: General Settings -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-gradient text-white py-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h5 class="mb-0 fw-bold"><i data-lucide="info" class="me-2" style="width:18px;"></i>ข้อมูลทั่วไป</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <label class="form-label d-block fw-bold mb-3">โลโก้ร้านค้า</label>
                            <div class="position-relative d-inline-block">
                                <img id="logoPreview" src="<?= ADMIN_ASSETS ?>/images/placeholder.png" 
                                     class="rounded-3 border shadow-sm mb-2" 
                                     style="width: 160px; height: 160px; object-fit: contain; background: #f8f9fa;">
                                <div class="position-absolute bottom-0 end-0 mb-2 me-2">
                                    <label for="shop_logo" class="btn btn-sm btn-primary rounded-circle shadow" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                        <i data-lucide="camera" style="width: 18px; height: 18px;"></i>
                                    </label>
                                    <input type="file" id="shop_logo" name="shop_logo" class="d-none" accept="image/*" onchange="previewLogo(this)">
                                </div>
                            </div>
                            <small class="text-muted d-block">คลิกที่ไอคอนกล้องเพื่ออัปโหลด</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i data-lucide="phone" class="me-1" style="width:14px;"></i>เบอร์โทรศัพท์</label>
                            <input type="text" class="form-control" name="shop_phone" id="shop_phone" placeholder="0xx-xxx-xxxx">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i data-lucide="mail" class="me-1" style="width:14px;"></i>อีเมล</label>
                            <input type="email" class="form-control" name="shop_email" id="shop_email" placeholder="email@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i data-lucide="hash" class="me-1" style="width:14px;"></i>เลขประจำตัวผู้เสียภาษี</label>
                            <input type="text" class="form-control" name="shop_tax_id" id="shop_tax_id" placeholder="x-xxxx-xxxxx-xx-x">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><i data-lucide="qr-code" class="me-1" style="width:14px;"></i>พร้อมเพย์ (PromptPay ID — เบอร์โทร / เลขบัตรประชาชน)</label>
                            <input type="text" class="form-control" name="promptpay_id" id="promptpay_id" placeholder="เช่น 0800617073 หรือเลขบัตร 13 หลัก">
                            <small class="text-muted" style="font-size: 0.78rem;">ใช้สร้าง QR Code พร้อมเพย์อัตโนมัติในหน้าชำระเงินของลูกค้า</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Multi-language Address -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i data-lucide="globe" class="me-2" style="width:20px;"></i>ที่อยู่ร้านค้าหลายภาษา</h5>
                            <!-- Language Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" id="languageDropdown" data-bs-toggle="dropdown">
                                    <img id="currentFlag" src="https://flagcdn.com/w20/th.png" class="me-2" style="width:20px;">
                                    <span id="currentLangName">ไทย</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" id="languageMenu" style="max-height: 400px; overflow-y: auto;">
                                    <!-- Will be populated by JS -->
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div id="langTabContent">
                            <!-- Tab panes will be generated by JS -->
                        </div>
                        
                        <!-- Submit Button moved to right column -->
                        <div class="text-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                <i data-lucide="save" class="me-2"></i>บันทึกการตั้งค่า
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const ADMIN_URL = '<?= ADMIN_URL ?>';
const ROOT_URL = '<?= ROOT_URL ?>';
</script>
<script src="<?= ADMIN_ASSETS ?>/js/shop_settings.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
