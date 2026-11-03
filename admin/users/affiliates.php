<?php
/**
 * ============================================================
 * Yak Pho Aroma — Admin Therapist Partners (Affiliates) Dashboard
 * Conforms to md/AGENT.md: Strict External CSS, SweetAlert2, Universal Spinner
 * ============================================================
 */

$page_title = "ตัวแทนหมอนวด & สปา (Therapist Partners)";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/member_helper.php';
require_once __DIR__ . '/../../includes/line_helper.php';

$alert = null;

// Handle payout marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_payout') {
    $affiliateId = (int)($_POST['affiliate_id'] ?? 0);
    $payoutNote = trim((string)($_POST['payout_note'] ?? ''));

    if ($affiliateId > 0) {
        try {
            $stmt = $db->prepare("SELECT user_id, affiliate_code FROM affiliates WHERE affiliate_id = ?");
            $stmt->execute([$affiliateId]);
            $aff = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($aff) {
                // Get unpaid commission from affiliate_commissions table
                $commStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM affiliate_commissions WHERE affiliate_id = ? AND status = 'unpaid'");
                $commStmt->execute([$affiliateId]);
                $amountPaid = (float)$commStmt->fetchColumn();

                if ($amountPaid > 0) {
                    $up = $db->prepare("
                        UPDATE affiliate_commissions 
                        SET status = 'paid', paid_at = NOW(), note = ? 
                        WHERE affiliate_id = ? AND status = 'unpaid'
                    ");
                    $up->execute([$payoutNote ?: 'โอนเงินค่าคอมมิชชันเรียบร้อยแล้ว', $affiliateId]);

                    // Update related order details if column exists
                    try {
                        $upOrders = $db->prepare("
                            UPDATE orders 
                            SET commission_status = 'paid' 
                            WHERE affiliate_code = ? AND commission_status = 'pending'
                        ");
                        $upOrders->execute([$aff['affiliate_code']]);
                    } catch (Exception $oe) {}

                    // Notify via LINE if user connected LINE
                    $uStmt = $db->prepare("SELECT line_user_id, user_name FROM user WHERE user_id = ?");
                    $uStmt->execute([$aff['user_id']]);
                    $u = $uStmt->fetch(PDO::FETCH_ASSOC);

                    if (!empty($u['line_user_id'])) {
                        $msg = "💰 แจ้งโอนเงินค่าคอมมิชชัน Yak Pho Partner\n"
                             . "คุณ {$u['user_name']}\n"
                             . "ยอดเงิน: ฿" . number_format($amountPaid, 2) . " บาท\n"
                             . "สถานะ: โอนเงินเรียบร้อยแล้ว\n"
                             . (!empty($payoutNote) ? "หมายเหตุ: {$payoutNote}\n" : "")
                             . "ขอบพระคุณที่เป็นส่วนหนึ่งของครอบครัวยักษ์โพธิ์ 🌿";
                        send_line_push_message($db, $u['line_user_id'], $msg);
                    }

                    $alert = ['type' => 'success', 'msg' => "บันทึกการโอนเงินจำนวน ฿" . number_format($amountPaid, 2) . " ให้กับ Partner เรียบร้อยแล้ว"];
                } else {
                    $alert = ['type' => 'info', 'msg' => "ไม่มีรายการค่าคอมมิชชันค้างจ่ายสำหรับตัวแทนนี้"];
                }
            }
        } catch (Exception $e) {
            $alert = ['type' => 'danger', 'msg' => "เกิดข้อผิดพลาด: " . $e->getMessage()];
        }
    }
}

// Fetch affiliates list with actual computed commissions
$stmt = $db->query("
    SELECT a.*, u.user_name, u.user_lastname, u.user_mobile, u.user_email, u.line_user_id, u.line_picture_url,
           COALESCE((SELECT SUM(amount) FROM affiliate_commissions ac WHERE ac.affiliate_id = a.affiliate_id AND ac.status = 'unpaid'), 0) AS unpaid_commission,
           COALESCE((SELECT SUM(amount) FROM affiliate_commissions ac WHERE ac.affiliate_id = a.affiliate_id AND ac.status = 'paid'), 0) AS paid_commission,
           COALESCE((SELECT COUNT(*) FROM affiliate_commissions ac WHERE ac.affiliate_id = a.affiliate_id), 0) AS total_comm_orders
    FROM affiliates a
    JOIN user u ON a.user_id = u.user_id
    ORDER BY unpaid_commission DESC, a.affiliate_id DESC
");
$affiliates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totals
$totPartners = count($affiliates);
$totUnpaid = 0;
$totPaid = 0;
foreach ($affiliates as $a) {
    $totUnpaid += (float)$a['unpaid_commission'];
    $totPaid += (float)$a['paid_commission'];
}

ob_start();
?>

<div class="row align-items-center mb-3">
    <div class="col-sm-6">
        <h4 class="mb-0 fw-bold">
            <i data-lucide="handshake" class="me-2 text-warning icon-md"></i>
            ตัวแทนหมอนวด &amp; สปา (Therapist Partners)
        </h4>
        <small class="text-muted">ระบบ Affiliate ค่าคอมมิชชัน 10% สำหรับหมอนวดและพาร์ทเนอร์ร้านสปา</small>
    </div>
    <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
        <div class="btn-group">
            <a href="<?= ADMIN_URL ?>/users/members.php" class="btn btn-outline-secondary btn-sm">
                <i data-lucide="users" class="me-1 icon-sm"></i> สมาชิก &amp; Tiers
            </a>
            <a href="<?= ADMIN_URL ?>/users/points_log.php" class="btn btn-outline-secondary btn-sm">
                <i data-lucide="coins" class="me-1 icon-sm"></i> ประวัติ M-Stamp
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

<!-- Summary Cards -->
<div class="row g-3 my-2">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 text-center card-kpi-emerald">
            <div class="small text-muted fw-bold">⭐ จำนวนตัวแทน Partner</div>
            <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($totPartners) ?> คน</div>
            <div class="small text-muted">หมอนวด &amp; สปาเธอราพิสต์</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 text-center card-kpi-red">
            <div class="small text-muted fw-bold">⏳ ค่าคอมมิชชันค้างจ่าย</div>
            <div class="fs-4 fw-bold text-danger mt-1">฿<?= number_format($totUnpaid, 2) ?></div>
            <div class="small text-muted">รอเคลียร์ยอดโอน</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 text-center card-kpi-green">
            <div class="small text-muted fw-bold">✅ ค่าคอมมิชชันจ่ายแล้ว</div>
            <div class="fs-4 fw-bold text-success mt-1">฿<?= number_format($totPaid, 2) ?></div>
            <div class="small text-muted">ยอดรวมจ่ายสะสม</div>
        </div>
    </div>
</div>

<!-- Partners Table -->
<div class="card border-0 shadow-sm rounded-3 my-3">
    <div class="card-header bg-transparent fw-bold py-3 border-bottom">
        <i data-lucide="users" class="me-2 text-primary"></i>รายชื่อตัวแทนหมอนวด Partner ทั้งหมด
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-dense">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>ตัวแทน / หมอนวด</th>
                    <th>รหัส Affiliate &amp; ลิงก์</th>
                    <th>LINE เชื่อมต่อ</th>
                    <th class="text-center">ออเดอร์ที่แนะนำ</th>
                    <th class="text-end">คอมมิชชันค้างจ่าย</th>
                    <th class="text-end">คอมมิชชันจ่ายแล้ว</th>
                    <th class="text-center">จัดการการเงิน</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($affiliates)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">ยังไม่มีตัวแทนหมอนวดที่ลงทะเบียน</td></tr>
                <?php else: ?>
                <?php foreach ($affiliates as $a): 
                    $refLink = ROOT_URL . '/?ref=' . $a['affiliate_code'];
                ?>
                <tr>
                    <td class="text-muted fw-bold">#<?= $a['affiliate_id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!empty($a['line_picture_url'])): ?>
                            <img src="<?= htmlspecialchars($a['line_picture_url']) ?>" alt="Avatar" class="rounded-circle avatar-img-sm">
                            <?php else: ?>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-muted fw-bold avatar-placeholder-sm">
                                <?= mb_substr($a['user_name'] ?: 'P', 0, 1) ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($a['user_name'] . ' ' . $a['user_lastname']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($a['user_mobile'] ?: $a['user_email'] ?: '-') ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div><code class="fw-bold fs-6 text-dark"><?= htmlspecialchars($a['affiliate_code']) ?></code></div>
                        <div class="d-flex align-items-center gap-1 mt-1">
                            <input type="text" class="form-control form-control-sm py-0 px-2 input-ref-link" value="<?= htmlspecialchars($refLink) ?>" readonly id="ref_<?= $a['affiliate_id'] ?>">
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1" onclick="copyRefLink(this, '<?= addslashes($refLink) ?>')" title="คัดลอก">
                                <i data-lucide="copy" class="icon-xs"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($a['line_user_id'])): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                            <i data-lucide="check" class="icon-xs"></i> เชื่อมต่อแล้ว
                        </span>
                        <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">ยังไม่ผูก LINE</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center fw-bold text-dark">
                        <?= number_format($a['total_comm_orders']) ?> รายการ
                    </td>
                    <td class="text-end">
                        <span class="fw-bold <?= $a['unpaid_commission'] > 0 ? 'text-danger fs-6' : 'text-muted' ?>">
                            ฿<?= number_format($a['unpaid_commission'], 2) ?>
                        </span>
                    </td>
                    <td class="text-end text-success fw-bold">
                        ฿<?= number_format($a['paid_commission'], 2) ?>
                    </td>
                    <td class="text-center">
                        <?php if ($a['unpaid_commission'] > 0): ?>
                        <button type="button" class="btn btn-success btn-sm py-1 px-2" onclick="openPayoutModal(<?= $a['affiliate_id'] ?>, '<?= htmlspecialchars($a['user_name']) ?>', <?= $a['unpaid_commission'] ?>)">
                            <i data-lucide="check-circle" class="icon-sm"></i> บันทึกโอนเงิน
                        </button>
                        <?php else: ?>
                        <span class="text-muted small">เคลียร์ยอดแล้ว</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payout Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="payoutForm">
                <input type="hidden" name="action" value="mark_payout">
                <input type="hidden" name="affiliate_id" id="payout_affiliate_id" value="">

                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i data-lucide="credit-card" class="me-1 text-success"></i>บันทึกการโอนเงินค่าคอมมิชชัน</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">เมื่อกดบันทึก ระบบจะปรับสถานะรายการเป็น "จ่ายแล้ว" และส่งการแจ้งเตือนไปยัง LINE ของหมอนวดอัตโนมัติ</p>
                    <div class="mb-2">
                        <strong>ตัวแทน:</strong> <span id="payoutPartnerName"></span>
                    </div>
                    <div class="mb-3">
                        <strong>ยอดเงินที่ต้องโอน:</strong> <span id="payoutAmount" class="fs-5 fw-bold text-success"></span> บาท
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">บันทึกเพิ่มเติม (Note):</label>
                        <input type="text" name="payout_note" class="form-control form-control-sm" value="โอนเงินผ่าน KBank พร้อมเพย์ วันที่ <?= date('d/m/Y') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success btn-sm px-4" id="btnConfirmPayout">
                        <span class="spinner-snake d-none me-1" id="payoutSpinner"></span>
                        <span id="payoutBtnText">ยืนยันการโอนเงิน</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyRefLink(btn, url) {
    navigator.clipboard.writeText(url).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'คัดลอกสำเร็จ',
            text: 'คัดลอกลิงก์แนะนำตัวแทนแล้ว',
            timer: 1200,
            showConfirmButton: false
        });
    }).catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'คัดลอกไม่สำเร็จ',
            text: 'กรุณาคัดลอกจากกล่องข้อความโดยตรง'
        });
    });
}

function openPayoutModal(id, name, amount) {
    document.getElementById('payout_affiliate_id').value = id;
    document.getElementById('payoutPartnerName').textContent = name;
    document.getElementById('payoutAmount').textContent = parseFloat(amount).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const modal = new bootstrap.Modal(document.getElementById('payoutModal'));
    modal.show();
}

document.getElementById('payoutForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnConfirmPayout');
    const spinner = document.getElementById('payoutSpinner');
    const text = document.getElementById('payoutBtnText');
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
