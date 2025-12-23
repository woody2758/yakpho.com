<?php
/**
 * Hero Slider Management
 * Admin interface for managing homepage hero slides
 */

session_start();
require_once __DIR__ . '/../../includes/config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

$page_title = 'Hero Slider Management';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
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
                    
                    <div class="row">
                        <!-- Left Column: Image & Settings -->
                        <div class="col-md-5">
                            <!-- Image Upload -->
                            <div class="mb-4">
                                <label class="form-label">รูปภาพ Hero (แนะนำ 1920x600px)</label>
                                <div class="image-upload-area" id="imageUploadArea">
                                    <img id="imagePreview" src="" alt="Preview" style="display:none; max-width:100%; border-radius:8px;">
                                    <div id="uploadPlaceholder" class="text-center p-5" style="border: 2px dashed #ddd; border-radius:8px;">
                                        <i data-lucide="upload" width="48" height="48" style="color:#999;"></i>
                                        <p class="mt-3">คลิกเพื่ออัปโหลดรูปภาพ</p>
                                        <p class="text-muted small">รองรับ JPG, PNG (สูงสุด 2MB)</p>
                                    </div>
                                    <input type="file" id="slide_image" name="slide_image" accept="image/*" style="display:none;">
                                </div>
                                <input type="hidden" id="existing_image" name="existing_image">
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
                            <!-- Language Tabs -->
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#lang-th">🇹🇭 ไทย</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-en">🇬🇧 English</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-zh">🇨🇳 中文</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-others">อื่นๆ...</button>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- Thai -->
                                <div class="tab-pane fade show active" id="lang-th">
                                    <?php include 'hero_lang_form.php'; ?>
                                </div>
                                <!-- English -->
                                <div class="tab-pane fade" id="lang-en">
                                    <?php $lang = 'en'; include 'hero_lang_form.php'; ?>
                                </div>
                                <!-- Chinese -->
                                <div class="tab-pane fade" id="lang-zh">
                                    <?php $lang = 'zh'; include 'hero_lang_form.php'; ?>
                                </div>
                                <!-- Others -->
                                <div class="tab-pane fade" id="lang-others">
                                    <div class="row">
                                        <?php 
                                        $other_langs = ['de', 'fr', 'ja', 'ko', 'ru', 'ar', 'he'];
                                        foreach ($other_langs as $lang): 
                                        ?>
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-muted"><?= strtoupper($lang) ?></h6>
                                            <?php include 'hero_lang_form.php'; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
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
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

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
