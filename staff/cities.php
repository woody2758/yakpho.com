<?php
require_once "db.php";
require_login();

// เฟ้นเพียง Add City (Requeue/Complete ใช้ AJAX ผ่าน api/action.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $city   = trim($_POST['city']);
    $region = trim($_POST['region'] ?? '');
    $country = trim($_POST['country']);
    
    $stmt = $db->prepare("INSERT INTO target_cities (city, region, country, status) VALUES (?, ?, ?, 'pending')");
    try {
        $stmt->execute([$city, $region, $country]);
        $_SESSION['alert'] = "เพิ่มเมือง $city ($country) สำเร็จ";
    } catch(PDOException $e) {
        $_SESSION['error'] = "เมืองนี้อาจมีอยู่ในระบบแล้ว หรือเกิดข้อผิดพลาด: " . $e->getMessage();
    }
    header("Location: cities.php"); exit;
}

// ── พารามิเตอร์ Pagination ──
$limit = 100;
$page  = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// ── นับจำนวนทั้งหมดเพื่อทำ Pagination ──
$count_stmt = $db->query("SELECT COUNT(*) FROM target_cities");
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// 1. ดึงเป้าหมายเมืองทั้งหมดเรียงตามล่าสุด (ID ใหม่สุดขึ้นก่อน) พร้อม Pagination
$stmt = $db->prepare("SELECT id, city, region, country, status, updated_at FROM target_cities ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute();
$cities = $stmt->fetchAll();

include "components/header.php"; 
?>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">จัดการเป้าหมาย AI (Target Cities)</h2>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <div class="btn-list">
          <a href="import_cities.php" class="btn btn-outline-secondary d-none d-sm-inline-block">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><line x1="12" y1="11" x2="12" y2="17" /><polyline points="9 14 12 11 15 14" /></svg>
            Import CSV / Excel
          </a>
          <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#modal-add-city">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
            เพิ่มเมืองเป้าหมายใหม่
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
              <th>City</th>
              <th>Region</th>
              <th>Country</th>
              <th>Status</th>
              <th>Last Active</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($cities as $c): ?>
            <tr>
              <td><span class="text-muted"><?= $c['id'] ?></span></td>
              <td><strong><?= htmlspecialchars($c['city']) ?></strong></td>
              <td class="text-muted"><?= htmlspecialchars($c['region'] ?? '') ?: '—' ?></td>
              <td><?= htmlspecialchars($c['country']) ?></td>
              <td>
                <?php if($c['status'] == 'completed'): ?>
                    <span class="badge bg-success text-white">Completed</span>
                <?php else: ?>
                    <span class="badge bg-warning text-white"><span class="spinner-grow spinner-grow-sm me-2" role="status"></span> Pending</span>
                <?php endif; ?>
              </td>
              <td class="text-muted"><?= date('d M Y H:i', strtotime($c['updated_at'])) ?></td>
              <td>
                <div class="btn-list flex-nowrap">
                  <?php if($c['status'] == 'completed'): ?>
                    <button class="btn btn-outline-warning btn-sm btn-city-action"
                      data-id="<?= $c['id'] ?>" data-action="city_requeue"
                      title="สั่งให้ AI กลับไปขุดข้อมูลเมืองนี้ใหม่">
                      Requeue to Pending
                    </button>
                  <?php else: ?>
                    <button class="btn btn-outline-success btn-sm btn-city-action"
                      data-id="<?= $c['id'] ?>" data-action="city_complete"
                      title="บังคับจบเมืองนี้ (Skip)">
                      Force Complete
                    </button>
                  <?php endif; ?>
                  <button class="btn btn-outline-danger btn-sm btn-city-action"
                    data-id="<?= $c['id'] ?>" data-action="city_delete"
                    data-confirm="ยืนยันลบเมืองนี้?">
                    ✕
                  </button>
                </div>
              </td>
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
            $build_url = function($p) {
                return 'cities.php?page=' . $p;
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
  </div>
</div>

<!-- Modal Add City -->
<div class="modal modal-blur fade" id="modal-add-city" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">เพิ่มเมืองเป้าหมายใหม่ (Target City)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="cities.php" method="POST">
          <input type="hidden" name="action" value="add">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label required">ชื่อเมือง (City)</label>
              <input type="text" class="form-control" name="city" required placeholder="เช่น London, Pattaya, Chiang Mai">
            </div>
            <div class="mb-3">
              <label class="form-label">จังหวัด / แคว้น (Region) <span class="text-muted">— ไม่บังคับ</span></label>
              <input type="text" class="form-control" name="region" placeholder="เช่น England, Chiang Rai, Bavaria">
            </div>
            <div class="mb-3">
              <label class="form-label required">ประเทศ (Country)</label>
              <input type="text" class="form-control" name="country" required placeholder="เช่น United Kingdom, Thailand">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary">เพิ่มเข้าคิว Pending</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php include "components/footer.php"; ?>

<script>
// ===== AJAX City Actions =====
// Toast helper
function showToast(msg, ok = true) {
  const div = document.createElement('div');
  div.className = `alert alert-${ok ? 'success' : 'danger'} position-fixed`;
  div.style.cssText = 'top:1rem;right:1rem;z-index:9999;min-width:260px;box-shadow:0 4px 16px rgba(0,0,0,.15)';
  div.textContent = msg;
  document.body.appendChild(div);
  setTimeout(() => div.remove(), 3000);
}

document.querySelectorAll('.btn-city-action').forEach(btn => {
  btn.addEventListener('click', async function () {
    const id     = this.dataset.id;
    const action = this.dataset.action;
    const confirm_msg = this.dataset.confirm;

    if (confirm_msg) {
        let { isConfirmed } = await Swal.fire({
            title: 'ยืนยัน?', text: confirm_msg, icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: 'ตกลง', cancelButtonText: 'ยกเลิก'
        });
        if (!isConfirmed) return;
    }

    // แสดง Loading
    const original = this.innerHTML;
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    try {
      const form = new FormData();
      form.append('action', action);
      form.append('id', id);

      const res  = await fetch('api/action.php', { method: 'POST', body: form });
      const data = await res.json();

      if (data.ok) {
        showToast(data.msg);
        const row = this.closest('tr');

        if (action === 'city_delete') {
          // ลบแถวออกจากตาราง
          row.style.transition = 'opacity .3s';
          row.style.opacity = '0';
          setTimeout(() => row.remove(), 300);
        } else {
          // สลับสถานะ Badge + ปุ่ม
          const isPending = (action === 'city_requeue');
          const badge = row.querySelector('.badge');
          const btnCell = row.querySelector('.btn-list');

          badge.className = isPending
            ? 'badge bg-warning text-white'
            : 'badge bg-success text-white';
          badge.innerHTML = isPending
            ? '<span class="spinner-grow spinner-grow-sm me-2" role="status"></span> Pending'
            : 'Completed';

          btnCell.innerHTML = isPending
            ? `<button class="btn btn-outline-success btn-sm btn-city-action" data-id="${id}" data-action="city_complete">Force Complete</button>
               <button class="btn btn-outline-danger btn-sm btn-city-action" data-id="${id}" data-action="city_delete" data-confirm="ยืนยันลบเมืองนี้?">&times;</button>`
            : `<button class="btn btn-outline-warning btn-sm btn-city-action" data-id="${id}" data-action="city_requeue">Requeue to Pending</button>
               <button class="btn btn-outline-danger btn-sm btn-city-action" data-id="${id}" data-action="city_delete" data-confirm="ยืนยันลบเมืองนี้?">&times;</button>`;

          // Bind Event ใหม่สำหรับปุ่มที่สร้างใหม่
          btnCell.querySelectorAll('.btn-city-action').forEach(b => {
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
