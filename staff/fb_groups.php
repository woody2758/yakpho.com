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
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN quality_star DECIMAL(3,1) NOT NULL DEFAULT 0.0"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE facebook_groups ADD COLUMN manual_post_count INT NOT NULL DEFAULT 0"); } catch (PDOException $e) {}

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
$params = [];

if ($search) {
    $where[] = "(group_name LIKE ? OR group_url LIKE ? OR location_focus LIKE ? OR campaign_tag LIKE ? OR category_tag LIKE ?)";
    $params  = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
}
if ($status_f) {
    $where[] = "join_status = ?";
    $params[] = $status_f;
}
if ($cat_f) {
    $where[] = "category_tag LIKE ?";
    $params[] = "%$cat_f%";
}
if ($zone_f) {
    $where[] = "target_zone = ?";
    $params[] = $zone_f;
}
if ($strategy_f) {
    $where[] = "strategy_marker = ?";
    $params[] = $strategy_f;
}

// แยกรวบรวม Where สำหรับหน้าแท็บ
$tab_where_str = implode(' AND ', $where);
$tab_params = $params;

// กรอง Quality
if ($quality_f) {
    $where[] = "post_quality = ?";
    $params[] = $quality_f;
} else {
    // หน้าหลัก (Total) จะต้องไม่รวมถังขยะ
    $where[] = "post_quality != 'Delete'";
}

$where_str = implode(' AND ', $where);

// ── Sorting Logic ──
$order_by = "suitability_score DESC, member_count DESC";
if ($sort == 'members_desc' || $sort == 'members') $order_by = "member_count DESC";
if ($sort == 'members_asc') $order_by = "member_count ASC";
if ($sort == 'newest')  $order_by = "id DESC";
if ($sort == 'updated') $order_by = "last_scraped_at DESC";

// ── นับจำนวนทั้งหมดสำหรับทำ Pagination ──
$count_sql = "SELECT COUNT(*) FROM facebook_groups WHERE $where_str";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// ── ดึงข้อมูลตาม Page ──
$sql = "SELECT g.*, 
        (
            (SELECT COUNT(*) FROM creative_post_queue q WHERE q.target_id = g.id AND q.target_type = 'group' AND q.status = 'completed') +
            (SELECT COUNT(*) FROM fb_campaign_schedules s WHERE s.group_id = g.id AND s.status = 'success') +
            IFNULL(g.manual_post_count, 0)
        ) as total_posts,
        (
            SELECT GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ')
            FROM fb_campaign_schedules s
            JOIN fb_campaigns c ON s.campaign_id = c.id
            WHERE s.group_id = g.id AND s.status = 'success'
        ) as posted_campaigns
        FROM facebook_groups g
        WHERE $where_str
        ORDER BY $order_by
        LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$groups = $stmt->fetchAll();

// ── สถิติสำหรับแท็บ (อิงตาม Filter ปัจจุบัน ยกเว้น Quality) ──
$tab_sql = "SELECT 
    SUM(CASE WHEN post_quality != 'Delete' THEN 1 ELSE 0 END) as active_total,
    SUM(CASE WHEN post_quality = 'Selected' THEN 1 ELSE 0 END) as quality_selected,
    SUM(CASE WHEN post_quality = 'Best' THEN 1 ELSE 0 END) as quality_best,
    SUM(CASE WHEN post_quality = 'Good' THEN 1 ELSE 0 END) as quality_good,
    SUM(CASE WHEN post_quality = 'Poor' THEN 1 ELSE 0 END) as quality_poor,
    SUM(CASE WHEN post_quality = 'Delete' THEN 1 ELSE 0 END) as quality_delete
FROM facebook_groups WHERE $tab_where_str";
$tab_stmt = $db->prepare($tab_sql);
$tab_stmt->execute($tab_params);
$tab_stats = $tab_stmt->fetch();

// ── สถิติเบื้องต้นรวมทั้งหมด ──
$stats = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN join_status = 'Joined' THEN 1 ELSE 0 END) as joined,
    SUM(CASE WHEN join_status = 'Pending' THEN 1 ELSE 0 END) as pending
FROM facebook_groups")->fetch();

// ── ดึงแคมเปญทั้งหมด ──
$active_campaigns = $db->query("SELECT id, name FROM fb_campaigns WHERE status IN ('draft', 'active') ORDER BY id DESC")->fetchAll();

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
              <span class="avatar avatar-sm rounded-circle border border-2 border-white shadow-sm" style="background-image: url(img/bot_ai_brain.png)" title="คุณหมอเจนี่ (Strategy Analyzer)" data-bs-toggle="tooltip"></span>
              <span class="avatar avatar-sm rounded-circle border border-2 border-white shadow-sm" style="background-image: url(img/bot_joiner.png)" title="คุณแมท (Community Connector)" data-bs-toggle="tooltip"></span>
            </div>
          </div>
          <div class="btn-list">
            <button type="button" class="btn btn-outline-primary d-none d-sm-inline-flex" data-bs-toggle="modal" data-bs-target="#modal-manual-group">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
              เพิ่มกลุ่ม (Manual)
            </button>
            <a href="fb_settings.php" class="btn btn-primary d-none d-sm-inline-flex ms-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37a1.724 1.724 0 0 0 2.572 -1.065z" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>
              Scraper Settings
            </a>
            <span class="d-none d-sm-inline ms-2">
              <span class="badge bg-success-lt p-2 px-3">Joined: <?= number_format($stats['joined']) ?></span>
              <span class="badge bg-warning-lt p-2 px-3 ms-2">Pending: <?= number_format($stats['pending']) ?></span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid">

    <!-- Filter Bar -->
    <form method="GET" class="card mb-3">
      <div class="card-body py-2">
        <div class="row g-2 align-items-end">
          <div class="col-md-2">
            <label class="form-label small mb-1">ค้นหาชื่อกลุ่ม / URL / สถานที่</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="เช่น Massage, UK, Spa..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1">โซนเป้าหมาย</label>
            <select name="zone" class="form-select form-select-sm">
              <option value="">— ทั้งหมด —</option>
              <option value="Thailand" <?= $zone_f=='Thailand'?'selected':'' ?>>🇹🇭 ในประเทศ</option>
              <option value="Global"   <?= $zone_f=='Global'?'selected':'' ?>>🌍 ต่างประเทศ</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1">สถานะ</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">— ทั้งหมด —</option>
              <option value="Not Joined" <?= $status_f=='Not Joined'?'selected':'' ?>>🔘 Not Joined</option>
              <option value="Queued"     <?= $status_f=='Queued'?'selected':'' ?>>🗂️ Queued</option>
              <option value="Pending"    <?= $status_f=='Pending'?'selected':'' ?>>⏳ Pending</option>
              <option value="Joined"     <?= $status_f=='Joined'?'selected':'' ?>>✅ Joined</option>
              <option value="Blocked"    <?= $status_f=='Blocked'?'selected':'' ?>>🚫 Blocked</option>
            </select>
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
              <option value="Observe"  <?= $strategy_f=='Observe'?'selected':'' ?>>🔴 Case Red: Observer</option>
              <option value="Soft"     <?= $strategy_f=='Soft'?'selected':'' ?>>🟡 Case Yellow: Soft Sell</option>
              <option value="Hard"     <?= $strategy_f=='Hard'?'selected':'' ?>>🟢 Case Green: Hard Sell</option>
              <option value="Blacklist" <?= $strategy_f=='Blacklist'?'selected':'' ?>>⚪ Case White: Blacklist</option>
            </select>
          </div>
          <div class="col-md-auto">
            <button type="submit" class="btn btn-primary btn-sm">🔍 กรองข้อมูล</button>
            <a href="fb_groups.php" class="btn btn-icon btn-sm btn-ghost-secondary ms-1" title="รีเซ็ต">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon m-0" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
            </a>
          </div>
        </div>
      </div>
    </form>

    <!-- Groups Table -->
    <div class="card">
      <div class="card-header border-bottom-0">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
          <li class="nav-item">
            <a href="?q=<?=urlencode($search)?>&status=<?=urlencode($status_f)?>&cat=<?=urlencode($cat_f)?>&sort=<?=urlencode($sort)?>" class="nav-link <?= $quality_f == '' ? 'active fw-bold' : '' ?>">
              รวมทั้งหมด <span class="badge bg-secondary ms-2" id="count-active"><?= number_format((int)$tab_stats['active_total']) ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a href="?q=<?=urlencode($search)?>&status=<?=urlencode($status_f)?>&cat=<?=urlencode($cat_f)?>&sort=<?=urlencode($sort)?>&quality=Selected" class="nav-link text-info <?= $quality_f == 'Selected' ? 'active fw-bold' : '' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-checkbox me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11l3 3l8 -8" /><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9" /></svg>
              Selected <span class="badge bg-info ms-2" id="count-selected"><?= number_format((int)$tab_stats['quality_selected']) ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a href="?q=<?=urlencode($search)?>&status=<?=urlencode($status_f)?>&cat=<?=urlencode($cat_f)?>&sort=<?=urlencode($sort)?>&quality=Best" class="nav-link text-success <?= $quality_f == 'Best' ? 'active fw-bold' : '' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-star me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" /></svg>
              ⭐ Best (สุดยอด) <span class="badge bg-success ms-2" id="count-best"><?= number_format((int)$tab_stats['quality_best']) ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a href="?q=<?=urlencode($search)?>&status=<?=urlencode($status_f)?>&cat=<?=urlencode($cat_f)?>&sort=<?=urlencode($sort)?>&quality=Good" class="nav-link text-primary <?= $quality_f == 'Good' ? 'active fw-bold' : '' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-thumb-up me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 11v8a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-7a1 1 0 0 1 1 -1h3a4 4 0 0 0 4 -4v-1a2 2 0 0 1 4 0v5h3a2 2 0 0 1 2 2l-1 5a2 3 0 0 1 -2 2h-7a3 3 0 0 1 -3 -3" /></svg>
              Good <span class="badge bg-primary ms-2" id="count-good"><?= number_format((int)$tab_stats['quality_good']) ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a href="?q=<?=urlencode($search)?>&status=<?=urlencode($status_f)?>&cat=<?=urlencode($cat_f)?>&sort=<?=urlencode($sort)?>&quality=Poor" class="nav-link text-danger <?= $quality_f == 'Poor' ? 'active fw-bold' : '' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-thumb-down me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 13v-8a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v7a1 1 0 0 0 1 1h3a4 4 0 0 1 4 4v1a2 2 0 0 0 4 0v-5h3a2 2 0 0 0 2 -2l-1 -5a2 3 0 0 0 -2 -2h-7a3 3 0 0 0 -3 3" /></svg>
              Poor <span class="badge bg-danger ms-2" id="count-poor"><?= number_format((int)$tab_stats['quality_poor']) ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a href="?q=<?=urlencode($search)?>&status=<?=urlencode($status_f)?>&cat=<?=urlencode($cat_f)?>&sort=<?=urlencode($sort)?>&quality=Delete" class="nav-link text-secondary <?= $quality_f == 'Delete' ? 'active fw-bold' : '' ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
              Bin <span class="badge bg-secondary ms-2" id="count-bin"><?= number_format((int)$tab_stats['quality_delete']) ?></span>
            </a>
          </li>
          <li class="nav-item ms-auto d-flex align-items-center pe-3">
            <div class="input-group input-group-sm">
              <select id="bulkActionSelect" class="form-select border-primary text-primary" style="min-width: 150px;">
                <option value="">-- ทำการหลายรายการ --</option>
                <optgroup label="แคมเปญโฆษณา">
                  <option value="assign_campaign">📌 นำเข้าแคมเปญ (Assign)</option>
                </optgroup>
                <optgroup label="การเข้าร่วม (Join)">
                  <option value="queue">⏳ นำเข้าคิว (Queue)</option>
                  <option value="mark_joined">✅ มาร์คว่าเข้าร่วมแล้ว (Mark Joined)</option>
                </optgroup>
                <optgroup label="การโพสต์ (Posting)">
                  <option value="post_queue">📋 ส่งเข้าคิวโพสต์ (Add to Post Queue)</option>
                </optgroup>
                <optgroup label="AI Strategy">
                  <option value="strategy_hard">🟢 ตั้งเป็น Hard Sell (Green)</option>
                  <option value="strategy_soft">🟡 ตั้งเป็น Soft Sell (Yellow)</option>
                  <option value="strategy_observe">🔴 ตั้งเป็น Observer (Red)</option>
                  <option value="strategy_blacklist">⚪ ตั้งเป็น Blacklist</option>
                </optgroup>
                <optgroup label="อื่นๆ">
                  <option value="zone_global">🌍 เซ็ตเป็น ต่างประเทศ</option>
                  <option value="zone_thailand">🇹🇭 เซ็ตเป็น ในประเทศ</option>
                  <option value="quality_good">👍 เซ็ตเป็น Good</option>
                  <option value="delete">🗑 ลบทิ้ง (Delete)</option>
                </optgroup>
              </select>
              <button id="btnApplyBulk" class="btn btn-primary">นำไปใช้</button>
            </div>
          </li>
        </ul>
      </div>
      <div class="table-responsive">
        <table class="table table-vcenter table-hover table-sm card-table">
          <thead class="table-light">
            <tr>
              <th style="width: 30px"><input type="checkbox" id="selectAll" class="form-check-input m-0"></th>
              <th style="width: 40px">ID</th>
              <th style="width: 50px">Score</th>
              <th style="width: 110px">AI Strategy</th>
              <th>Group Name & URL</th>
              <th class="text-center" style="width: 80px;">โพสต์แล้ว</th>
              <th class="text-danger" style="min-width: 180px; width: 180px;">TAGS</th>
              <th class="text-center" style="width: 70px;">Members</th>
              <th class="text-center" style="width: 70px;">Privacy</th>
              <th style="width: 100px;">AI Tags / Category</th>
              <th class="text-center" style="width: 50px;">Zone</th>
              <th class="text-center" style="width: 90px;">Quality</th>
              <th class="text-center" style="width: 80px;">Status</th>
              <th class="text-end" style="width: 100px;">Actions</th>
            </tr>
          </thead>
          <tbody id="group-table-body">
            <?php if(empty($groups)): ?>
            <tr><td colspan="8" class="text-center text-muted py-5">ไม่พบข้อมูลกลุ่ม Facebook</td></tr>
            <?php endif; ?>
            <?php foreach($groups as $g): ?>
            <tr>
              <td><input type="checkbox" class="form-check-input m-0 row-checkbox <?= $g['join_status'] !== 'Joined' ? 'd-none' : '' ?>" value="<?= $g['id'] ?>"></td>
              <td><span class="text-muted small">#<?= $g['id'] ?></span></td>
              <td>
                <div class="d-flex align-items-center gap-1">
                  <span class="badge bg-<?= $g['suitability_score'] >= 7 ? 'success' : ($g['suitability_score'] >= 4 ? 'warning' : 'danger') ?>-lt p-1 d-flex align-items-center">
                    <input type="number" class="inline-edit-field bg-transparent border-0 text-center fw-bold" style="width: 30px; outline: none; color: inherit; appearance: none; -moz-appearance: textfield;" value="<?= $g['suitability_score'] ?>" data-id="<?= $g['id'] ?>" data-field="suitability_score" min="0" max="10">
                    /10
                  </span>
                </div>
              </td>
              <td class="text-center">
                <?php
                $strat = $g['strategy_marker'] ?? 'Observe';
                $strat_color = 'danger';
                if($strat == 'Soft') $strat_color = 'warning';
                if($strat == 'Hard') $strat_color = 'success';
                if($strat == 'Blacklist') $strat_color = 'secondary';
                ?>
                <select class="form-select form-select-sm badge bg-<?=$strat_color?> fw-bold strategy-select" data-id="<?=$g['id']?>" style="width:125px; cursor:pointer;">
                  <option value="Observe"   <?= $strat=='Observe'?'selected':'' ?>>🔴 Observer</option>
                  <option value="Soft"      <?= $strat=='Soft'?'selected':'' ?>>🟡 Case Yellow</option>
                  <option value="Hard"      <?= $strat=='Hard'?'selected':'' ?>>🟢 Case Green</option>
                  <option value="Blacklist" <?= $strat=='Blacklist'?'selected':'' ?>>⚪ Blacklist</option>
                </select>
              </td>
              <td style="max-width: 0; width: 100%;">
                <div class="fw-bold text-truncate" title="<?= htmlspecialchars($g['group_name']) ?>"><?= htmlspecialchars($g['group_name']) ?></div>
                <div class="small text-muted mt-1 d-flex align-items-center flex-wrap gap-1">
                  <a href="<?= htmlspecialchars($g['group_url']) ?>" target="_blank" class="text-decoration-none me-2 text-truncate" style="max-width: 80%;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6l-8 4l8 4l8 -4l-8 -4" /><path d="M4 14l8 4l8 -4" /></svg>
                  </a>
                  <span class="text-muted text-nowrap me-2" style="font-size: 0.65rem;" title="เวลาที่ดึงข้อมูล">
                    <?= isset($g['last_scraped_at']) ? date('d M y', strtotime($g['last_scraped_at'])) : (isset($g['created_at']) ? date('d M y', strtotime($g['created_at'])) : 'N/A') ?>
                  </span>
                  
                  <!-- Note Icon and Display -->
                  <span style="cursor:pointer;" class="text-secondary" onclick="editGroupNote(<?= $g['id'] ?>, '<?= htmlspecialchars(addslashes($g['notes'] ?? '')) ?>')" title="บันทึกย่อ (Note)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-pencil" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
                  </span>
                  <?php if (!empty($g['notes'])): ?>
                    <span class="badge bg-yellow-lt text-wrap text-start" style="font-size: 0.7rem; font-weight: normal; max-width: 100%; cursor:pointer;" onclick="editGroupNote(<?= $g['id'] ?>, '<?= htmlspecialchars(addslashes($g['notes'])) ?>')">
                        📝 <?= htmlspecialchars($g['notes']) ?>
                    </span>
                  <?php endif; ?>
                  
                  <?php if (!empty($g['posted_campaigns'])): ?>
                    <span class="text-muted ms-2" style="font-size: 0.7rem;">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-send text-primary" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="margin-top:-2px;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14l11 -11" /><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" /></svg>
                      <?= htmlspecialchars($g['posted_campaigns']) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="text-center text-nowrap" id="td-total-posts-<?= $g['id'] ?>">
                <?php 
                $manual_cnt = (int)($g['manual_post_count'] ?? 0);
                $total_cnt = (int)($g['total_posts'] ?? 0);
                $bot_cnt = max(0, $total_cnt - $manual_cnt);
                if($total_cnt > 0): ?>
                  <a href="javascript:void(0)" class="text-purple fw-bold text-decoration-none" style="font-size: 12px;" onclick="openPastPostModal(<?= $g['id'] ?>, <?= $manual_cnt ?>, <?= $bot_cnt ?>)" title="คลิกเพื่อแก้ไขจำนวนโพสต์ในอดีต">
                      <?= $total_cnt ?> ครั้ง
                  </a>
                <?php else: ?>
                  <a href="javascript:void(0)" class="text-muted text-decoration-none" style="font-size: 12px;" onclick="openPastPostModal(<?= $g['id'] ?>, 0, 0)" title="คลิกเพื่อตั้งค่าจำนวนโพสต์ในอดีต">
                      -
                  </a>
                <?php endif; ?>
              </td>
              <td id="tags-cell-<?= $g['id'] ?>">
                <div class="d-flex flex-wrap align-items-center gap-1">
                  <?php $tagStr = $g['campaign_tag'] ?? ''; ?>
                  <span class="badge bg-secondary-lt text-muted" style="cursor: pointer; padding: 2px 4px; font-size: 10px; font-weight: normal;" onclick="openTagModal(<?= $g['id'] ?>, '<?= htmlspecialchars(addslashes($tagStr)) ?>')" title="เพิ่ม/แก้ไขแท็ก">+</span>
                  <?php 
                    $tags = $tagStr ? array_filter(array_map('trim', explode(',', $tagStr))) : [];
                    if($tags): 
                      foreach($tags as $t): ?>
                        <a href="?q=<?= urlencode($t) ?>" class="badge bg-<?= getTagColor($t) ?>-lt text-decoration-none" style="font-size: 9px; font-weight: normal; padding: 2px 6px;"><?= htmlspecialchars($t) ?></a>
                      <?php endforeach;
                    endif; ?>
                </div>
              </td>
              <td class="text-center">
                <div class="fw-medium">
                  <input type="text" class="inline-edit-field form-control form-control-sm text-center border-0 bg-transparent p-0 fw-medium" value="<?= number_format((float)$g['member_count']) ?>" data-id="<?= $g['id'] ?>" data-field="member_count" style="width: 60px; margin: 0 auto; outline: none; font-size: 0.8rem;" placeholder="0">
                </div>
              </td>
              <td class="text-center">
                <select class="form-select form-select-sm badge bg-<?= $g['privacy_type'] == 'Public' ? 'azure' : 'purple' ?>-lt fw-bold border-0 inline-edit-field" data-id="<?=$g['id']?>" data-field="privacy_type" style="width:90px; text-align:center; cursor:pointer;" onclick="event.stopPropagation();">
                  <option value="Public" <?= $g['privacy_type']=='Public'?'selected':'' ?> class="text-dark bg-white">Public</option>
                  <option value="Private" <?= $g['privacy_type']=='Private'?'selected':'' ?> class="text-dark bg-white">Private</option>
                  <option value="Hidden" <?= $g['privacy_type']=='Hidden'?'selected':'' ?> class="text-dark bg-white">Hidden</option>
                </select>
                <?php if($g['approval_required']): ?>
                  <div class="small text-danger mt-1" style="font-size: .6rem;">Admin Approval</div>
                <?php endif; ?>
              </td>
              <td>
                <?php if($g['category_tag'] && $g['category_tag'] !== 'Scraped'): ?>
                  <div class="d-flex flex-wrap gap-1">
                    <?php 
                      $tags = explode(" ", str_replace("][", "] [", $g['category_tag']));
                      foreach($tags as $t): if(trim($t)):
                    ?>
                      <span class="badge badge-outline text-secondary" style="font-size: .6rem;"><?= htmlspecialchars($t) ?></span>
                    <?php endif; endforeach; ?>
                  </div>
                  <?php if($g['ai_summary']): ?>
                    <div class="small text-muted mt-1" style="font-size: .7rem; font-style: italic;" title="<?= htmlspecialchars($g['ai_summary']) ?>">
                      <?= mb_strimwidth($g['ai_summary'], 0, 50, "...") ?>
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted small">Not Analyzed</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php
                $z_color = 'secondary';
                if($g['target_zone'] === 'Thailand') $z_color = 'info';
                if($g['target_zone'] === 'Global') $z_color = 'orange';
                ?>
                <div class="dropdown">
                  <div class="badge bg-<?=$z_color?>-lt fw-bold border-0 text-center dropdown-toggle" data-bs-toggle="dropdown" style="width:45px; cursor:pointer; padding: 4px;" id="zone-btn-<?=$g['id']?>">
                    <?php if($g['target_zone']=='Thailand'): ?>
                      <img src="https://flagcdn.com/16x12/th.png" alt="TH" style="vertical-align: middle; border-radius: 2px;">
                    <?php elseif($g['target_zone']=='Global'): ?>
                      <img src="https://flagcdn.com/16x12/un.png" alt="Global" style="vertical-align: middle; border-radius: 2px;">
                    <?php else: ?>
                      ❓
                    <?php endif; ?>
                  </div>
                  <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 120px; font-size: 0.8rem;">
                    <li><a class="dropdown-item py-1 zone-dropdown" href="javascript:void(0)" data-id="<?=$g['id']?>" data-val="Uncategorized">❓ Uncategorized</a></li>
                    <li><a class="dropdown-item py-1 zone-dropdown" href="javascript:void(0)" data-id="<?=$g['id']?>" data-val="Thailand"><img src="https://flagcdn.com/16x12/th.png" alt="TH" class="me-2" style="width:16px;">🇹🇭 ในประเทศ</a></li>
                    <li><a class="dropdown-item py-1 zone-dropdown" href="javascript:void(0)" data-id="<?=$g['id']?>" data-val="Global"><img src="https://flagcdn.com/16x12/un.png" alt="Global" class="me-2" style="width:16px;">🌍 ต่างประเทศ</a></li>
                  </ul>
                </div>
              </td>
              <td class="text-center">
                <div class="star-rating" data-id="<?=$g['id']?>" data-rating="<?= (float)($g['quality_star'] ?? 0) ?>" style="display:inline-flex; cursor:pointer;" title="ให้คะแนน (คลิกครึ่งดาวได้)">
                  <?php
                    $r = (float)($g['quality_star'] ?? 0);
                    for ($i=1; $i<=5; $i++) {
                      $fill = ($r >= $i) ? 100 : (($r >= $i - 0.5) ? 50 : 0);
                      $color = ($fill > 0) ? 'text-warning' : 'text-muted';
                      echo "<div class='star-item position-relative' data-idx='$i' style='width:16px; height:16px; display:inline-block; margin-right:2px;'>
                              <svg xmlns='http://www.w3.org/2000/svg' class='icon $color position-absolute top-0 start-0 m-0 w-100 h-100 star-svg' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' fill='".($fill==100?"currentColor":"none")."' stroke-linecap='round' stroke-linejoin='round'>
                                <path stroke='none' d='M0 0h24v24H0z' fill='none'/>
                                <path d='M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z' />";
                      if($fill == 50) {
                          echo "<path d='M12 17.75v-11.5l-3.086 6.253l-6.9 1l5 4.867l-1.179 6.873z' fill='currentColor' stroke='none' />";
                      }
                      echo "  </svg>
                            </div>";
                    }
                  ?>
                </div>
              </td>
              <td class="text-center">
                <?php
                  $st_color = 'secondary';
                  if($g['join_status'] == 'Joined') $st_color = 'success';
                  if($g['join_status'] == 'Pending') $st_color = 'warning';
                  if($g['join_status'] == 'Blocked') $st_color = 'danger';
                  if($g['join_status'] == 'Queued') $st_color = 'warning';
                ?>
                <select class="form-select form-select-sm badge bg-<?= $st_color ?>-lt fw-bold border-0 status-select" data-id="<?=$g['id']?>" style="width:90px; text-align:center; cursor:pointer;" onclick="event.stopPropagation();">
                  <option value="Not Joined" <?= $g['join_status']=='Not Joined'?'selected':'' ?> class="text-dark bg-white">Not Joined</option>
                  <option value="Queued" <?= $g['join_status']=='Queued'?'selected':'' ?> class="text-dark bg-white">Queued</option>
                  <option value="Pending" <?= $g['join_status']=='Pending'?'selected':'' ?> class="text-dark bg-white">Pending</option>
                  <option value="Joined" <?= $g['join_status']=='Joined'?'selected':'' ?> class="text-dark bg-white">Joined</option>
                  <option value="Blocked" <?= $g['join_status']=='Blocked'?'selected':'' ?> class="text-dark bg-white">Blocked</option>
                </select>
              </td>
              <td class="text-end">
                <div class="btn-group">

                  <button class="btn btn-icon btn-ghost-warning px-1 py-1 edit-group-btn" style="font-size:0.7rem;" 
                      data-id="<?= $g['id'] ?>"
                      data-name="<?= htmlspecialchars($g['group_name']) ?>"
                      data-url="<?= htmlspecialchars($g['group_url']) ?>"
                      data-member="<?= (int)$g['member_count'] ?>"
                      data-privacy="<?= htmlspecialchars($g['privacy_type']) ?>"
                      data-category="<?= htmlspecialchars($g['category_tag']) ?>"
                      data-zone="<?= htmlspecialchars($g['target_zone']) ?>"
                      data-status="<?= htmlspecialchars($g['join_status']) ?>"
                      title="แก้ไขข้อมูล (Manual Edit)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
                  </button>
                  <button class="btn btn-icon btn-ghost-danger px-1 py-1 delete-group" style="font-size:0.7rem;" data-id="<?= $g['id'] ?>" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7" /><line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="card-footer d-flex align-items-center">
        <ul class="pagination m-0 ms-auto">
          <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <?php
            // Build query string for prev link
            $q_params_prev = [
                'q' => $search,
                'status' => $status_f,
                'cat' => $cat_f,
                'sort' => $sort,
                'zone' => $zone_f,
                'strategy' => $strategy_f,
                'quality' => $quality_f
            ];
            $q_params_prev = array_filter($q_params_prev, fn($v) => $v !== '');
            $q_str_prev = http_build_query($q_params_prev);
            $q_str_prev = $q_str_prev ? '&' . $q_str_prev : '';
            ?>
            <a class="page-link" href="?page=<?= $page - 1 ?><?= $q_str_prev ?>" tabindex="-1" aria-disabled="true">prev</a>
          </li>
          <?php 
          ?>
          <?php
          // Build query string for pagination
          $q_params = [
              'q' => $search,
              'status' => $status_f,
              'cat' => $cat_f,
              'sort' => $sort,
              'zone' => $zone_f,
              'strategy' => $strategy_f,
              'quality' => $quality_f
          ];
          // Remove empty params to keep URL clean
          $q_params = array_filter($q_params, fn($v) => $v !== '');
          $q_str = http_build_query($q_params);
          $q_str = $q_str ? '&' . $q_str : '';
          ?>
          <?php for ($i = max(1, $page - 3); $i <= min($total_pages, $page + 3); $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?><?= $q_str ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?><?= $q_str ?>">next</a>
          </li>
        </ul>
      </div>
      <?php endif; ?>
    </div>

  </div>
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

<!-- Past Posts Edit Modal -->
<div class="modal modal-blur fade" id="modal-past-posts" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">แก้ไขจำนวนโพสต์ในอดีต</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p class="text-muted small mb-3">ระบุตัวเลขจำนวนครั้งที่คุณเคยโพสต์ลงกลุ่มนี้ด้วยตัวเองในอดีต (ระบบจะนำไปรวมกับยอดโพสต์โดยบอทให้อัตโนมัติ)</p>
        <div class="input-group mb-2">
            <span class="input-group-text">ในอดีต</span>
            <input type="number" id="past-posts-input" class="form-control text-center" min="0">
            <span class="input-group-text">ครั้ง</span>
        </div>
        <div class="small text-primary mt-2">
            โพสต์โดยบอท: <span id="past-posts-bot-count" class="fw-bold">0</span> ครั้ง
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary" id="btn-save-past-posts">บันทึกข้อมูล</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Manual Group Modal -->
<div class="modal modal-blur fade" id="modal-manual-group" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-manual-group">
      <div class="modal-header">
        <h5 class="modal-title">➕ เพิ่มกลุ่ม (Manual Add)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">ชื่อกลุ่ม <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="group_name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">URL กลุ่ม (https://facebook.com/groups/...) <span class="text-danger">*</span></label>
          <input type="url" class="form-control" id="manual-group-url" name="group_url" required>
          <div id="url-feedback" class="invalid-feedback" style="display:none; font-size: 0.8rem; margin-top: 4px;"></div>
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
              <input type="text" class="form-control" name="category_tag" value="Manual">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">โซน (Zone)</label>
              <select name="target_zone" class="form-select">
                <option value="Uncategorized">❓ Uncategorized</option>
                <option value="Thailand">🇹🇭 ในประเทศ</option>
                <option value="Global">🌍 ต่างประเทศ</option>
              </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">สถานะ (Status)</label>
            <select name="join_status" class="form-select">
                <option value="Not Joined">Not Joined (ยังไม่เข้า)</option>
                <option value="Pending">Pending (รออนุมัติ)</option>
                <option value="Joined">Joined (เข้าร่วมแล้ว)</option>
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="btn-save-manual">บันทึกกลุ่ม</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Manual Group Modal -->
<div class="modal modal-blur fade" id="modal-edit-group" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-edit-group">
      <input type="hidden" name="id" id="edit-group-id">
      <div class="modal-header">
        <h5 class="modal-title">✏️ แก้ไขกลุ่มแบบแมนนวล</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">ชื่อกลุ่ม <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="group_name" id="edit-group-name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">URL กลุ่ม <span class="text-danger">*</span></label>
          <input type="url" class="form-control" name="group_url" id="edit-group-url" required>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">จำนวนสมาชิก</label>
              <input type="number" class="form-control" name="member_count" id="edit-group-member">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">ประเภท (Privacy)</label>
              <select name="privacy_type" id="edit-group-privacy" class="form-select">
                <option value="Public">Public (สาธารณะ)</option>
                <option value="Private">Private (ส่วนตัว)</option>
                <option value="Hidden">Hidden (ซ่อน)</option>
              </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">หมวดหมู่/แท็ก (Category)</label>
              <input type="text" class="form-control" name="category_tag" id="edit-group-category">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">โซน (Zone)</label>
              <select name="target_zone" id="edit-group-zone" class="form-select">
                <option value="Uncategorized">❓ Uncategorized</option>
                <option value="Thailand">🇹🇭 ในประเทศ</option>
                <option value="Global">🌍 ต่างประเทศ</option>
              </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">สถานะ (Status)</label>
            <select name="join_status" id="edit-group-status" class="form-select">
                <option value="Not Joined">Not Joined (ยังไม่เข้า)</option>
                <option value="Pending">Pending (รออนุมัติ)</option>
                <option value="Joined">Joined (เข้าร่วมแล้ว)</option>
                <option value="Queued">Queued (นำเข้าคิว)</option>
                <option value="Blocked">Blocked (บล็อค)</option>
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-primary" id="btn-save-edit">บันทึกข้อมูล</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script>
// Edit Modal Logic
document.querySelectorAll('.edit-group-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit-group-id').value = this.dataset.id;
        document.getElementById('edit-group-name').value = this.dataset.name;
        document.getElementById('edit-group-url').value = this.dataset.url;
        document.getElementById('edit-group-member').value = this.dataset.member;
        document.getElementById('edit-group-privacy').value = this.dataset.privacy;
        document.getElementById('edit-group-category').value = this.dataset.category;
        document.getElementById('edit-group-zone').value = this.dataset.zone;
        document.getElementById('edit-group-status').value = this.dataset.status;
        
        new bootstrap.Modal(document.getElementById('modal-edit-group')).show();
    });
});

document.getElementById('form-edit-group').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-edit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> กำลังบันทึก...';
    
    const formData = new FormData(this);
    
    fetch('api/edit_manual_group.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.ok) {
            const id = document.getElementById('edit-group-id').value;
            const tr = document.querySelector(`.edit-group-btn[data-id="${id}"]`).closest('tr');
            
            // Update the data attributes on the button so if opened again it has latest data
            const btnEdit = tr.querySelector('.edit-group-btn');
            if (btnEdit) {
                btnEdit.dataset.name = formData.get('group_name');
                btnEdit.dataset.url = formData.get('group_url');
                btnEdit.dataset.member = formData.get('member_count');
                btnEdit.dataset.privacy = formData.get('privacy_type');
                btnEdit.dataset.category = formData.get('category_tag');
                btnEdit.dataset.zone = formData.get('target_zone');
                btnEdit.dataset.status = formData.get('join_status');
            }

            // Update DOM visual parts
            const nameEl = tr.querySelector('.fw-bold.text-truncate');
            if(nameEl) nameEl.innerText = formData.get('group_name');
            
            const urlEl = tr.querySelector('a[target="_blank"]');
            if(urlEl) urlEl.href = formData.get('group_url');

            const memberInput = tr.querySelector('input[data-field="member_count"]');
            if(memberInput) memberInput.value = parseFloat(formData.get('member_count')).toLocaleString();
            
            const privacySel = tr.querySelector('select[data-field="privacy_type"]');
            if(privacySel) privacySel.value = formData.get('privacy_type');
            
            const catBadge = tr.querySelector('.badge.badge-outline.text-secondary');
            if(catBadge) catBadge.innerText = formData.get('category_tag');

            const zoneBtn = document.getElementById('zone-btn-' + id);
            if(zoneBtn) {
                let z = formData.get('target_zone');
                let z_color = 'secondary';
                let iconHTML = '❓';
                if(z === 'Thailand') { z_color = 'info'; iconHTML = '<img src="https://flagcdn.com/16x12/th.png" alt="TH" style="vertical-align: middle; border-radius: 2px;">'; }
                if(z === 'Global') { z_color = 'orange'; iconHTML = '<img src="https://flagcdn.com/16x12/un.png" alt="Global" style="vertical-align: middle; border-radius: 2px;">'; }
                zoneBtn.className = `badge bg-${z_color}-lt fw-bold border-0 text-center dropdown-toggle`;
                zoneBtn.innerHTML = iconHTML;
            }

            const statusBadge = tr.querySelector('.status-badge');
            if(statusBadge) {
                let st = formData.get('join_status');
                let st_color = 'secondary';
                if(st == 'Joined') st_color = 'success';
                if(st == 'Pending') st_color = 'warning';
                if(st == 'Blocked') st_color = 'danger';
                if(st == 'Queued') st_color = 'warning';
                statusBadge.className = `badge status-badge bg-${st_color}-lt`;
                statusBadge.innerText = st;
            }

            bootstrap.Modal.getInstance(document.getElementById('modal-edit-group')).hide();
        } else {
            alert(data.msg);
        }
    })
    .catch(e => alert('Error connecting to server'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'บันทึกข้อมูล';
    });
});

// 1. Queue for Join Action
document.querySelectorAll('.q-join').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const originalText = this.innerText;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        this.disabled = true;
        
        fetch('api/update_group_status.php?api_key=yakpho2026', {
            method: 'POST',
            body: JSON.stringify({ id: id, status: 'Queued' })
        }).then(r => r.json()).then(data => {
            if(data.ok) {
                // อัปเดต UI ทันทีไม่ต้องรีเฟรช
                const tr = btn.closest('tr');
                const badge = tr.querySelector('.status-badge');
                if (badge) {
                    badge.className = 'badge status-badge bg-warning-lt';
                    badge.innerText = 'Queued';
                }
                const joinBtn = tr.querySelector('.update-status[data-status="Pending"]');
                if (joinBtn) joinBtn.remove(); // ลบปุ่ม Join เดิมทิ้ง
                btn.remove(); // ลบปุ่ม Queue ออก
            } else {
                alert(data.msg);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }).catch(e => {
            alert('Connection Error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
});

// 2. AI Drafting Tool
function draftIntro(groupId) {
    const modal = new bootstrap.Modal(document.getElementById('modal-ai-draft'));
    const content = document.getElementById('ai-draft-content');
    content.innerHTML = '<div class="text-center py-4"><span class="spinner-border spinner-border-sm text-muted"></span> กำลังใช้ Gemini ร่างข้อความแนะนำตัว...</div>';
    modal.show();

    fetch('api/get_ai_draft.php?id=' + groupId + '&api_key=yakpho2026')
        .then(r => r.json())
        .then(data => {
            if(data.ok) {
                content.innerText = data.draft;
            } else {
                content.innerHTML = '<div class="alert alert-danger">' + data.msg + '</div>';
            }
        });
}

function copyDraft() {
    const text = document.getElementById('ai-draft-content').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('คัดลอกข้อความสำเร็จ! นำไปใช้โพสต์ได้เลยครับ');
    });
}
</script>


    <!-- AI Strategy Manual -->
    <div class="card mt-4 border-0 shadow-sm">
      <div class="card-header bg-dark text-white rounded-top">
        <h3 class="card-title">📖 คู่มือกลยุทธ์การวิเคราะห์ของ AI (AI Strategy Manual)</h3>
      </div>
      <div class="card-body bg-light-lt">
        <div class="row g-3">
          <div class="col-md-3">
            <div class="p-3 bg-white rounded border-start border-success border-4 shadow-sm h-100">
              <div class="d-flex align-items-center mb-2">
                <span class="badge bg-success me-2">🟢</span>
                <h4 class="m-0 text-success">Case Green: Hard Sell</h4>
              </div>
              <p class="small text-muted mb-0">
                <strong>ความหมาย:</strong> กลุ่มเน้นซื้อขาย/ตลาดนัด (Marketplace) ที่เปิดกว้าง<br>
                <strong>กลยุทธ์:</strong> สามารถโพสต์ขายสินค้า/บริการ (Hard Sell) ได้โดยตรง มีโอกาสได้ลูกค้าไว
              </p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 bg-white rounded border-start border-warning border-4 shadow-sm h-100">
              <div class="d-flex align-items-center mb-2">
                <span class="badge bg-warning me-2">🟡</span>
                <h4 class="m-0 text-warning">Case Yellow: Soft Sell</h4>
              </div>
              <p class="small text-muted mb-0">
                <strong>ความหมาย:</strong> กลุ่มสังคม/สุขภาพ/เครือข่ายวิชาชีพ<br>
                <strong>กลยุทธ์:</strong> เน้นโพสต์ให้ความรู้ (Value) หรือแชร์ประสบการณ์ก่อน แล้วค่อยแฝงการขายเบาๆ (Soft Sell)
              </p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 bg-white rounded border-start border-danger border-4 shadow-sm h-100">
              <div class="d-flex align-items-center mb-2">
                <span class="badge bg-danger me-2">🔴</span>
                <h4 class="m-0 text-danger">Case Red: Observer</h4>
              </div>
              <p class="small text-muted mb-0">
                <strong>ความหมาย:</strong> กลุ่มปิด/กลุ่มทางการ/ระเบียบจัด<br>
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
          * กลยุทธ์เหล่านี้ถูกวิเคราะห์โดย Gemini AI โดยอิงจาก ชื่อกลุ่ม, สมาชิก และข้อมูลบริบท เพื่อช่วยให้ทีมงานทำงานได้รวดเร็วขึ้น
        </div>
      </div>
    </div>

<!-- Assign Campaign Modal -->
<div class="modal modal-blur fade" id="modal-assign-campaign" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">📌 นำเข้าแคมเปญโฆษณา</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="assign-selected-ids" value="">
        <div class="mb-3">
          <label class="form-label">เลือกแคมเปญที่ต้องการนำกลุ่มไปใส่</label>
          <select id="assign-campaign-id" class="form-select">
            <option value="">-- เลือกแคมเปญ --</option>
            <?php foreach($active_campaigns as $cp): ?>
              <option value="<?= $cp['id'] ?>"><?= htmlspecialchars($cp['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary" id="btn-confirm-assign">เพิ่มเข้าแคมเปญ</button>
      </div>
    </div>
  </div>
</div>

<?php include "components/footer.php"; ?>


<script>
let currentEditPastGroupId = 0;
let currentEditBotCount = 0;

function openPastPostModal(groupId, manualCount, botCount) {
    currentEditPastGroupId = groupId;
    currentEditBotCount = botCount;
    document.getElementById('past-posts-input').value = manualCount;
    document.getElementById('past-posts-bot-count').innerText = botCount;
    new bootstrap.Modal(document.getElementById('modal-past-posts')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    
    // Past Posts Save Action
    const btnSavePast = document.getElementById('btn-save-past-posts');
    if (btnSavePast) {
        btnSavePast.addEventListener('click', async function() {
            const newVal = parseInt(document.getElementById('past-posts-input').value) || 0;
            const btn = this;
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            try {
                const response = await fetch('api/update_group_info.php?api_key=yakpho2026', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: currentEditPastGroupId, field: 'manual_post_count', value: newVal })
                });
                const res = await response.json();
                if (res.ok) {
                    const total = currentEditBotCount + newVal;
                    const td = document.getElementById('td-total-posts-' + currentEditPastGroupId);
                    if (td) {
                        if (total > 0) {
                            td.innerHTML = `<a href="javascript:void(0)" class="text-purple fw-bold text-decoration-none" style="font-size: 12px;" onclick="openPastPostModal(${currentEditPastGroupId}, ${newVal}, ${currentEditBotCount})" title="คลิกเพื่อแก้ไขจำนวนโพสต์ในอดีต">${total} ครั้ง</a>`;
                        } else {
                            td.innerHTML = `<a href="javascript:void(0)" class="text-muted text-decoration-none" style="font-size: 12px;" onclick="openPastPostModal(${currentEditPastGroupId}, 0, 0)" title="คลิกเพื่อตั้งค่าจำนวนโพสต์ในอดีต">-</a>`;
                        }
                    }
                    const modalEl = document.getElementById('modal-past-posts');
                    const modalInst = bootstrap.Modal.getInstance(modalEl);
                    if(modalInst) modalInst.hide();
                } else {
                    alert(res.msg);
                }
            } catch (e) {
                alert('Connection Error');
            } finally {
                btn.innerHTML = origHtml;
                btn.disabled = false;
            }
        });
    }

    // Removed toggle-ready functionality

    // ฟังก์ชันช่วยอัปเดตตัวเลขบนแท็บแบบ Real-time
    function updateTabCounts(oldQuality, newQuality) {
        const badges = {
            'active': document.getElementById('count-active'),
            'Selected': document.getElementById('count-selected'),
            'Best': document.getElementById('count-best'),
            'Good': document.getElementById('count-good'),
            'Poor': document.getElementById('count-poor'),
            'Delete': document.getElementById('count-bin')
        };

        const getVal = (el) => el ? parseInt(el.innerText.replace(/,/g, '')) || 0 : 0;
        const setVal = (el, val) => { if(el) el.innerText = Math.max(0, val).toLocaleString(); };

        // ลดตัวเก่า (ถ้าไม่ใช่การเรียกดูหน้าแท็บนั้นๆ อยู่)
        if (oldQuality && badges[oldQuality]) {
            setVal(badges[oldQuality], getVal(badges[oldQuality]) - 1);
        }
        
        // เพิ่มตัวใหม่
        if (newQuality && badges[newQuality]) {
            setVal(badges[newQuality], getVal(badges[newQuality]) + 1);
        }

        // จัดการ "รวมทั้งหมด" (active)
        if (oldQuality !== 'Delete' && newQuality === 'Delete') {
            setVal(badges['active'], getVal(badges['active']) - 1);
        } else if (oldQuality === 'Delete' && newQuality !== 'Delete') {
            setVal(badges['active'], getVal(badges['active']) + 1);
        }
    }

    // Quality Star Rating Action
    const renderStars = (rating, container) => {
        container.querySelectorAll('.star-item').forEach(item => {
            const idx = parseInt(item.dataset.idx);
            const svg = item.querySelector('.star-svg');
            let fill = 0;
            if (rating >= idx) fill = 100;
            else if (rating >= idx - 0.5) fill = 50;
            
            if (fill === 100) {
                svg.className.baseVal = 'icon text-warning position-absolute top-0 start-0 m-0 w-100 h-100 star-svg';
                svg.setAttribute('fill', 'currentColor');
                svg.innerHTML = '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />';
            } else if (fill === 50) {
                svg.className.baseVal = 'icon text-warning position-absolute top-0 start-0 m-0 w-100 h-100 star-svg';
                svg.setAttribute('fill', 'none');
                svg.innerHTML = '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" /><path d="M12 17.75v-11.5l-3.086 6.253l-6.9 1l5 4.867l-1.179 6.873z" fill="currentColor" stroke="none" />';
            } else {
                svg.className.baseVal = 'icon text-muted position-absolute top-0 start-0 m-0 w-100 h-100 star-svg';
                svg.setAttribute('fill', 'none');
                svg.innerHTML = '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />';
            }
        });
    };

    document.querySelectorAll('.star-rating').forEach(container => {
        container.addEventListener('mousemove', e => {
            const item = e.target.closest('.star-item');
            if (!item) return;
            const rect = item.getBoundingClientRect();
            const isHalf = (e.clientX - rect.left) < (rect.width / 2);
            const idx = parseInt(item.dataset.idx);
            const hoverRating = isHalf ? idx - 0.5 : idx;
            renderStars(hoverRating, container);
        });

        container.addEventListener('mouseleave', () => {
            const currentRating = parseFloat(container.dataset.rating);
            renderStars(currentRating, container);
        });

        container.addEventListener('click', async e => {
            const item = e.target.closest('.star-item');
            if (!item) return;
            const rect = item.getBoundingClientRect();
            const isHalf = (e.clientX - rect.left) < (rect.width / 2);
            const idx = parseInt(item.dataset.idx);
            const newRating = isHalf ? idx - 0.5 : idx;
            const id = container.dataset.id;
            
            container.dataset.rating = newRating;
            renderStars(newRating, container);
            
            container.style.pointerEvents = 'none';
            try {
                const response = await fetch('api/update_group_info.php?api_key=yakpho2026', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, field: 'quality_star', value: newRating })
                });
                const res = await response.json();
                if (!res.ok) alert(res.msg);
            } catch (err) {
                alert('Connection Error');
            } finally {
                container.style.pointerEvents = 'auto';
            }
        });
    });

    // Zone Selection Action
    document.querySelectorAll('.zone-dropdown').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const zone = this.dataset.val;
            const btnEl = document.getElementById('zone-btn-' + id);
            const originalHTML = btnEl.innerHTML;
            btnEl.style.opacity = '0.5';
            try {
                const response = await fetch('api/update_group_info.php?api_key=yakpho2026', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, field: 'target_zone', value: zone })
                });
                const res = await response.json();
                if (res.ok) {
                    let z_color = 'secondary';
                    let iconHTML = '❓';
                    if(zone === 'Thailand') { z_color = 'info'; iconHTML = '<img src="https://flagcdn.com/16x12/th.png" alt="TH" style="vertical-align: middle; border-radius: 2px;">'; }
                    if(zone === 'Global') { z_color = 'orange'; iconHTML = '<img src="https://flagcdn.com/16x12/un.png" alt="Global" style="vertical-align: middle; border-radius: 2px;">'; }
                    btnEl.className = `badge bg-${z_color}-lt fw-bold border-0 text-center dropdown-toggle`;
                    btnEl.innerHTML = iconHTML;
                }
                else { alert(res.msg); btnEl.innerHTML = originalHTML; }
            } catch (e) { alert('Connection Error'); btnEl.innerHTML = originalHTML; } finally { btnEl.style.opacity = '1'; }
        });
    });

    // Strategy Selection Action
    document.querySelectorAll('.strategy-select').forEach(sel => {
        sel.addEventListener('change', async function() {
            const id = this.dataset.id;
            const strat = this.value;
            this.disabled = true;
            try {
                const response = await fetch('api/update_group_info.php?api_key=yakpho2026', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, field: 'strategy_marker', value: strat })
                });
                const res = await response.json();
                if (res.ok) {
                    let strat_color = 'danger';
                    if(strat === 'Soft') strat_color = 'warning';
                    if(strat === 'Hard') strat_color = 'success';
                    if(strat === 'Blacklist') strat_color = 'secondary';
                    this.className = `form-select form-select-sm badge bg-${strat_color} fw-bold strategy-select`;
                }
                else alert(res.msg);
            } catch (e) { alert('Connection Error'); } finally { this.disabled = false; }
        });
    });

    // Status Selection Action
    document.querySelectorAll('.status-select').forEach(sel => {
        sel.addEventListener('change', async function() {
            const id = this.dataset.id;
            const status = this.value;
            this.disabled = true;
            try {
                const response = await fetch('api/fb_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=update_status&id=${id}&status=${status}`
                });
                const res = await response.json();
                if (res.ok) {
                    let st_color = 'secondary';
                    if(status === 'Joined') st_color = 'success';
                    if(status === 'Pending') st_color = 'warning';
                    if(status === 'Blocked') st_color = 'danger';
                    if(status === 'Queued') st_color = 'warning';
                    this.className = `form-select form-select-sm badge bg-${st_color}-lt fw-bold border-0 status-select`;
                    
                    const cb = this.closest('tr').querySelector('.row-checkbox');
                    if (cb) {
                        if (status !== 'Joined') {
                            cb.classList.add('d-none');
                            cb.checked = false;
                        } else {
                            cb.classList.remove('d-none');
                        }
                    }
                }
                else alert(res.msg);
            } catch (e) { alert('Connection Error'); } finally { this.disabled = false; }
        });
    });

    // Delete Group Action
    document.querySelectorAll('.delete-group').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            let { isConfirmed } = await Swal.fire({
                title: 'ยืนยันการลบ?', text: 'ต้องการลบกลุ่มนี้ออกจากระบบใช่หรือไม่?',
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
                confirmButtonText: 'ลบเลย', cancelButtonText: 'ยกเลิก'
            });
            if (!isConfirmed) return;
            
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;
            
            try {
                const response = await fetch('api/fb_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&id=${id}`
                });
                const res = await response.json();
                if (res.ok) {
                    const qSelect = btn.closest('tr').querySelector('.quality-select');
                    const oldQuality = qSelect ? qSelect.dataset.current : 'Good';
                    updateTabCounts(oldQuality, 'Delete');

                    btn.closest('tr').style.opacity = '0';
                    setTimeout(() => btn.closest('tr').remove(), 300);
                }
                else { alert(res.msg); btn.innerHTML = originalHTML; btn.disabled = false; }
            } catch (e) {
                alert('Connection Error');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        });
    });

    // ----------------------------------------
    // Bulk Actions (Multi Action)
    // ----------------------------------------
    const selectAll = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const btnApplyBulk = document.getElementById('btnApplyBulk');
    const bulkActionSelect = document.getElementById('bulkActionSelect');

    if(selectAll) {
        selectAll.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => {
                if (!cb.classList.contains('d-none')) {
                    cb.checked = this.checked;
                } else {
                    cb.checked = false;
                }
            });
        });
    }

    if(btnApplyBulk) {
        btnApplyBulk.addEventListener('click', async function() {
            const action = bulkActionSelect.value;
            if (!action) {
                Swal.fire('ข้อผิดพลาด', 'กรุณาเลือกคำสั่งก่อนนำไปใช้', 'warning');
                return;
            }

            const selectedIds = Array.from(rowCheckboxes)
                                     .filter(cb => cb.checked)
                                     .map(cb => cb.value);

            if (selectedIds.length === 0) {
                Swal.fire('ข้อผิดพลาด', 'กรุณาติ๊กเลือกกลุ่มอย่างน้อย 1 รายการ', 'warning');
                return;
            }

            if (action === 'assign_campaign') {
                document.getElementById('assign-selected-ids').value = selectedIds.join(',');
                const modal = new bootstrap.Modal(document.getElementById('modal-assign-campaign'));
                modal.show();
                return;
            }

            let { isConfirmed } = await Swal.fire({
                title: 'ยืนยันการทำรายการ?',
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

    // Confirm Assign Campaign Action
    const btnConfirmAssign = document.getElementById('btn-confirm-assign');
    if (btnConfirmAssign) {
        btnConfirmAssign.addEventListener('click', async function() {
            const campaignId = document.getElementById('assign-campaign-id').value;
            const idsStr = document.getElementById('assign-selected-ids').value;
            if (!campaignId) {
                Swal.fire('ข้อผิดพลาด', 'กรุณาเลือกแคมเปญเป้าหมาย', 'warning');
                return;
            }

            btnConfirmAssign.disabled = true;
            btnConfirmAssign.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังประมวลผล...';

            try {
                const response = await fetch('api/assign_groups_to_campaign.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ campaign_id: campaignId, group_ids: idsStr.split(',') })
                });
                
                const res = await response.json();
                if (res.ok) {
                    Swal.fire('สำเร็จ!', `นำเข้ากลุ่ม ${res.count} กลุ่ม ไปยังแคมเปญเรียบร้อยแล้ว`, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.msg, 'error');
                }
            } catch (e) {
                Swal.fire('เกิดข้อผิดพลาด', 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้', 'error');
            } finally {
                btnConfirmAssign.disabled = false;
                btnConfirmAssign.innerHTML = 'เพิ่มเข้าแคมเปญ';
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

    // Edit Group Note Function
    window.editGroupNote = async function(groupId, currentNote) {
        const { value: text } = await Swal.fire({
            title: 'บันทึกย่อ (Note)',
            input: 'textarea',
            inputLabel: 'ใส่ข้อความเตือนความจำ (เช่น เวิร์คไหม โพสต์ติดไหม บ่อยแค่ไหน)',
            inputValue: currentNote,
            inputPlaceholder: 'พิมพ์ข้อความที่นี่...',
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก'
        });

        if (text !== undefined) {
            try {
                const response = await fetch('api/update_group_info.php?api_key=yakpho2026', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: groupId, field: 'notes', value: text })
                });
                const res = await response.json();
                if (res.ok) {
                    window.location.reload();
                } else {
                    Swal.fire('ข้อผิดพลาด', res.msg, 'error');
                }
            } catch(e) {
                Swal.fire('ข้อผิดพลาด', 'เชื่อมต่อขัดข้อง', 'error');
            }
        }
    };

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

    // URL Duplicate Check
    const urlInput = document.getElementById('manual-group-url');
    const urlFeedback = document.getElementById('url-feedback');
    const btnSaveManual = document.getElementById('btn-save-manual');
    let urlCheckTimeout = null;

    if (urlInput) {
        urlInput.addEventListener('input', function() {
            clearTimeout(urlCheckTimeout);
            const url = this.value.trim();
            
            // Reset
            this.classList.remove('is-invalid', 'is-valid');
            urlFeedback.style.display = 'none';
            btnSaveManual.disabled = false;

            if (!url) return;

            urlCheckTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`api/check_group_url.php?url=${encodeURIComponent(url)}`);
                    const res = await response.json();
                    if (res.ok) {
                        if (res.exists) {
                            urlInput.classList.add('is-invalid');
                            urlFeedback.style.display = 'block';
                            urlFeedback.innerHTML = `⚠️ <b>มีกลุ่มนี้ในระบบแล้ว!</b> <br><small class="text-muted">ID: #${res.id} - ${res.group_name}</small>`;
                            btnSaveManual.disabled = true;
                        } else {
                            urlInput.classList.add('is-valid');
                        }
                    }
                } catch (e) {
                    // Ignore fetch errors during typing
                }
            }, 500); // 500ms debounce
        });
    }

});
</script>
