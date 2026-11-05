<?php
require_once "db.php";
require_login();

// เฟ้นเพียง Add User (Status change ใช้ AJAX ผ่าน api/action.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $role     = $_POST['role'] ?? 'admin';
    $password = trim($_POST['password']);
    $hash     = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $db->prepare("INSERT INTO staff_admins (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    try {
        $stmt->execute([$name, $email, $hash, $role]);
        $_SESSION['alert'] = "เพิ่มผู้ดูแลใหม่สำเร็จ";
    } catch(PDOException $e) {
        $_SESSION['error'] = "อีเมลซ้ำ หรือเกิดข้อผิดพลาด: " . $e->getMessage();
    }
    header("Location: users.php"); exit;
}

// 1. ดึงข้อมูล User (ยกเว้นคนที่ถูกลบทิ้งไปแล้ว)
$stmt = $db->query("SELECT id, name, email, role, status, created_at FROM staff_admins WHERE status != 'deleted' ORDER BY id DESC");
$users = $stmt->fetchAll();

include "components/header.php"; 
?>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">ระบบจัดการผู้ดูแล (Staff Admins)</h2>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
          <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#modal-add-user">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
            เพิ่มแอดมินใหม่
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Page body -->
<div class="page-body">
  <div class="container-fluid">
    
    <?php if(isset($_SESSION['alert'])): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex">
                <div><?= $_SESSION['alert'] ?></div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <div class="d-flex">
                <div><?= $_SESSION['error'] ?></div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
      <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap datatable">
          <thead>
            <tr>
              <th class="w-1">ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($users as $user): ?>
            <tr>
              <td><span class="text-muted"><?= $user['id'] ?></span></td>
              <td><?= htmlspecialchars($user['name']) ?></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td class="text-muted">
                <?= $user['role'] === 'superadmin' ? 'Super Admin' : 'Admin' ?>
              </td>
              <td>
                <?php if($user['status'] == 'active'): ?>
                    <span class="badge bg-success me-1"></span> Active
                <?php else: ?>
                    <span class="badge bg-warning me-1"></span> On Hold
                <?php endif; ?>
              </td>
              <td>
                <div class="btn-list flex-nowrap">
                  <!-- Toggle Status -->
                  <?php if($user['status'] == 'active'): ?>
                    <button class="btn btn-warning btn-sm btn-user-action"
                      data-id="<?= $user['id'] ?>" data-action="user_status" data-status="hold"
                      <?= ($user['id'] == $_SESSION['staff_id']) ? 'disabled' : '' ?>>ระงับ (Hold)
                    </button>
                  <?php else: ?>
                    <button class="btn btn-success btn-sm btn-user-action"
                      data-id="<?= $user['id'] ?>" data-action="user_status" data-status="active"
                      <?= ($user['id'] == $_SESSION['staff_id']) ? 'disabled' : '' ?>>เปิดใช้
                    </button>
                  <?php endif; ?>
                  <!-- Soft Delete -->
                  <button class="btn btn-danger btn-sm btn-user-action"
                    data-id="<?= $user['id'] ?>" data-action="user_status" data-status="deleted"
                    data-confirm="ยืนยันลบผู้ใช้นี้ (Soft Delete)?"
                    <?= ($user['id'] == $_SESSION['staff_id']) ? 'disabled' : '' ?>>ลบ
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Add User -->
<div class="modal modal-blur fade" id="modal-add-user" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">เพิ่มผู้ดูแลใหม่</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="users.php" method="POST">
          <input type="hidden" name="action" value="add">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label required">ชื่อ-นามสกุล (Name)</label>
              <input type="text" class="form-control" name="name" required placeholder="เช่น John Doe">
            </div>
            <div class="mb-3">
              <label class="form-label required">อีเมล (Email)</label>
              <input type="email" class="form-control" name="email" required placeholder="john@yakpho.com">
            </div>
            <div class="mb-3">
              <label class="form-label required">รหัสผ่าน (Password)</label>
              <input type="text" class="form-control" name="password" required placeholder="ตั้งรหัสผ่าน">
            </div>
            <div class="mb-3">
              <div class="form-label">สิทธิ์การเข้าถึง (Role)</div>
              <select class="form-select" name="role">
                <option value="admin">Admin ทั่วไป</option>
                <option value="superadmin">Super Admin</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php include "components/footer.php"; ?>

<script>
// ===== AJAX User Actions =====
function showToast(msg, ok = true) {
  const div = document.createElement('div');
  div.className = `alert alert-${ok ? 'success' : 'danger'} position-fixed`;
  div.style.cssText = 'top:1rem;right:1rem;z-index:9999;min-width:260px;box-shadow:0 4px 16px rgba(0,0,0,.15)';
  div.textContent = msg;
  document.body.appendChild(div);
  setTimeout(() => div.remove(), 3000);
}

document.querySelectorAll('.btn-user-action').forEach(btn => {
  btn.addEventListener('click', async function () {
    const id     = this.dataset.id;
    const action = this.dataset.action;
    const status = this.dataset.status;
    const confirm_msg = this.dataset.confirm;

    if (confirm_msg) {
        let { isConfirmed } = await Swal.fire({
            title: 'ยืนยัน?', text: confirm_msg, icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: 'ตกลง', cancelButtonText: 'ยกเลิก'
        });
        if (!isConfirmed) return;
    }

    const original = this.innerHTML;
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    try {
      const form = new FormData();
      form.append('action', action);
      form.append('id', id);
      form.append('status', status);

      const res  = await fetch('api/action.php', { method: 'POST', body: form });
      const data = await res.json();

      if (data.ok) {
        showToast(data.msg);
        const row = this.closest('tr');

        if (status === 'deleted') {
          // ลบแถวออกจากตาราง
          row.style.transition = 'opacity .3s';
          row.style.opacity = '0';
          setTimeout(() => row.remove(), 300);
        } else {
          // สลับสถานะ Badge + ปุ่ม inline
          const statusCell = row.cells[4];
          const btnCell    = row.querySelector('.btn-list');
          const isActive   = (status === 'active');

          statusCell.innerHTML = isActive
            ? '<span class="badge bg-success me-1"></span> Active'
            : '<span class="badge bg-warning me-1"></span> On Hold';

          btnCell.innerHTML = isActive
            ? `<button class="btn btn-warning btn-sm btn-user-action" data-id="${id}" data-action="user_status" data-status="hold">ระงับ (Hold)</button>
               <button class="btn btn-danger btn-sm btn-user-action" data-id="${id}" data-action="user_status" data-status="deleted" data-confirm="ยืนยันลบ?">ลบ</button>`
            : `<button class="btn btn-success btn-sm btn-user-action" data-id="${id}" data-action="user_status" data-status="active">เปิดใช้</button>
               <button class="btn btn-danger btn-sm btn-user-action" data-id="${id}" data-action="user_status" data-status="deleted" data-confirm="ยืนยันลบ?">ลบ</button>`;

          btnCell.querySelectorAll('.btn-user-action').forEach(b => {
            b.addEventListener('click', arguments.callee.bind(b));
          });
        }
      } else {
        showToast(data.msg || 'เกิดข้อผิดพลาด', false);
        this.disabled = false;
        this.innerHTML = original;
      }
    } catch (e) {
      showToast('ไม่สามารถเชื่อมต่อ Server ได้', false);
      this.disabled = false;
      this.innerHTML = original;
    }
  });
});
</script>
