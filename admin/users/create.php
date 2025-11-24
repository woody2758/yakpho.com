<?php
$page_title = "เพิ่มสมาชิกใหม่";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>เพิ่มสมาชิกใหม่</h4>
                </div>
                <div class="card-body p-4">
                    
                    <form action="save.php" method="POST" autocomplete="off">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                                <div class="form-text">อย่างน้อย 6 ตัวอักษร</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ - นามสกุล</label>
                                <input type="text" name="name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เบอร์โทรศัพท์</label>
                                <input type="text" name="tel" class="form-control">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">ตำแหน่ง (Role)</label>
                            <select name="role" class="form-select">
                                <option value="member">Member (สมาชิกทั่วไป)</option>
                                <option value="staff">Staff (พนักงาน)</option>
                                <option value="editor">Editor (ผู้ดูแลเนื้อหา)</option>
                                <option value="manager">Manager (ผู้จัดการ)</option>
                                <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php" class="btn btn-light">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> บันทึกข้อมูล
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
