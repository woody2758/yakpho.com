<?php
require_once "db.php";
require_login();

// ── จัดการ Migration แบบ On-the-fly (สร้างคอลัมน์ถ้ายังไม่มี) ──
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN post_quality VARCHAR(20) NOT NULL DEFAULT 'Good'"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN target_zone VARCHAR(50) NOT NULL DEFAULT 'Uncategorized'"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN strategy_marker VARCHAR(20) NOT NULL DEFAULT 'Observe'"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN ai_reason TEXT"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN post_ready TINYINT(1) NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN campaign_tag VARCHAR(255) NULL"); } catch (PDOException $e) {}

// ── ค้นหา / กรอง ──
$search  = trim($_GET['q'] ?? '');
$status_f = trim($_GET['status'] ?? '');
$cat_f    = trim($_GET['cat'] ?? '');
$quality_f = trim($_GET['quality'] ?? '');
$zone_f   = trim($_GET['zone'] ?? '');
$sort     = trim($_GET['sort'] ?? 'newest');
$strategy_f = trim($_GET['strategy'] ?? '');

// ── พารามิเตอร์ Pagination ──
$limit = 50;
$page  = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$where = ['1=1'];
$params = []
























                <strong>ความหมาย:</strong> กลุ่มปิด/กลุ่มทางการ/ระเบียบจัด<br>

























































include "components/header.php";
?>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">Facebook Groups Management
          <span class="badge bg-primary ms-2" style="font-size:.7rem"><?= number_format($total_rows) ?> กลุ่ม</span>
        </h2>
        <div class="text-muted mt-1 small">แผนก 5 · Social Automation — ค้นหาและจัดการกลุ่มเป้าหมายสำหรับโพสต์โปรโมท</div>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <div class="d-flex align-items-center">
          <div class="d-none d-md-flex align-items-center me-3 border-end pe-3">
            <div class="text-end me-2">
              <div class="small fw-bold text-indigo">ทีม AI รับผิดชอบ:</div>
              <div class="text-muted" style="font-size: 0.65rem;">Linda / Dr.Janie / Matt</div>
            </div>
            <div class="avatar-list avatar-list-stacked">
              <span class="avatar avatar-sm rounded-circle border border-2 border-white shadow-sm" style="background-image: url(img/bot_fb_group.png)" title="คุณลินดา (Scout & Scraper)" data-bs-toggle="tooltip"></span>
              <span class="avatar avatar-sm rounded-circle border border-2 border-white shadow-sm" style














































              <option value="Blocked"    <?= $status_f=='Blocked'?'selected':'' ?>>🚫 Blocked</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1">หมวดหมู่/แท็ก</label>
            <input type="text" name="cat" class="form-control form-control-sm" placeholder="เช่น Spa, Owner..." value="<?= htmlspecialchars($cat_f) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1">เรียงตาม</label>
            <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="score"   <?= $sort=='score'?'selected':'' ?>>🔝 คะแนนสูงสุด</option>
              <option value="members_desc" <?= ($sort=='members_desc' || $sort=='members')?'selected':'' ?>>👥 สมาชิก (มาก ➔ น้อย)</option>
              <option value="members_asc"  <?= $sort=='members_asc'?'selected':'' ?>>👥 สมาชิก (น้อย ➔ มาก)</option>
              <option value="newest"  <?= $sort=='newest'?'selected':'' ?>>🆕 เพิ่มล่าสุด</option>
              <option value="updated" <?= $sort=='updated'?'selected':'' ?>>🔄 อัปเดตล่าสุด</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1">AI Strategy</label>
            <select name="strategy" class="form-select form-select-sm">
              <option value="">— ทั้งหมด —</option>



















































































































































































































































































































































</div>

<!-- AI Draft Modal -->
<div class="modal modal-blur fade" id="modal-ai-draft" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">AI Introduction Draft (ร่างโปรดเลือก)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="ai-draft-content" class="p-3 bg-light rounded" style="white-space: pre-wrap; min-height: 100px;">
          <div class="text-center py-4">
            <span class="spinner-border spinner-border-sm text-muted"></span> 
            กำลังประมวลผลด้วย AI...
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">ปิด</button>
        <button type="button" class="btn btn-primary" onclick="copyDraft()">คัดลอกข้อความ</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Manual Group Modal -->
<div class="modal modal-blur fade" id="modal-manual-group" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const originalText = this.innerText;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
        fetch('api/update_group_status.php?api_key=yakpho2026', {
            method: 'POST',
          <label class="form-label">ชื่อกลุ่ม <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="group_name" required>
            if(data.ok) {
                // อัปเดต UI ทันทีไม่ต้องรีเฟรช
          <label class="form-label">URL กลุ่ม (https://facebook.com/groups/...) <span class="text-danger">*</span></label>
          <input type="url" class="form-control" name="group_url" required>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">จำนวนสมาชิก</label>
              <input type="number" class="form-control" name="member_count" value="0">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">ประเภท (Privacy)</label>
              <select name="privacy_type" class="form-select">
                <option value="Public">Public (สาธารณะ)</option>
                <option value="Private">Private (ส่วนตัว)</option>
                <option value="Hidden">Hidden (ซ่อน)</option>
              </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">หมวดหมู่/แท็ก (Category)</label>





















































                <strong>กลยุทธ์:</strong> <u>ห้ามขายตรงๆ</u> ให้เน้นเข้าไปตอบคอมเมนต์ให้คำปรึกษา เพื่อสร้างความน่าเชื่อถือเท่านั้น
              </p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 bg-white rounded border-start border-secondary border-4 shadow-sm h-100">
              <div class="d-flex align-items-center mb-2">
                <span class="badge bg-secondary me-2">⚪</span>
                <h4 class="m-0 text-secondary">Case White: Blacklist</h4>
              </div>
              <p class="small text-muted mb-0">
                <strong>ความหมาย:</strong> กลุ่มขยะ/สแปม/กลุ่มต่างชาติ/ไม่ตรงกลุ่ม<br>
                <strong>กลยุทธ์:</strong> ปล่อยผ่านหรือลบออกจากระบบ บอทจะไม่นำกลุ่มนี้มาประมวลผลต่อ
              </p>
            </div>
          </div>
        </div>
        <div class="mt-3 small text-muted text-center italic">
          * กลยุทธ์เหล่านี้ถูกวิเคราะห์โดย Gemini AI โดยอิงจาก ชื่อกลุ่ม, สมาชิก และข้อมูลบริบท เพื่อช่วยให้ทีมงานทำงานได้รวดเร



























                        this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>';
                    } else {
                        this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-muted" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /></svg>';
                    }
                }
                else { alert(res.msg); this.innerHTML = originalHTML; }
            } catch (e) { alert('Connection Error'); this.innerHTML = originalHTML; } finally { this.style.pointerEvents = 'auto'; }
        });
    });

    // ฟังก์ชันช่วยอัปเดตตัวเลขบนแท็บแบบ Real-time
    function updateTabCounts(oldQuality, newQuality) {
        const badges = {
            'active': document.getElementById('count-active'),
            'Selected': document.getElementById('count-selected'),
            'Best': document.getElementById('count-best'),
            'Good': document.getElementById('count-good'),
            'Poor': document.getElementById('count-poor'),
            'Delet



























































































































































































































































                text: `คุณกำลังจะจัดการข้อมูลจำนวน ${selectedIds.length} รายการ ใช่หรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ยืนยันทำรายการ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#3085d6'
            });

            if (!isConfirmed) return;

            btnApplyBulk.disabled = true;
            btnApplyBulk.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const response = await fetch('api/fb_bulk_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: action, ids: selectedIds })
                });
                
                const res = await response.json();
                if (res.ok) {
                    Swal.fire('สำเร็จ!', `จัดการกลุ่มจำนวน ${res.count} รายการเรียบร้อยแล้ว`, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.msg, 'error');
                }
            } catch (e) {
                Swal.fire('เกิดข้อผิดพลาด', 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้', 'error');
            } finally {



































































































                text: `คุณกำลังจะจัดการข้อมูลจำนวน ${selectedIds.length} รายการ ใช่หรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ยืนยันทำรายการ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#3085d6'
            });

            if (!isConfirmed) return;

            btnApplyBulk.disabled = true;
            btnApplyBulk.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const response = await fetch('api/fb_bulk_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: action, ids: selectedIds })
                });
                
                const res = await response.json();
                if (res.ok) {
                    Swal.fire('สำเร็จ!', `จัดการกลุ่มจำนวน ${res.count} รายการเรียบร้อยแล้ว`, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.msg, 'error');
                }
            } catch (e) {
                Swal.fire('เกิดข้อผิดพลาด', 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้', 'error');
            } finally {
                btnApplyBulk.disabled = false;
                btnApplyBulk.innerHTML = 'นำไปใช้';
            }
        });
    }

    // Inline Editing for Score, Members, Privacy
    document.querySelectorAll('.inline-edit-field').forEach(el => {
        const saveField = async (inputEl) => {
            const id = inputEl.dataset.id;
            const field = inputEl.dataset.field;
            let value = inputEl.value;
            // Remove commas for member count before saving
            if(field === 'member_count') value = value.replace(/,/g, '');
            
            inputEl.style.opacity = '0.5';
            try {
                const response = await fetch('api/update_group_info.php?api_key=yakpho2026', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, field: field, value: value })
                });
                const res = await response.json();
                if (res.ok) {
                    inputEl.style.opacity = '1';
                    if(field === 'privacy_type') {
                        inputEl.className = `form-select form-select-sm badge bg-${value === 'Public' ? 'azure' : 'purple'}-lt fw-bold border-0 inline-edit-field`;
                    }
                } else {
                    alert(res.msg);
                    inputEl.style.opacity = '1';
                }
            } catch (e) {
                alert('Connection Error');
                inputEl.style.opacity = '1';
            }
        };

        // For select inputs, use 'change'
        if(el.tagName === 'SELECT') {
            el.addEventListener('change', () => saveField(el));
        } else {
            // For text/number inputs, use 'blur' and 'keypress' (enter)
            el.addEventListener('blur', () => saveField(el));
            el.addEventListener('keypress', (e) => {
                if(e.key === 'Enter') {
                    e.preventDefault();
                    el.blur(); // Trigger blur to save
                }
            });
        }
    });

    // Manual Add Group Form Submit
    const formManualGroup = document.getElementById('form-manual-group');
    if (formManualGroup) {
        formManualGroup.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-manual');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';
            btn.disabled = true;

            const fd = new FormData(this);
            try {
                const response = await fetch('api/add_manual_group.php', {
                    method: 'POST',
                    body: fd
                });
                const res = await response.json();
                if (res.ok) {
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('modal-manual-group')).hide();
                    // Prepend new row to table
                    const tbody = document.getElementById('group-table-body');
                    if (tbody) {
                        tbody.insertAdjacentHTML('afterbegin', res.html);
                    }
                    // Reset form
                    this.reset();
                    // Alert
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: 'เพิ่มกลุ่มแบบ Manual เรียบร้อยแล้ว',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Update count
                    const activeCount = document.getElementById('count-active');
                    if (activeCount) {
                        activeCount.innerText = (parseInt(activeCount.innerText.replace(/,/g, '')) + 1).toLocaleString();
                    }
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.msg, 'error');
                }
            } catch (err) {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }

});
</script>

