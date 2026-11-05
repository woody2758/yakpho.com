<?php
require_once "db.php";
require_login();
include "components/header.php";

// Fetch bot processes to map status
$stmt = $db->query("SELECT * FROM bot_processes");
$bot_processes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $bot_processes[$row['bot_key']] = $row;
}

// Fetch all active staff
$stmt = $db->query("SELECT * FROM ai_staff WHERE status='Active' ORDER BY id ASC");
$ai_staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  .bot-card { transition: all .25s ease; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-radius: 12px; overflow: hidden; height: 100%; background: #fff; position: relative; display: flex; flex-direction: column; }
  .bot-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.1); }
  .bot-avatar-bg { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 24px; text-align: center; border-bottom: 1px solid rgba(0,0,0,0.03); }
  .bot-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #fff; }
  .status-indicator { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
  .status-running { background: #2fb344; box-shadow: 0 0 8px #2fb344; animation: bot-pulse 2s infinite; }
  .status-pending { background: #f59e0b; box-shadow: 0 0 8px #f59e0b; animation: bot-pulse 1s infinite; }
  .status-idle { background: #94a3b8; }
  .status-offline { background: #ef4444; }
  @keyframes bot-pulse { 0% { transform: scale(0.9); opacity: 1; } 50% { transform: scale(1.1); opacity: .7; } 100% { transform: scale(0.9); opacity: 1; } }
  .active-signal { animation: avatar-radar 2s infinite linear; border-color: #2fb344 !important; }
  @keyframes avatar-radar { 0% { box-shadow: 0 0 0 0 rgba(47, 179, 68, 0.7); } 70% { box-shadow: 0 0 0 15px rgba(47, 179, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(47, 179, 68, 0); } }
  .smallest { font-size: 11px; }
  .bot-card-actions { position: absolute; top: 12px; right: 12px; z-index: 10; display: flex; gap: 8px; }
  .bot-card-actions .bot-action-btn { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid #eaeaea; box-shadow: 0 2px 4px rgba(0,0,0,0.05); color: #6c757d; transition: all 0.2s; padding: 0; }
  .bot-card-actions .bot-action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
  .bot-card-actions .bot-action-btn svg { width: 16px; height: 16px; margin: 0; stroke-width: 2.5; }
  .bot-card-actions .btn-view:hover { color: #206bc4; border-color: #206bc4; }
  .bot-card-actions .btn-edit:hover { color: #f59e0b; border-color: #f59e0b; }
  .bot-card-actions .btn-delete:hover { color: #d63939; border-color: #d63939; }
  .btn-working { background-color: #e65c00 !important; border-color: #e65c00 !important; color: white !important; }
  .btn-working:hover { background-color: #d35400 !important; border-color: #d35400 !important; }
  @keyframes dotAnim { 0% { opacity: 0.2; } 20% { opacity: 1; } 100% { opacity: 0.2; } }
  .animated-dots span { animation: dotAnim 1.4s infinite; animation-fill-mode: both; font-size: 1.1em; letter-spacing: 2px; }
  .animated-dots span:nth-child(1) { animation-delay: 0.2s; }
  .animated-dots span:nth-child(2) { animation-delay: 0.4s; }
  .animated-dots span:nth-child(3) { animation-delay: 0.6s; }
</style>

<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title text-indigo">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
          ทำเนียบพนักงานเอไอ (AI Staff Directory)
        </h2>
        <p class="text-muted small">คงเดิมทุกชื่อพนักงานที่คุณลูกค้าตกลงไว้ พร้อมคู่มือปฏิบัติงานเชิงลึก (Manual)</p>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <button class="btn btn-primary" onclick="openStaffModal()">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
          เพิ่มพนักงานใหม่
        </button>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <!-- Global API Status -->
  <div id="global-api-status" class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div class="card shadow-sm border-0 bg-dark-lt text-white">
      <div class="card-body py-2 px-3">
        <span id="api-status-dot" class="status-indicator status-offline"></span>
        <span id="api-status-label" class="fw-bold small">API: กำลังเชื่อมต่อ...</span>
      </div>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row row-cards" id="staff-cards-container">
      <?php foreach($ai_staff_list as $staff): 
          $bot_key = $staff['bot_script'];
          $has_process = isset($bot_processes[$bot_key]);
          $bot = $bot_processes[$bot_key] ?? null;
          $isRunning = ($bot && $bot['status'] === 'running');
      ?>
      <div class="col-md-6 col-lg-4 col-xl-3" id="card-staff-<?= $staff['id'] ?>">
        <div class="card bot-card h-100">
          <div class="bot-card-actions">
            <a href="javascript:void(0)" class="bot-action-btn btn-view" 
               onclick="showManual(<?= htmlspecialchars(json_encode($staff['image_path']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($staff['name_th']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($staff['role']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($staff['identity_desc']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($staff['objective']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($staff['details']), ENT_QUOTES, 'UTF-8') ?>)" title="ดูคู่มือ">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
            </a>
            <a href="javascript:void(0)" class="bot-action-btn btn-edit" onclick="editStaff(<?= $staff['id'] ?>)" title="แก้ไข">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
            </a>
            <a href="javascript:void(0)" class="bot-action-btn btn-delete" onclick="deleteStaff(<?= $staff['id'] ?>)" title="ลบ">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
            </a>
          </div>
          <div class="bot-avatar-bg">
            <img src="img/<?= htmlspecialchars($staff['image_path']) ?>" class="bot-avatar mb-2 js-card-img status-<?= $bot_key ?>-avatar <?= $isRunning ? 'active-signal' : '' ?>" alt="Avatar">
            <h3 class="mb-0 fw-bold js-card-title"><?= htmlspecialchars($staff['name_th']) ?></h3>
            <div class="text-muted small mb-2 js-card-subtitle"><?= htmlspecialchars($staff['name_en']) ?></div>
            <div class="badge bg-primary-lt p-2 text-wrap js-card-role" style="font-size: 0.65rem;">ตำแหน่ง: <?= htmlspecialchars($staff['role']) ?></div>
          </div>
          <div class="card-body d-flex flex-column text-center">
            <?php if($has_process): ?>
            <div class="mb-4">
              <div class="d-flex align-items-center justify-content-center mb-2">
                <span class="status-indicator status-<?= $bot_key ?>-dot bg-secondary shadow-sm"></span>
                <span class="fw-bold status-<?= $bot_key ?>-label text-secondary small">IDLE</span>
                <span class="ms-auto small">PID: <span class="status-<?= $bot_key ?>-pid">--</span></span>
              </div>
              <div class="text-muted smallest mt-1">ไฟล์: <code class="text-primary fw-bold js-card-script"><?= $bot['script_name'] ?></code></div>
              <div class="text-muted smallest">เข้างานล่าสุด: <span class="status-<?= $bot_key ?>-time">--</span></div>
            </div>
            <div class="mt-auto btn-list">
              <button class="btn btn-success btn-start w-100 py-2 mb-2 shadow-sm fw-bold" data-bot="<?= $bot_key ?>">เรียกเข้างาน</button>
              <button class="btn btn-working btn-stop w-100 py-2 mb-2 d-none shadow-sm fw-bold" data-bot="<?= $bot_key ?>" title="คลิกเพื่อหยุดการทำงาน (ส่งพักผ่อน)">กำลังทำงาน<span class="animated-dots"><span>.</span><span>.</span><span>.</span></span></button>
              <button class="btn btn-ghost-primary w-100 py-2 border btn-logs" data-bot="<?= $bot_key ?>">ดูรายงาน Logs</button>
            </div>
            <?php else: ?>
            <div class="mb-4">
              <div class="text-muted smallest mt-1">ไฟล์: <code class="text-primary fw-bold js-card-script"><?= $bot_key ?>.py</code></div>
              <div class="alert alert-info mt-3 small py-2">ไม่ได้เชื่อมต่อ Python API</div>
            </div>
            <div class="mt-auto btn-list">
              <button class="btn btn-secondary w-100 py-2 mb-2 shadow-sm fw-bold" disabled>รันระบบไม่ได้</button>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Modal Staff Manage Form (AJAX) -->
<div class="modal modal-blur fade" id="modal-staff" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal-staff-title">เพิ่มพนักงานใหม่</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="staff-form">
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

<!-- Modal Manual -->
<div class="modal modal-blur fade" id="modal-bot-manual" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-primary">
      <div class="modal-header bg-primary-lt"><h5 class="modal-title fw-bold" id="m-thai">คู่มือพนักงาน</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body py-4">
        <div class="text-center mb-4"><img src="" id="m-img" class="bot-avatar mb-3" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" alt="Avatar"><br><div class="badge bg-indigo-lt p-2 mb-2" id="m-role">ตำแหน่ง</div><h2 class="mb-0 text-primary fw-bold" id="m-title">Name</h2></div>
        <div class="mb-3"><label class="form-label fw-bold text-muted small">🎭 Identities:</label><div class="bg-light p-3 rounded-2 fw-bold text-primary" id="m-iden">---</div></div>
        <div class="mb-3"><label class="form-label fw-bold text-muted small">🎯 Objective:</label><div class="p-1 fw-bold" id="m-obj">---</div></div>
        <div class="mb-0 border-top pt-3"><label class="form-label fw-bold text-muted small">📜 Details:</label><div class="text-muted small italic" id="m-det">---</div></div>
      </div>
      <div class="modal-footer p-2"><button type="button" class="btn btn-primary w-100 py-2 fw-bold" data-bs-dismiss="modal">รับทราบคู่มือแล้ว</button></div>
    </div>
  </div>
</div>

<!-- Modal Logs -->
<div class="modal modal-blur fade" id="modal-bot-logs" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">รายงานการปฏิบัติงาน</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body p-0 border-top"><pre id="bot-log-content" class="bg-dark text-success m-0 p-3" style="font-size: 0.75rem; min-height: 250px; max-height: 450px; overflow-y: auto; overflow-x: hidden; white-space: pre-wrap; word-break: break-word; font-family: monospace;"></pre></div>
    </div>
  </div>
</div>

<script>
const BOT_SECRET = 'yakpho2026';
let currentLogBot = '';
let staffModalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    staffModalInstance = new bootstrap.Modal(document.getElementById('modal-staff'));
});

// HR Management Actions
function openStaffModal() {
    document.getElementById('modal-staff-title').innerText = 'เพิ่มพนักงานใหม่';
    document.getElementById('staff-form').reset();
    document.getElementById('form-id').value = '';
    document.getElementById('form-start-date').value = new Date().toISOString().split('T')[0];
    staffModalInstance.show();
}

function editStaff(id) {
    Swal.fire({ title: 'กำลังโหลด...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});
    fetch('api/bot_staff_action.php?action=get&id=' + id)
        .then(r => r.json())
        .then(res => {
            Swal.close();
            if(res.ok) {
                const data = res.data;
                document.getElementById('modal-staff-title').innerText = 'แก้ไขข้อมูล: ' + data.name_th;
                document.getElementById('form-id').value = data.id;
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
                staffModalInstance.show();
            } else {
                Swal.fire('Error', res.msg, 'error');
            }
        });
}

function deleteStaff(id) {
    Swal.fire({
        title: 'ยืนยันเลิกจ้างพนักงาน?', 
        text: 'คุณต้องการย้ายพนักงานคนนี้ลงถังขยะใช่หรือไม่?', 
        icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, เลิกจ้างเลย!', cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
            fetch('api/bot_staff_action.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.ok) {
                        const card = document.getElementById('card-staff-' + id);
                        if(card) {
                            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.9)';
                            setTimeout(() => card.remove(), 300);
                        }
                        Swal.fire('Deleted!', res.msg, 'success');
                    } else Swal.fire('Error', res.msg, 'error');
                });
        }
    });
}

document.getElementById('staff-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'save');
    if (typeof BOT_SECRET !== 'undefined') fd.append('api_key', BOT_SECRET);
    const btn = document.getElementById('form-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';

    fetch('api/bot_staff_action.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = 'บันทึกข้อมูล';
            if(res.ok) {
                staffModalInstance.hide();
                Swal.fire({ title: 'Success', text: 'บันทึกข้อมูลแล้ว กำลังรีเฟรชหน้ากระดาน...', icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
            } else {
                Swal.fire('Error', res.msg, 'error');
            }
        });
});

function showManual(img, thai, role, iden, obj, det) {
    document.getElementById('m-img').src = 'img/' + img;
    document.getElementById('m-thai').innerText = 'คู่มือพนักงาน — ' + thai;
    document.getElementById('m-title').innerText = thai;
    document.getElementById('m-role').innerText = role;
    document.getElementById('m-iden').innerText = iden;
    document.getElementById('m-obj').innerText = obj;
    document.getElementById('m-det').innerText = det;
    new bootstrap.Modal(document.getElementById('modal-bot-manual')).show();
}

/**
 * 🛠️ ROBUST CHECK STATUS 
 */
function checkAllStatus() {
    const timestamp = new Date().getTime();
    fetch(`api/bot_manager.php?action=status_all&api_key=${BOT_SECRET}&v=${timestamp}`)
        .then(r => {
            const apiDot = document.getElementById('api-status-dot');
            const apiLabel = document.getElementById('api-status-label');
            if (r.ok) {
                if (apiDot) apiDot.className = 'status-indicator status-running';
                if (apiLabel) apiLabel.innerText = 'API: พร้อมทำงาน (OK)';
            } else {
                if (apiDot) apiDot.className = 'status-indicator status-offline';
                if (apiLabel) apiLabel.innerText = 'API: ตัดการเชื่อมต่อ';
            }
            return r.json();
        })
        .then(data => {
            if (!data || !data.ok || !data.bots) return;
            Object.keys(data.bots).forEach(bot => {
                try {
                    const botData = data.bots[bot];
                    const btnStartNode = document.querySelector(`.btn-start[data-bot="${bot}"]`);
                    if(!btnStartNode) return;
                    const card = btnStartNode.closest('.card');
                    if (!card) return;

                    const dot = card.querySelector(`.status-${bot}-dot`);
                    const label = card.querySelector(`.status-${bot}-label`);
                    const pidText = card.querySelector(`.status-${bot}-pid`);
                    const timeText = card.querySelector(`.status-${bot}-time`);
                    const btnStart = card.querySelector(`.btn-start[data-bot="${bot}"]`);
                    const btnStop = card.querySelector(`.btn-stop[data-bot="${bot}"]`);
                    const avatar = card.querySelector('.bot-avatar');

                    if (botData.pending_command) {
                        if (botData.pending_command === 'stop') {
                            if (dot) dot.className = `status-indicator status-${bot}-dot status-pending bg-warning`;
                            if (label) { 
                                label.innerText = 'STOPPING...'; 
                                label.className = `fw-bold status-${bot}-label text-warning small`; 
                            }
                            if (btnStart) btnStart.classList.add('d-none');
                            if (btnStop) {
                                btnStop.classList.remove('d-none');
                                btnStop.disabled = true;
                                btnStop.style.setProperty('background-color', '#d97706', 'important');
                                btnStop.style.setProperty('border-color', '#b45309', 'important');
                                btnStop.style.setProperty('color', 'white', 'important');
                                btnStop.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังส่งไปพักผ่อน...';
                            }
                        } else {
                            if (dot) dot.className = `status-indicator status-${bot}-dot status-pending bg-warning`;
                            if (label) { 
                                label.innerText = 'STARTING...'; 
                                label.className = `fw-bold status-${bot}-label text-warning small`; 
                            }
                            if (btnStart) {
                                btnStart.classList.remove('d-none');
                                btnStart.disabled = true;
                                btnStart.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังเรียกงาน...';
                            }
                            if (btnStop) btnStop.classList.add('d-none');
                        }
                    } else if (botData.is_running) {
                        if (avatar) avatar.classList.add('active-signal');
                        if (dot) dot.className = `status-indicator status-${bot}-dot status-running bg-success`;
                        if (label) { 
                            label.innerText = 'ONLINE'; 
                            label.className = `fw-bold status-${bot}-label text-success small`; 
                        }
                        if (pidText) pidText.innerText = botData.pid;
                        if (timeText) timeText.innerText = botData.last_started || '--';
                        if (btnStart) btnStart.classList.add('d-none');
                        if (btnStop) {
                            btnStop.classList.remove('d-none');
                            btnStop.disabled = false;
                            btnStop.style.removeProperty('background-color');
                            btnStop.style.removeProperty('border-color');
                            btnStop.style.removeProperty('color');
                            btnStop.innerHTML = 'กำลังทำงาน<span class="animated-dots"><span>.</span><span>.</span><span>.</span></span>';
                        }
                    } else if (botData.status === 'error_not_found') {
                        if (dot) dot.className = `status-indicator status-${bot}-dot bg-danger`;
                        if (label) { 
                            label.innerText = 'FILE NOT FOUND'; 
                            label.className = `fw-bold status-${bot}-label text-danger small`; 
                        }
                        if (pidText) pidText.innerText = '--';
                        if (btnStart) { 
                            btnStart.classList.remove('d-none', 'btn-success'); 
                            btnStart.classList.add('btn-danger');
                            btnStart.disabled = true; 
                            btnStart.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-triangle me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M12 16v.01" /><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" /></svg>ไม่พบไฟล์';
                        }
                        if (btnStop) btnStop.classList.add('d-none');
                    } else {
                        if (avatar) avatar.classList.remove('active-signal');
                        if (dot) dot.className = `status-indicator status-${bot}-dot status-idle`;
                        if (label) { 
                            label.innerText = 'IDLE'; 
                            label.className = `fw-bold status-${bot}-label text-secondary small`; 
                        }
                        if (pidText) pidText.innerText = '--';
                        if (btnStart) { 
                            btnStart.classList.remove('d-none', 'btn-danger'); 
                            if (!btnStart.classList.contains('spinner-loading')) {
                                btnStart.classList.add('btn-success');
                                btnStart.disabled = false; 
                                btnStart.innerHTML = 'เรียกเข้างาน'; 
                            }
                        }
                        if (btnStop) btnStop.classList.add('d-none');
                    }
                } catch (e) { console.error('Error updating bot: ' + bot, e); }
            });
        });
}

// Start Action (Delegated for dynamically generated elements eventually, but for now standard)
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-start')) {
        const btn = e.target.closest('.btn-start');
        const bot = btn.dataset.bot;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>เรียกงาน...';
        fetch(`api/bot_manager.php?action=start&bot=${bot}&api_key=${BOT_SECRET}`)
            .then(r => r.json()).then(() => setTimeout(checkAllStatus, 1000));
    }
    
    if (e.target.closest('.btn-stop')) {
        const btn = e.target.closest('.btn-stop');
        const bot = btn.dataset.bot;
        Swal.fire({
            title: 'ยืนยันพักการทำงาน?', text: 'ต้องการส่งพนักงานคนนี้ไปพักผ่อนใช่ไหม?', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if(result.isConfirmed) {
                btn.disabled = true;
                btn.classList.remove('btn-working');
                btn.style.setProperty('background-color', '#d97706', 'important');
                btn.style.setProperty('border-color', '#b45309', 'important');
                btn.style.setProperty('color', 'white', 'important');
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังส่งไปพักผ่อน...';
                fetch(`api/bot_manager.php?action=stop&bot=${bot}&api_key=${BOT_SECRET}`)
                    .then(r => r.json()).then(() => setTimeout(checkAllStatus, 1000));
            }
        });
    }

    if (e.target.closest('.btn-logs')) {
        const btn = e.target.closest('.btn-logs');
        currentLogBot = btn.dataset.bot;
        const modalEl = document.getElementById('modal-bot-logs');
        const modal = new bootstrap.Modal(modalEl);
        const content = document.getElementById('bot-log-content');
        content.innerText = 'กำลังโหลด Log...';
        modal.show();
        fetchLogs();
        
        if (logRefreshInterval) clearInterval(logRefreshInterval);
        logRefreshInterval = setInterval(fetchLogs, 2000);
    }
});

// Logs Logic
let logRefreshInterval = null;

const fetchLogs = () => {
    if (!currentLogBot) return;
    const content = document.getElementById('bot-log-content');
    fetch(`api/bot_manager.php?action=logs&bot=${currentLogBot}&api_key=${BOT_SECRET}`)
        .then(r => r.json()).then(data => { 
            const isScrolledToBottom = content.scrollHeight - content.clientHeight <= content.scrollTop + 50;
            const currentScrollTop = content.scrollTop;
            
            content.innerText = data.logs; 
            
            if (isScrolledToBottom) {
                content.scrollTop = content.scrollHeight; 
            } else {
                content.scrollTop = currentScrollTop;
            }
        });
};

document.getElementById('modal-bot-logs').addEventListener('hidden.bs.modal', function () {
    if (logRefreshInterval) clearInterval(logRefreshInterval);
    logRefreshInterval = null;
});

setInterval(checkAllStatus, 5000);
checkAllStatus();
</script>

<?php include "components/footer.php"; ?>
