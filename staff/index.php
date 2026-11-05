<?php
require_once "db.php";
require_login(); 

// 1. ดึงจำนวนร้านนวด (Leads) ทั้งหมด
$stmt = $db->query("SELECT COUNT(*) FROM massage_leads");
$total_leads = $stmt->fetchColumn();

// 2. ดึงจำนวนเมืองทั้งหมด และแยกสถานะ Completed / Pending
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_cities,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_cities,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cities
    FROM target_cities
");
$city_stats = $stmt->fetch();
$total_cities = $city_stats['total_cities'] ?? 0;
$completed_cities = $city_stats['completed_cities'] ?? 0;
$pending_cities = $city_stats['pending_cities'] ?? 0;

// ป้องกันหารศูนย์เพื่อทำกราฟ
$completed_percent = ($total_cities > 0) ? round(($completed_cities / $total_cities) * 100) : 0;
$pending_percent = ($total_cities > 0) ? round(($pending_cities / $total_cities) * 100) : 0;

// 3. ดึง 5 ร้านล่าสุดที่บอทหามาได้
$stmt = $db->query("SELECT id, shop_name, city, country, email, timestamp FROM massage_leads ORDER BY timestamp DESC LIMIT 5");
$latest_leads = $stmt->fetchAll();

// 5. ดึงสถิติกลุ่ม Facebook
$fb_total = $db->query("SELECT COUNT(*) FROM facebook_groups")->fetchColumn();
$fb_joined = $db->query("SELECT COUNT(*) FROM facebook_groups WHERE join_status = 'Joined'")->fetchColumn();
$fb_queued = $db->query("SELECT COUNT(*) FROM facebook_groups WHERE join_status = 'Queued'")->fetchColumn();

// 6. ดึงสถิติคิวโพสต์ AI
$post_stats = $db->query("
    SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_posts,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_posts
    FROM creative_post_queue
")->fetch();
$pending_posts = $post_stats['pending_posts'] ?? 0;
$completed_posts = $post_stats['completed_posts'] ?? 0;

// 4. ดึงสถานะการทำงานของบอท และการตั้งค่าต่างๆ (System Settings)
$stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$bot_status = $settings['bot_status'] ?? 'on';
$bot_action = $settings['bot_current_action'] ?? 'Waiting...';
$bot_progress = (int)($settings['bot_progress_percent'] ?? 0);
$tg_token = $settings['telegram_bot_token'] ?? '';
$tg_chat_id = $settings['telegram_chat_id'] ?? '';

include "components/header.php"; 
?>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle">Overview</div>
        <h2 class="page-title">Yakpho AI Dashboard</h2>
      </div>
      <div class="col-auto ms-auto">
        <div class="d-flex gap-2">
            <button class="btn btn-icon btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-settings" title="Staff Configuration">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37.996 .608 2.296 .07 2.572 -1.065z" /><circle cx="12" cy="12" r="3" /></svg>
            </button>
            <div class="card bg-primary-lt border-0 shadow-none">
              <div class="card-body py-2 px-3">
                 <div class="d-flex align-items-center gap-3">
                    <div class="text-primary fw-bold small">AI PULSE:</div>
                    <label class="form-check form-switch mb-0">
                      <input class="form-check-input" type="checkbox" id="bot-toggle" <?= $bot_status === 'on' ? 'checked' : '' ?>>
                      <span class="form-check-label badge <?= $bot_status === 'on' ? 'bg-success' : 'bg-danger' ?>" id="bot-status-label">
                        <?= strtoupper($bot_status) ?>
                      </span>
                    </label>
                 </div>
              </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- Page body -->
<div class="page-body">
  <div class="container-fluid">
    <div class="row row-deck row-cards">

      <!-- Stats Cards -->
      <div class="col-sm-6 col-lg-4">
        <div class="card card-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-auto">
                <span class="bg-primary text-white avatar">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                </span>
              </div>
              <div class="col">
                <div class="font-weight-medium">ร้านนวดทั้งหมด (Leads)</div>
                <div class="text-muted"><?= number_format($total_leads) ?> ร้านทั่วโลก</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-4">
        <div class="card card-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-auto">
                <span class="bg-success text-white avatar">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                </span>
              </div>
              <div class="col">
                <div class="font-weight-medium">เมืองที่ขุดเสร็จแล้ว (Completed)</div>
                <div class="text-muted"><?= number_format($completed_cities) ?> เมือง</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-4">
        <div class="card card-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-auto">
                <span class="bg-warning text-white avatar">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><polyline points="12 7 12 12 15 15" /></svg>
                </span>
              </div>
              <div class="col">
                <div class="font-weight-medium">เมืองที่รอคิว (Pending)</div>
                <div class="text-muted"><?= number_format($pending_cities) ?> เมือง</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 mt-3 mb-2">
        <h3 class="page-title text-muted" style="font-size: 1.1rem;">ภาพรวมระบบกลุ่ม Facebook & โพสต์ AI</h3>
      </div>

      <!-- FB Stats Cards -->
      <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-auto">
                <span class="bg-facebook text-white avatar">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3" /></svg>
                </span>
              </div>
              <div class="col">
                <div class="font-weight-medium">กลุ่ม Facebook ทั้งหมด</div>
                <div class="text-muted"><?= number_format($fb_total) ?> กลุ่ม</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-auto">
                <span class="bg-success text-white avatar">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                </span>
              </div>
              <div class="col">
                <div class="font-weight-medium">เข้าร่วมแล้ว (Joined)</div>
                <div class="text-muted"><?= number_format($fb_joined) ?> กลุ่ม</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-auto">
                <span class="bg-purple text-white avatar">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11 5h2" /><path d="M12 17v.01" /><path d="M11 12h1v4h1" /><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /></svg>
                </span>
              </div>
              <div class="col">
                <div class="font-weight-medium">รอ AI โพสต์ (Pending)</div>
                <div class="text-muted"><?= number_format($pending_posts) ?> งาน</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-auto">
                <span class="bg-info text-white avatar">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
                </span>
              </div>
              <div class="col">
                <div class="font-weight-medium">โพสต์สำเร็จ (Completed)</div>
                <div class="text-muted"><?= number_format($completed_posts) ?> โพสต์</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      
      <!-- Chart -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h3 class="card-title">สัดส่วนการขุดข้อมูลเมือง (City Scraping Progress)</h3>
            <div id="chart-tasks-overview" class="mt-4"></div>
          </div>
        </div>
      </div>

      <!-- Latest 5 Leads Table -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">ข้อมูลร้านนวดล่าสุด (Latest 5 Leads)</h3>
          </div>
          <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap datatable">
              <thead>
                <tr>
                  <th class="w-1">No.</th>
                  <th>Shop Name</th>
                  <th>City / Country</th>
                  <th>Email Found</th>
                  <th>Time</th>
                </tr>
              </thead>
              <tbody>
                <?php if(count($latest_leads) > 0): ?>
                  <?php foreach($latest_leads as $index => $lead): ?>
                  <tr>
                    <td><span class="text-muted"><?= sprintf("%04d", $lead['id']) ?></span></td>
                    <td><?= htmlspecialchars($lead['shop_name']) ?></td>
                    <td>
                      <?= htmlspecialchars($lead['city']) ?>, <?= htmlspecialchars($lead['country']) ?>
                    </td>
                    <td>
                      <?php if($lead['email'] == 'Not Found' || $lead['email'] == 'N/A'): ?>
                        <span class="badge bg-danger-lt">Missing</span>
                      <?php else: ?>
                        <span class="badge bg-success-lt">Found</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted"><?= date('H:i', strtotime($lead['timestamp'])) ?></td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-3">ยังไม่มีข้อมูลในระบบ</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  // ApexChart for City Statuses
  document.addEventListener("DOMContentLoaded", function () {
    window.ApexCharts && (new ApexCharts(document.getElementById('chart-tasks-overview'), {
      chart: { type: "donut", fontFamily: 'inherit', height: 288, sparkline: { enabled: true }, animations: { enabled: true } },
      fill: { opacity: 1 },
      series: [<?= $completed_cities ?>, <?= $pending_cities ?>],
      labels: ["Completed", "Pending"],
      tooltip: { theme: 'dark' },
      grid: { strokeDashArray: 4 },
      colors: ["#2fb344", "#f59f00"],
      legend: { show: true, position: 'bottom', offsetY: 12, markers: { width: 10, height: 10, radius: 100 } },
      tooltip: { fillSeriesColor: false }
    })).render();
  });

  // ===== Bot Status Toggle =====
  const botToggle = document.getElementById('bot-toggle');
  const botLabel  = document.getElementById('bot-status-label');
  const botPulse  = document.getElementById('bot-pulse-spinner'); // Might be null now

  botToggle.addEventListener('change', async function() {
      const status = this.checked ? 'on' : 'off';
      
      // Update UI immediate feedback
      if (botLabel) {
          botLabel.textContent = status.toUpperCase();
          botLabel.className = `form-check-label badge ${this.checked ? 'bg-success' : 'bg-danger'}`;
      }
      if (botPulse) botPulse.classList.toggle('d-none', !this.checked);
      
      const formData = new FormData();
      formData.append('action', 'toggle_bot');
      formData.append('status', status);

      try {
          const res = await fetch('api/action.php', { method: 'POST', body: formData });
          const data = await res.json();
          if (!data.ok) {
              alert('Error: ' + data.msg);
              // revert UI
              this.checked = !this.checked;
              if (botLabel) {
                  botLabel.textContent = this.checked ? 'ON' : 'OFF';
                  botLabel.className = `form-check-label badge ${this.checked ? 'bg-success' : 'bg-danger'}`;
              }
              if (botPulse) botPulse.classList.toggle('d-none', !this.checked);
          } else {
              // Trigger immediate global UI update (Spotify Bar)
              if (typeof updateGlobalBotStatus === 'function') {
                  updateGlobalBotStatus();
              }
          }
      } catch (err) {
          alert('ไม่สามารถเชื่อมต่อ Server ได้');
          this.checked = !this.checked;
      }
  });

  // ===== Settings Save =====
  const btnSaveSettings = document.getElementById('save-settings');
  if(btnSaveSettings) {
      btnSaveSettings.addEventListener('click', async function() {
          this.disabled = true;
          this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
          
          const formData = new FormData();
          formData.append('action', 'save_telegram_config');
          formData.append('token', document.getElementById('token').value);
          formData.append('chat_id', document.getElementById('chat_id').value);

          try {
              const res = await fetch('api/action.php', { method: 'POST', body: formData });
              const data = await res.json();
              alert(data.msg);
              if(data.ok) {
                  bootstrap.Modal.getInstance(document.getElementById('modal-settings')).hide();
              }
          } catch (err) {
              alert('Save Error');
          } finally {
              this.disabled = false;
              this.textContent = 'Save Changes';
          }
      });
  }

  // Dashboard polling removed (Handled by global footer polling)
</script>

<!-- Settings Modal -->
<div class="modal modal-blur fade" id="modal-settings" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">การตั้งค่าทีมงาน & Telegram</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Telegram Bot Token</label>
          <input type="text" class="form-control" name="token" id="token" placeholder="Paste Token here" value="<?= htmlspecialchars($tg_token) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Telegram Chat ID</label>
          <input type="text" class="form-control" name="chat_id" id="chat_id" placeholder="Paste Chat ID here" value="<?= htmlspecialchars($tg_chat_id) ?>">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="save-settings">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<?php include "components/footer.php"; ?>
