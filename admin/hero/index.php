<?php
/**
 * Hero Slider Management
 * Admin interface for managing homepage hero slides
 */

$page_title = 'Hero Slider Management';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

ob_start();
?>

<!-- Page Header -->
<div class="page-header">
            <h1><i data-lucide="image"></i> จัดการ Hero Slider</h1>
            <button class="btn btn-primary" onclick="Hero.openModal()">
                <i data-lucide="plus"></i> เพิ่ม Slide ใหม่
            </button>
        </div>

        <!-- Hero Slides Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="hero-table">
                        <thead>
                            <tr>
                                <th width="50">ลำดับ</th>
                                <th width="120">รูปภาพ</th>
                                <th>หัวข้อ (TH)</th>
                                <th>คำบรรยาย</th>
                                <th width="100">สถานะ</th>
                                <th width="150">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="hero-tbody">
                            <!-- Data loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hero Slide Modal -->
<div class="modal fade" id="heroModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="heroModalLabel">เพิ่ม Hero Slide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="heroForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="slide_id" name="slide_id">
                    <input type="hidden" id="heroImageBase64" name="hero_image_base64">
                    
                    <div class="row">
                        <!-- Left Column: Image & Settings -->
                        <div class="col-md-5">
                            <!-- Image Upload -->
                            <div class="mb-4">
                                <label class="form-label">รูปภาพ Hero (1920x1080px - 16:9)</label>
                                <div class="image-upload-area" id="imageUploadArea" style="cursor: pointer; border: 2px dashed #ddd; border-radius: 8px; padding: 20px; text-align: center; position: relative;">
                                    <img id="imagePreview" src="" alt="Preview" style="display:none; max-width:100%; max-height:300px; border-radius:8px;">
                                    <div id="uploadPlaceholder">
                                        <i data-lucide="upload" width="48" height="48" style="color:#999;"></i>
                                        <p class="mt-3 mb-1">คลิกเพื่ออัปโหลดรูปภาพ</p>
                                        <p class="text-muted small">รองรับ JPG, PNG, WEBP (สูงสุด 5MB)</p>
                                        <p class="text-muted small"><strong>สัดส่วน: 16:9 (1920x1080px)</strong></p>
                                    </div>
                                    <input type="file" id="slide_image" name="slide_image" accept="image/*" style="display:none;" onchange="handleHeroImageSelect(event)">
                                </div>
                                
                                <!-- Cropper Container -->
                                <div id="heroCropperContainer" style="display: none;" class="mt-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <p class="mb-2"><strong>ปรับขนาดรูปภาพ (16:9)</strong></p>
                                            <div style="max-height: 400px; overflow: hidden;">
                                                <img id="heroImageToCrop" style="max-width: 100%;">
                                            </div>
                                            <div class="mt-3 d-flex gap-2">
                                                <button type="button" class="btn btn-primary btn-sm" onclick="cropHeroImage()">
                                                    <i data-lucide="crop" width="16" height="16"></i> ครอปรูป
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="useOriginalHeroImage()">
                                                    <i data-lucide="maximize" width="16" height="16"></i> ใช้รูปเต็ม
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cancelHeroCrop()">
                                                    <i data-lucide="x" width="16" height="16"></i> ยกเลิก
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <input type="hidden" id="existing_image" name="existing_image">
                                
                                <!-- Remove Button -->
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeHeroCroppedImage()" style="display:none;" id="removeImageBtn">
                                    <i data-lucide="trash-2" width="14" height="14"></i> ลบรูป
                                </button>
                            </div>

                            <!-- Background Color -->
                            <div class="mb-3">
                                <label class="form-label">สีพื้นหลัง</label>
                                <input type="color" class="form-control" id="slide_bg_color" name="slide_bg_color" value="#0A2F2A">
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <label class="form-label">สถานะ</label>
                                <select class="form-select" id="slide_status" name="slide_status">
                                    <option value="active">เปิดใช้งาน</option>
                                    <option value="inactive">ปิดใช้งาน</option>
                                </select>
                            </div>
                        </div>

                        <!-- Right Column: Multi-language Content -->
                        <div class="col-md-7">
                            <!-- Language Dropdown -->
                            <div class="language-dropdown-container mb-3" data-content-selector="#heroLangContent"></div>
                            
                            <!-- Language Content -->
                            <div id="heroLangContent">
                                <?php
                                $languages = [
                                    'th' => 'Thai',
                                    'en' => 'English',
                                    'de' => 'German',
                                    'fr' => 'French',
                                    'zh' => 'Chinese',
                                    'ko' => 'Korean',
                                    'ja' => 'Japanese',
                                    'ru' => 'Russian',
                                    'ar' => 'Arabic',
                                    'he' => 'Hebrew'
                                ];
                                
                                foreach ($languages as $code => $name):
                                    $isRTL = in_array($code, ['ar', 'he']);
                                    $dirAttr = $isRTL ? 'dir="rtl"' : '';
                                ?>
                                <div data-lang="<?= $code ?>">
                                    <div class="mb-3">
                                        <label class="form-label">หัวข้อ<?= $isRTL ? " ($name)" : '' ?></label>
                                        <input type="text" class="form-control" 
                                               name="slide_title_<?= $code ?>" 
                                               id="slide_title_<?= $code ?>" 
                                               <?= $dirAttr ?>
                                               placeholder="Biblical Wellness from Ancient Soil...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">คำบรรยาย<?= $isRTL ? " ($name)" : '' ?></label>
                                        <textarea class="form-control" 
                                                  name="slide_subtitle_<?= $code ?>" 
                                                  id="slide_subtitle_<?= $code ?>" 
                                                  <?= $dirAttr ?>
                                                  rows="3" 
                                                  placeholder="ผลิตภัณฑ์สุขภาพและความงาม..."></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">ปุ่ม 1 - ข้อความ<?= $isRTL ? " ($name)" : '' ?></label>
                                            <input type="text" class="form-control" 
                                                   name="button1_text_<?= $code ?>" 
                                                   id="button1_text_<?= $code ?>" 
                                                   <?= $dirAttr ?>
                                                   placeholder="เลือกซื้อผลิตภัณฑ์">
                                            <?php if ($code === 'th'): ?>
                                            <small class="text-muted">ลิงก์ตั้งค่าที่ด้านซ้าย</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">ปุ่ม 2 - ข้อความ<?= $isRTL ? " ($name)" : '' ?></label>
                                            <input type="text" class="form-control" 
                                                   name="button2_text_<?= $code ?>" 
                                                   id="button2_text_<?= $code ?>" 
                                                   <?= $dirAttr ?>
                                                   placeholder="เรียนรู้เพิ่มเติม">
                                            <?php if ($code === 'th'): ?>
                                            <small class="text-muted">ลิงก์ตั้งค่าที่ด้านซ้าย</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i> บันทึก
                    </button>
        </div>
    </div>
</div>

<!-- Language Dropdown Component -->
<script src="<?= ADMIN_URL ?>/assets/js/language-dropdown.js"></script>

<!-- Hero Image Cropping Functions -->
<script src="<?= ADMIN_URL ?>/assets/js/hero-image-functions.js"></script>

<script src="<?= ADMIN_URL ?>/assets/js/hero.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    // Initialize Sortable for drag-and-drop
    new Sortable(document.getElementById('hero-tbody'), {
        animation: 150,
        onEnd: function(evt) {
            Hero.saveOrder();
        }
    });
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
