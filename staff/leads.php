<?php
require_once "db.php";
require_login();

// ── ค้นหา / กรอง ──
$search  = trim($_GET['q'] ?? '');
$city_f  = trim($_GET['city'] ?? '');
$email_f = trim($_GET['email'] ?? '');

// ── พารามิเตอร์ Pagination ──
$limit = 50;
$page  = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(shop_name LIKE ? OR city LIKE ? OR country LIKE ?)";
    $params  = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($city_f) {
    $where[] = "city = ?";
    $params[] = $city_f;
}
if ($email_f === 'found') {
    $where[] = "email != 'Not Found' AND email != '' AND email != 'N/A'";
} elseif ($email_f === 'missing') {
    $where[] = "(email = 'Not Found' OR email = '' OR email = 'N/A')";
}

$where_str = implode(' AND ', $where);

// ── นับจำนวนทั้งหมดเพื่อทำ Pagination ──
$count_sql = "SELECT COUNT(*) FROM massage_leads WHERE $where_str";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// ── ดึงข้อมูลตาม Page ──
$sql = "SELECT id, shop_name, website, city, region, country, email, review_count, status, timestamp
        FROM massage_leads
        WHERE $where_str
        ORDER BY id DESC
        LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

// ── เตรียมตัวแปรสำหรับแสดงสถานะการค้นหา ──
$active_filters = [];
if ($search) $active_filters[] = "คำค้น: \"<strong class='text-primary'>$search</strong>\"";
if ($city_f) $active_filters[] = "เมือง: \"<strong class='text-primary'>$city_f</strong>\"";
if ($email_f === 'found')   $active_filters[] = "สถานะ: <span class='badge bg-success-lt'>📧 มีอีเมล</span>";
if ($email_f === 'missing') $active_filters[] = "สถานะ: <span class='badge bg-danger-lt'>❌ ไม่มีอีเมล</span>";

$is_searching = !empty($active_filters);

// ── ดึงเมืองทั้งหมดสำหรับ filter dropdown ──
$cities = $db->query("SELECT DISTINCT city FROM massage_leads ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

include "components/header.php";
?>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">Leads Database
          <span class="badge bg-primary ms-2" style="font-size:.7rem"><?= number_format($total_rows) ?> ร้าน</span>
        </h2>
        <div class="text-muted mt-1 small">แผนก 1 · Lead Generation — ข้อมูลร้านนวดที่ AI ค้นหามา</div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid">

    <!-- Search Status / Summary -->
    <div class="mb-3 d-flex align-items-center justify-content-between">
      <div>
        <?php if ($is_searching): ?>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">กำลังแสดงผลการค้นหา:</span>
            <?php foreach ($active_filters as $f): ?>
              <span class="bg-white border rounded px-2 py-1 small shadow-sm"><?= $f ?></span>
            <?php endforeach; ?>
            <a href="leads.php" class="btn btn-sm btn-ghost-danger py-0 px-2" style="font-size:.7rem">ล้างการค้นหา</a>
          </div>
        <?php else: ?>
          <div class="text-muted small">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><line x1="12" y1="8" x2="12.01" y2="8" /><polyline points="11 12 12 12 12 16 13 16" /></svg>
            แสดงร้านนวดทั้งหมดที่มีอยู่ในระบบ (เรียงจากล่าสุด)
          </div>
        <?php endif; ?>
      </div>
      <div class="text-muted small">
        พบข้อมูลทั้งหมด <strong><?= number_format($total_rows) ?></strong> รายการ
      </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" class="card mb-3">
      <div class="card-body py-2">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small mb-1">ค้นหาชื่อร้าน / เมือง / ประเทศ</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="เช่น Lotus, Zurich, UK..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1">กรองตามเมือง</label>
            <select name="city" class="form-select form-select-sm">
              <option value="">— ทุกเมือง —</option>
              <?php foreach($cities as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $city_f==$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1">อีเมล</label>
            <select name="email" class="form-select form-select-sm">
              <option value="">— ทั้งหมด —</option>
              <option value="found"   <?= $email_f=='found'?'selected':''   ?>>📧 พบอีเมล</option>
              <option value="missing" <?= $email_f=='missing'?'selected':'' ?>>❌ ไม่มีอีเมล</option>
            </select>
          </div>
          <div class="col-md-auto">
            <button type="submit" class="btn btn-primary btn-sm">🔍 ค้นหา</button>
            <a href="leads.php" class="btn btn-sm btn-ghost-secondary ms-1">รีเซ็ต</a>
          </div>
        </div>
      </div>
    </form>

    <!-- Leads Table -->
    <div class="card">
      <div class="table-responsive">
        <table class="table table-vcenter table-hover table-sm card-table">
          <thead class="table-light">
            <tr>
               <th class="text-muted" style="width:100px">Lead ID</th>
              <th>ชื่อร้าน</th>
              <th>เมือง / ประเทศ</th>
              <th class="text-center">รีวิว</th>
              <th class="text-center">อีเมล</th>
              <th class="text-center">สถานะ</th>
              <th class="text-muted text-end">วันที่</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($leads)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">ไม่พบข้อมูล</td></tr>
            <?php endif; ?>
            <?php foreach($leads as $i => $l): ?>
            <tr style="cursor:pointer" class="lead-row" data-id="<?= $l['id'] ?>" data-name="<?= htmlspecialchars($l['shop_name']) ?>">
              <td class="text-muted small"><?= str_pad($l['id'], 5, '0', STR_PAD_LEFT) ?></td>
              <td>
                <div class="fw-medium text-body editable hover-edit" data-field="shop_name" data-id="<?= $l['id'] ?>" contenteditable="true" title="คลิกเพื่อแก้ไข"><?= htmlspecialchars($l['shop_name']) ?></div>
                <?php 
                  $web = trim($l['website'] ?? '');
                  if(!empty($web) && !in_array(strtolower($web), ['not found', 'n/a', '-', 'null'])): 
                ?>
                  <a href="<?= htmlspecialchars($web) ?>" target="_blank" class="text-muted small text-decoration-none d-block mt-1" title="<?= htmlspecialchars($web) ?>" onclick="event.stopPropagation();">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-world" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M3.6 9h16.8" /><path d="M3.6 15h16.8" /><path d="M11.5 3a17 17 0 0 0 0 18" /><path d="M12.5 3a17 17 0 0 1 0 18" /></svg>
                    <?= parse_url($web, PHP_URL_HOST) ?? $web ?>
                  </a>
                <?php endif; ?>
              </td>
              <td>
                <div><?= htmlspecialchars($l['city']) ?><?= $l['region'] ? ', '.$l['region'] : '' ?></div>
                <div class="text-muted small"><?= htmlspecialchars($l['country']) ?></div>
              </td>
              <td class="text-center">
                <?php if($l['review_count'] > 0): ?>
                  <span class="badge bg-blue-lt text-blue">⭐ <?= number_format($l['review_count']) ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php $hasEmail = !in_array($l['email'], ['Not Found','N/A','']); ?>
                <?php if($hasEmail): ?>
                  <div class="editable hover-edit text-success small fw-bold" data-field="email" data-id="<?= $l['id'] ?>" contenteditable="true" title="คลิกเพื่อแก้ไข"><?= htmlspecialchars($l['email']) ?></div>
                <?php else: ?>
                  <div class="editable hover-edit text-danger small opacity-50" data-field="email" data-id="<?= $l['id'] ?>" contenteditable="true" title="คลิกเพื่อป้อนอีเมล">-</div>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php
                $curr_st = htmlspecialchars($l['status']);
                $bg_color = 'bg-azure-lt text-azure';
                if ($curr_st === 'Reviewed') $bg_color = 'bg-cyan-lt text-cyan';
                elseif ($curr_st === 'Emailed') $bg_color = 'bg-yellow-lt text-yellow';
                elseif ($curr_st === 'Replied') $bg_color = 'bg-orange-lt text-orange';
                elseif ($curr_st === 'Interested') $bg_color = 'bg-green-lt text-green';
                elseif ($curr_st === 'Customer') $bg_color = 'bg-purple-lt text-purple';
                elseif ($curr_st === 'Dead') $bg_color = 'bg-red-lt text-red';
                ?>
                <select class="form-select form-select-sm status-select badge <?= $bg_color ?> border-0 fw-bold" data-id="<?= $l['id'] ?>" style="width:115px; display:inline-block; appearance:none; text-align:center; cursor:pointer;" onclick="event.stopPropagation();">
                   <?php
                   $statuses = ['New Lead', 'Reviewed', 'Emailed', 'Replied', 'Interested', 'Customer', 'Dead'];
                   foreach($statuses as $st) {
                       $sel = ($l['status'] == $st) ? 'selected' : '';
                       echo "<option value=\"$st\" $sel class=\"text-dark bg-white\">$st</option>";
                   }
                   ?>
                </select>
              </td>
              <td class="text-end text-muted small"><?= $l['timestamp'] ? date('d M y', strtotime($l['timestamp'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <?php if ($total_pages > 1): ?>
      <div class="card-footer d-flex align-items-center border-top-0">
        <p class="m-0 text-muted small d-none d-sm-block">
          Showing <span><?= $offset + 1 ?></span> to <span><?= min($offset + $limit, $total_rows) ?></span> of <span><?= number_format($total_rows) ?></span> entries
        </p>
        <ul class="pagination m-0 ms-auto">
          <?php 
            $url_params = $_GET;
            $build_url = function($p) use ($url_params) {
                $url_params['page'] = $p;
                return 'leads.php?' . http_build_query($url_params);
            };
          ?>
          
          <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= ($page <= 1) ? '#' : $build_url($page - 1) ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="15 6 9 12 15 18" /></svg> prev
            </a>
          </li>

          <?php 
          $start_page = max(1, $page - 2);
          $end_page = min($total_pages, $page + 2);
          
          if ($start_page > 1) {
              echo '<li class="page-item"><a class="page-link" href="'.$build_url(1).'">1</a></li>';
              if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
          }

          for ($i = $start_page; $i <= $end_page; $i++): 
          ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
              <a class="page-link" href="<?= $build_url($i) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>

          <?php 
          if ($end_page < $total_pages) {
              if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
              echo '<li class="page-item"><a class="page-link" href="'.$build_url($total_pages).'">'.$total_pages.'</a></li>';
          }
          ?>

          <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= ($page >= $total_pages) ? '#' : $build_url($page + 1) ?>">
              next <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 6 15 12 9 18" /></svg>
            </a>
          </li>
        </ul>
      </div>
      <?php endif; ?>
    </div>

    <!-- CRM Status Legend -->
    <div class="card mt-3 bg-light-lt border-0 shadow-sm opacity-75 hover-opacity-100 transition-opacity">
      <div class="card-body py-3">
        <h4 class="card-title text-muted mb-2 fs-6">📌 คู่มือความหมายของสถานะการประทับตรา (CRM Pipeline)</h4>
        <div class="row g-2 text-muted small">
          <div class="col-md-3">
            <div><span class="badge bg-azure-lt text-azure me-1" style="width:75px">New Lead</span> รายชื่อใหม่เพิ่งขุดพบ</div>
            <div class="mt-1"><span class="badge bg-cyan-lt text-cyan me-1" style="width:75px">Reviewed</span> ทิ้งรีวิวให้แล้ว (AI จะข้าม)</div>
          </div>
          <div class="col-md-3">
            <div><span class="badge bg-yellow-lt text-yellow me-1" style="width:75px">Emailed</span> ส่งอีเมล+แคตตาล็อกแล้ว</div>
            <div class="mt-1"><span class="badge bg-orange-lt text-orange me-1" style="width:75px">Replied</span> ลูกค้าตอบกลับอีเมลแล้ว</div>
          </div>
          <div class="col-md-3">
            <div><span class="badge bg-green-lt text-green me-1" style="width:75px">Interested</span> สนใจ ขอ Sample</div>
            <div class="mt-1"><span class="badge bg-purple-lt text-purple me-1" style="width:75px">Customer</span> เป็นลูกค้าตัวยงแล้ว</div>
          </div>
          <div class="col-md-3">
            <div><span class="badge bg-red-lt text-red me-1" style="width:75px">Dead</span> ปฏิเสธ / ส่งอีเมลไม่ผ่าน</div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ── Lead Detail Modal ── -->
<div class="modal modal-blur fade" id="modal-lead-detail" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title" id="lead-modal-title">Lead Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="lead-modal-body">
        <!-- Content will be loaded via AJAX -->
        <div class="text-center py-5">
           <div class="spinner-border text-primary" role="status"></div>
           <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include "components/footer.php"; ?>

<style>
.hover-edit {
    padding: 2px 4px;
    border-radius: 4px;
    border: 1px solid transparent;
    transition: all 0.2s;
    outline: none;
}
.hover-edit:hover, .hover-edit:focus {
    border-color: #cbd5e1;
    background-color: #f8fafc;
    box-shadow: 0 0 0 2px rgba(32,107,196,.1);
}
.hover-edit.saving {
    opacity: 0.5;
    pointer-events: none;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const detailModal = new bootstrap.Modal(document.getElementById('modal-lead-detail'));
    const modalBody = document.getElementById('lead-modal-body');
    const modalTitle = document.getElementById('lead-modal-title');

    // จัดการการส่งข้อมูลแบบ Ajax เมื่อแก้ไขเสร็จ
    document.querySelectorAll('.editable').forEach(el => {
        el.addEventListener('click', function(e) {
            e.stopPropagation(); // ไม่ให้เด้งเปิด Modal Detail เวลาคลิกแก้ไข
        });
        
        el.addEventListener('blur', async function(e) {
            const field = this.dataset.field;
            const id = this.dataset.id;
            const value = this.innerText.trim();
            const originalValue = this.dataset.original || '';
            
            if (value === originalValue) return;
            
            this.classList.add('saving');
            
            try {
                const response = await fetch('api/update_lead_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, field: field, value: value })
                });
                
                const data = await response.json();
                if (data.ok) {
                    this.dataset.original = value;
                    this.parentElement.classList.add('bg-success-lt');
                    setTimeout(() => this.parentElement.classList.remove('bg-success-lt'), 1000);
                } else {
                    alert('Error: ' + data.msg);
                    this.innerText = originalValue;
                }
            } catch (error) {
                alert('Connection Error');
                this.innerText = originalValue;
            } finally {
                this.classList.remove('saving');
            }
        });
        
        el.addEventListener('focus', function() {
            if (!this.dataset.original) {
                this.dataset.original = this.innerText.trim();
            }
        });
        
        // ให้กด Enter เป็นการเซฟและออกจากช่องแทนการขึ้นบรรทัดใหม่
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });

    // ดักจับและเปลี่ยนสถานะ Dropdown โดยอัตโนมัติ
    document.querySelectorAll('.status-select').forEach(sel => {
        sel.addEventListener('change', async function() {
            const id = this.dataset.id;
            const value = this.value;
            this.disabled = true;
            
            try {
                const response = await fetch('api/update_lead_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, field: 'status', value: value })
                });
                const data = await response.json();
                if(data.ok) {
                    location.reload(); // รีเฟรชหน้าเพื่อให้สี badge เปลี่ยน
                } else {
                    alert('Error: ' + data.msg);
                }
            } catch(e) {
                alert('Connection Error');
            } finally {
                this.disabled = false;
            }
        });
    });

    document.querySelectorAll('.lead-row').forEach(row => {
        row.addEventListener('click', async function (e) {
            if (e.target.closest('.editable') || e.target.closest('a')) return;
            const id = this.dataset.id;
            const name = this.dataset.name;

            // Reset modal & Show
            modalTitle.textContent = name;
            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>
                </div>
            `;
            detailModal.show();

            try {
                const response = await fetch(`api/get_lead_detail.php?id=${id}`);
                const html = await response.text();
                modalBody.innerHTML = html;
            } catch (error) {
                modalBody.innerHTML = `<div class="alert alert-danger">ไม่สามารถโหลดข้อมูลได้: ${error}</div>`;
            }
        });
    });
});
</script>
