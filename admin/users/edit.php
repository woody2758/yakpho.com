<?php
$page_title = "แก้ไขข้อมูลสมาชิก";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../../includes/functions/user.php";

$id = $_GET['id'] ?? 0;
$user = get_user_by_id($id);

if (!$user) {
    $_SESSION['alert'] = ['error', 'ไม่พบข้อมูลสมาชิก'];
    header("Location: index.php");
    exit;
}

ob_start();
?>

<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>แก้ไขข้อมูลสมาชิก</h4>
                    <span class="badge bg-secondary">ID: <?= $user['user_id'] ?></span>
                </div>
                <div class="card-body p-4">
                    
                    <form action="save.php" method="POST" autocomplete="off">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['user_email']) ?>" required>
                            </div>
                        </div>

                        <div class="alert alert-light border mb-3">
                            <div class="mb-2 fw-bold"><i class="fas fa-key me-1"></i> เปลี่ยนรหัสผ่าน (ถ้าไม่เปลี่ยนให้เว้นว่าง)</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="password" name="password" class="form-control" placeholder="รหัสผ่านใหม่" minlength="6">
                                </div>
                                <div class="col-md-6">
                                    <input type="password" name="confirm_password" class="form-control" placeholder="ยืนยันรหัสผ่านใหม่" minlength="6">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ - นามสกุล</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['user_name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เบอร์โทรศัพท์</label>
                                <input type="text" name="tel" class="form-control" value="<?= htmlspecialchars($user['user_mobile']) ?>">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">ตำแหน่ง (Role)</label>
                                <select name="role" class="form-select">
                                    <option value="member" <?= $user['role'] == 'member' ? 'selected' : '' ?>>Member (สมาชิกทั่วไป)</option>
                                    <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff (พนักงาน)</option>
                                    <option value="editor" <?= $user['role'] == 'editor' ? 'selected' : '' ?>>Editor (ผู้ดูแลเนื้อหา)</option>
                                    <option value="manager" <?= $user['role'] == 'manager' ? 'selected' : '' ?>>Manager (ผู้จัดการ)</option>
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin (ผู้ดูแลระบบ)</option>
                                    <option value="owner" <?= $user['role'] == 'owner' ? 'selected' : '' ?>>Owner (เจ้าของ)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">สถานะ</label>
                                <select name="status" class="form-select">
                                    <option value="1" <?= $user['user_status'] == 1 ? 'selected' : '' ?>>Active (ใช้งานปกติ)</option>
                                    <option value="0" <?= $user['user_status'] == 0 ? 'selected' : '' ?>>Inactive (ระงับใช้งาน)</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php" class="btn btn-light">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> บันทึกการแก้ไข
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
