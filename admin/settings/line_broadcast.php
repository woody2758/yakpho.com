<?php
/**
 * ============================================================
 * Yak Pho Aroma — LINE Broadcast & Resend Integration Settings
 * Conforms to md/AGENT.md: Strict External CSS, SweetAlert2, Universal Spinner
 * ============================================================
 */

$page_title = "LINE Targeted Broadcast & API Settings";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/line_helper.php';

$alert = null;

// Handle Settings Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_settings') {
    $keys = [
        'resend_api_key'             => trim((string)($_POST['resend_api_key'] ?? '')),
        'resend_from_email'          => trim((string)($_POST['resend_from_email'] ?? '')),
        'admin_notify_email'         => trim((string)($_POST['admin_notify_email'] ?? 'thaiherbfc@gmail.com')),
        'line_channel_access_token'  => trim((string)($_POST['line_channel_access_token'] ?? '')),
        'admin_line_user_id'         => trim((string)($_POST['admin_line_user_id'] ?? '')),
        'line_login_channel_id'      => trim((string)($_POST['line_login_channel_id'] ?? '')),
        'line_login_channel_secret'  => trim((string)($_POST['line_login_channel_secret'] ?? '')),
    ];

    try {
        foreach ($keys as $k => $v) {
            $checkStmt = $db->prepare("SELECT setting_id FROM sitesettings WHERE setting_name = ? LIMIT 1");
            $checkStmt->execute([$k]);
            $existingId = $checkStmt->fetchColumn();

            if ($existingId) {
                $up = $db->prepare("UPDATE sitesettings SET setting_value = ?, updated_at = NOW() WHERE setting_id = ?");
                $up->execute([$v, $existingId]);
            } else {
                $ins = $db->prepare("INSERT INTO sitesettings (setting_name, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $ins->execute([$k, $v]);
            }
        }
        $alert = ['type' => 'success', 'msg' => 'บันทึกการตั้งค่า API และการแจ้งเตือนสำเร็จเรียบร้อยแล้ว'];
    } catch (Exception $e) {
        $alert = ['type' => 'danger', 'msg' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()];
    }
}

// Handle Test Admin Notifications (Email + LINE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'test_admin_notify') {
    $testOrderData = [
        'orders_no'        => 'TEST-' . date('Hi'),
        'order_date'       => date('d/m/Y H:i'),
        'customer_name'    => 'คุณสมชาย ทดสอบระบบแจ้งเตือน',
        'customer_code'    => 'C1',
        'customer_phone'   => '080-061-7073',
        'customer_email'   => 'thaiherbfc@gmail.com',
        'customer_line'    => '@yakpho',
        'shipping_address' => '32/4 ถ.พระยาสุเรนทร์ แขวงสามวาตะวันตก เขตคลองสามวา กรุงเทพฯ 10510',
        'payment_method'   => 'โอนเงินผ่าน PromptPay QR / บัญชีธนาคาร',
        'status_text'      => 'รอชำระเงิน / รอแนบสลิป',
        'order_items_html' => '<tr><td style="padding:8px;border-bottom:1px solid #eee;">H001 สูตรร้อน ยักษ์โพธิ์ อโรม่า สีขาวต้นตำรับ 1 กิโล</td><td style="text-align:right;padding:8px;border-bottom:1px solid #eee;">2</td><td style="text-align:right;padding:8px;border-bottom:1px solid #eee;">1,180.00 บาท</td></tr>',
        'total_qty'        => 2,
        'total_weight_g'   => 2000,
        'subtotal'         => '1,180.00',
        'delivery_cost'    => '50.00',
        'cod_fee'          => '0.00',
        'stamp_discount'   => '30',
        'other_discount'   => '0.00',
        'grand_total'      => '1,200.00',
        'order_note'       => 'ทดสอบระบบส่งอีเมลและแจ้งเตือน LINE สำหรับ Admin',
        'tracking_url'     => ROOT_URL . '/th/orders',
        'admin_url'        => ROOT_URL . '/admin/orders/',
    ];

    $emailRes = send_admin_order_email($db, $testOrderData);
    $lineTestMsg = "🔔 [ทดสอบแจ้งเตือนคำสั่งซื้อใหม่เข้า Admin]\n"
                 . "เลขที่: #{$testOrderData['orders_no']}\n"
                 . "เวลา: {$testOrderData['order_date']} น.\n"
                 . "━━━━━━━━━━━━━━\n"
                 . "👤 ลูกค้า: {$testOrderData['customer_name']} (C1)\n"
                 . "📞 โทร: {$testOrderData['customer_phone']}\n"
                 . "📍 ที่อยู่: {$testOrderData['shipping_address']}\n"
                 . "━━━━━━━━━━━━━━\n"
                 . "🛒 รายการสินค้า (2 ชิ้น | 2,000g):\n"
                 . "• H001 สูตรร้อน ยักษ์โพธิ์ อโรม่า 1 กิโล x2 (฿1,180)\n"
                 . "━━━━━━━━━━━━━━\n"
                 . "• ยอดรวมสินค้า: ฿1,180.00\n"
                 . "• ค่าจัดส่ง: ฿50.00\n"
                 . "• ใช้แต้ม E-Stamp: 30 แต้ม (-฿30.00)\n"
                 . "💰 ยอดสุทธิ: ฿1,200.00 บาท\n"
                 . "💳 ชำระโดย: โอนเงินผ่าน PromptPay QR\n"
                 . "📌 สถานะ: รอชำระเงิน\n"
                 . "━━━━━━━━━━━━━━\n"
                 . "🔗 จัดการออเดอร์:\n" . ROOT_URL . "/admin/orders/";
    $lineRes = send_admin_line_notification($db, $lineTestMsg);

    $msgParts = [];
    $msgParts[] = $emailRes['success'] ? 'ส่งอีเมลเข้า Admin สำเร็จ' : ('อีเมล: ' . ($emailRes['message'] ?? 'ไม่สำเร็จ'));
    $msgParts[] = $lineRes['success'] ? ('ส่ง LINE เข้า Admin สำเร็จ (' . ($lineRes['sent_count'] ?? 1) . ' บัญชี)') : ('LINE: ' . ($lineRes['message'] ?? 'ยังไม่ได้ตั้งค่า Token/ผูก LINE Admin'));
    $alert = [
        'type' => ($emailRes['success'] || $lineRes['success']) ? 'success' : 'danger',
        'msg'  => implode(' | ', $msgParts),
    ];
}

// Handle LINE Broadcast Send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_broadcast') {
    $targetTier = trim((string)($_POST['target_tier'] ?? 'all'));
    $messageText = trim((string)($_POST['message_text'] ?? ''));

    if ($messageText !== '') {
        $res = broadcast_line_message($db, $targetTier, $messageText);
        if ($res['success']) {
            $alert = [
                'type' => 'success', 
                'msg'  => "🎉 ส่งบรอดแคสต์ LINE สำเร็จ! ไปยังสมาชิกกลุ่ม <strong>" . htmlspecialchars($targetTier) . "</strong> จำนวน <strong>" . number_format($res['recipients']) . "</strong> บัญชี"
            ];
        } else {
            $alert = [
                'type' => 'danger', 
                'msg'  => "การส่งบรอดแคสต์ล้มเหลว: " . htmlspecialchars($res['message'])
            ];
        }
    } else {
        $alert = ['type' => 'warning', 'msg' => 'กรุณาระบุข้อความที่ต้องการส่ง'];
    }
}

// Ensure required tables exist (Self-healing for production DB)
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `sitesettings` (
        `setting_id` INT(11) NOT NULL AUTO_INCREMENT,
        `setting_name` VARCHAR(100) NOT NULL,
        `setting_value` TEXT DEFAULT NULL,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `save_id` INT(11) DEFAULT 0,
        `update_id` INT(11) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`setting_id`),
        UNIQUE KEY `setting_name` (`setting_name`),
        KEY `idx_setting_name` (`setting_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS `line_broadcast_logs` (
        `broadcast_id` INT(11) NOT NULL AUTO_INCREMENT,
        `target_tier` VARCHAR(50) NOT NULL DEFAULT 'all',
        `message_text` TEXT NOT NULL,
        `recipient_count` INT(11) DEFAULT 0,
        `sent_by_user_id` INT(11) NOT NULL DEFAULT 0,
        `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`broadcast_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {}

// Load Current Settings
$settings = [];
try {
    $stmt = $db->query("SELECT setting_name, setting_value FROM sitesettings WHERE setting_name IN ('resend_api_key', 'resend_from_email', 'admin_notify_email', 'line_channel_access_token', 'admin_line_user_id', 'line_login_channel_id', 'line_login_channel_secret')");
    if ($stmt) {
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    }
} catch (Throwable $e) {}

// Count LINE connected users per tier
$row = [];
try {
    $tierCountsStmt = $db->query("
        SELECT 
            COUNT(CASE WHEN line_user_id IS NOT NULL AND line_user_id != '' THEN 1 END) as all_line,
            COUNT(CASE WHEN member_tier = 'therapist_partner' AND line_user_id IS NOT NULL AND line_user_id != '' THEN 1 END) as partner_line,
            COUNT(CASE WHEN member_tier = 'vip_spa' AND line_user_id IS NOT NULL AND line_user_id != '' THEN 1 END) as vip_line,
            COUNT(CASE WHEN (member_tier = 'general' OR member_tier IS NULL OR member_tier = '') AND line_user_id IS NOT NULL AND line_user_id != '' THEN 1 END) as general_line,
            COUNT(CASE WHEN member_tier = 'oem_export' AND line_user_id IS NOT NULL AND line_user_id != '' THEN 1 END) as oem_line
        FROM user WHERE user_del = 0
    ");
    if ($tierCountsStmt) {
        $row = $tierCountsStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {}

$lineCounts = [
    'all'               => (int)($row['all_line'] ?? 0),
    'therapist_partner' => (int)($row['partner_line'] ?? 0),
    'vip_spa'           => (int)($row['vip_line'] ?? 0),
    'general'           => (int)($row['general_line'] ?? 0),
    'oem_export'        => (int)($row['oem_line'] ?? 0),
];

// Fetch recent broadcast logs
$history = [];
try {
    $histStmt = $db->query("SELECT * FROM line_broadcast_logs ORDER BY broadcast_id DESC LIMIT 10");
    if ($histStmt) {
        $history = $histStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {}

ob_start();
?>

<div class="row align-items-center mb-3">
    <div class="col-sm-6">
        <h4 class="mb-0 fw-bold">
            <i data-lucide="radio" class="me-2 text-success icon-md"></i>
            LINE Targeted Broadcast &amp; API Integration
        </h4>
        <small class="text-muted">บรอดแคสต์ข้อความตรงกลุ่มเป้าหมาย (หมอนวด / VIP Spa) และตั้งค่า API Resend &amp; LINE OA</small>
    </div>
    <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
        <div class="btn-group">
            <a href="<?= ADMIN_URL ?>/users/members.php" class="btn btn-outline-secondary btn-sm">
                <i data-lucide="users" class="me-1 icon-sm"></i>ระบบสมาชิก
            </a>
            <a href="<?= ADMIN_URL ?>/settings/email_templates.php" class="btn btn-outline-secondary btn-sm">
                <i data-lucide="mail" class="me-1 icon-sm"></i>จัดการเทมเพลตอีเมล
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

<div class="row g-4 mt-1">
    <!-- Broadcast Composer Column -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-transparent fw-bold py-3 border-bottom">
                <i data-lucide="send" class="me-2 text-success"></i>สร้างข้อความบรอดแคสต์ LINE (Targeted Broadcast)
            </div>
            <div class="card-body">
                <form method="POST" id="broadcastForm">
                    <input type="hidden" name="action" value="send_broadcast">

                    <div class="mb-3">
                        <label class="form-label fw-bold small">เลือกกลุ่มเป้าหมาย (Member Tier):</label>
                        <select name="target_tier" id="targetTierSelect" class="form-select" onchange="updateRecipientCount()">
                            <option value="all" data-count="<?= $lineCounts['all'] ?>">🌐 ลูกค้าที่เชื่อมต่อ LINE ทั้งหมด (<?= number_format($lineCounts['all']) ?> คน)</option>
                            <option value="therapist_partner" data-count="<?= $lineCounts['therapist_partner'] ?>">⭐ Yak Pho Partner (หมอนวด Affiliate) (<?= number_format($lineCounts['therapist_partner']) ?> คน)</option>
                            <option value="vip_spa" data-count="<?= $lineCounts['vip_spa'] ?>">👑 VIP Spa Member (ร้านนวด &amp; สปา) (<?= number_format($lineCounts['vip_spa']) ?> คน)</option>
                            <option value="general" data-count="<?= $lineCounts['general'] ?>">🌿 สมาชิกทั่วไป (General Retail) (<?= number_format($lineCounts['general']) ?> คน)</option>
                            <option value="oem_export" data-count="<?= $lineCounts['oem_export'] ?>">💎 OEM &amp; Export (<?= number_format($lineCounts['oem_export']) ?> คน)</option>
                        </select>
                        <div class="form-text text-muted" id="recipientNote">
                            ยอดผู้รับโดยประมาณ: <strong class="text-success" id="recipientCountBadge"><?= number_format($lineCounts['all']) ?></strong> บัญชี LINE
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label fw-bold small mb-1">ข้อความบรอดแคสต์ (LINE Message):</label>
                            <small class="text-muted" id="charCounter">0 ตัวอักษร</small>
                        </div>
                        <textarea name="message_text" id="broadcastMsgInput" class="form-control" rows="6" 
                                  placeholder="พิมพ์ข้อความที่ต้องการแจ้งเตือน เช่น โปรโมชั่นพิเศษเรทส่งสำหรับร้านสปา หรือแจ้งค่าคอมมิชชันหมอนวด..."
                                  oninput="document.getElementById('charCounter').textContent = this.value.length + ' ตัวอักษร'" required></textarea>
                    </div>

                    <div class="alert alert-info py-2 small mb-3">
                        <i data-lucide="info" class="me-1"></i>
                        <strong>ข้อดีของ Targeted Broadcast:</strong> ยิงตรงหาเฉพาะกลุ่มเป้าหมาย เช่น หมอนวด หรือร้านนวดสปา โดยไม่รบกวนลูกค้าทั่วไป และประหยัดโควต้าข้อความฟรีของ LINE OA
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-emerald-send px-4" id="btnSendBroadcast">
                            <span class="spinner-snake d-none me-1" id="broadcastSpinner"></span>
                            <span id="broadcastBtnText"><i data-lucide="send" class="me-1 icon-sm"></i>ส่งบรอดแคสต์ทันที</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Broadcast History Log -->
        <div class="card border-0 shadow-sm rounded-3 mt-4">
            <div class="card-header bg-transparent fw-bold py-3 border-bottom">
                <i data-lucide="history" class="me-2 text-primary"></i>ประวัติการบรอดแคสต์ล่าสุด (10 รายการ)
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-dense">
                    <thead class="table-light">
                        <tr>
                            <th>วัน-เวลา</th>
                            <th>กลุ่มเป้าหมาย</th>
                            <th>ข้อความ</th>
                            <th class="text-center">จำนวนผู้รับ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">ยังไม่มีประวัติการบรอดแคสต์</td></tr>
                        <?php else: ?>
                        <?php foreach ($history as $h): ?>
                        <tr>
                            <td class="text-nowrap text-muted"><?= date('d/m/y H:i', strtotime($h['sent_at'])) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($h['target_tier']) ?></span></td>
                            <td class="text-truncate text-truncate-260" title="<?= htmlspecialchars($h['message_text']) ?>">
                                <?= htmlspecialchars($h['message_text']) ?>
                            </td>
                            <td class="text-center fw-bold text-success"><?= number_format($h['recipient_count']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- API Settings Column -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-transparent fw-bold py-3 border-bottom">
                <i data-lucide="key" class="me-2 text-warning"></i>การตั้งค่า LINE &amp; Resend API Keys
            </div>
            <div class="card-body">
                <form method="POST" id="apiSettingsForm">
                    <input type="hidden" name="action" id="apiFormAction" value="save_settings">

                    <!-- Resend Section -->
                    <div class="admin-section-tag admin-tag-resend">
                        <i data-lucide="mail" class="me-1 icon-xs"></i>Resend Email Service (Transactional Email)
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Resend API Key:</label>
                        <input type="password" name="resend_api_key" class="form-control form-control-sm font-monospace" 
                               value="<?= htmlspecialchars($settings['resend_api_key'] ?? '') ?>" placeholder="re_xxxxxxxxxxxx">
                        <div class="form-text text-fs-11">รับ API Key ได้จาก <a href="https://resend.com/api-keys" target="_blank" rel="noopener">resend.com</a></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">อีเมลผู้ส่ง (From Email):</label>
                        <input type="text" name="resend_from_email" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($settings['resend_from_email'] ?? 'Yak Pho Aroma <orders@thaispa.app>') ?>">
                        <div class="form-text text-fs-11">ระบบใช้โดเมนที่ Verify แล้วอัตโนมัติ (เช่น <code>orders@thaispa.app</code>) และตั้ง Reply-To ไปที่อีเมลแอดมิน</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-success"><i data-lucide="bell-ring" class="me-1 icon-xs"></i>อีเมล Admin สำหรับรับแจ้งเตือนคำสั่งซื้อใหม่:</label>
                        <input type="text" name="admin_notify_email" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($settings['admin_notify_email'] ?? 'thaiherbfc@gmail.com') ?>" placeholder="thaiherbfc@gmail.com">
                        <div class="form-text text-fs-11">ส่งรายงานคำสั่งซื้อใหม่และสลิปโอนเงินเข้า <strong>thaiherbfc@gmail.com</strong> อัตโนมัติ (ใส่หลายอีเมลคั่นด้วยเครื่องหมายจุลภาค <code>,</code> ได้)</div>
                    </div>

                    <hr>

                    <!-- LINE Section -->
                    <div class="admin-section-tag admin-tag-line">
                        <i data-lucide="message-circle" class="me-1 icon-xs"></i>LINE Official Account &amp; Messaging API
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Channel Access Token (Long-Lived):</label>
                        <input type="password" name="line_channel_access_token" class="form-control form-control-sm font-monospace" 
                               value="<?= htmlspecialchars($settings['line_channel_access_token'] ?? '') ?>" placeholder="ใส่ Channel Access Token จาก LINE Developers Console">
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-success mb-0"><i data-lucide="bell" class="me-1 icon-xs"></i>LINE User ID ของ Admin (รับแจ้งเตือนออเดอร์เข้า):</label>
                            <a href="<?= ROOT_URL ?>/api/line_auth.php?action=login&amp;redirect=<?= urlencode(ADMIN_URL . '/settings/line_broadcast.php') ?>" class="btn btn-xs btn-success py-0 px-2" style="font-size: 0.72rem;">
                                🔗 คลิกผูกบัญชี LINE ของฉันเป็น Admin
                            </a>
                        </div>
                        <input type="text" name="admin_line_user_id" class="form-control form-control-sm font-monospace" 
                               value="<?= htmlspecialchars($settings['admin_line_user_id'] ?? '') ?>" placeholder="เช่น U123456... หรือ C123456... (คั่นด้วย , ได้หลายไอดี)">
                        <div class="form-text text-fs-11">รองรับทั้ง LINE ส่วนตัว (<code>U...</code>) และ LINE กลุ่ม (<code>C...</code>) รวมถึงส่งหาผู้ใช้สิทธิ์ <code>owner / admin / staff</code> ที่ผูก LINE แล้วทุกบัญชีอัตโนมัติ</div>
                    </div>
                    <div class="callback-url-box mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small text-gold-accent">
                                <i data-lucide="webhook" class="me-1 icon-xs"></i>Webhook URL (สำหรับดึงบอทเข้า LINE กลุ่มแอดมิน):
                            </strong>
                            <button type="button" class="btn btn-sm btn-outline-gold py-0 px-2 text-fs-11" onclick="navigator.clipboard.writeText('<?= ROOT_URL ?>/api/line_webhook.php'); Swal.fire({toast:true, position:'top-end', icon:'success', title:'คัดลอก Webhook URL แล้ว', timer:1500, showConfirmButton:false});">
                                <i data-lucide="copy" class="icon-xs me-1"></i>คัดลอก
                            </button>
                        </div>
                        <code class="callback-url-text"><?= ROOT_URL ?>/api/line_webhook.php</code>
                        <div class="form-text text-fs-11 mt-1">เมื่อเชิญบอทเข้ากลุ่ม หรือพิมพ์ <code>#ผูกกลุ่ม</code> ในกลุ่ม บอทจะบันทึก Group ID (<code>C...</code>) ให้อัตโนมัติ</div>
                    </div>

                    <hr>

                    <!-- LINE Social Login Section -->
                    <div class="admin-section-tag admin-tag-login">
                        <i data-lucide="log-in" class="me-1 icon-xs"></i>LINE Login Channel (Social Login)
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">LINE Login Channel ID:</label>
                        <input type="text" name="line_login_channel_id" class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($settings['line_login_channel_id'] ?? '') ?>" placeholder="เช่น 200xxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">LINE Login Channel Secret:</label>
                        <input type="password" name="line_login_channel_secret" class="form-control form-control-sm font-monospace" 
                               value="<?= htmlspecialchars($settings['line_login_channel_secret'] ?? '') ?>">
                    </div>
                    <div class="callback-url-box mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small text-gold-accent">
                                <i data-lucide="link" class="me-1 icon-xs"></i>Callback URL สำหรับใส่ใน LINE Console:
                            </strong>
                            <button type="button" class="btn btn-sm btn-outline-gold py-0 px-2 text-fs-11" onclick="navigator.clipboard.writeText('<?= ROOT_URL ?>/api/line_auth.php?action=callback'); Swal.fire({toast:true, position:'top-end', icon:'success', title:'คัดลอก Callback URL แล้ว', timer:1500, showConfirmButton:false});">
                                <i data-lucide="copy" class="icon-xs me-1"></i>คัดลอก
                            </button>
                        </div>
                        <code class="callback-url-text"><?= ROOT_URL ?>/api/line_auth.php?action=callback</code>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top">
                        <button type="submit" class="btn btn-outline-success btn-sm" onclick="document.getElementById('apiFormAction').value='test_admin_notify'">
                            <i data-lucide="send" class="me-1 icon-sm"></i>ทดสอบส่งเมล &amp; LINE แจ้งเตือน Admin
                        </button>
                        <button type="submit" class="btn btn-gold-save px-4 btn-sm" id="btnSaveApiSettings" onclick="document.getElementById('apiFormAction').value='save_settings'">
                            <span class="spinner-snake d-none me-1" id="saveApiSpinner"></span>
                            <span id="saveApiBtnText"><i data-lucide="save" class="me-1 icon-sm"></i>บันทึกการตั้งค่า API</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updateRecipientCount() {
    const sel = document.getElementById('targetTierSelect');
    const opt = sel.options[sel.selectedIndex];
    const count = opt.getAttribute('data-count') || 0;
    document.getElementById('recipientCountBadge').textContent = parseInt(count).toLocaleString();
}

document.getElementById('broadcastForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const sel = document.getElementById('targetTierSelect');
    const tierName = sel.options[sel.selectedIndex].text;
    const msg = document.getElementById('broadcastMsgInput').value.trim();

    if (!msg) {
        Swal.fire({
            icon: 'warning',
            title: 'กรุณากรอกข้อความ',
            text: 'กรุณาระบุข้อความที่ต้องการส่งบรอดแคสต์'
        });
        return;
    }

    const confirmRes = await Swal.fire({
        title: 'ยืนยันการส่งบรอดแคสต์?',
        html: `ต้องการส่งข้อความไปยังกลุ่ม:<br><b>${tierName}</b><br><small class="text-danger">ข้อความจะถูกส่งทันทีผ่าน LINE OA Messaging API</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยันและส่งข้อความ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#16a34a'
    });

    if (confirmRes.isConfirmed) {
        const btn = document.getElementById('btnSendBroadcast');
        const spinner = document.getElementById('broadcastSpinner');
        const text = document.getElementById('broadcastBtnText');
        btn.disabled = true;
        spinner?.classList.remove('d-none');
        if (text) text.textContent = 'กำลังส่งบรอดแคสต์...';
        this.submit();
    }
});

document.getElementById('apiSettingsForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnSaveApiSettings');
    const spinner = document.getElementById('saveApiSpinner');
    const text = document.getElementById('saveApiBtnText');
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
