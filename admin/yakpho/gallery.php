<?php
$page_title = 'จัดการแกลเลอรี (Gallery)';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// ── Handle AJAX Cropped Image Upload ──
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'upload_cropped_image') {
    header('Content-Type: application/json');
    $cat_id = (int)($_POST['category_id'] ?? 0);
    if ($cat_id && !empty($_POST['cropped_image_data'])) {
        $upload_dir = __DIR__ . '/../../uploads/yakpho/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $base64_string = $_POST['cropped_image_data'];
        $base64_string = str_replace('data:image/webp;base64,', '', $base64_string);
        $base64_string = str_replace(' ', '+', $base64_string);
        $image_data = base64_decode($base64_string);
        
        $image_name = 'gallery_' . time() . '_' . rand(1000, 9999) . '.webp';
        if (file_put_contents($upload_dir . $image_name, $image_data)) {
            // Create thumbnail
            $img = @imagecreatefromstring($image_data);
            if ($img !== false) {
                $width = imagesx($img);
                $height = imagesy($img);
                $new_width = 400;
                $new_height = floor($height * ($new_width / $width));
                $tmp_img = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                $thumb_name = str_replace('.webp', '_thumb.webp', $image_name);
                imagewebp($tmp_img, $upload_dir . $thumb_name, 80);
                imagedestroy($img);
                imagedestroy($tmp_img);
            }

            $stmt = $db->prepare("INSERT INTO yakpho_gallery_images (category_id, image_file) VALUES (?, ?)");
            $stmt->execute([$cat_id, $image_name]);
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
    echo json_encode(['status' => 'error']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $langs = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar'];
        $name_th = trim($_POST['category_name_th'] ?? '');
        if ($name_th) {
            $stmt = $db->prepare("INSERT INTO yakpho_gallery_categories (category_name) VALUES ('')");
            $stmt->execute();
            $cat_id = $db->lastInsertId();
            
            $ins = $db->prepare("INSERT INTO yakpho_gallery_category_translations (category_id, lang_code, category_name) VALUES (?, ?, ?)");
            foreach($langs as $lc) {
                $n = trim($_POST['category_name_' . $lc] ?? '');
                if ($n) $ins->execute([$cat_id, $lc, $n]);
            }
            
            $_SESSION['flash_msg'] = "เพิ่มหมวดหมู่เรียบร้อยแล้ว";
        }
        header("Location: gallery.php");
        exit;
    } elseif ($action === 'edit_category') {
        $id = (int)($_POST['category_id'] ?? 0);
        $langs = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar'];
        $name_th = trim($_POST['category_name_th'] ?? '');
        if ($id && $name_th) {
            $db->prepare("DELETE FROM yakpho_gallery_category_translations WHERE category_id = ?")->execute([$id]);
            
            $ins = $db->prepare("INSERT INTO yakpho_gallery_category_translations (category_id, lang_code, category_name) VALUES (?, ?, ?)");
            foreach($langs as $lc) {
                $n = trim($_POST['category_name_' . $lc] ?? '');
                if ($n) $ins->execute([$id, $lc, $n]);
            }

            $_SESSION['flash_msg'] = "แก้ไขหมวดหมู่เรียบร้อยแล้ว";
        }
        header("Location: gallery.php");
        exit;
    } elseif ($action === 'delete_category') {
        $id = (int)($_POST['category_id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("SELECT image_file FROM yakpho_gallery_images WHERE category_id = ?");
            $stmt->execute([$id]);
            while ($row = $stmt->fetch()) {
                $file = __DIR__ . '/../../uploads/yakpho/' . $row['image_file'];
                if (file_exists($file) && !is_dir($file)) @unlink($file);
                $thumb = str_replace('.webp', '_thumb.webp', $file);
                if (file_exists($thumb) && !is_dir($thumb)) @unlink($thumb);
            }
            $db->prepare("DELETE FROM yakpho_gallery_categories WHERE category_id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "ลบหมวดหมู่และรูปภาพเรียบร้อยแล้ว";
        }
        header("Location: gallery.php");
        exit;
    } elseif ($action === 'delete_image') {
        $id = (int)($_POST['image_id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("SELECT image_file FROM yakpho_gallery_images WHERE image_id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) {
                $file = __DIR__ . '/../../uploads/yakpho/' . $row['image_file'];
                if (file_exists($file) && !is_dir($file)) @unlink($file);
                $thumb = str_replace('.webp', '_thumb.webp', $file);
                if (file_exists($thumb) && !is_dir($thumb)) @unlink($thumb);
                
                $db->prepare("DELETE FROM yakpho_gallery_images WHERE image_id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "ลบรูปภาพเรียบร้อยแล้ว";
            }
        }
        header("Location: gallery.php");
        exit;
    }
}

// Fetch categories with translations
$stmt = $db->query("
    SELECT c.*, 
           MAX(IF(t.lang_code = 'th', t.category_name, NULL)) as name_th,
           MAX(IF(t.lang_code = 'en', t.category_name, NULL)) as name_en,
           MAX(IF(t.lang_code = 'de', t.category_name, NULL)) as name_de,
           MAX(IF(t.lang_code = 'fr', t.category_name, NULL)) as name_fr,
           MAX(IF(t.lang_code = 'zh', t.category_name, NULL)) as name_zh,
           MAX(IF(t.lang_code = 'ko', t.category_name, NULL)) as name_ko,
           MAX(IF(t.lang_code = 'ja', t.category_name, NULL)) as name_ja,
           MAX(IF(t.lang_code = 'ru', t.category_name, NULL)) as name_ru,
           MAX(IF(t.lang_code = 'ar', t.category_name, NULL)) as name_ar
    FROM yakpho_gallery_categories c
    LEFT JOIN yakpho_gallery_category_translations t ON c.category_id = t.category_id
    GROUP BY c.category_id
    ORDER BY c.sort_order ASC, c.category_id ASC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<style>
  .img-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
  .img-card { border-radius: 8px; overflow: hidden; position: relative; border: 1px solid #e2e8f0; }
  .img-card img { width: 100%; height: 150px; object-fit: cover; }
  .img-actions { position: absolute; top: 5px; right: 5px; background: rgba(255,255,255,0.9); border-radius: 4px; padding: 2px; }
</style>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1><i data-lucide="images"></i> จัดการแกลเลอรี (Gallery)</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i data-lucide="folder-plus"></i> เพิ่มหมวดหมู่
    </button>
</div>

<?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['flash_msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_msg']); ?>
<?php endif; ?>

<div class="row">
    <?php foreach ($categories as $cat): ?>
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h5 class="mb-0 text-primary">
                        <i data-lucide="folder" class="me-2"></i> <?= htmlspecialchars($cat['name_th'] ?? $cat['category_name']) ?>
                        <?php if(!empty($cat['name_en'])): ?>
                            <small class="text-muted d-block mt-1" style="font-size: 0.8rem; margin-left: 28px;"><?= htmlspecialchars($cat['name_en']) ?></small>
                        <?php endif; ?>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-success me-2" onclick="openAddImageModal(<?= $cat['category_id'] ?>)">
                            <i data-lucide="image-plus"></i> เพิ่มรูปภาพ
                        </button>
                        <button class="btn btn-sm btn-warning me-2" onclick="openEditCategoryModal(<?= $cat['category_id'] ?>, '<?= htmlspecialchars($cat['name_th'] ?? $cat['category_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['name_en'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['name_de'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['name_fr'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['name_zh'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['name_ko'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['name_ja'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['name_ru'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['name_ar'] ?? '', ENT_QUOTES) ?>')">
                            <i data-lucide="edit"></i> แก้ไขชื่อ
                        </button>
                        <form method="post" action="" class="d-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบหมวดหมู่นี้และรูปภาพทั้งหมดภายในหมวดหมู่นี้?');">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i data-lucide="trash"></i> ลบหมวดหมู่
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    $stmtImg = $db->prepare("SELECT * FROM yakpho_gallery_images WHERE category_id = ? ORDER BY sort_order ASC, image_id DESC");
                    $stmtImg->execute([$cat['category_id']]);
                    $images = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if (count($images) > 0): ?>
                        <div class="img-grid">
                            <?php foreach ($images as $img): 
                                $thumb_name = str_replace('.webp', '_thumb.webp', $img['image_file']);
                                $thumb_path = __DIR__ . '/../../uploads/yakpho/' . $thumb_name;
                                $display_src = file_exists($thumb_path) ? $thumb_name : $img['image_file'];
                            ?>
                                <div class="img-card shadow-sm">
                                    <img src="<?= BASE_URL ?>/uploads/yakpho/<?= $display_src ?>" loading="lazy" alt="Gallery Image" style="cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=1;">
                                    <div class="img-actions">
                                        <form method="post" action="" class="m-0 p-0" onsubmit="return confirm('ต้องการลบรูปภาพนี้ใช่หรือไม่?');">
                                            <input type="hidden" name="action" value="delete_image">
                                            <input type="hidden" name="image_id" value="<?= $img['image_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-ghost-danger px-2" title="ลบรูปภาพ">
                                                <i data-lucide="trash" style="width:16px; height:16px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            ยังไม่มีรูปภาพในหมวดหมู่นี้
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (count($categories) === 0): ?>
        <div class="col-12 text-center py-5 text-muted">
            <i data-lucide="folder" style="width: 48px; height: 48px; opacity: 0.5" class="mb-3"></i>
            <h5>ยังไม่มีหมวดหมู่ Gallery</h5>
            <p>กรุณาเพิ่มหมวดหมู่ก่อนทำการอัปโหลดรูปภาพ</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="">
          <input type="hidden" name="action" value="add_category">
          <div class="modal-header">
            <h5 class="modal-title" id="addCategoryModalLabel">เพิ่มหมวดหมู่ใหม่</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">ชื่อหมวดหมู่ (ภาษาไทย)</label>
                    <input type="text" name="category_name_th" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">English</label>
                    <input type="text" name="category_name_en" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Deutsch</label>
                    <input type="text" name="category_name_de" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Français</label>
                    <input type="text" name="category_name_fr" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">中文</label>
                    <input type="text" name="category_name_zh" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">한국어</label>
                    <input type="text" name="category_name_ko" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">日本語</label>
                    <input type="text" name="category_name_ja" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Русский</label>
                    <input type="text" name="category_name_ru" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">العربية</label>
                    <input type="text" name="category_name_ar" class="form-control">
                </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary">บันทึก</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Category -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="">
          <input type="hidden" name="action" value="edit_category">
          <input type="hidden" name="category_id" id="editCategoryId">
          <div class="modal-header">
            <h5 class="modal-title" id="editCategoryModalLabel">แก้ไขหมวดหมู่</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">ชื่อหมวดหมู่ (ภาษาไทย)</label>
                    <input type="text" name="category_name_th" id="editCategoryNameTh" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">English</label>
                    <input type="text" name="category_name_en" id="editCategoryNameEn" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Deutsch</label>
                    <input type="text" name="category_name_de" id="editCategoryNameDe" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Français</label>
                    <input type="text" name="category_name_fr" id="editCategoryNameFr" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">中文</label>
                    <input type="text" name="category_name_zh" id="editCategoryNameZh" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">한국어</label>
                    <input type="text" name="category_name_ko" id="editCategoryNameKo" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">日本語</label>
                    <input type="text" name="category_name_ja" id="editCategoryNameJa" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Русский</label>
                    <input type="text" name="category_name_ru" id="editCategoryNameRu" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">العربية</label>
                    <input type="text" name="category_name_ar" id="editCategoryNameAr" class="form-control">
                </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary">บันทึก</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Add Image (Dropzone) -->
<div class="modal fade" id="addImageModal" tabindex="-1" aria-labelledby="addImageModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addImageModalLabel">เพิ่มรูปภาพเข้าหมวดหมู่</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="addImageCategoryId">
          <div id="drop-zone" class="p-4 bg-light rounded text-center border" style="border: 2px dashed #ccc !important; cursor: pointer; transition: all 0.3s;">
            <div class="mb-2 text-muted">
              <i data-lucide="upload-cloud" style="width: 48px; height: 48px;"></i>
            </div>
            <h5 class="mb-1 text-primary">ลากไฟล์รูปภาพมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</h5>
            <div class="mt-3">
              <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('upload-input').click()">เลือกไฟล์รูปภาพ</button>
            </div>
            <input type="file" id="upload-input" class="d-none" accept="image/*" multiple>
          </div>
          <small class="text-muted mt-2 d-block text-center">ระบบรองรับการเลือกทีละหลายไฟล์ และให้คุณ Crop ทีละรูปก่อนอัปโหลดเข้าเซิร์ฟเวอร์</small>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Crop Queue -->
<div class="modal modal-blur fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cropModalLabel">ครอบตัดรูปภาพ (Crop)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="max-height: 60vh; background: #333; display: flex; justify-content: center; align-items: center; overflow: hidden;">
        <img id="imageToCrop" src="" style="max-width: 100%; display: none;">
      </div>
      <div class="modal-footer d-flex justify-content-between align-items-center">
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="window.setCropperRatio(1)">1:1 (Square)</button>
            <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="window.setCropperRatio(4/5)">4:5 (Portrait)</button>
            <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="window.setCropperRatio(1.91/1)">1.91:1 (Landscape)</button>
            <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="window.setCropperRatio(NaN)">Free</button>
            <div class="btn-group ms-2">
              <button type="button" class="btn btn-outline-info btn-sm" onclick="window.rotateImage(-90)" title="หมุนซ้าย">↺ หมุนซ้าย</button>
              <button type="button" class="btn btn-outline-info btn-sm" onclick="window.rotateImage(90)" title="หมุนขวา">↻ หมุนขวา</button>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="queue-status" class="text-muted small me-2"></span>
            <button type="button" class="btn btn-primary" id="cropAndNextButton">
                <i data-lucide="check-circle" class="me-1"></i> บันทึกและถัดไป
            </button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extra_scripts = "
<script>
function openEditCategoryModal(id, th, en, de, fr, zh, ko, ja, ru, ar) {
    document.getElementById('editCategoryId').value = id;
    document.getElementById('editCategoryNameTh').value = th;
    document.getElementById('editCategoryNameEn').value = en || '';
    document.getElementById('editCategoryNameDe').value = de || '';
    document.getElementById('editCategoryNameFr').value = fr || '';
    document.getElementById('editCategoryNameZh').value = zh || '';
    document.getElementById('editCategoryNameKo').value = ko || '';
    document.getElementById('editCategoryNameJa').value = ja || '';
    document.getElementById('editCategoryNameRu').value = ru || '';
    document.getElementById('editCategoryNameAr').value = ar || '';
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

function openAddImageModal(categoryId) {
    document.getElementById('addImageCategoryId').value = categoryId;
    document.getElementById('upload-input').value = '';
    new bootstrap.Modal(document.getElementById('addImageModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    let cropper;
    let filesQueue = [];
    let currentFileIndex = 0;
    
    const dropZone = document.getElementById('drop-zone');
    const uploadInput = document.getElementById('upload-input');
    const imageToCrop = document.getElementById('imageToCrop');
    const cropModalObj = new bootstrap.Modal(document.getElementById('cropModal'));
    const cropAndNextButton = document.getElementById('cropAndNextButton');
    
    if (dropZone) {
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#0d6efd';
            dropZone.style.backgroundColor = '#f8f9fa';
        });
        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#ccc';
            dropZone.style.backgroundColor = '#f8f9fa';
        });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#ccc';
            dropZone.style.backgroundColor = '#f8f9fa';
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                handleUploadFiles(e.dataTransfer.files);
            }
        });
        dropZone.addEventListener('click', (e) => {
            if(e.target !== uploadInput) {
                uploadInput.click();
            }
        });
    }

    uploadInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleUploadFiles(e.target.files);
        }
    });

    function handleUploadFiles(files) {
        filesQueue = Array.from(files);
        currentFileIndex = 0;
        bootstrap.Modal.getInstance(document.getElementById('addImageModal')).hide();
        processNextImage();
    }

    function processNextImage() {
        if (currentFileIndex >= filesQueue.length) {
            cropModalObj.hide();
            window.location.reload();
            return;
        }
        
        document.getElementById('queue-status').innerText = `รูปที่ \${currentFileIndex + 1} จาก \${filesQueue.length}`;
        
        const file = filesQueue[currentFileIndex];
        const reader = new FileReader();
        reader.onload = function(event) {
            imageToCrop.src = event.target.result;
            imageToCrop.style.display = 'block';
            cropModalObj.show();
            
            setTimeout(() => {
                if (cropper) cropper.destroy();
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1,
                    viewMode: 2, dragMode: 'move', autoCropArea: 1,
                    restore: false, guides: true, center: true, highlight: false,
                    cropBoxMovable: true, cropBoxResizable: true, toggleDragModeOnDblclick: false,
                });
            }, 300);
        };
        reader.readAsDataURL(file);
    }

    window.setCropperRatio = function(ratio) {
        if (cropper) cropper.setAspectRatio(ratio);
    };
    
    window.rotateImage = function(degree) {
        if (cropper) cropper.rotate(degree);
    };

    cropAndNextButton.addEventListener('click', function() {
        if (!cropper) return;
        
        const canvas = cropper.getCroppedCanvas({
            width: 1200,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });
        
        const webpBase64 = canvas.toDataURL('image/webp', 0.85);
        const categoryId = document.getElementById('addImageCategoryId').value;
        
        const originalText = cropAndNextButton.innerHTML;
        cropAndNextButton.innerHTML = '<span class=\"spinner-border spinner-border-sm\" role=\"status\" aria-hidden=\"true\"></span> กำลังบันทึก...';
        cropAndNextButton.disabled = true;

        const formData = new FormData();
        formData.append('ajax_action', 'upload_cropped_image');
        formData.append('category_id', categoryId);
        formData.append('cropped_image_data', webpBase64);

        fetch('gallery.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            cropAndNextButton.innerHTML = originalText;
            cropAndNextButton.disabled = false;
            
            if (data.status === 'success') {
                currentFileIndex++;
                processNextImage();
            } else {
                alert('เกิดข้อผิดพลาดในการอัปโหลดภาพ');
            }
        })
        .catch(err => {
            cropAndNextButton.innerHTML = originalText;
            cropAndNextButton.disabled = false;
            alert('Upload Error!');
        });
    });

    document.getElementById('cropModal').addEventListener('hidden.bs.modal', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        if (currentFileIndex > 0 && currentFileIndex < filesQueue.length) {
            window.location.reload();
        }
    });
});
</script>
";

$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php';
?>
