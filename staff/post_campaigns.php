<?php
require_once "db.php";
require_login();

// ── Handle AJAX Requests ──
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];
    
    // API: Create new campaign (Name only)
    if ($action === 'create_campaign') {
        $name = $_POST['campaign_name'] ?? '';
        
        if(empty($name)) {
            echo json_encode(['ok' => false, 'msg' => 'กรุณาระบุชื่อแคมเปญ']);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO fb_campaigns (name, status, created_at) VALUES (?, 'draft', NOW())");
            $stmt->execute([$name]);
            $campaign_id = $db->lastInsertId();
            
            echo json_encode(['ok' => true, 'msg' => 'สร้างแคมเปญเรียบร้อยแล้ว!', 'id' => $campaign_id]);
            
        } catch(Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    // API: Edit campaign name
    if ($action === 'edit_campaign') {
        $id = $_POST['campaign_id'] ?? 0;
        $name = $_POST['campaign_name'] ?? '';
        
        if(empty($name) || empty($id)) {
            echo json_encode(['ok' => false, 'msg' => 'ข้อมูลไม่ครบถ้วน']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE fb_campaigns SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            echo json_encode(['ok' => true, 'msg' => 'แก้ไขชื่อแคมเปญเรียบร้อยแล้ว!']);
        } catch(Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // API: Repost campaign (Queue all targets that are not already pending)
    if ($action === 'repost_campaign') {
        $campaign_id = $_POST['campaign_id'] ?? 0;
        try {
            $db->beginTransaction();
            
            // Re-generate schedules
            $contents = $db->prepare("SELECT id FROM fb_campaign_contents WHERE campaign_id = ?");
            $contents->execute([$campaign_id]);
            $content_ids = $contents->fetchAll(PDO::FETCH_COLUMN);
            
            if(empty($content_ids)) {
                throw new Exception("ไม่สามารถเริ่มแคมเปญได้: ยังไม่มีเนื้อหา");
            }
            
            // Get targets that DO NOT currently have a 'pending' schedule for this campaign
            $targets = $db->prepare("
                SELECT fb_group_id 
                FROM fb_campaign_targets 
                WHERE campaign_id = ? 
                AND fb_group_id NOT IN (
                    SELECT group_id FROM fb_campaign_schedules 
                    WHERE campaign_id = ? AND status = 'pending'
                )
            ");
            $targets->execute([$campaign_id, $campaign_id]);
            $group_ids = $targets->fetchAll(PDO::FETCH_COLUMN);
            
            if(!empty($group_ids)) {
                $stmt_schedule = $db->prepare("INSERT INTO fb_campaign_schedules (campaign_id, group_id, content_id, scheduled_time, status) VALUES (?, ?, ?, NOW(), 'pending')");
                $content_count = count($content_ids);
                foreach($group_ids as $index => $gid) {
                    $selected_content_id = $content_ids[$index % $content_count];
                    $stmt_schedule->execute([$campaign_id, $gid, $selected_content_id]);
                }
            }
            
            // Auto-pause other active campaigns and set this one to active
            $db->prepare("UPDATE fb_campaigns SET status = 'paused' WHERE status = 'active' AND id != ?")->execute([$campaign_id]);
            $db->prepare("UPDATE fb_campaigns SET status = 'active' WHERE id = ?")->execute([$campaign_id]);
            
            $db->commit();
            echo json_encode(['ok' => true, 'msg' => 'สั่งโพสต์ซ้ำเรียบร้อยแล้ว กลุ่มที่ยังไม่รอคิวจะถูกสร้างคิวใหม่ทั้งหมด']);
        } catch(Exception $e) {
            $db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }
    // API: Toggle Campaign Status
    if ($action === 'toggle_status') {
        $id = $_POST['campaign_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        try {
            if ($status === 'active') {
                $db->prepare("UPDATE fb_campaigns SET status = 'paused' WHERE status = 'active' AND id != ?")->execute([$id]);
            }
            $stmt = $db->prepare("UPDATE fb_campaigns SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['ok' => true]);
        } catch(Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// ── Load Dashboard Data ──
$campaigns = $db->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM fb_campaign_targets WHERE campaign_id = c.id) as total_groups,
           (SELECT COUNT(*) FROM fb_campaign_schedules WHERE campaign_id = c.id AND status = 'success') as posted_count,
           (SELECT SUM(click_count) FROM fb_link_tracking WHERE campaign_id = c.id) as total_clicks
    FROM fb_campaigns c
    ORDER BY c.id DESC
")->fetchAll();

include "components/header.php";
?>

<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M3 12h6" /><path d="M15 12h6" /><path d="M12 3v6" /><path d="M12 15v6" /></svg>
          Facebook Ads / Campaigns
        </h2>
        <div class="text-muted mt-1 small">จัดการแคมเปญโฆษณา และ A/B Testing สำหรับดึงดูดลูกค้า</div>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
          <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#modal-campaign">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
            สร้างแคมเปญใหม่
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid">
    
    <div class="row row-cards">
      <?php foreach($campaigns as $c): ?>
      <div class="col-md-4">
        <div class="card <?= $c['status'] == 'active' ? 'border-success' : '' ?>">
          <div class="card-body">
            <div class="d-flex align-items-center mb-3">
              <h3 class="card-title m-0 align-items-center d-flex">
                <?= htmlspecialchars($c['name']) ?>
                <a href="#" class="ms-2 text-muted" onclick="openEditModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')" title="แก้ไขชื่อแคมเปญ">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
                </a>
              </h3>
              <div class="ms-auto">
                <?php if($c['status'] == 'active'): ?>
                  <span class="badge bg-success" style="cursor: pointer;" onclick="toggleCampaignStatus(<?= $c['id'] ?>, 'paused')" title="คลิกเพื่อหยุดการทำงานชั่วคราว">กำลังทำงาน (ACTIVE)</span>
                <?php elseif($c['status'] == 'paused'): ?>
                  <span class="badge bg-warning" style="cursor: pointer;" onclick="toggleCampaignStatus(<?= $c['id'] ?>, 'active')" title="คลิกเพื่อกลับมาทำงานต่อ">หยุดพัก (PAUSED)</span>
                <?php else: ?>
                  <span class="badge bg-secondary">ฉบับร่าง (DRAFT)</span>
                <?php endif; ?>
              </div>
            </div>
            
            <div class="row g-2 align-items-center mb-3">
              <div class="col-auto">
                <span class="avatar avatar-sm bg-blue-lt">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1" /><path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M17 10h2a2 2 0 0 1 2 2v1" /><path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M3 13v-1a2 2 0 0 1 2 -2h2" /></svg>
                </span>
              </div>
              <div class="col">
                <div class="font-weight-medium">เป้าหมาย <?= number_format($c['total_groups']) ?> กลุ่ม</div>
                <div class="text-muted small">โพสต์แล้ว <?= number_format($c['posted_count']) ?> กลุ่ม</div>
              </div>
            </div>

            <div class="row g-2 align-items-center">
              <div class="col-auto">
                <span class="avatar avatar-sm bg-purple-lt">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z" /><path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /></svg>
                </span>
              </div>
              <div class="col">
                <div class="font-weight-medium text-purple"><?= number_format($c['total_clicks'] ?: 0) ?> Clicks</div>
                <div class="text-muted small">ผลลัพธ์จากลิงก์ย่อ</div>
              </div>
            </div>

          </div>
          <div class="card-footer p-2 text-end">
            <button onclick="repostCampaign(<?= $c['id'] ?>)" class="btn btn-sm btn-outline-success me-2" title="เริ่มโพสต์แคมเปญนี้ใหม่อีกครั้งไปยังทุกกลุ่ม">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
              โพสต์ซ้ำอีกครั้ง
            </button>
            <a href="manage_campaign.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">ตั้งค่าเนื้อหา / สั่งเริ่มแคมเปญ</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($campaigns)): ?>
      <div class="col-12 text-center py-5 text-muted">
         ยังไม่มีแคมเปญ กรุณากดปุ่มสร้างแคมเปญใหม่ด้านบนขวา
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal: Create Campaign Wizard -->
<div class="modal modal-blur fade" id="modal-campaign" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">✨ สร้างแคมเปญใหม่</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label required">ชื่อแคมเปญ</label>
            <input type="text" id="cp_name" class="form-control" placeholder="เช่น โปรโมชั่นสงกรานต์ 2026">
            <small class="text-muted mt-1">ตั้งชื่อแคมเปญก่อน หลังจากนั้นระบบจะพาไปหน้าตั้งค่ารูปภาพ/กลุ่มเป้าหมาย</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary" onclick="createCampaign(this)">สร้างแคมเปญ</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Edit Campaign -->
<div class="modal modal-blur fade" id="modal-edit-campaign" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">✏️ แก้ไขชื่อแคมเปญ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label required">ชื่อแคมเปญ</label>
            <input type="hidden" id="edit_cp_id">
            <input type="text" id="edit_cp_name" class="form-control" placeholder="ชื่อแคมเปญใหม่">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary" onclick="saveEditCampaign(this)">บันทึกการแก้ไข</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function createCampaign(btn) {
        const name = document.getElementById('cp_name').value.trim();
        if(!name) {
            Swal.fire('Error', 'กรุณาระบุชื่อแคมเปญ', 'error');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> กำลังสร้าง...';
        
        const fd = new FormData();
        fd.append('ajax_action', 'create_campaign');
        fd.append('campaign_name', name);
        
        fetch('post_campaigns.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.ok) {
                window.location.href = 'manage_campaign.php?id=' + res.id;
            } else {
                Swal.fire('Error', res.msg, 'error');
                btn.disabled = false;
                btn.innerHTML = 'สร้างแคมเปญ';
            }
        })
        .catch(e => {
            Swal.fire('Error', e.message, 'error');
            btn.disabled = false;
            btn.innerHTML = 'สร้างแคมเปญ';
        });
    }

    function openEditModal(id, currentName) {
        document.getElementById('edit_cp_id').value = id;
        document.getElementById('edit_cp_name').value = currentName;
        new bootstrap.Modal(document.getElementById('modal-edit-campaign')).show();
    }

    function saveEditCampaign(btn) {
        const id = document.getElementById('edit_cp_id').value;
        const name = document.getElementById('edit_cp_name').value.trim();
        
        if(!name) {
            Swal.fire('Error', 'กรุณาระบุชื่อแคมเปญ', 'error');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> กำลังบันทึก...';
        
        const fd = new FormData();
        fd.append('ajax_action', 'edit_campaign');
        fd.append('campaign_id', id);
        fd.append('campaign_name', name);
        
        fetch('post_campaigns.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.ok) {
                location.reload();
            } else {
                Swal.fire('Error', res.msg, 'error');
                btn.disabled = false;
                btn.innerHTML = 'บันทึกการแก้ไข';
            }
        })
        .catch(e => {
            Swal.fire('Error', e.message, 'error');
            btn.disabled = false;
            btn.innerHTML = 'บันทึกการแก้ไข';
        });
    }

    function repostCampaign(id) {
        Swal.fire({
            title: 'โพสต์แคมเปญนี้อีกครั้ง?',
            text: "ระบบจะล้างประวัติการโพสต์ของแคมเปญนี้และสั่งให้บอทเริ่มโพสต์ใหม่ทั้งหมด คุณแน่ใจหรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, โพสต์ซ้ำเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({title: 'กำลังประมวลผล...', allowOutsideClick: false});
                Swal.showLoading();
                
                const fd = new FormData();
                fd.append('ajax_action', 'repost_campaign');
                fd.append('campaign_id', id);
                
                fetch('post_campaigns.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.ok) {
                        Swal.fire('สำเร็จ', res.msg, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.msg, 'error');
                    }
                })
                .catch(e => {
                    Swal.fire('ข้อผิดพลาด', e.message, 'error');
                });
            }
        });
    }
    function toggleCampaignStatus(id, newStatus) {
        let title = newStatus === 'paused' ? 'ต้องการหยุดพักแคมเปญนี้?' : 'ต้องการให้แคมเปญนี้กลับมาทำงาน?';
        let text = newStatus === 'paused' ? 'บอทจะหยุดโพสต์คิวของแคมเปญนี้ชั่วคราว' : 'บอทจะเริ่มโพสต์แคมเปญนี้ต่อ (และแคมเปญอื่นจะหยุดลง)';
        
        Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: newStatus === 'active' ? '#28a745' : '#ffc107',
            confirmButtonText: 'ใช่, ' + (newStatus === 'paused' ? 'หยุดพักเลย' : 'ทำงานต่อเลย'),
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('ajax_action', 'toggle_status');
                fd.append('campaign_id', id);
                fd.append('status', newStatus);
                
                fetch('post_campaigns.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.ok) {
                        location.reload();
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.msg, 'error');
                    }
                });
            }
        });
    }
</script>
<?php include "components/footer.php"; ?>
