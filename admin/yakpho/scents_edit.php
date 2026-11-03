<?php
$page_title = 'เพิ่ม/แก้ไข กลิ่นยอดนิยม';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = null;
$translations = [];

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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_status = isset($_POST['item_status']) ? 1 : 0;
    
    // File upload (Support Cropped WebP Base64)
    $image_name = $_POST['existing_image'] ?? '';
    
    if (!empty($_POST['cropped_image_data'])) {
        $upload_dir = __DIR__ . '/../../uploads/yakpho/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $base64_string = $_POST['cropped_image_data'];
        $base64_string = str_replace('data:image/webp;base64,', '', $base64_string);
        $base64_string = str_replace(' ', '+', $base64_string);
        $image_data = base64_decode($base64_string);
        
        $image_name = 'scent_' . time() . '.webp';
        file_put_contents($upload_dir . $image_name, $image_data);
    } elseif (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../uploads/yakpho/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $image_name = 'scent_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $image_name);
    }
    
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE yakpho_scent_items SET image_file = ?, item_status = ? WHERE item_id = ?");
        $stmt->execute([$image_name, $item_status, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO yakpho_scent_items (image_file, item_status) VALUES (?, ?)");
        $stmt->execute([$image_name, $item_status]);
        $id = $db->lastInsertId();
    }
    
    // Save translations
    foreach ($languages as $code => $name) {
        $title = $_POST['title_'.$code] ?? '';
        $subtitle = $_POST['subtitle_'.$code] ?? '';
        
        $stmt = $db->prepare("SELECT translation_id FROM yakpho_scent_translations WHERE item_id = ? AND lang_code = ?");
        $stmt->execute([$id, $code]);
        if ($stmt->fetch()) {
            $update = $db->prepare("UPDATE yakpho_scent_translations SET title=?, subtitle=? WHERE item_id=? AND lang_code=?");
            $update->execute([$title, $subtitle, $id, $code]);
        } else {
            $insert = $db->prepare("INSERT INTO yakpho_scent_translations (item_id, lang_code, title, subtitle) VALUES (?, ?, ?, ?)");
            $insert->execute([$id, $code, $title, $subtitle]);
        }
    }
    
    $_SESSION['flash_msg'] = "บันทึกข้อมูลสำเร็จ";
    header("Location: scents.php");
    exit;
}

// Fetch existing data
if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM yakpho_scent_items WHERE item_id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt_tr = $db->prepare("SELECT * FROM yakpho_scent_translations WHERE item_id = ?");
    $stmt_tr->execute([$id]);
    while ($row = $stmt_tr->fetch(PDO::FETCH_ASSOC)) {
        $translations[$row['lang_code']] = $row;
    }
}

ob_start();
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1><i data-lucide="edit"></i> <?= $id > 0 ? 'แก้ไข' : 'เพิ่ม' ?> กลิ่นยอดนิยม</h1>
    <a href="scents.php" class="btn btn-outline-secondary">
        <i data-lucide="arrow-left"></i> กลับ
    </a>
</div>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">ข้อมูลหลัก</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">รูปภาพ (บังคับ Crop เป็นแนวนอน 16:9)</label>
                        <input type="file" id="imageInput" name="image_file" class="form-control" accept="image/*">
                        <input type="hidden" name="cropped_image_data" id="croppedImageData">
                        <input type="hidden" name="existing_image" value="<?= $item['image_file'] ?? '' ?>">
                        
                        <div class="mt-3 text-center">
                            <img id="imagePreview" src="<?= !empty($item['image_file']) ? BASE_URL . '/uploads/yakpho/' . $item['image_file'] : '' ?>" class="img-thumbnail" style="max-width: 100%; <?= empty($item['image_file']) ? 'display:none;' : '' ?>">
                        </div>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="item_status" id="item_status" value="1" <?= (!isset($item['item_status']) || $item['item_status'] == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="item_status">เปิดใช้งาน</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">ข้อมูลหลายภาษา</h5></div>
                <div class="card-body">
                    <!-- Language Dropdown -->
                    <div class="language-dropdown-container mb-3" data-content-selector="#langTabsContent"></div>
                    
                    <div id="langTabsContent">
                        <?php foreach($languages as $code => $name): 
                            $tr = $translations[$code] ?? [];
                            $isRTL = in_array($code, ['ar', 'he']);
                            $dirAttr = $isRTL ? 'dir="rtl"' : '';
                        ?>
                        <div data-lang="<?= $code ?>">
                            <div class="mb-3">
                                <label class="form-label">ชื่อกลิ่น (Title) - <?= $name ?></label>
                                <input type="text" name="title_<?= $code ?>" class="form-control" value="<?= htmlspecialchars($tr['title'] ?? '') ?>" <?= $dirAttr ?>>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">คำบรรยายสั้น (Subtitle) - <?= $name ?></label>
                                <textarea name="subtitle_<?= $code ?>" class="form-control" rows="3" <?= $dirAttr ?>><?= htmlspecialchars($tr['subtitle'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer text-end bg-transparent border-top-0 pt-0 pb-3">
                    <button type="submit" class="btn btn-primary px-4"><i data-lucide="save"></i> บันทึกข้อมูล</button>
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
        <h5 class="modal-title" id="cropModalLabel">ครอบตัดรูปภาพแนวนอน (16:9)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <div style="max-height: 500px; overflow: hidden;">
            <img id="imageToCrop" src="" style="max-width: 100%;">
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary" id="cropButton">ครอบตัด & นำไปใช้</button>
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
            aspectRatio: 16 / 9, // บังคับแนวนอน 16:9
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

    document.getElementById('cropButton').addEventListener('click', function () {
        if (!cropper) return;
        
        const canvas = cropper.getCroppedCanvas({
            width: 800,
            height: 450,
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
</script>
";

$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
