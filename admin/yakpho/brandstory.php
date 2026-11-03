<?php
$page_title = 'จัดการเรื่องราวแบรนด์ (Brand Story)';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Available languages
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // 1. Handle image upload (if any)
        $image_name = $_POST['existing_image'] ?? '';
        
        if (!empty($_POST['cropped_image_data'])) {
            $upload_dir = __DIR__ . '/../../uploads/yakpho/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $base64_string = $_POST['cropped_image_data'];
            $base64_string = str_replace('data:image/webp;base64,', '', $base64_string);
            $base64_string = str_replace(' ', '+', $base64_string);
            $image_data = base64_decode($base64_string);
            
            $image_name = 'brandstory_' . time() . '.webp';
            file_put_contents($upload_dir . $image_name, $image_data);

            // Update main table
            $stmtUpdImage = $db->prepare("UPDATE yakpho_brandstory_intro SET image_file = ?, updated_at = NOW() WHERE intro_id = 1");
            $stmtUpdImage->execute([$image_name]);
        } elseif (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/yakpho/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            $image_name = 'brandstory_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $image_name);

            // Update main table
            $stmtUpdImage = $db->prepare("UPDATE yakpho_brandstory_intro SET image_file = ?, updated_at = NOW() WHERE intro_id = 1");
            $stmtUpdImage->execute([$image_name]);
        }

        // 2. Handle translations
        $translations = $_POST['translations'] ?? [];
        foreach ($translations as $lang_code => $trans) {
            $title = trim($trans['intro_title']);
            $subtitle = trim($trans['intro_subtitle']);
            $html_content = trim($trans['html_content']);

            // Check if translation exists
            $stmtCheck = $db->prepare("SELECT translation_id FROM yakpho_brandstory_intro_translations WHERE intro_id = 1 AND lang_code = ?");
            $stmtCheck->execute([$lang_code]);
            $transExists = $stmtCheck->fetchColumn();

            if ($transExists) {
                $stmtUpd = $db->prepare("UPDATE yakpho_brandstory_intro_translations SET intro_title = ?, intro_subtitle = ?, html_content = ? WHERE translation_id = ?");
                $stmtUpd->execute([$title, $subtitle, $html_content, $transExists]);
            } else {
                $stmtIns = $db->prepare("INSERT INTO yakpho_brandstory_intro_translations (intro_id, lang_code, intro_title, intro_subtitle, html_content) VALUES (1, ?, ?, ?, ?)");
                $stmtIns->execute([$lang_code, $title, $subtitle, $html_content]);
            }
        }

        $db->commit();
        $_SESSION['flash_msg'] = "บันทึกข้อมูลเรียบร้อยแล้ว";
        header("Location: brandstory.php");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch current main data
$stmtMain = $db->prepare("SELECT * FROM yakpho_brandstory_intro WHERE intro_id = 1");
$stmtMain->execute();
$main_data = $stmtMain->fetch(PDO::FETCH_ASSOC);

// Fetch translations
$stmtTrans = $db->prepare("SELECT * FROM yakpho_brandstory_intro_translations WHERE intro_id = 1");
$stmtTrans->execute();
$current_trans = [];
while ($row = $stmtTrans->fetch(PDO::FETCH_ASSOC)) {
    $current_trans[$row['lang_code']] = $row;
}

ob_start();
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1><i data-lucide="book-open"></i> จัดการเรื่องราวแบรนด์ (Brand Story)</h1>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data">
    <div class="row">
        <!-- Left Column: Main Info (Image) -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">ข้อมูลรูปภาพ</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">รูปภาพประกอบ (รองรับ Crop และแปลงเป็น .webp อัตโนมัติ)</label>
                        <input type="file" id="imageInput" name="image_file" class="form-control" accept="image/*">
                        <input type="hidden" name="cropped_image_data" id="croppedImageData">
                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($main_data['image_file'] ?? '') ?>">
                        
                        <div class="mt-3 text-center">
                            <?php 
                                $preview_url = '';
                                if (!empty($main_data['image_file'])) {
                                    if ($main_data['image_file'] === 'brand_story.webp') {
                                        $preview_url = BASE_URL . '/catalog/assets/img/brand_story.webp';
                                    } elseif (file_exists(__DIR__ . '/../../uploads/yakpho/' . $main_data['image_file'])) {
                                        $preview_url = BASE_URL . '/uploads/yakpho/' . $main_data['image_file'];
                                    } else {
                                        $preview_url = BASE_URL . '/catalog/assets/img/brand_story.webp';
                                    }
                                }
                            ?>
                            <img id="imagePreview" src="<?= $preview_url ?>" class="img-thumbnail" style="max-width: 100%; <?= empty($preview_url) ? 'display:none;' : '' ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Translations -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">แก้ไขเนื้อหาแบบหลายภาษา</h5>
                </div>
                <div class="card-body">
                    
                    <!-- Language Dropdown -->
                    <div class="language-dropdown-container mb-3" data-content-selector="#langTabsContent"></div>

                    <div id="langTabsContent">
                        <?php foreach ($languages as $code => $name): 
                            $tData = $current_trans[$code] ?? [];
                            $isRTL = in_array($code, ['ar', 'he']);
                            $dirAttr = $isRTL ? 'dir="rtl"' : '';
                        ?>
                            <div data-lang="<?= $code ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">หัวข้อหลัก (Title) - <?= $name ?> <?= $code === 'th' ? '<span class="text-danger">*</span>' : '' ?></label>
                                    <input type="text" name="translations[<?= $code ?>][intro_title]" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($tData['intro_title'] ?? '') ?>" 
                                           <?= $code === 'th' ? 'required' : '' ?> <?= $dirAttr ?>>
                                    <small class="text-muted">เช่น: Brand Story</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">คำโปรย (Subtitle) - <?= $name ?></label>
                                    <input type="text" name="translations[<?= $code ?>][intro_subtitle]" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($tData['intro_subtitle'] ?? '') ?>" <?= $dirAttr ?>>
                                    <small class="text-muted">เช่น: Touch of Thai Wellness</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">เนื้อหา (HTML Content) - <?= $name ?></label>
                                    <!-- ใช้ class summernote ตามที่ผู้ใช้ระบุ เพื่อให้แปลงเป็น Summernote Editor -->
                                    <textarea name="translations[<?= $code ?>][html_content]" 
                                              class="form-control summernote" 
                                              rows="10" <?= $dirAttr ?>><?= htmlspecialchars($tData['html_content'] ?? '') ?></textarea>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                </div>
                <div class="card-footer text-end bg-transparent border-top-0 pt-0 pb-3">
                    <button type="submit" class="btn btn-primary px-4">
                        <i data-lucide="save"></i> บันทึกข้อมูล
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Cropper Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cropModalLabel">ครอบตัดรูปภาพ (Crop Image)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <div style="max-height: 500px; overflow: hidden;">
            <img id="imageToCrop" src="" style="max-width: 100%;">
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="window.setCropperRatio(16/9)">16:9 (แนวนอน)</button>
            <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="window.setCropperRatio(4/3)">4:3</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.setCropperRatio(1/1)">1:1</button>
        </div>
        <div>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-primary" id="cropButton">ครอบตัด & นำไปใช้</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Load Language Dropdown JS -->
<script src="<?= ADMIN_URL ?>/assets/js/language-dropdown.js"></script>

<?php
$extra_scripts = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    let cropper;
    const imageInput = document.getElementById('imageInput');
    const imageToCrop = document.getElementById('imageToCrop');
    const cropModalElement = document.getElementById('cropModal');
    const cropModal = new bootstrap.Modal(cropModalElement);

    imageInput.addEventListener('change', function (e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const file = files[0];
            const reader = new FileReader();
            reader.onload = function (event) {
                imageToCrop.src = event.target.result;
                cropModal.show();
            };
            reader.readAsDataURL(file);
        }
    });

    cropModalElement.addEventListener('shown.bs.modal', function () {
        cropper = new Cropper(imageToCrop, {
            aspectRatio: NaN, // Free crop
            viewMode: 1,
            autoCropArea: 1,
        });
    });

    cropModalElement.addEventListener('hidden.bs.modal', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        if (!document.getElementById('croppedImageData').value) {
            imageInput.value = '';
        }
    });

    // Make functions global for the onclick handlers in HTML
    window.setCropperRatio = function(ratio) {
        if (cropper) cropper.setAspectRatio(ratio);
    };

    document.getElementById('cropButton').addEventListener('click', function () {
        if (!cropper) return;
        
        const canvas = cropper.getCroppedCanvas({
            width: 1200,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });
        
        const webpBase64 = canvas.toDataURL('image/webp', 0.85);
        document.getElementById('croppedImageData').value = webpBase64;
        
        const preview = document.getElementById('imagePreview');
        preview.src = webpBase64;
        preview.style.display = 'block';
        
        cropModal.hide();
    });
});

$(document).ready(function() {
    // Initialize Summernote
    $('.summernote').summernote({
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
});
</script>
";

$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php';
?>
