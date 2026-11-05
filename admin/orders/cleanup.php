<?php
$page_title = "จัดการออเดอร์แอดมินยกเลิก & รอของ (ลบถาวร)";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

// Target special statuses:
// 8  = แอดมินยกเลิก (J)
// 10 = รอส่ง สินค้าหมด (P)
// 7  = ลูกค้ายกเลิก (H)
// 9  = ตีคืนพัสดุสำเร็จ (R)
$allowedStatuses = [8, 10, 7, 9];

$statusFilter = isset($_GET['status']) ? trim((string)$_GET['status']) : '8_10';
$search       = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = isset($_GET['limit']) ? max(15, min(200, (int)$_GET['limit'])) : 50;
$offset       = ($page - 1) * $limit;

// Fetch status metadata & counts
$statusCounts = [
    8  => 0,
    10 => 0,
    7  => 0,
    9  => 0,
];
try {
    $cntStmt = $db->query("
        SELECT orders_status, COUNT(*) as cnt 
        FROM orders 
        WHERE orders_status IN (8, 10, 7, 9)
        GROUP BY orders_status
    ");
    while ($row = $cntStmt->fetch(PDO::FETCH_ASSOC)) {
        $statusCounts[(int)$row['orders_status']] = (int)$row['cnt'];
    }
} catch (Exception $e) {}

$totalAdminAndOut = $statusCounts[8] + $statusCounts[10];
$totalAllSpecial  = array_sum($statusCounts);

// All active statuses for quick restore/change
$allActiveStatuses = $db->query("SELECT orsts_id, orsts_code, orsts_detail, orsts_color FROM orsts WHERE orsts_del = 0 ORDER BY orsts_index ASC")->fetchAll(PDO::FETCH_ASSOC);

// Build WHERE conditions
$where = [];
$params = [];

if ($statusFilter === '8') {
    $where[] = "o.orders_status = 8";
} elseif ($statusFilter === '10') {
    $where[] = "o.orders_status = 10";
} elseif ($statusFilter === '7') {
    $where[] = "o.orders_status = 7";
} elseif ($statusFilter === '9') {
    $where[] = "o.orders_status = 9";
} elseif ($statusFilter === 'all_special') {
    $where[] = "o.orders_status IN (8, 10, 7, 9)";
} else {
    $statusFilter = '8_10';
    $where[] = "o.orders_status IN (8, 10)";
}

if ($search !== '') {
    $searchConds = [
        "o.orders_no LIKE :q",
        "u.user_name LIKE :q",
        "u.user_lastname LIKE :q",
        "CONCAT(COALESCE(u.user_name,''), ' ', COALESCE(u.user_lastname,'')) LIKE :q",
        "u.user_mobile LIKE :q",
        "a.addr_name LIKE :q",
        "a.addr_mobile LIKE :q"
    ];
    $params[':q'] = "%{$search}%";

    $digits = preg_replace('/[^0-9]/', '', $search);
    if ($digits !== '' && is_numeric($digits)) {
        $searchConds[] = "o.user_id = :uid";
        $params[':uid'] = (int)$digits;
    }
    $where[] = "(" . implode(" OR ", $searchConds) . ")";
}

$whereSql = implode(" AND ", $where);

// Count total matching rows
$countSql = "
    SELECT COUNT(*) 
    FROM orders o
    LEFT JOIN user u ON o.user_id = u.user_id
    LEFT JOIN addr a ON o.addr_id = a.addr_id
    WHERE $whereSql
";
$stmtCount = $db->prepare($countSql);
foreach ($params as $k => $v) {
    $stmtCount->bindValue($k, $v);
}
$stmtCount->execute();
$totalRecords = (int)$stmtCount->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $limit));

// Fetch rows
$sql = "
    SELECT o.orders_id, o.orders_no, o.orders_date, o.orders_update, o.orders_status,
           o.orders_grandtotal, o.orders_total, o.orders_delicost, o.paycat_id, o.user_id,
           u.user_name, u.user_lastname, u.user_nickname, u.user_mobile,
           a.addr_name, a.addr_mobile, a.addr_detail,
           s.orsts_detail, s.orsts_code, s.orsts_color,
           (SELECT COUNT(*) FROM ordetail d WHERE d.orders_id = o.orders_id) as item_count,
           (SELECT GROUP_CONCAT(CONCAT(COALESCE(p.product_code,''), ' ', COALESCE(p.product_name,''), ' (x', d.ordetail_qty, ')') SEPARATOR ', ')
            FROM ordetail d LEFT JOIN product p ON d.product_id = p.product_id WHERE d.orders_id = o.orders_id) as items_summary
    FROM orders o
    LEFT JOIN user u ON o.user_id = u.user_id
    LEFT JOIN addr a ON o.addr_id = a.addr_id
    LEFT JOIN orsts s ON o.orders_status = s.orsts_id
    WHERE $whereSql
    ORDER BY o.orders_date DESC, o.orders_id DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<style>
.cleanup-tab-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    text-decoration: none;
    display: block;
}
.cleanup-tab-card:hover {
    transform: translateY(-2px);
}
.cleanup-tab-card.is-active {
    border-color: #10B981 !important;
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.22) !important;
}
.items-summary-cell {
    max-width: 340px;
    font-size: 0.8rem;
    line-height: 1.4;
    color: #94A3B8;
}
</style>

<div class="container-fluid px-2 px-md-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h3 class="mb-1 fs-5 fs-md-4 fw-bold d-flex align-items-center gap-2">
                <i data-lucide="trash-2" class="text-danger"></i>
                <span>จัดการคำสั่งซื้อ: แอดมินยกเลิก &amp; รอส่ง/สินค้าหมด (ลบถาวร)</span>
            </h3>
            <p class="text-muted small mb-0">
                ตารางจัดการเฉพาะออเดอร์ที่ถูกยกเลิกโดยแอดมิน (J) และสถานะของหมด/รอของ (P) เพื่อตรวจสอบ เปลี่ยนสถานะ หรือลบออกจากฐานข้อมูลอย่างถาวร
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= ADMIN_URL ?>/orders/" class="btn btn-sm btn-outline-secondary fw-bold px-3">
                <i data-lucide="arrow-left" class="me-1" style="width:15px;height:15px;"></i> กลับหน้ารายการสั่งซื้อหลัก
            </a>
        </div>
    </div>

    <!-- Status Filter Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3 col-xl-2">
            <a href="?status=8_10&amp;q=<?= urlencode($search) ?>&amp;limit=<?= $limit ?>" class="card shadow-sm cleanup-tab-card <?= $statusFilter === '8_10' ? 'is-active' : '' ?>">
                <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-muted fw-bold">แอดมินยกเลิก + รอของ</div>
                        <div class="fs-4 fw-bold text-warning"><?= number_format($totalAdminAndOut) ?></div>
                    </div>
                    <i data-lucide="layers" class="text-warning opacity-75"></i>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
            <a href="?status=8&amp;q=<?= urlencode($search) ?>&amp;limit=<?= $limit ?>" class="card shadow-sm cleanup-tab-card <?= $statusFilter === '8' ? 'is-active' : '' ?>">
                <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-muted fw-bold">J - แอดมินยกเลิก</div>
                        <div class="fs-4 fw-bold text-danger"><?= number_format($statusCounts[8]) ?></div>
                    </div>
                    <i data-lucide="x-octagon" class="text-danger opacity-75"></i>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
            <a href="?status=10&amp;q=<?= urlencode($search) ?>&amp;limit=<?= $limit ?>" class="card shadow-sm cleanup-tab-card <?= $statusFilter === '10' ? 'is-active' : '' ?>">
                <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-muted fw-bold">P - รอส่ง สินค้าหมด</div>
                        <div class="fs-4 fw-bold text-info"><?= number_format($statusCounts[10]) ?></div>
                    </div>
                    <i data-lucide="clock" class="text-info opacity-75"></i>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
            <a href="?status=7&amp;q=<?= urlencode($search) ?>&amp;limit=<?= $limit ?>" class="card shadow-sm cleanup-tab-card <?= $statusFilter === '7' ? 'is-active' : '' ?>">
                <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-muted fw-bold">H - ลูกค้ายกเลิก</div>
                        <div class="fs-4 fw-bold text-secondary"><?= number_format($statusCounts[7]) ?></div>
                    </div>
                    <i data-lucide="user-x" class="text-secondary opacity-75"></i>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
            <a href="?status=9&amp;q=<?= urlencode($search) ?>&amp;limit=<?= $limit ?>" class="card shadow-sm cleanup-tab-card <?= $statusFilter === '9' ? 'is-active' : '' ?>">
                <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-muted fw-bold">R - ตีคืนพัสดุ</div>
                        <div class="fs-4 fw-bold text-secondary"><?= number_format($statusCounts[9]) ?></div>
                    </div>
                    <i data-lucide="rotate-ccw" class="text-secondary opacity-75"></i>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
            <a href="?status=all_special&amp;q=<?= urlencode($search) ?>&amp;limit=<?= $limit ?>" class="card shadow-sm cleanup-tab-card <?= $statusFilter === 'all_special' ? 'is-active' : '' ?>">
                <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-muted fw-bold">รวมทุกสถานะยกเลิก/รอ</div>
                        <div class="fs-4 fw-bold text-primary"><?= number_format($totalAllSpecial) ?></div>
                    </div>
                    <i data-lucide="archive" class="text-primary opacity-75"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Search & Bulk Action Toolbar -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-md-6">
                    <form method="GET" class="d-flex gap-2">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i data-lucide="search" style="width:15px;height:15px;"></i></span>
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="ค้นหาเลขที่ใบสั่งซื้อ, รหัสลูกค้า (เช่น C10253), ชื่อ, เบอร์โทร...">
                            <?php if ($search !== ''): ?>
                                <a href="?status=<?= urlencode($statusFilter) ?>&amp;limit=<?= $limit ?>" class="btn btn-outline-secondary">ล้าง</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary fw-bold px-3">ค้นหา</button>
                        </div>
                        <select name="limit" class="form-select form-select-sm" style="width:115px;" onchange="this.form.submit()">
                            <?php foreach ([25, 50, 100, 200] as $limOpt): ?>
                                <option value="<?= $limOpt ?>" <?= $limit === $limOpt ? 'selected' : '' ?>><?= $limOpt ?> แถว/หน้า</option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="col-12 col-md-6 d-flex justify-content-md-end align-items-center gap-2 flex-wrap">
                    <span class="small text-muted me-2">
                        เลือกแล้ว <strong id="selectedCountBadge" class="text-danger fs-6">0</strong> รายการ
                    </span>
                    <button type="button" id="btnBulkDelete" onclick="deleteSelectedOrders()" class="btn btn-sm btn-danger fw-bold px-3" disabled>
                        <i data-lucide="trash-2" class="me-1" style="width:15px;height:15px;"></i>
                        ลบที่เลือกอย่างถาวร (<span id="btnBulkCount">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dedicated Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:42px;">
                                <input type="checkbox" class="form-check-input" id="checkAllOrders" onchange="toggleSelectAll(this)">
                            </th>
                            <th>เลขที่คำสั่งซื้อ</th>
                            <th>วันที่สั่งซื้อ</th>
                            <th>ลูกค้า / ผู้รับ</th>
                            <th>รายการสินค้า</th>
                            <th class="text-end">ยอดสุทธิ</th>
                            <th class="text-center">สถานะปัจจุบัน</th>
                            <th class="text-end pe-3" style="min-width: 210px;">จัดการ / ลบถาวร</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i data-lucide="check-circle" class="mb-2 text-success" style="width:36px;height:36px;"></i>
                                    <div class="fw-bold">ไม่พบรายการคำสั่งซื้อในหมวดหมู่นี้</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                                <?php
                                $oid = (int)$o['orders_id'];
                                $uid = (int)$o['user_id'];
                                $custName = trim(($o['user_name'] ?? '') . ' ' . ($o['user_lastname'] ?? ''));
                                if ($custName === '') $custName = $o['addr_name'] ?? 'ไม่ระบุชื่อ';
                                $phone = $o['user_mobile'] ?: ($o['addr_mobile'] ?? '-');
                                $grand = (float)$o['orders_grandtotal'] > 0 ? (float)$o['orders_grandtotal'] : ((float)$o['orders_total'] + (float)$o['orders_delicost']);
                                $stColor = $o['orsts_color'] ?: '#6c757d';
                                ?>
                                <tr id="order-row-<?= $oid ?>">
                                    <td class="ps-3">
                                        <input type="checkbox" class="form-check-input order-row-check" value="<?= $oid ?>" data-orderno="<?= htmlspecialchars((string)$o['orders_no']) ?>" onchange="updateBulkSelection()">
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary">#<?= htmlspecialchars((string)$o['orders_no']) ?></div>
                                        <div class="small text-muted">ID: <?= $oid ?> <?= (int)$o['paycat_id'] === 2 ? '<span class="badge bg-danger ms-1">COD</span>' : '' ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= date('d/m/Y H:i', strtotime($o['orders_date'])) ?></div>
                                        <?php if (!empty($o['orders_update']) && $o['orders_update'] !== '0000-00-00 00:00:00'): ?>
                                            <div class="text-muted" style="font-size:0.72rem;">อัปเดต: <?= date('d/m/Y H:i', strtotime($o['orders_update'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold">
                                            <?php if ($uid > 0): ?>
                                                <span class="badge bg-secondary me-1">C<?= $uid ?></span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($custName) ?>
                                        </div>
                                        <div class="small text-muted">📞 <?= htmlspecialchars((string)$phone) ?></div>
                                    </td>
                                    <td>
                                        <div class="badge bg-dark bg-opacity-25 text-info mb-1"><?= (int)$o['item_count'] ?> รายการ</div>
                                        <div class="items-summary-cell text-truncate" title="<?= htmlspecialchars((string)$o['items_summary']) ?>">
                                            <?= htmlspecialchars((string)($o['items_summary'] ?: '-')) ?>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        ฿<?= number_format($grand, 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill px-3 py-2" style="background-color: <?= htmlspecialchars($stColor) ?>; color: #fff;">
                                            <?= htmlspecialchars($o['orsts_code'] . ' - ' . $o['orsts_detail']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <select class="form-select form-select-sm" style="width: 135px; font-size: 0.78rem;" onchange="quickRestoreStatus(<?= $oid ?>, '<?= htmlspecialchars((string)$o['orders_no']) ?>', this)">
                                                <option value="">-- ย้ายสถานะ --</option>
                                                <?php foreach ($allActiveStatuses as $st): ?>
                                                    <?php if ((int)$st['orsts_id'] === (int)$o['orders_status']) continue; ?>
                                                    <option value="<?= (int)$st['orsts_id'] ?>">
                                                        <?= htmlspecialchars($st['orsts_code'] . ' - ' . $st['orsts_detail']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-sm btn-danger fw-bold" onclick="deleteSingleOrder(<?= $oid ?>, '<?= htmlspecialchars((string)$o['orders_no']) ?>')" title="ลบคำสั่งซื้อนี้อย่างถาวร">
                                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i> ลบถาวร
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Footer -->
        <?php if ($totalPages > 1): ?>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div class="small text-muted">
                แสดงหน้า <?= $page ?> จาก <?= $totalPages ?> (ทั้งหมด <?= number_format($totalRecords) ?> รายการ)
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?status=<?= urlencode($statusFilter) ?>&amp;q=<?= urlencode($search) ?>&amp;limit=<?= $limit ?>&amp;page=<?= $page - 1 ?>">&laquo;</a>
                    </li>
                    <?php
                    $startP = max(1, $page - 3);
                    $endP   = min($totalPages, $page + 3);
                    for ($p = $startP; $p <= $endP; $p++):
                    ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?status=<?= urlencode($statusFilter) ?>&amp;q=<?= urlencode($search) ?>&amp;limit=<?= $limit ?>&amp;page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?status=<?= urlencode($statusFilter) ?>&amp;q=<?= urlencode($search) ?>&amp;limit=<?= $limit ?>&amp;page=<?= $page + 1 ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.order-row-check');
    checkboxes.forEach(cb => {
        cb.checked = masterCheckbox.checked;
    });
    updateBulkSelection();
}

function updateBulkSelection() {
    const checked = document.querySelectorAll('.order-row-check:checked');
    const count = checked.length;
    document.getElementById('selectedCountBadge').textContent = count;
    document.getElementById('btnBulkCount').textContent = count;
    document.getElementById('btnBulkDelete').disabled = (count === 0);
}

async function executePermanentDelete(orderIds, labelText) {
    const confirmResult = await Swal.fire({
        icon: 'warning',
        title: 'ยืนยันการลบอย่างถาวร?',
        html: `คุณต้องการลบคำสั่งซื้อ <b>${labelText}</b> ออกจากฐานข้อมูลอย่างถาวรใช่หรือไม่?<br><span class="text-danger small">* ข้อมูลรายการสินค้า สลิปโอนเงิน และบันทึกของออเดอร์นี้จะถูกลบทั้งหมดและไม่สามารถกู้คืนได้</span>`,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ลบถาวรทันที!',
        cancelButtonText: 'ยกเลิก'
    });

    if (!confirmResult.isConfirmed) return;

    Swal.fire({
        title: 'กำลังลบข้อมูลอย่างถาวร...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const res = await fetch('<?= ADMIN_URL ?>/api/delete_orders_permanently.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ orders_ids: orderIds })
        });
        const data = await res.json();
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'ลบถาวรสำเร็จ!',
                text: data.message,
                timer: 1400,
                showConfirmButton: false
            });
            window.location.reload();
        } else {
            Swal.fire('เกิดข้อผิดพลาด', data.message || 'ไม่สามารถลบข้อมูลได้', 'error');
        }
    } catch (err) {
        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
    }
}

function deleteSingleOrder(orderId, orderNo) {
    executePermanentDelete([orderId], '#' + orderNo);
}

function deleteSelectedOrders() {
    const checked = Array.from(document.querySelectorAll('.order-row-check:checked'));
    if (!checked.length) return;
    const ids = checked.map(cb => parseInt(cb.value, 10));
    executePermanentDelete(ids, `ที่เลือกทั้งหมด ${ids.length} รายการ`);
}

async function quickRestoreStatus(orderId, orderNo, selectEl) {
    const newStatus = parseInt(selectEl.value, 10);
    if (!newStatus) return;
    const statusText = selectEl.options[selectEl.selectedIndex].text;

    const confirmResult = await Swal.fire({
        icon: 'question',
        title: 'เปลี่ยนสถานะคำสั่งซื้อ?',
        html: `ต้องการย้ายออเดอร์ <b>#${orderNo}</b> ไปยังสถานะ <b>${statusText}</b> ใช่หรือไม่?`,
        showCancelButton: true,
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    });

    if (!confirmResult.isConfirmed) {
        selectEl.value = '';
        return;
    }

    try {
        const res = await fetch('<?= ADMIN_URL ?>/api/update_order_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ orders_id: orderId, orsts_id: newStatus })
        });
        const data = await res.json();
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'อัปเดตสถานะเรียบร้อย',
                timer: 1100,
                showConfirmButton: false
            });
            window.location.reload();
        } else {
            Swal.fire('ผิดพลาด', data.message || 'ไม่สามารถเปลี่ยนสถานะได้', 'error');
            selectEl.value = '';
        }
    } catch (e) {
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        selectEl.value = '';
    }
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php";
?>

