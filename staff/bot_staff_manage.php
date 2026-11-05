<?php
require_once "db.php";
require_login();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Helper function for image upload
    function handleImageUpload($file) {
        if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'bot_' . uniqid() . '.' . $ext;
            $destination = 'img/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                return $filename;
            }
        }
        return null;
    }

    if ($action === 'add') {
        $bot_script = trim($_POST['bot_script']);
        $name_en = trim($_POST['name_en']);
        $name_th = trim($_POST['name_th']);
        $department = trim($_POST['department']);
        $role = trim($_POST['role']);
        $identity_desc = trim($_POST['identity_desc']);
        $objective = trim($_POST['objective']);
        $details = trim($_POST['details']);
        $start_date = trim($_POST['start_date']) ?: date('Y-m-d');
        $status = trim($_POST['status']);
        
        $image_path = handleImageUpload($_FILES['image']) ?? 'bot_default.png';

        $stmt = $db->prepare("INSERT INTO ai_staff (bot_script, name_en, name_th, department, role, identity_desc, objective, details, image_path, start_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$bot_script, $name_en, $name_th, $department, $role, $identity_desc, $objective, $details, $image_path, $start_date, $status]);
            $_SESSION['alert'] = "เพิ่มพนักงานใหม่สำเร็จ";
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
        header("Location: bot_staff_manage.php"); exit;
    } 
    elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $bot_script = trim($_POST['bot_script']);
        $name_en = trim($_POST['name_en']);
        $name_th = trim($_POST['name_th']);
        $department = trim($_POST['department']);
        $role = trim($_POST['role']);
        $identity_desc = trim($_POST['identity_desc']);
        $objective = trim($_POST['objective']);
        $details = trim($_POST['details']);
        $start_date = trim($_POST['start_date']);
        $status = trim($_POST['status']);
        
        $new_image = handleImageUpload($_FILES['image']);
        
        if ($new_image) {
            $stmt = $db->prepare("UPDATE ai_staff SET bot_script=?, name_en=?, name_th=?, department=?, role=?, identity_desc=?, objective=?, details=?, image_path=?, start_date=?, status=? WHERE id=?");
            $stmt->execute([$bot_script, $name_en, $name_th, $department, $role, $identity_desc, $objective, $details, $new_image, $start_date, $status, $id]);
        } else {
            $stmt = $db->prepare("UPDATE ai_staff SET bot_script=?, name_en=?, name_th=?, department=?, role=?, identity_desc=?, objective=?, details=?, start_date=?, status=? WHERE id=?");
            $stmt->execute([$bot_script, $name_en, $name_th, $department, $role, $identity_desc, $objective, $details, $start_date, $status, $id]);
        }
        $_SESSION['alert'] = "อัปเดตข้อมูลพนักงานสำเร็จ";
        header("Location: bot_staff_manage.php"); exit;
    }
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE ai_staff SET status='Deleted' WHERE id=?")->execute([$id]);
        $_SESSION['alert'] = "ย้ายพนักงานลงถังขยะเรียบร้อยแล้ว";
        header("Location: bot_staff_manage.php"); exit;
    }
    elseif ($action === 'restore') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE ai_staff SET status='Inactive' WHERE id=?")->execute([$id]);
        $_SESSION['alert'] = "กู้คืนพนักงานกลับมาแล้ว (สถานะ Inactive)";
        header("Location: bot_staff_manage.php?tab=recycle"); exit;
    }
    elseif ($action === 'hard_delete') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM ai_staff WHERE id=?")->execute([$id]);
        $_SESSION['alert'] = "ลบพนักงานออกจากระบบถาวรเรียบร้อยแล้ว";
        header("Location: bot_staff_manage.php?tab=recycle"); exit;
    }
}

// Fetch active/inactive staff
$stmt = $db->query("SELECT * FROM ai_staff WHERE status != 'Deleted' ORDER BY department, name_en");
$staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch deleted staff
$stmt2 = $db->query("SELECT * FROM ai_staff WHERE status = 'Deleted' ORDER BY name_en");
$recycle_list = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$active_tab = $_GET['tab'] ?? 'active';

include "components/header.php"; 
?>

<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users-cog text-indigo" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h2.5" /><path d="M19.001 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19.001 15.5v1.5" /><path d="M19.001 21v1.5" /><path d="M22.032 17.25l-1.299 .75" /><path d="M17.27 20l-1.3 .75" /><path d="M15.97 17.25l1.3 .75" /><path d="M20.733 20l1.3 .75" /></svg>
          ระบบจัดการพนักงาน (AI Staff HR)
        </h2>
        <p class="text-muted small">เพิ่ม แก้ไข เลิกจ้าง และดูประวัติพนักงาน AI ในองค์กร</p>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-staff" onclick="openAddModal()">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
          เพิ่มพนักงานใหม่
        </button>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid">
    
    <?php if(isset($_SESSION['alert'])): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex"><div><?= $_SESSION['alert'] ?></div></div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <div class="d-flex"><div><?= $_SESSION['error'] ?></div></div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
      <div class="card-header border-bottom-0 pb-0">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
          <li class="nav-item">
            <a href="#tabs-active" class="nav-link <?= $active_tab === 'active' ? 'active' : '' ?>" data-bs-toggle="tab">พนักงานปัจจุบัน (<?= count($staff_list) ?>)</a>
          </li>
          <li class="nav-item">
            <a href="#tabs-recycle" class="nav-link <?= $active_tab === 'recycle' ? 'active' : '' ?>" data-bs-toggle="tab">พนักงานที่ถูกเลิกจ้าง (<?= count($recycle_list) ?>)</a>
          </li>
        </ul>
      </div>
      <div class="card-body p-0">
        <div class="tab-content">
          <!-- Active Tab -->
          <div class="tab-pane <?= $active_tab === 'active' ? 'active show' : '' ?>" id="tabs-active">
            <div class="table-responsive">
              <table class="table card-table table-vcenter table-hover text-nowrap">
                <thead>
                  <tr>
                    <th class="w-1">ID</th>
                    <th>ภาพโปรไฟล์</th>
                    <th>ชื่อพนักงาน</th>
                    <th>สคริปต์หลัก</th>
                    <th>แผนก / หน้าที่</th>
                    <th>สถานะ</th>
                    <th>เริ่มงาน</th>
                    <th>จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($staff_list as $s): ?>
                  <tr>
                    <td><span class="text-muted"><?= $s['id'] ?></span></td>
                    <td><img src="img/<?= htmlspecialchars($s['image_path']) ?>" class="avatar avatar-sm rounded-circle" alt=""></td>
                    <td>
                        <div class="font-weight-bold"><?= htmlspecialchars($s['name_th']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($s['name_en']) ?></div>
                    </td>
                    <td><span class="badge bg-blue-lt"><?= htmlspecialchars($s['bot_script']) ?></span></td>
                    <td>
                        <div><?= htmlspecialchars($s['department']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($s['role']) ?></div>
                    </td>
                    <td>
                        <?php if($s['status'] == 'Active'): ?>
                            <span class="badge bg-success text-white">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary text-white">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted"><?= date('d M Y', strtotime($s['start_date'])) ?></td>
                    <td>
                      <div class="btn-list flex-nowrap">
                        <button class="btn btn-outline-primary btn-sm" onclick='openEditModal(<?= json_encode($s) ?>)'>แก้ไข</button>
                        <form action="bot_staff_manage.php" method="POST" class="d-inline" onsubmit="return confirm('ยืนยันย้ายพนักงานคนนี้ลงถังขยะ?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">เลิกจ้าง</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if(empty($staff_list)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">ไม่พบข้อมูลพนักงาน</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Recycle Bin Tab -->
          <div class="tab-pane <?= $active_tab === 'recycle' ? 'active show' : '' ?>" id="tabs-recycle">
            <div class="table-responsive">
              <table class="table card-table table-vcenter table-hover text-nowrap">
                <thead>
                  <tr>
                    <th class="w-1">ID</th>
                    <th>ชื่อพนักงาน</th>
                    <th>แผนก / หน้าที่</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($recycle_list as $s): ?>
                  <tr>
                    <td><span class="text-muted"><?= $s['id'] ?></span></td>
                    <td>
                        <div class="font-weight-bold text-decoration-line-through"><?= htmlspecialchars($s['name_th']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($s['name_en']) ?></div>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($s['department']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($s['role']) ?></div>
                    </td>
                    <td><span class="badge bg-danger text-white">Deleted</span></td>
                    <td>
                      <div class="btn-list flex-nowrap">
                        <form action="bot_staff_manage.php" method="POST" class="d-inline">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-outline-success btn-sm">กู้คืน (Restore)</button>
                        </form>
                        <form action="bot_staff_manage.php" method="POST" class="d-inline" onsubmit="return confirm('คำเตือน: การลบถาวรจะไม่สามารถกู้คืนได้ ยืนยันใช่ไหม?')">
                            <input type="hidden" name="action" value="hard_delete">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">ลบถาวร</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if(empty($recycle_list)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">ถังขยะว่างเปล่า</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Staff Form -->
<div class="modal modal-blur fade" id="modal-staff" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-staff-title">เพิ่มพนักงานใหม่</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="bot_staff_manage.php" method="POST" enctype="multipart/form-data" id="staff-form">
          <input type="hidden" name="action" id="form-action" value="add">
          <input type="hidden" name="id" id="form-id" value="">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label required">ชื่อภาษาไทย (แสดงผลหลัก)</label>
                <input type="text" class="form-control" name="name_th" id="form-name-th" required placeholder="เช่น คุณลินดา (Linda)">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label required">ชื่อภาษาอังกฤษ</label>
                <input type="text" class="form-control" name="name_en" id="form-name-en" required placeholder="เช่น Linda J. Robinson">
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label required">ชื่อไฟล์สคริปต์ / Bot Key</label>
                <input type="text" class="form-control" name="bot_script" id="form-bot-script" required placeholder="เช่น fb_group_scraper">
                <small class="text-muted">ชื่อโค้ดที่รัน (ไม่ต้องใส่ .py)</small>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label required">สถานะ</label>
                <select class="form-select" name="status" id="form-status" required>
                    <option value="Active">Active (กำลังปฏิบัติงาน)</option>
                    <option value="Inactive">Inactive (พักงานชั่วคราว)</option>
                </select>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label required">แผนก (Department)</label>
                <input type="text" class="form-control" name="department" id="form-department" required value="Social Automation" placeholder="เช่น Social Automation">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label required">ตำแหน่งหน้าที่ (Role)</label>
                <input type="text" class="form-control" name="role" id="form-role" required placeholder="เช่น International Group Scout">
              </div>

              <div class="col-12 mb-3">
                <label class="form-label required">ตัวตนที่ใช้ (Identity Description)</label>
                <input type="text" class="form-control" name="identity_desc" id="form-identity" required placeholder="เช่น FB Profile: YakPho.Aroma">
              </div>

              <div class="col-12 mb-3">
                <label class="form-label required">วัตถุประสงค์ (Objective)</label>
                <input type="text" class="form-control" name="objective" id="form-objective" required placeholder="เป้าหมายสั้นๆ ของพนักงานคนนี้">
              </div>

              <div class="col-12 mb-3">
                <label class="form-label required">รายละเอียดการทำงานแบบเต็ม (Details)</label>
                <textarea class="form-control" name="details" id="form-details" rows="3" required></textarea>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label required">วันที่เริ่มงาน</label>
                <input type="date" class="form-control" name="start_date" id="form-start-date" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">อัปโหลดภาพโปรไฟล์ใหม่</label>
                <input type="file" class="form-control" name="image" accept="image/*">
                <small class="text-muted" id="form-image-help">หากไม่เลือก จะใช้รูปเริ่มต้น หรือรูปเดิม</small>
              </div>

            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary" id="form-submit-btn">บันทึกข้อมูล</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modal-staff-title').innerText = 'เพิ่มพนักงานใหม่';
    document.getElementById('form-action').value = 'add';
    document.getElementById('form-id').value = '';
    document.getElementById('form-submit-btn').innerText = 'เพิ่มพนักงานใหม่';
    document.getElementById('staff-form').reset();
    document.getElementById('form-start-date').value = new Date().toISOString().split('T')[0];
}

function openEditModal(data) {
    document.getElementById('modal-staff-title').innerText = 'แก้ไขข้อมูล: ' + data.name_th;
    document.getElementById('form-action').value = 'edit';
    document.getElementById('form-id').value = data.id;
    document.getElementById('form-submit-btn').innerText = 'บันทึกการแก้ไข';
    
    document.getElementById('form-name-th').value = data.name_th;
    document.getElementById('form-name-en').value = data.name_en;
    document.getElementById('form-bot-script').value = data.bot_script;
    document.getElementById('form-status').value = data.status;
    document.getElementById('form-department').value = data.department;
    document.getElementById('form-role').value = data.role;
    document.getElementById('form-identity').value = data.identity_desc;
    document.getElementById('form-objective').value = data.objective;
    document.getElementById('form-details').value = data.details;
    document.getElementById('form-start-date').value = data.start_date;
    
    var modal = new bootstrap.Modal(document.getElementById('modal-staff'));
    modal.show();
}
</script>

<?php include "components/footer.php"; ?>
