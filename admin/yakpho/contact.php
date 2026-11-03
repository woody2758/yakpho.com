<?php
$page_title = 'จัดการข้อมูลเกี่ยวกับเรา & ติดต่อเรา (About Us & Contact)';
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
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] === '1');
    try {
        $db->beginTransaction();

        $phone = $_POST['phone'] ?? '';
        $line_id = $_POST['line_id'] ?? '';
        $facebook = $_POST['facebook'] ?? '';
        $email = $_POST['email'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';
        $map_embed = $_POST['map_embed'] ?? '';

        // Handle about_image upload (if any)
        $about_image = $_POST['existing_image'] ?? '';
        
        if (!empty($_POST['cropped_image_data'])) {
            $upload_dir = __DIR__ . '/../../uploads/yakpho/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $base64_string = $_POST['cropped_image_data'];
            $base64_string = str_replace('data:image/webp;base64,', '', $base64_string);
            $base64_string = str_replace(' ', '+', $base64_string);
            $image_data = base64_decode($base64_string);
            
            $about_image = 'about_' . time() . '.webp';
            file_put_contents($upload_dir . $about_image, $image_data);
        } elseif (isset($_FILES['about_image_file']) && $_FILES['about_image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/yakpho/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $ext = pathinfo($_FILES['about_image_file']['name'], PATHINFO_EXTENSION);
            $about_image = 'about_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['about_image_file']['tmp_name'], $upload_dir . $about_image);
        }

        // 1. Update main table
        $stmtUpd = $db->prepare("UPDATE yakpho_contact SET phone=?, line_id=?, facebook=?, email=?, whatsapp=?, map_embed=?, about_image=?, updated_at=NOW() WHERE contact_id=1");
        $stmtUpd->execute([$phone, $line_id, $facebook, $email, $whatsapp, $map_embed, $about_image]);

        // 2. Handle translations
        $translations = $_POST['translations'] ?? [];
        foreach ($translations as $lang_code => $trans) {
            $title = trim($trans['title'] ?? '');
            $company_name = trim($trans['company_name'] ?? '');
            $address = trim($trans['address'] ?? '');
            $about_title = trim($trans['about_title'] ?? '');
            $about_subtitle = trim($trans['about_subtitle'] ?? '');
            $about_html = trim($trans['about_html'] ?? '');

            // Check if translation exists
            $stmtCheck = $db->prepare("SELECT translation_id FROM yakpho_contact_translations WHERE contact_id = 1 AND lang_code = ?");
            $stmtCheck->execute([$lang_code]);
            $transExists = $stmtCheck->fetchColumn();

            if ($transExists) {
                $stmtUpdTrans = $db->prepare("UPDATE yakpho_contact_translations SET title=?, company_name=?, address=?, about_title=?, about_subtitle=?, about_html=? WHERE translation_id=?");
                $stmtUpdTrans->execute([$title, $company_name, $address, $about_title, $about_subtitle, $about_html, $transExists]);
            } else {
                $stmtInsTrans = $db->prepare("INSERT INTO yakpho_contact_translations (contact_id, lang_code, title, company_name, address, about_title, about_subtitle, about_html) VALUES (1, ?, ?, ?, ?, ?, ?, ?)");
                $stmtInsTrans->execute([$lang_code, $title, $company_name, $address, $about_title, $about_subtitle, $about_html]);
            }
        }

        $db->commit();
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'success',
                'message' => 'บันทึกข้อมูลเกี่ยวกับเรา & ติดต่อเราเรียบร้อยแล้ว'
            ]);
            exit;
        }
        $_SESSION['flash_msg'] = "บันทึกข้อมูลเรียบร้อยแล้ว";
        header("Location: contact.php");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        if ($isAjax) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
            exit;
        }
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch current main data
$stmtMain = $db->prepare("SELECT * FROM yakpho_contact WHERE contact_id = 1");
$stmtMain->execute();
$main_data = $stmtMain->fetch(PDO::FETCH_ASSOC);
if (!$main_data) {
    $main_data = ['phone'=>'', 'line_id'=>'', 'facebook'=>'', 'email'=>'', 'whatsapp'=>'', 'map_embed'=>'', 'about_image'=>''];
}

// Fetch translations
$stmtTrans = $db->prepare("SELECT * FROM yakpho_contact_translations WHERE contact_id = 1");
$stmtTrans->execute();
$current_trans = [];
while ($row = $stmtTrans->fetch(PDO::FETCH_ASSOC)) {
    $current_trans[$row['lang_code']] = $row;
}

ob_start();
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1><i data-lucide="info"></i> จัดการข้อมูลเกี่ยวกับเรา & ติดต่อเรา (About Us & Contact)</h1>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data" id="contactForm">
    <div class="row">
        <!-- Left Column: Main Info -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">ข้อมูลเกี่ยวกับเรา (ภาพประกอบ)</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">รูปภาพประกอบ (รองรับ Crop และแปลงเป็น .webp อัตโนมัติ)</label>
                        <input type="file" id="imageInput" name="about_image_file" class="form-control" accept="image/*">
                        <input type="hidden" name="cropped_image_data" id="croppedImageData">
                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($main_data['about_image'] ?? '') ?>">
                        
                        <div class="mt-3 text-center">
                            <?php 
                                $preview_url = '';
                                if (!empty($main_data['about_image'])) {
                                    if ($main_data['about_image'] === 'tarragon.webp') {
                                        $preview_url = BASE_URL . '/catalog/assets/img/tarragon.webp';
                                    } else {
                                        $preview_url = BASE_URL . '/uploads/yakpho/' . $main_data['about_image'];
                                    }
                                }
                            ?>
                            <img id="imagePreview" src="<?= $preview_url ?>" class="img-thumbnail about-preview-img <?= empty($preview_url) ? 'd-none' : '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">ข้อมูลการติดต่อหลัก (ทุกภาษา)</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($main_data['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Line Official</label>
                        <input type="text" name="line_id" class="form-control" value="<?= htmlspecialchars($main_data['line_id'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Facebook</label>
                        <input type="text" name="facebook" class="form-control" value="<?= htmlspecialchars($main_data['facebook'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($main_data['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($main_data['whatsapp'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Google Maps Embed HTML (iframe)</label>
                        <textarea name="map_embed" class="form-control" rows="4" placeholder="<iframe src=...></iframe>"><?= htmlspecialchars($main_data['map_embed'] ?? '') ?></textarea>
                        <small class="text-muted">คัดลอกโค้ด Embed จาก Google Maps มาใส่ที่นี่</small>
                    </div>
                    <?php if (!empty($main_data['map_embed'])): ?>
                        <div class="mt-2 map-preview border p-2">
                            <?= $main_data['map_embed'] ?>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Translations -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลแบบหลายภาษา</h5>
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
                                
                                <h6 class="text-primary mt-3 mb-3 border-bottom pb-2">1. ส่วนเกี่ยวกับเรา (About Us)</h6>
                                <div class="mb-3">
                                    <label class="form-label">หัวข้อ About Us - <?= $name ?> <?= $code === 'th' ? '<span class="text-danger">*</span>' : '' ?></label>
                                    <input type="text" name="translations[<?= $code ?>][about_title]" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($tData['about_title'] ?? '') ?>" 
                                           <?= $code === 'th' ? 'required' : '' ?> <?= $dirAttr ?>>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">คำโปรย / ชื่อบริษัทย่อย About Us - <?= $name ?></label>
                                    <input type="text" name="translations[<?= $code ?>][about_subtitle]" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($tData['about_subtitle'] ?? '') ?>" <?= $dirAttr ?>>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">เนื้อหา HTML About Us - <?= $name ?></label>
                                    <textarea name="translations[<?= $code ?>][about_html]" 
                                              class="form-control summernote" 
                                              rows="6" <?= $dirAttr ?>><?= htmlspecialchars($tData['about_html'] ?? '') ?></textarea>
                                </div>

                                <h6 class="text-success mt-4 mb-3 border-bottom pb-2">2. ส่วนติดต่อเรา (Contact Us)</h6>
                                <div class="mb-3">
                                    <label class="form-label">หัวข้อ Contact - <?= $name ?> <?= $code === 'th' ? '<span class="text-danger">*</span>' : '' ?></label>
                                    <input type="text" name="translations[<?= $code ?>][title]" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($tData['title'] ?? 'ติดต่อเรา') ?>" 
                                           <?= $code === 'th' ? 'required' : '' ?> <?= $dirAttr ?>>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ชื่อบริษัทที่ติดต่อ (Company Name) - <?= $name ?></label>
                                    <input type="text" name="translations[<?= $code ?>][company_name]" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($tData['company_name'] ?? '') ?>" <?= $dirAttr ?>>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ที่อยู่ (Address) - <?= $name ?></label>
                                    <textarea name="translations[<?= $code ?>][address]" 
                                              class="form-control" 
                                              rows="3" <?= $dirAttr ?>><?= htmlspecialchars($tData['address'] ?? '') ?></textarea>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                </div>
                <div class="card-footer text-end bg-transparent border-top-0 pt-0 pb-3">
                    <button type="submit" class="btn btn-primary px-4" id="btnSubmitContact">
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
        <div class="cropper-modal-container">
            <img id="imageToCrop" src="" class="cropper-modal-img">
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
ob_start();
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let cropper;
    const imageInput = document.getElementById('imageInput');
    const imageToCrop = document.getElementById('imageToCrop');
    const cropModalElement = document.getElementById('cropModal');
    const cropModal = new bootstrap.Modal(cropModalElement);

    if(imageInput) {
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
    }

    if(cropModalElement) {
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
                if(imageInput) imageInput.value = '';
            }
        });
    }

    window.setCropperRatio = function(ratio) {
        if (cropper) cropper.setAspectRatio(ratio);
    };

    const cropButton = document.getElementById('cropButton');
    if(cropButton) {
        cropButton.addEventListener('click', function () {
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
            preview.classList.remove('d-none');
            
            cropModal.hide();
        });
    }
});


    // AJAX Form Submission with Spinner & SweetAlert2 Toast (md/AGENT.md)
    const contactForm = document.getElementById('contactForm');
    const submitBtn = document.getElementById('btnSubmitContact');
    if (contactForm && submitBtn) {
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (submitBtn.disabled) return;

            // Sync Summernote WYSIWYG content
            if (window.$ && $.fn.summernote) {
                $('.summernote').each(function() {
                    $(this).val($(this).summernote('code'));
                });
            }

            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.classList.add('btn-loading');
            submitBtn.innerHTML = '<span class="spinner-snake me-2"></span> กำลังบันทึกข้อมูล...';

            try {
                const formData = new FormData(contactForm);
                formData.append('ajax', '1');

                const res = await fetch('contact.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const result = await res.json();

                if (result.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: result.message || 'บันทึกข้อมูลเรียบร้อยแล้ว',
                            showConfirmButton: false,
                            timer: 2500,
                            timerProgressBar: true
                        });
                    } else {
                        alert(result.message);
                    }
                } else {
                    throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                }
            } catch (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: err.message
                    });
                } else {
                    alert('เกิดข้อผิดพลาด: ' + err.message);
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
                submitBtn.innerHTML = originalBtnHtml;
                if (window.lucide) lucide.createIcons();
            }
        });
    }

$(document).ready(function() {
    // Initialize Summernote
    $('.summernote').summernote({
        height: 250,
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
<?php
$extra_scripts = ob_get_clean();

$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php';
?>
