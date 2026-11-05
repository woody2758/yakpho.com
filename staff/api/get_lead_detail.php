<?php
require_once "../db.php";
require_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { exit("ID Required"); }

$stmt = $db->prepare("SELECT * FROM massage_leads WHERE id = ?");
$stmt->execute([$id]);
$lead = $stmt->fetch();

if (!$lead) { exit("<div class='alert alert-danger'>ไม่พบข้อมูลร้านนี้</div>"); }

// สร้าง query สำหรับ Google Maps embed
$map_query = urlencode($lead['shop_name'] . ', ' . $lead['city'] . ', ' . $lead['country']);
?>

<div class="row g-3">
  <!-- ── ข้อมูลร้าน ── -->
  <div class="col-lg-5">
    <!-- Info Card -->
    <div class="card mb-3 border-0 shadow-sm bg-white">
      <div class="card-body p-3">
        <div class="row g-2">
          <div class="col-4 text-muted small">ชื่อร้าน</div>
          <div class="col-8 fw-bold text-dark fs-3"><?= htmlspecialchars($lead['shop_name']) ?></div>
          
          <div class="col-4 text-muted small">เมือง / แคว้น</div>
          <div class="col-8 fw-medium"><?= htmlspecialchars($lead['city']) ?><?= $lead['region'] ? ' · '.$lead['region'] : '' ?></div>
          
          <div class="col-4 text-muted small">ประเทศ</div>
          <div class="col-8 fw-medium"><?= htmlspecialchars($lead['country']) ?></div>
          
          <div class="col-4 text-muted small">สถานะ</div>
          <div class="col-8"><span class="badge bg-primary-lt text-primary fw-bold"><?= htmlspecialchars($lead['status']) ?></span></div>
        </div>
      </div>
    </div>

    <!-- Contact & Review -->
    <div class="card mb-3">
      <div class="card-body p-3">
        <h4 class="card-title mb-2">📞 ติดต่อ & รีวิว</h4>
        <div class="mb-2">
          <div class="text-muted small">อีเมล</div>
          <?php if(!in_array($lead['email'], ['Not Found','N/A',''])): ?>
            <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" class="text-primary fw-medium"><?= htmlspecialchars($lead['email']) ?></a>
          <?php else: ?>
            <span class="text-danger small">✗ ยังไม่พบอีเมล</span>
          <?php endif; ?>
        </div>
        
        <div class="row g-2 mb-3">
          <div class="col-6">
            <div class="text-muted small">จำนวนรีวิว</div>
            <div class="fw-bold">⭐ <?= number_format($lead['review_count']) ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted small">จุดเด่น</div>
            <div class="small"><?= $lead['review_highlights'] ?: '—' ?></div>
          </div>
        </div>

        <?php if($lead['praise_draft']): ?>
        <div class="bg-blue-lt p-2 rounded small">
          <div class="text-muted mb-1 fw-medium">💬 AI Praise Draft:</div>
          <div class="fst-italic">"<?= htmlspecialchars($lead['praise_draft']) ?>"</div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-grid gap-2">
      <?php if($lead['website'] && $lead['website'] != 'N/A'): ?>
      <a href="<?= htmlspecialchars($lead['website']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
        เปิดเว็บไซต์ร้าน
      </a>
      <?php endif; ?>
      <?php if($lead['maps_url'] && $lead['maps_url'] != 'N/A'): ?>
      <a href="<?= htmlspecialchars($lead['maps_url']) ?>" target="_blank" class="btn btn-ghost-secondary btn-sm">
        ดูใน Google Maps เต็มหน้าจอ
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── แผนที่ ── -->
  <div class="col-lg-7">
    <div class="ratio ratio-4x3 border rounded bg-light overflow-hidden shadow-sm">
      <iframe
        src="https://maps.google.com/maps?q=<?= $map_query ?>&output=embed&hl=en&z=15"
        style="border:0;"
        allowfullscreen
        loading="lazy">
      </iframe>
    </div>
  </div>
</div>
