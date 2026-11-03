<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/member_helper.php';

$page_title = "ประวัติความเคลื่อนไหวแต้ม M-Stamp";
include __DIR__ . '/../includes/layout.php';

// Filters
$actionFilter = $_GET['action_type'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = 30;
$offset       = ($page - 1) * $limit;

// KPI Summary
$stats = [
    'total_rewarded'  => 0,
    'total_discount'  => 0,
    'total_logs'      => 0,
];

try {
    $statStmt = $db->query("
        SELECT 
            COUNT(*) as total_logs,
            SUM(CASE WHEN points_change > 0 THEN points_change ELSE 0 END) as total_rewarded,
            SUM(CASE WHEN points_change < 0 THEN ABS(points_change) ELSE 0 END) as total_discount
        FROM member_points_log
    ");
    $r = $statStmt->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        $stats['total_logs']     = (int)$r['total_logs'];
        $stats['total_rewarded'] = (int)$r['total_rewarded'];
        $stats['total_discount'] = (int)$r['total_discount'];
    }
} catch (Exception $e) {}

// Build query
$where = ["1=1"];
$params = [];

if (!empty($actionFilter)) {
    $where[] = "l.action_type = ?";
    $params[] = $actionFilter;
}

if (!empty($search)) {
    $where[] = "(u.user_name LIKE ? OR u.user_mobile LIKE ? OR u.user_email LIKE ? OR l.note LIKE ? OR o.orders_no LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

$whereSql = implode(" AND ", $where);

// Count
$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM member_points_log l
    LEFT JOIN user u ON l.user_id = u.user_id
    LEFT JOIN orders o ON l.reference_id = o.orders_id
    WHERE $whereSql
");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch data
$query = "
    SELECT l.*, u.user_name, u.user_lastname, u.user_mobile, u.user_email, u.line_picture_url, u.member_tier,
           o.orders_no
    FROM member_points_log l
    LEFT JOIN user u ON l.user_id = u.user_id
    LEFT JOIN orders o ON l.reference_id = o.orders_id
    WHERE $whereSql
    ORDER BY l.log_id DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function get_action_label(string $type): array {
    $types = [
        'order_reward'     => ['label' => 'สะสมจากยอดสั่งซื้อ', 'class' => 'bg-success bg-opacity-10 text-success border border-success'],
        'order_discount'   => ['label' => 'ใช้แต้มเป็นส่วนลด', 'class' => 'bg-danger bg-opacity-10 text-danger border border-danger'],
        'welcome_bonus'    => ['label' => 'แต้มต้อนรับสมาชิก', 'class' => 'bg-primary bg-opacity-10 text-primary border border-primary'],
        'admin_adjustment' => ['label' => 'แอดมินปรับยอดแต้ม', 'class' => 'bg-warning bg-opacity-10 text-dark border border-warning'],
        'review_reward'    => ['label' => 'แต้มรีวิวสินค้า', 'class' => 'bg-info bg-opacity-10 text-info border border-info'],
    ];
    return $types[$type] ?? ['label' => $type, 'class' => 'bg-secondary text-white'];
}
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="mb-0 fw-bold"><i data-lucide="coins" class="me-2 text-warning"></i>ประวัติความเคลื่อนไหวแต้ม M-Stamp (Points Ledger)</h4>
            <div class="text-muted small">ตรวจสอบบันทึกการแจกแต้ม และการใช้แต้มเป็นส่วนลดเงินสดในระบบ E-Commerce ทั้งหมด</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= ADMIN_URL ?>/users/members.php" class="btn btn-outline-secondary btn-sm">
                <i data-lucide="crown" class="me-1"></i>จัดการระดับสมาชิก
            </a>
            <a href="<?= ADMIN_URL ?>/users/affiliates.php" class="btn btn-outline-success btn-sm">
                <i data-lucide="award" class="me-1"></i>หมอนวด Partner
            </a>
        </div>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 my-2">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 text-center card-kpi-green">
            <div class="small text-muted fw-bold">🪙 แต้มสะสมที่แจกทั้งหมด</div>
            <div class="fs-4 fw-bold text-success mt-1">+<?= number_format($stats['total_rewarded']) ?> แต้ม</div>
            <div class="small text-muted">จากคำสั่งซื้อและโบนัสสมาชิก</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 text-center card-kpi-red">
            <div class="small text-muted fw-bold">🏷️ แต้มที่ถูกนำมาใช้ลดราคา</div>
            <div class="fs-4 fw-bold text-danger mt-1">-<?= number_format($stats['total_discount']) ?> แต้ม</div>
            <div class="small text-muted">เทียบเท่าส่วนลดเงินสด ฿<?= number_format($stats['total_discount']) ?> บาท</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 text-center card-kpi-emerald">
            <div class="small text-muted fw-bold">📑 จำนวนรายการบันทึก</div>
            <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($stats['total_logs']) ?> รายการ</div>
            <div class="small text-muted">ประวัติธุรกรรมแต้มทั้งหมด</div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-3 my-3">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="action_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- แสดงทุกประเภทธุรกรรม --</option>
                    <option value="order_reward" <?= $actionFilter === 'order_reward' ? 'selected' : '' ?>>🎁 สะสมจากยอดสั่งซื้อ (Order Reward)</option>
                    <option value="order_discount" <?= $actionFilter === 'order_discount' ? 'selected' : '' ?>>🏷️ ใช้แต้มเป็นส่วนลด (Order Discount)</option>
                    <option value="welcome_bonus" <?= $actionFilter === 'welcome_bonus' ? 'selected' : '' ?>>🎉 แต้มต้อนรับสมาชิก (Welcome Bonus)</option>
                    <option value="admin_adjustment" <?= $actionFilter === 'admin_adjustment' ? 'selected' : '' ?>>⚙️ แอดมินปรับยอด (Admin Adjustment)</option>
                </select>
            </div>
            <div class="col-md-7">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="ค้นหาชื่อสมาชิก, เบอร์โทร, เลขออเดอร์ หรือหมายเหตุ..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i data-lucide="search" class="me-1"></i>ค้นหา</button>
                <?php if ($actionFilter || $search): ?>
                <a href="<?= ADMIN_URL ?>/users/points_log.php" class="btn btn-outline-secondary btn-sm"><i data-lucide="rotate-ccw"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-dense">
            <thead class="table-light">
                <tr>
                    <th>Log ID</th>
                    <th>วัน-เวลา</th>
                    <th>สมาชิก</th>
                    <th>ประเภทรายการ</th>
                    <th class="text-end">จำนวนแต้ม</th>
                    <th class="text-end">แต้มคงเหลือ</th>
                    <th class="text-center">อ้างอิงออเดอร์</th>
                    <th>บันทึกหมายเหตุ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">ยังไม่มีประวัติการเคลื่อนไหวแต้ม</td></tr>
                <?php else: ?>
                <?php foreach ($logs as $l): 
                    $act = get_action_label($l['action_type']);
                    $tier = get_member_tier_info($l['member_tier'] ?: 'general');
                ?>
                <tr>
                    <td class="text-muted fw-bold">#<?= $l['log_id'] ?></td>
                    <td class="text-nowrap text-muted"><?= date('d/m/y H:i', strtotime($l['created_at'])) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!empty($l['line_picture_url'])): ?>
                            <img src="<?= htmlspecialchars($l['line_picture_url']) ?>" alt="Avatar" class="rounded-circle avatar-img-xs">
                            <?php else: ?>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-muted fw-bold avatar-placeholder-xs">
                                <?= mb_substr($l['user_name'] ?: 'U', 0, 1) ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($l['user_name'] . ' ' . $l['user_lastname']) ?></div>
                                <small class="text-muted"><?= $tier['badge'] ?> <?= $tier['short_name'] ?> • <?= htmlspecialchars($l['user_mobile'] ?: '-') ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?= $act['class'] ?>"><?= $act['label'] ?></span>
                    </td>
                    <td class="text-end">
                        <?php if ($l['points_change'] > 0): ?>
                        <strong class="text-success fs-6">+<?= number_format($l['points_change']) ?></strong>
                        <?php else: ?>
                        <strong class="text-danger fs-6"><?= number_format($l['points_change']) ?></strong>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-bold text-dark">
                        <?= number_format($l['balance_after']) ?>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($l['orders_no'])): ?>
                        <a href="<?= ADMIN_URL ?>/orders/?search=<?= urlencode($l['orders_no']) ?>" class="badge bg-light text-primary text-decoration-none border text-fs-11">
                            #<?= htmlspecialchars($l['orders_no']) ?>
                        </a>
                        <?php elseif (!empty($l['reference_id'])): ?>
                        <span class="text-muted">#<?= $l['reference_id'] ?></span>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted">
                        <?= htmlspecialchars($l['note'] ?: '-') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center py-3 border-top">
        <small class="text-muted">แสดงหน้า <?= $page ?> จากทั้งหมด <?= $totalPages ?> หน้า (รวม <?= number_format($totalRows) ?> รายการ)</small>
        <ul class="pagination pagination-sm mb-0">
            <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&action_type=<?= urlencode($actionFilter) ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

