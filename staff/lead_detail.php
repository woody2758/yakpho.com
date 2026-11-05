<?php
require_once "db.php";
require_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: leads.php"); exit; }

$stmt = $db->prepare("SELECT * FROM massage_leads WHERE id = ?");
$stmt->execute([$id]);
$lead = $stmt->fetch();
if (!$lead) { die("<div class='container mt-5 alert alert-danger'>ไม่พบข้อมูลร้านนี้</div>"); }

// สร้าง query สำหรับ Google Maps embed
$map_query = urlencode($lead['shop_name'] . ', ' . $lead['city'] . ', ' . $lead['country']);

include "components/header.php";
?>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle"><a href="leads.php" class="text-muted">← กลับรายการทั้งหมด</a></div>
        <h2 class="page-title"><?= htmlspecialchars($lead['shop_name']) ?></h2>
      </div>
      <div class="col-auto">
        <?php if($lead['maps_url'] && $lead['maps_url'] != 'N/A'): ?>
          <a href="<?= htmlspecialchars($lead['maps_url']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm me-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="11" r="3"/><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/></svg>
            เปิด Google Maps
          </a>
        <?php endif; ?>
        <?php if($lead['website'] && $lead['website'] != 'N/A'): ?>
          <a href="<?= htmlspecialchars($lead['website']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8"/><path d="M3.6 15h16.8"/><path d="M11.5 3a17 17 0 0 0 0 18"/><path d="M12.5 3a17 17 0 0 1 0 18"/></svg>
            เว็บไซต์
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    <div class="row g-3">

      <!-- ── ข้อมูลร้าน ── -->
      <div class="col-lg-5">

        <!-- Info Card -->
        <div class="card mb-3">
          <div class="card-header"><h3 class="card-title">📌 ข้อมูลร้าน</h3></div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-5 text-muted small">ชื่อร้าน</dt>
              <dd class="col-7 fw-medium"><?= htmlspecialchars($lead['shop_name']) ?></dd>

              <dt class="col-5 text-muted small">เมือง</dt>
              <dd class="col-7"><?= htmlspecialchars($lead['city']) ?><?= $lead['region'] ? ' · '.$lead['region'] : '' ?></dd>

              <dt class="col-5 text-muted small">ประเทศ</dt>
              <dd class="col-7"><?= htmlspecialchars($lead['country']) ?></dd>

              <dt class="col-5 text-muted small">สถานะ</dt>
              <dd class="col-7"><span class="badge bg-azure-lt text-azure"><?= htmlspecialchars($lead['status']) ?></span></dd>

              <dt class="col-5 text-muted small">บันทึกวันที่</dt>
              <dd class="col-7 small text-muted"><?= $lead['timestamp'] ? date('d M Y H:i', strtotime($lead['timestamp'])) : '—' ?></dd>
            </dl>
          </div>
        </div>

        <!-- Contact Card -->
        <div class="card mb-3">
          <div class="card-header"><h3 class="card-title">📞 ช่องทางติดต่อ</h3></div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-4 text-muted small">อีเมล</dt>
              <dd class="col-8">
                <?php if(!in_array($lead['email'], ['Not Found','N/A',''])): ?>
                  <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" class="text-primary"><?= htmlspecialchars($lead['email']) ?></a>
                <?php else: ?>
                  <span class="badge bg-danger-lt text-danger">ไม่พบ</span>
                <?php endif; ?>
              </dd>

              <dt class="col-4 text-muted small">เว็บไซต์</dt>
              <dd class="col-8">
                <?php if($lead['website'] && $lead['website'] != 'N/A'): ?>
                  <a href="<?= htmlspecialchars($lead['website']) ?>" target="_blank" class="text-truncate d-block" style="max-width:200px"><?= htmlspecialchars($lead['website']) ?></a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </dd>
            </dl>
          </div>
        </div>

        <!-- Reviews Card -->
        <div class="card mb-3">
          <div class="card-header"><h3 class="card-title">⭐ รีวิวจาก Google Maps</h3></div>
          <div class="card-body">
            <div class="row g-2 mb-3">
              <div class="col-6 text-center">
                <div class="fs-2 fw-bold text-primary"><?= number_format($lead['review_count']) ?></div>
                <div class="text-muted small">จำนวนรีวิว</div>
              </div>
              <div class="col-6 text-center">
                <div class="fs-6 pt-2 text-muted"><?= $lead['review_highlights'] ?: '—' ?></div>
                <div class="text-muted small">จุดเด่น</div>
              </div>
            </div>
            <?php if($lead['praise_draft']): ?>
            <div class="alert alert-info mb-0 py-2">
              <div class="small text-muted mb-1">💬 ร่างคำชมเชย (AI Draft)</div>
              <div class="small"><?= htmlspecialchars($lead['praise_draft']) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- ── แผนที่ ── -->
      <div class="col-lg-7">
        <div class="card h-100">
          <div class="card-header">
            <h3 class="card-title">🗺️ แผนที่ตำแหน่งร้าน</h3>
          </div>
          <div class="card-body p-0" style="min-height:420px">
            <iframe
              src="https://maps.google.com/maps?q=<?= $map_query ?>&output=embed&hl=en&z=15"
              width="100%"
              height="100%"
              style="min-height:420px;border:0;border-radius:0 0 8px 8px"
              allowfullscreen
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>
          </div>
          <?php if($lead['maps_url'] && $lead['maps_url'] != 'N/A'): ?>
          <div class="card-footer py-2 text-center">
            <a href="<?= htmlspecialchars($lead['maps_url']) ?>" target="_blank" class="text-primary small">
              🔗 เปิดร้านนี้ใน Google Maps แบบเต็มหน้าจอ
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include "components/footer.php"; ?>
