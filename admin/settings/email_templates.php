<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/resend_helper.php';

$page_title = "จัดการเทมเพลตอีเมล (Resend Email Templates)";

// Handle Save Template Post
$alert = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_template') {
        $templateId = (int)($_POST['template_id'] ?? 0);
        $subject = trim($_POST['email_subject'] ?? '');
        $bodyHtml = $_POST['email_body_html'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($templateId > 0 && !empty($subject)) {
            $stmt = $db->prepare("UPDATE resend_email_templates SET email_subject = ?, email_body_html = ?, is_active = ?, updated_at = NOW() WHERE template_id = ?");
            $stmt->execute([$subject, $bodyHtml, $isActive, $templateId]);
            $alert = ['type' => 'success', 'msg' => 'บันทึกการแก้ไขเทมเพลตเรียบร้อยแล้ว'];
        }
    } elseif ($action === 'test_send') {
        $templateCode = trim($_POST['test_template_code'] ?? '');
        $recipientEmail = trim($_POST['test_recipient_email'] ?? '');

        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $alert = ['type' => 'danger', 'msg' => 'อีเมลผู้รับไม่ถูกต้อง'];
        } else {
            // Mock sample tags
            $sampleTags = [
                'customer_name'          => 'คุณสมชาย ใจดี (ทดสอบระบบ)',
                'order_no'               => '690123',
                'order_date'             => date('d/m/Y H:i'),
                'payment_method'         => 'PromptPay QR (สแกนจ่ายสำเร็จ)',
                'shipping_address'       => '99/4 หมู่ 2 ต.บางพูน อ.เมือง จ.ปทุมธานี 12000',
                'order_items_html'       => '<tr><td>สูตรร้อน ยักษ์ไพร บาล์ม 1 กก. (x2)</td><td align="right">2</td><td align="right">1,180.00</td></tr>',
                'subtotal'               => '1,180.00',
                'discount_amount'        => '50.00',
                'delivery_cost'          => '0.00',
                'grand_total'            => '1,130.00',
                'm_stamp_earned'         => '11',
                'm_stamp_balance'        => '65',
                'tracking_url'           => ROOT_URL . '/th/profile',
                'courier_name'           => 'Flash Express',
                'tracking_number'        => 'TH0123456789A',
                'verify_url'             => ROOT_URL . '/api/member.php?action=verify_email&token=sample_test_token',
                'm_stamp_bonus'          => '20',
                'member_tier_name'       => '👑 VIP Spa Member',
                'recommended_products_url'=> ROOT_URL . '/th/products',
                'partner_name'           => 'หมอนวดสมศรี (เธอราพิสต์พาร์ทเนอร์)',
                'period_month'           => date('m/Y'),
                'total_orders'           => '8',
                'total_sales'            => '5,520.00',
                'commission_amount'      => '552.00',
                'payout_status'          => 'รอโอนเข้าบัญชี (รอบสิ้นเดือน)',
                'affiliate_dashboard_url'=> ROOT_URL . '/th/profile',
                'spa_name'               => 'ศิริล้านนา สปา & มาสสาจ',
                'last_order_date'        => date('d/m/Y', strtotime('-35 days')),
                'reorder_url'            => ROOT_URL . '/th/corporate-gifts',
            ];

            $res = send_templated_email($db, $recipientEmail, $templateCode, $sampleTags);
            if ($res['success']) {
                $alert = ['type' => 'success', 'msg' => 'ส่งอีเมลทดสอบสำเร็จไปยัง ' . htmlspecialchars($recipientEmail) . ' (Resend ID: ' . ($res['id'] ?? '-') . ')'];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'ส่งอีเมลไม่สำเร็จ: ' . ($res['message'] ?? 'Unknown error')];
            }
        }
    }
}

// Ensure resend_email_templates table exists and default templates are seeded
if (function_exists('ensure_default_resend_templates')) {
    ensure_default_resend_templates($db);
}

// Fetch all templates
$templates = [];
try {
    $stmt = $db->query("SELECT * FROM resend_email_templates ORDER BY template_id ASC");
    if ($stmt) {
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {}

// Check if Resend API key is configured
$resendApiKey = get_site_setting($db, 'resend_api_key');

ob_start();
?>

<div class="row align-items-center mb-3">
    <div class="col-sm-6">
        <h4 class="mb-0 fw-bold">
            <i data-lucide="mail" class="me-2 text-success icon-md"></i>
            จัดการเทมเพลตอีเมล (Resend API)
        </h4>
        <small class="text-muted">เทมเพลตมาตรฐาน 6 แบบ สำหรับแจ้งเตือนคำสั่งซื้อ, ขนส่ง, สมาชิก M-Stamp และหมอนวด Affiliate</small>
    </div>
    <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
        <div class="btn-group">
            <a href="<?= ADMIN_URL ?>/settings/line_broadcast.php" class="btn btn-outline-secondary btn-sm">
                <i data-lucide="key" class="me-1 icon-sm"></i>ตั้งค่า API Key
            </a>
            <a href="<?= ADMIN_URL ?>/users/members.php" class="btn btn-outline-secondary btn-sm">
                <i data-lucide="users" class="me-1 icon-sm"></i>ระบบสมาชิก
            </a>
        </div>
    </div>
</div>

<?php if ($alert): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: '<?= $alert['type'] === 'danger' ? 'error' : 'success' ?>',
            title: '<?= addslashes(strip_tags($alert['msg'])) ?>',
            showConfirmButton: false,
            timer: 2500
        });
    }
});
</script>
<?php endif; ?>

<?php if (empty($resendApiKey)): ?>
<div class="alert alert-warning my-3 d-flex align-items-center justify-content-between">
    <div>
        <strong><i data-lucide="alert-triangle" class="me-1 icon-sm"></i> ยังไม่ได้ระบุ Resend API Key:</strong> 
        กรุณาใส่ API Key ในหน้าตั้งค่าเพื่อเปิดใช้งานการส่งอีเมลจริง
    </div>
    <a href="<?= ADMIN_URL ?>/settings/line_broadcast.php" class="btn btn-sm btn-gold-save">ไปตั้งค่า API Key</a>
</div>
<?php endif; ?>

<div class="row g-4 mt-1">
    <!-- Templates List -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-transparent fw-bold py-3 border-bottom">
                <i data-lucide="list" class="me-2 text-primary"></i>รายชื่อเทมเพลต (<?= count($templates) ?> แบบ)
            </div>
            <div class="list-group list-group-flush" id="templateList">
                <?php foreach ($templates as $i => $t): ?>
                <button type="button" 
                        class="list-group-item list-group-item-action py-3 <?= $i === 0 ? 'active' : '' ?>" 
                        onclick="selectTemplate(<?= htmlspecialchars(json_encode($t)) ?>, this)">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <span class="badge <?= $t['is_active'] ? 'bg-success' : 'bg-secondary' ?> mb-1">
                            <?= $t['is_active'] ? 'Active' : 'Disabled' ?>
                        </span>
                        <small class="text-muted text-fs-11">#<?= $t['template_code'] ?></small>
                    </div>
                    <div class="fw-bold fs-6 mt-1"><?= htmlspecialchars($t['template_name']) ?></div>
                    <div class="small text-truncate mt-1 text-muted"><?= htmlspecialchars($t['email_subject']) ?></div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Template Editor & Preview -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-bold" id="editorTitle">
                    <i data-lucide="edit-3" class="me-1 text-primary"></i>แก้ไขเทมเพลต: <span id="currentTemplateName">-</span>
                </span>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" onclick="previewHtmlModal()">
                        <i data-lucide="eye" class="me-1"></i>ดูตัวอย่าง (Preview)
                    </button>
                    <button type="button" class="btn btn-outline-gold" onclick="openTestSendModal()">
                        <i data-lucide="send" class="me-1"></i>ทดสอบส่งอีเมล (Test)
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" id="templateForm">
                    <input type="hidden" name="action" value="save_template">
                    <input type="hidden" name="template_id" id="edit_template_id" value="">

                    <div class="row g-3">
                        <div class="col-md-9">
                            <label class="form-label fw-bold small">หัวข้ออีเมล (Email Subject):</label>
                            <input type="text" class="form-control" name="email_subject" id="edit_email_subject" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">สถานะใช้งาน:</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1" checked>
                                <label class="form-check-label small" for="edit_is_active">เปิดใช้งาน</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small mb-0">Merge Tags ที่สามารถใช้ได้ (คลิกเพื่อแทรก):</label>
                            </div>
                            <div id="mergeTagsContainer" class="d-flex flex-wrap gap-1 p-2 bg-light rounded border mb-2"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small">เนื้อหา HTML (HTML Body):</label>
                            <textarea class="form-control font-monospace text-fs-13" name="email_body_html" id="edit_email_body_html" rows="18"></textarea>
                        </div>

                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-gold-save px-4" id="btnSaveTemplate">
                                <span class="spinner-snake d-none me-1" id="saveTemplateSpinner"></span>
                                <span id="saveTemplateBtnText"><i data-lucide="save" class="me-1 icon-sm"></i>บันทึกการเปลี่ยนแปลง</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i data-lucide="eye" class="me-1"></i>ตัวอย่างการแสดงผลอีเมล</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="previewIframe" class="preview-iframe"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Test Send Modal -->
<div class="modal fade" id="testSendModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="testSendForm">
                <input type="hidden" name="action" value="test_send">
                <input type="hidden" name="test_template_code" id="test_template_code" value="">

                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i data-lucide="send" class="me-1 text-primary"></i>ส่งอีเมลทดสอบ (Resend Test Send)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        ระบบจะนำข้อมูลตัวอย่าง (Mock Data) มาแทนที่ Merge Tags และส่งผ่าน Resend API ไปยังอีเมลที่ระบุด้านล่าง:
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">อีเมลผู้รับ:</label>
                        <input type="email" name="test_recipient_email" class="form-control" placeholder="your_email@example.com" required>
                    </div>
                    <div class="small bg-light p-2 rounded">
                        <strong>เทมเพลต:</strong> <span id="testTemplateLabel"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-gold-save btn-sm px-3" id="btnSendTest">
                        <span class="spinner-snake d-none me-1" id="sendTestSpinner"></span>
                        <span id="sendTestBtnText"><i data-lucide="send" class="me-1 icon-sm"></i>ส่งทันที</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentTemplate = <?= !empty($templates) ? json_encode($templates[0]) : 'null' ?>;

function selectTemplate(tmpl, btnEl) {
    currentTemplate = tmpl;
    if (btnEl) {
        document.querySelectorAll('#templateList .list-group-item').forEach(el => el.classList.remove('active'));
        btnEl.classList.add('active');
    }

    document.getElementById('currentTemplateName').textContent = tmpl.template_name;
    document.getElementById('edit_template_id').value = tmpl.template_id;
    document.getElementById('edit_email_subject').value = tmpl.email_subject;
    document.getElementById('edit_email_body_html').value = tmpl.email_body_html;
    document.getElementById('edit_is_active').checked = (parseInt(tmpl.is_active) === 1);

    // Render merge tags
    const container = document.getElementById('mergeTagsContainer');
    container.innerHTML = '';
    if (tmpl.available_merge_tags) {
        const tags = tmpl.available_merge_tags.split(',').map(t => t.trim());
        tags.forEach(t => {
            const span = document.createElement('span');
            span.className = 'badge bg-secondary bg-opacity-25 text-dark border badge-merge-tag';
            span.textContent = t;
            span.title = 'คลิกเพื่อแทรกลงในกล่องข้อความ';
            span.onclick = () => insertTag(t);
            container.appendChild(span);
        });
    }
}

function insertTag(tag) {
    const textarea = document.getElementById('edit_email_body_html');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + tag + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + tag.length;
}

function previewHtmlModal() {
    const html = document.getElementById('edit_email_body_html').value;
    const iframe = document.getElementById('previewIframe');
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
    setTimeout(() => {
        iframe.contentWindow.document.open();
        iframe.contentWindow.document.write(html);
        iframe.contentWindow.document.close();
    }, 200);
}

function openTestSendModal() {
    if (!currentTemplate) return;
    document.getElementById('test_template_code').value = currentTemplate.template_code;
    document.getElementById('testTemplateLabel').textContent = currentTemplate.template_name + ' (' + currentTemplate.template_code + ')';
    const modal = new bootstrap.Modal(document.getElementById('testSendModal'));
    modal.show();
}

document.getElementById('templateForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnSaveTemplate');
    const spinner = document.getElementById('saveTemplateSpinner');
    const text = document.getElementById('saveTemplateBtnText');
    if (btn.disabled) return false;
    btn.disabled = true;
    spinner?.classList.remove('d-none');
    if (text) text.textContent = 'กำลังบันทึก...';
});

document.getElementById('testSendForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnSendTest');
    const spinner = document.getElementById('sendTestSpinner');
    const text = document.getElementById('sendTestBtnText');
    if (btn.disabled) return false;
    btn.disabled = true;
    spinner?.classList.remove('d-none');
    if (text) text.textContent = 'กำลังส่ง...';
});

document.addEventListener('DOMContentLoaded', () => {
    if (currentTemplate) {
        selectTemplate(currentTemplate, null);
    }
    if (window.lucide) {
        window.lucide.createIcons();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';

