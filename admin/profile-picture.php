<?php
$page_title = "เปลี่ยนรูปโปรไฟล์";
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/auth.php";

// Processing upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'jfif', 'svg', 'bmp', 'heic', 'heif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Upload directory setup
            $upload_dir = __DIR__ . "/../uploads/profile/" . $ADMIN_ID . "/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            require_once __DIR__ . "/includes/functions/image.php";
            
            if ($ext === 'svg') {
                $new_name = "profile_" . time() . ".svg";
                $destination = $upload_dir . $new_name;
                $success = move_uploaded_file($file['tmp_name'], $destination);
            } else {
                $new_name = "profile_" . time() . ".webp";
                $destination = $upload_dir . $new_name;
                
                $sourceImage = load_image($file['tmp_name']);
                if ($sourceImage) {
                    $resizedImage = resize_image($sourceImage, 500, 500, true);
                    imagedestroy($sourceImage);
                    $success = save_as_webp($resizedImage, $destination, 85);
                    imagedestroy($resizedImage);
                } else {
                    $success = false;
                }
            }
            
            if ($success) {
                // Update database
                $stmt = $db->prepare("UPDATE user SET user_picture = ? WHERE user_id = ?");
                $stmt->execute([$new_name, $ADMIN_ID]);
                
                // Update session
                $_SESSION['admin_picture'] = $new_name;
                
                $_SESSION['alert'] = ['success', 'อัปเดตรูปโปรไฟล์เรียบร้อยแล้ว'];
                header("Location: profile-picture.php");
                exit;
            } else {
                $_SESSION['alert'] = ['error', 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์'];
            }
        } else {
            $_SESSION['alert'] = ['error', 'รองรับเฉพาะไฟล์รูปภาพทั่วไป (JPG, PNG, GIF, WEBP, JFIF, ฯลฯ) เท่านั้น'];
        }
    } else {
        $_SESSION['alert'] = ['error', 'กรุณาเลือกรูปภาพ'];
    }
}

// ดึงรูปโปรไฟล์ล่าสุดจากฐานข้อมูลเพื่อความชัวร์
$picStmt = $db->prepare("SELECT user_picture FROM user WHERE user_id = ? LIMIT 1");
$picStmt->execute([$ADMIN_ID]);
$dbPic = $picStmt->fetchColumn();
$ADMIN_PIC = !empty($dbPic) ? $dbPic : "default.png";

// Current picture
$current_pic = ROOT_URL . "/uploads/profile/" . ($ADMIN_ID ?: "default") . "/" . $ADMIN_PIC;
$check_file = __DIR__ . "/../uploads/profile/" . ($ADMIN_ID ?: "default") . "/" . $ADMIN_PIC;
if (!file_exists($check_file) || $ADMIN_PIC === 'default.png') {
    $current_pic = ROOT_URL . "/uploads/profile/default.png";
}

ob_start();
?>

<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0"><i data-lucide="image" class="me-2" style="width: 20px; height: 20px;"></i>เปลี่ยนรูปโปรไฟล์</h4>
                </div>
                <div class="card-body p-4 text-center">
                    
                    <div class="mb-4">
                        <img src="<?= htmlspecialchars($current_pic) ?>?t=<?= time() ?>" alt="Current Profile" class="rounded-circle border" style="width: 150px; height: 150px; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    </div>

                    <form action="profile-picture.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3 text-start">
                            <label class="form-label">เลือกรูปภาพใหม่ (รองรับไฟล์รูปภาพทุกชนิด)</label>
                            <input type="file" name="profile_pic" class="form-control" accept="image/*" required>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i data-lucide="upload" class="me-1" style="width: 18px; height: 18px;"></i> อัปโหลดรูปภาพ
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <!-- Image History -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i data-lucide="history" class="me-2" style="width: 18px; height: 18px;"></i>ประวัติรูปโปรไฟล์ที่เคยอัปโหลด</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2" id="imageHistoryGrid">
                        <div class="col-12 text-center py-3 text-muted small">
                            <div class="spinner-border spinner-border-sm mb-2"></div><br>กำลังโหลดประวัติ...
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.history-img-item {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
}
.history-img-item:hover {
    transform: translateY(-3px);
    border-color: var(--bs-primary);
    box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.2);
}
.history-img-wrapper {
    position: relative;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide) {
        lucide.createIcons();
    }
    loadUserImageHistory();
});

const ADMIN_URL = '<?= ADMIN_URL ?>';
const USER_ID = <?= $ADMIN_ID ?>;

function loadUserImageHistory() {
    const grid = document.getElementById('imageHistoryGrid');
    fetch(ADMIN_URL + '/api/get_user_image_history.php?user_id=' + USER_ID)
    .then(response => response.json())
    .then(data => {
        grid.innerHTML = '';
        if (data.success && data.images.length > 0) {
            data.images.forEach(img => {
                const col = document.createElement('div');
                col.className = 'col-3 col-sm-3 col-md-3';
                col.innerHTML = `
                    <div class="history-img-wrapper">
                        <div class="history-img-item" onclick="setUserPictureFromHistory('${img.filename}')" title="คลิกเพื่อตั้งเป็นรูปโปรไฟล์ปัจจุบัน">
                            <img src="${img.url}?t=${Date.now()}" class="w-100 rounded shadow-sm" style="height:100px;object-fit:cover;">
                        </div>
                    </div>`;
                grid.appendChild(col);
            });
        } else {
            grid.innerHTML = '<div class="col-12 text-center text-muted py-3 small">ยังไม่มีประวัติรูปภาพ</div>';
        }
    })
    .catch(() => {
        grid.innerHTML = '<div class="col-12 text-center text-danger py-3 small">โหลดประวัติรูปภาพไม่สำเร็จ</div>';
    });
}

function setUserPictureFromHistory(filename) {
    Swal.fire({
        title: 'ใช้รูปภาพนี้?',
        text: 'ต้องการเปลี่ยนกลับไปใช้รูปภาพนี้ใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่',
        cancelButtonText: 'ยกเลิก',
        background: (document.documentElement.getAttribute('data-theme') === 'dark' || document.body.getAttribute('data-theme') === 'dark') ? '#1e1e1e' : '#fff',
        color: (document.documentElement.getAttribute('data-theme') === 'dark' || document.body.getAttribute('data-theme') === 'dark') ? '#fff' : '#545454'
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            fetch(ADMIN_URL + '/api/set_user_picture_from_history.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: USER_ID, filename: filename })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'สำเร็จ!', showConfirmButton: false, timer: 1500 });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' });
            });
        }
    });
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/includes/layout.php"; 
?>
