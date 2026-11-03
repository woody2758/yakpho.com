<?php
/**
 * ============================================================
 * Yak Pho Aroma — Admin Member & Tier Management
 * Conforms to md/AGENT.md: Strict External CSS, SweetAlert2, Universal Spinner
 * ============================================================
 */

$page_title = "จัดการระดับสมาชิก & M-Stamp (Loyalty CRM)";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/member_helper.php';
require_once __DIR__ . '/../../includes/line_helper.php';

$alert = null;

// Handle manual tier or points adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_member') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $newTier = trim((string)($_POST['member_tier'] ?? 'general'));
    $pointsAdj = (int)($_POST['points_adjustment'] ?? 0);
    $adjNote = trim((string)($_POST['adjustment_note'] ?? ''));

    if ($userId > 0) {
        try {
            // Update tier
            $up = $db->prepare("UPDATE user SET member_tier = ? WHERE user_id = ?");
            $up->execute([$newTier, $userId]);

            // If partner tier, ensure affiliate record exists
            if ($newTier === 'therapist_partner') {
                register_as_therapist_partner($db, $userId);
            }

            // Adjust points if given
            if ($pointsAdj > 0) {
                award_m_stamp_points($db, $userId, $pointsAdj, 'admin_adjustment', null, $adjNote ?: 'Admin manual bonus');
            } elseif ($pointsAdj < 0) {
                deduct_m_stamp_points($db, $userId, abs($pointsAdj), 'admin_adjustment', null, $adjNote ?: 'Admin manual deduction');
            }

            // Send LINE alert if user has connected LINE
            $stmt = $db->prepare("SELECT line_user_id, user_name, m_stamp_points FROM user WHERE user_id = ?");
            $stmt->execute([$userId]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($u['line_user_id'])) {
                $tierInfo = get_member_tier_info($newTier);
                $msg = "🌿 แจ้งเตือนสิทธิประโยชน์สมาชิก Yak Pho\n"
                     . "คุณ {$u['user_name']}\n"
                     . "ระดับปัจจุบัน: {$tierInfo['name']}\n"
                     . "แต้ม M-Stamp คงเหลือ: " . number_format((int)$u['m_stamp_points']) . " แต้ม\n"
                     . (!empty($adjNote) ? "หมายเหตุ: {$adjNote}\n" : "")
                     . "ช้อปเลย: " . ROOT_URL . "/th/products";
                send_line_push_message($db, $u['line_user_id'], $msg);
            }

            $alert = ['type' => 'success', 'msg' => "อัปเดตข้อมูลระดับสมาชิกและแต้มของ #{$userId} เรียบร้อยแล้ว"];
        } catch (Exception $e) {
            $alert = ['type' => 'danger', 'msg' => "เกิดข้อผิดพลาด: " . $e->getMessage()];
        }
    }
}

// Filters & Pagination
$filterTier = trim((string)($_GET['tier'] ?? ''));
$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = ["user_del = 0"];
$params = [];

if ($filterTier !== '') {
    $where[] = "member_tier = ?";
    $params[] = $filterTier;
}

if ($search !== '') {
    $where[] = "(user_name LIKE ? OR user_mobile LIKE ? OR user_email LIKE ? OR affiliate_code LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM user WHERE {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Fetch members
$sql = "
    SELECT user_id, user_name, user_lastname, user_mobile, user_email, email_verified,
           member_tier, m_stamp_points, affiliate_code, line_user_id, line_display_name, line_picture_url,
           created_at
    FROM user
    WHERE {$whereSql}
    ORDER BY user_id DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tier counts summary
$summaryStmt = $db->query("
    SELECT 
        SUM(CASE WHEN member_tier = 'general' OR member_tier IS NULL OR member_tier = '' THEN 1 ELSE 0 END) as general,
        SUM(CASE WHEN member_tier = 'vip_spa' THEN 1 ELSE 0 END) as vip_spa,
        SUM(CASE WHEN member_tier = 'therapist_partner' THEN 1 ELSE 0 END) as therapist_partner,
        SUM(m_stamp_points) as total_points
    FROM user WHERE user_del = 0
");
$stats = $summaryStmt->fetch(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="row align-items-center mb-3">
    <div class="col-sm-6">
        <h4 class="mb-0 fw-bold">
            <i data-lucide="crown" class="me-2 text-warning icon-md"></i>
            ระบบสมาชิก &amp; สิทธิประโยชน์ M-Stamp (Loyalty CRM)
        </h4>
        <small class="text-muted">บริหารระดับสมาชิก VIP Spa, Yak Pho Partner (หมอนวด Affiliate) และแต้มสะสม</small>
    </div>
    <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
        <div class="btn-group">
            <a href="<?= ADMIN_URL ?>/users/affiliates.php" class="btn btn-outline-secondary btn-sm">
                <i data-lucide="handshake" class="me-1 icon-sm"></i> หมอนวด Affiliate
            </a>
            <a href="<?= ADMIN_URL ?>/users/points_log.php" class="btn btn-outline-secondary btn-sm">
                <i data-lucide="coins" class="me-1 icon-sm"></i> ประวัติแต้ม M-Stamp
            </a>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show my-3" role="alert">
    <?= $alert['msg'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Summary Stat Cards -->
<div class="row g-3 my-2">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 text-center card-kpi-green">
            <div class="small text-muted fw-bold">🌿 General Members</div>
            <div class="fs-4 fw-bold text-success mt-1"><?= number_format((int)$stats['general']) ?></div>
            <div class="small text-muted">ลูกค้าปลีกทั่วไป</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 text-center card-kpi-amber">
            <div class="small text-muted fw-bold">👑 VIP Spa Members</div>
            <div class="fs-4 fw-bold text-warning mt-1"><?= number_format((int)$stats['vip_spa']) ?></div>
            <div class="small text-muted">ร้านนวด &amp; สปา (เรทส่ง)</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 text-center card-kpi-emerald">
            <div class="small text-muted fw-bold">⭐ Yak Pho Partners</div>
            <div class="fs-4 fw-bold text-dark mt-1"><?= number_format((int)$stats['therapist_partner']) ?></div>
            <div class="small text-muted">หมอนวด Affiliate</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 text-center card-kpi-gold">
            <div class="small text-muted fw-bold">🪙 M-Stamp ในระบบ</div>
            <div class="fs-4 fw-bold text-primary mt-1"><?= number_format((int)$stats['total_points']) ?></div>
            <div class="small text-muted">แต้มสะสมพร้อมใช้</div>
        </div>
    </div>
</div>

<!-- Filter and Search Bar -->
<div class="card border-0 shadow-sm rounded-3 my-3">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="tier" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- ระดับสมาชิกทั้งหมด --</option>
                    <option value="general" <?= $filterTier === 'general' ? 'selected' : '' ?>>🌿 General Member</option>
                    <option value="vip_spa" <?= $filterTier === 'vip_spa' ? 'selected' : '' ?>>👑 VIP Spa Member (ร้านนวด)</option>
                    <option value="therapist_partner" <?= $filterTier === 'therapist_partner' ? 'selected' : '' ?>>⭐ Yak Pho Partner (หมอนวด)</option>
                    <option value="oem_export" <?= $filterTier === 'oem_export' ? 'selected' : '' ?>>💎 OEM &amp; Export</option>
                </select>
            </div>
            <div class="col-md-7">
                <div class="input-group input-group-sm">
                    <input type="text" name="q" class="form-control" placeholder="ค้นหาชื่อ, เบอร์โทร, อีเมล หรือรหัส Affiliate..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-gold-save" type="submit">
                        <i data-lucide="search" class="icon-sm"></i> ค้นหา
                    </button>
                </div>
            </div>
            <div class="col-md-2 text-md-end">
                <a href="<?= ADMIN_URL ?>/users/members.php" class="btn btn-outline-secondary btn-sm w-100">รีเซ็ต</a>
            </div>
        </form>
    </div>
</div>

<!-- Members Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-dense">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>สมาชิก</th>
                    <th>เบอร์โทร / อีเมล</th>
                    <th>LINE เชื่อมต่อ</th>
                    <th class="text-center">ระดับสมาชิก</th>
                    <th class="text-center">แต้ม M-Stamp</th>
                    <th>รหัส Affiliate</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">ไม่พบข้อมูลสมาชิกตามเงื่อนไข</td></tr>
                <?php else: ?>
                <?php foreach ($users as $u): 
                    $tierInfo = get_member_tier_info($u['member_tier'] ?: 'general');
                    $tierBadgeClass = match($u['member_tier']) {
                        'vip_spa'           => 'tier-badge-vip',
                        'therapist_partner' => 'tier-badge-dealer',
                        'oem_export'        => 'tier-badge-oem',
                        default             => 'tier-badge-member',
                    };
                ?>
                <tr>
                    <td class="ps-3">
                        <div class="fw-bold text-dark">C<?= $u['user_id'] ?></div>
                        <?php
                        $order_count_stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND orders_del = 0");
                        $order_count_stmt->execute([$u['user_id']]);
                        $order_count = (int)$order_count_stmt->fetchColumn();
                        if ($order_count > 0):
                        ?>
                        <div class="text-danger mt-1 cursor-pointer" style="font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px;" title="จำนวนการสั่งซื้อทั้งหมด คลิกเพื่อดู" onclick="window.location.href='<?= ADMIN_URL ?>/orders/?search=<?= urlencode('C'.$u['user_id']) ?>&status=all'">
                            <i data-lucide="heart" style="width:12px;height:12px;fill:#dc3545;"></i> <?= number_format($order_count) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!empty($u['line_picture_url'])): ?>
                            <img src="<?= htmlspecialchars($u['line_picture_url']) ?>" alt="Avatar" class="rounded-circle avatar-img-sm">
                            <?php else: ?>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-muted fw-bold avatar-placeholder-sm">
                                <?= mb_substr($u['user_name'] ?: 'U', 0, 1) ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($u['user_name'] . ' ' . $u['user_lastname']) ?></div>
                                <?php if (!empty($u['line_display_name'])): ?>
                                <small class="text-muted">LINE: <?= htmlspecialchars($u['line_display_name']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($u['user_mobile'] ?: '-') ?></div>
                        <small class="text-muted">
                            <?= htmlspecialchars($u['user_email'] ?: '-') ?>
                            <?php if ($u['email_verified']): ?>
                            <span class="badge bg-success-subtle text-success border border-success ms-1 badge-verified">Verified</span>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td>
                        <?php if (!empty($u['line_user_id'])): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                            <i data-lucide="check-circle" class="icon-xs"></i> เชื่อมต่อแล้ว
                        </span>
                        <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">ยังไม่ผูก LINE</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge rounded-pill <?= $tierBadgeClass ?>" id="member-tier-<?= $u['user_id'] ?>">
                            <?= $tierInfo['badge'] ?> <?= $tierInfo['short_name'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold text-primary fs-6" id="member-points-<?= $u['user_id'] ?>"><?= number_format((int)$u['m_stamp_points']) ?></span>
                        <div class="text-muted badge-sub" id="member-points-baht-<?= $u['user_id'] ?>">= ฿<?= number_format((int)$u['m_stamp_points']) ?></div>
                    </td>
                    <td>
                        <?php if (!empty($u['affiliate_code'])): ?>
                        <code class="fw-bold text-dark"><?= htmlspecialchars($u['affiliate_code']) ?></code>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2" onclick="openAdjustTierModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars(addslashes(trim($u['user_name'] . ' ' . $u['user_lastname']))) ?>', '<?= htmlspecialchars($u['member_tier'] ?: 'general') ?>', <?= (int)$u['m_stamp_points'] ?>, <?= !empty($u['line_user_id']) ? 'true' : 'false' ?>)">
                            <i data-lucide="award" class="icon-sm"></i> ปรับระดับ/แต้ม
                        </button>
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
        <small class="text-muted">แสดงหน้า <?= $page ?> จากทั้งหมด <?= $totalPages ?> หน้า (รวม <?= number_format($totalRows) ?> สมาชิก)</small>
        <ul class="pagination pagination-sm mb-0">
            <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&tier=<?= urlencode($filterTier) ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Member Modal -->
<div class="modal fade" id="memberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editMemberForm">
                <input type="hidden" name="action" value="update_member">
                <input type="hidden" name="user_id" id="modal_user_id" value="">

                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i data-lucide="user" class="me-1 text-primary"></i>ปรับระดับสมาชิกและแต้ม: <span id="modalUserName"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">ระดับสมาชิก (Member Tier):</label>
                        <select name="member_tier" id="modal_member_tier" class="form-select">
                            <option value="general">🌿 General Member (ลูกค้าปลีกทั่วไป)</option>
                            <option value="vip_spa">👑 VIP Spa Member (ร้านนวด &amp; สปา เรทส่ง 1 กก.)</option>
                            <option value="therapist_partner">⭐ Yak Pho Partner (หมอนวด Affiliate รับค่าคอม)</option>
                            <option value="oem_export">💎 OEM &amp; Export Member (สั่งผลิต 100 กก.+)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">ยอดแต้มสะสมปัจจุบัน: <span id="modalCurrentPoints" class="text-primary fw-bold"></span> แต้ม</label>
                        <div class="input-group">
                            <span class="input-group-text small">ปรับเพิ่ม / ลด (+ หรือ -)</span>
                            <input type="number" name="points_adjustment" class="form-control" placeholder="เช่น 50 หรือ -20" value="0">
                            <span class="input-group-text small">M-Stamp</span>
                        </div>
                        <div class="form-text text-fs-11">ใส่ค่าบวกเพื่อเพิ่มแต้ม หรือค่าลบเพื่อหักแต้ม (1 แต้ม = 1 บาท)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">บันทึกเหตุผลการปรับแต้ม (Note):</label>
                        <input type="text" name="adjustment_note" class="form-control form-control-sm" placeholder="เช่น โบนัสกิจกรรม, ปรับปรุงยอดคำสั่งซื้อ">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success btn-sm px-4" id="btnSaveMember">
                        <span class="spinner-snake d-none me-1" id="saveMemberSpinner"></span>
                        <span id="saveMemberBtnText">บันทึกข้อมูล</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= ADMIN_ASSETS ?>/js/users.js"></script>
<script>
function editMember(u) {
    document.getElementById('modal_user_id').value = u.user_id;
    document.getElementById('modalUserName').textContent = u.user_name || ('User #' + u.user_id);
    document.getElementById('modal_member_tier').value = u.member_tier || 'general';
    document.getElementById('modalCurrentPoints').textContent = parseInt(u.m_stamp_points || 0).toLocaleString();
    const modal = new bootstrap.Modal(document.getElementById('memberModal'));
    modal.show();
}

document.getElementById('editMemberForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnSaveMember');
    const spinner = document.getElementById('saveMemberSpinner');
    const text = document.getElementById('saveMemberBtnText');
    if (btn.disabled) return false;
    btn.disabled = true;
    spinner?.classList.remove('d-none');
    if (text) text.textContent = 'กำลังบันทึก...';
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
