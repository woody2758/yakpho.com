<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — PromptPay QR Payment & Slip Upload Page
 * ============================================================
 * Clean URL: /pay/{track_token} or /pay.php?v={track_token}
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/member_helper.php';

$token = trim((string)($_GET['v'] ?? $_GET['no'] ?? ''));
if (empty($token)) {
    header('Location: ' . ROOT_URL);
    exit;
}

// Fetch Order Details
$stmt = $db->prepare("
    SELECT o.*,
           u.user_name, u.user_lastname, u.user_tel, u.user_mobile, u.user_email,
           a.addr_name, a.addr_mobile, a.addr_detail, a.addr_postcode, p.name_in_thai AS province_name,
           pc.paycat_name
    FROM orders o
    LEFT JOIN user u ON o.user_id = u.user_id
    LEFT JOIN addr a ON o.addr_id = a.addr_id
    LEFT JOIN provinces p ON a.provinces_id = p.id
    LEFT JOIN paycat pc ON o.paycat_id = pc.paycat_id
    WHERE (o.track_token = ? OR o.orders_no = ?) AND o.orders_del = 0
    LIMIT 1
");
$stmt->execute([$token, $token]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    die('<div style="font-family:sans-serif;text-align:center;padding:50px;"><h2>ไม่พบข้อมูลคำสั่งซื้อ</h2><p>ลิงก์ชำระเงินอาจไม่ถูกต้อง หรือคำสั่งซื้อถูกลบไปแล้ว</p><a href="' . ROOT_URL . '">กลับสู่หน้าหลัก</a></div>');
}

$trackKey = !empty($order['track_token']) ? (string)$order['track_token'] : (string)$order['orders_no'];

// If COD order (paycat_id == 2), skip directly to order status page
if ((int)($order['paycat_id'] ?? 1) === 2) {
    header('Location: ' . ROOT_URL . '/track/' . urlencode($trackKey));
    exit;
}

// Fetch Shop Info (including PromptPay ID)
$shopStmt = $db->query("SELECT s.*, st.shop_name, st.official_name
                        FROM shop_info s
                        LEFT JOIN shop_info_translations st ON s.shop_id = st.shop_id AND st.lang_code = 'th'
                        WHERE s.shop_id = 1 LIMIT 1");
$shop = $shopStmt ? ($shopStmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

$promptPayRaw = trim((string)($shop['promptpay_id'] ?? ''));
if ($promptPayRaw === '') {
    $promptPayRaw = trim((string)($shop['shop_phone'] ?? '0800617073'));
}
$promptPayClean = preg_replace('/[^0-9]/', '', $promptPayRaw);
if ($promptPayClean === '') {
    $promptPayClean = '0800617073';
}

// Fetch Bank Accounts
$banks = [];
try {
    $bankStmt = $db->query("
        SELECT b.bank_id, b.bank_number, b.bank_SwiftCode,
               bt.bank_name, bt.bank_account, bt.bank_branch
        FROM bank b
        LEFT JOIN bank_translations bt ON b.bank_id = bt.bank_id AND bt.lang_code = 'th'
        WHERE b.bank_del = 0 AND b.bank_status = 1
        ORDER BY b.bank_sort ASC, b.bank_id ASC
    ");
    if ($bankStmt) {
        $banks = $bankStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {}

// EMVCo Standard PromptPay QR Payload Generator (Thai QR Payment)
function generate_promptpay_payload(string $target, float $amount = 0.0): string {
    $digits = preg_replace('/[^0-9]/', '', $target);
    if (strlen($digits) >= 15) {
        // E-Wallet ID (Tag 03)
        $subTag = '03' . sprintf('%02d', strlen($digits)) . $digits;
    } elseif (strlen($digits) === 13) {
        // National ID / Tax ID 13 digits (Tag 02)
        $subTag = '0213' . $digits;
    } else {
        // Mobile Number 10 digits -> 0066 + 9 digits (Tag 01)
        if (strpos($digits, '0') === 0) {
            $digits = substr($digits, 1);
        }
        $formattedPhone = '0066' . $digits;
        $subTag = '01' . sprintf('%02d', strlen($formattedPhone)) . $formattedPhone;
    }

    $aid = '0016A000000677010111';
    $tag29Content = $aid . $subTag;
    $tag29 = '29' . sprintf('%02d', strlen($tag29Content)) . $tag29Content;

    $isDynamic = ($amount > 0);
    $payload = '000201'
             . ($isDynamic ? '010212' : '010211')
             . $tag29
             . '5802TH'
             . '5303764';

    if ($isDynamic) {
        $amtStr = number_format($amount, 2, '.', '');
        $payload .= '54' . sprintf('%02d', strlen($amtStr)) . $amtStr;
    }

    $payload .= '6304';

    // CRC16-CCITT (0xFFFF, polynomial 0x1021)
    $crc = 0xFFFF;
    $len = strlen($payload);
    for ($i = 0; $i < $len; $i++) {
        $crc ^= (ord($payload[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if (($crc & 0x8000) !== 0) {
                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }

    return $payload . strtoupper(sprintf('%04X', $crc));
}

$grandTotal = (float)($order['orders_grandtotal'] ?? 0);
$promptPayPayload = generate_promptpay_payload($promptPayClean, $grandTotal);

// Format PromptPay display number nicely
$promptPayDisplay = $promptPayClean;
if (strlen($promptPayClean) === 10) {
    $promptPayDisplay = substr($promptPayClean, 0, 3) . '-' . substr($promptPayClean, 3, 3) . '-' . substr($promptPayClean, 6, 4);
} elseif (strlen($promptPayClean) === 13) {
    $promptPayDisplay = substr($promptPayClean, 0, 1) . '-' . substr($promptPayClean, 1, 4) . '-' . substr($promptPayClean, 5, 5) . '-' . substr($promptPayClean, 10, 2) . '-' . substr($promptPayClean, 12, 1);
}

$accountHolderName = !empty($banks[0]['bank_account'])
    ? $banks[0]['bank_account']
    : (!empty($shop['official_name']) ? $shop['official_name'] : ($shop['shop_name'] ?? 'บริษัท เอสเธอร์ อโรม่า จำกัด'));

$existingSlip = !empty($order['orders_slip']) ? (string)$order['orders_slip'] : '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ชำระเงินคำสั่งซื้อ #<?= htmlspecialchars((string)$order['orders_no']) ?> - <?= htmlspecialchars($shop['shop_name'] ?? 'Yak Pho Aroma') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- QRCode.js for crisp client-side PromptPay QR rendering -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root {
            --primary: #0F392B;
            --primary-light: #185440;
            --gold: #C5A059;
            --promptpay-blue: #003D6B;
            --promptpay-cyan: #00A6E6;
            --bg-body: #F1F5F9;
            --card-bg: #FFFFFF;
            --text-dark: #1E293B;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
            --success: #10B981;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Prompt', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            line-height: 1.5;
            padding-bottom: 40px;
            -webkit-font-smoothing: antialiased;
        }
        .app-container {
            max-width: 560px;
            margin: 0 auto;
            padding: 16px 14px;
        }
        .top-header-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 18px;
            padding: 20px 18px;
            color: #fff;
            position: relative;
            margin-bottom: 16px;
            box-shadow: 0 10px 25px rgba(15, 57, 43, 0.2);
            text-align: center;
        }
        .header-home-btn {
            position: absolute;
            top: 14px;
            left: 14px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .header-home-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }
        .header-track-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            padding: 6px 12px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #FFFFFF;
            font-size: 0.78rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .header-track-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        .order-badge-pill {
            display: inline-block;
            background: rgba(197, 160, 89, 0.25);
            border: 1px solid rgba(197, 160, 89, 0.5);
            color: #FDE68A;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            margin-top: 8px;
            margin-bottom: 6px;
        }
        .pay-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .pay-subtitle {
            font-size: 0.84rem;
            opacity: 0.85;
        }

        /* PromptPay QR Card */
        .qr-payment-card {
            background: #FFFFFF;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.07);
            border: 1px solid var(--border-color);
            margin-bottom: 16px;
        }
        .qr-banner-top {
            background: var(--promptpay-blue);
            color: #FFFFFF;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .qr-banner-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .promptpay-logo-box {
            background: #FFFFFF;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .promptpay-logo-text {
            color: #003D6B;
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: -0.3px;
            border-left: 3px solid #00A6E6;
            padding-left: 6px;
        }
        .qr-body {
            padding: 22px 20px;
            text-align: center;
        }
        .amount-box {
            background: #F8FAFC;
            border: 1.5px dashed #CBD5E1;
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 18px;
        }
        .amount-label {
            font-size: 0.82rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .amount-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
            margin-top: 2px;
        }
        .qr-canvas-frame {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: #FFFFFF;
            border: 2px solid #003D6B;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 4px 16px rgba(0, 61, 107, 0.08);
            margin-bottom: 14px;
        }
        .qr-frame-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #E2E8F0;
            width: 100%;
        }
        .qr-frame-badge {
            background: #003D6B;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 0.78rem;
            padding: 2px 10px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        #qrcodeContainer {
            width: 220px;
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        #qrcodeContainer img, #qrcodeContainer canvas {
            max-width: 100%;
            height: auto;
        }
        .qr-account-info {
            margin-top: 10px;
            font-size: 0.86rem;
            color: var(--text-dark);
        }
        .qr-account-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--promptpay-blue);
        }
        .qr-account-id {
            color: var(--text-muted);
            font-size: 0.82rem;
        }
        .btn-save-qr {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            max-width: 300px;
            background: #F1F5F9;
            color: var(--promptpay-blue);
            border: 1.5px solid #CBD5E1;
            border-radius: 12px;
            padding: 11px 18px;
            font-family: 'Prompt', sans-serif;
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 6px;
        }
        .btn-save-qr:hover {
            background: #E2E8F0;
            border-color: #94A3B8;
        }

        /* Bank Accounts Fallback Card */
        .bank-list-box {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--border-color);
            text-align: left;
        }
        .bank-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 10px 14px;
            margin-top: 8px;
            gap: 10px;
        }
        .bank-item-info {
            font-size: 0.84rem;
        }
        .bank-number-text {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.95rem;
        }
        .btn-copy-sm {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            color: var(--text-dark);
            border-radius: 8px;
            padding: 5px 10px;
            font-family: 'Prompt', sans-serif;
            font-size: 0.76rem;
            font-weight: 600;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* Slip Upload Card */
        .slip-upload-card {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.07);
            border: 2px solid var(--primary);
            margin-bottom: 16px;
        }
        .slip-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 6px;
        }
        .slip-card-desc {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-bottom: 14px;
        }
        .slip-dropzone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #94A3B8;
            border-radius: 14px;
            padding: 22px 16px;
            background: #F8FAFC;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        .slip-dropzone:hover {
            border-color: var(--primary);
            background: #F0FDF4;
        }
        .slip-preview-wrap {
            display: none;
            margin-top: 12px;
            text-align: center;
        }
        .slip-preview-img {
            max-width: 220px;
            max-height: 280px;
            border-radius: 12px;
            border: 1px solid #CBD5E1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            object-fit: contain;
        }
        .btn-submit-slip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: #FFFFFF;
            border: none;
            border-radius: 14px;
            padding: 14px 20px;
            font-family: 'Prompt', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 16px;
            box-shadow: 0 6px 18px rgba(15, 57, 43, 0.25);
            transition: all 0.2s ease;
        }
        .btn-submit-slip:hover {
            transform: translateY(-1px);
        }
        .btn-submit-slip:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-skip-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            text-align: center;
            padding: 12px;
            color: var(--text-muted);
            font-size: 0.86rem;
            text-decoration: none;
            font-weight: 500;
        }
        .btn-skip-status:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
<div class="app-container">

    <!-- Top Header with Home (links to Profile) & Order Status Link -->
    <div class="top-header-card">
        <a href="<?= ROOT_URL ?>/th/profile" class="header-home-btn" title="ไปหน้าโปรไฟล์สมาชิก">
            <i data-lucide="home" style="width:18px; height:18px;"></i>
        </a>
        <a href="<?= ROOT_URL ?>/track/<?= urlencode($trackKey) ?>" class="header-track-btn">
            <span>สถานะคำสั่งซื้อ</span>
            <i data-lucide="arrow-right" style="width:14px; height:14px;"></i>
        </a>

        <div class="order-badge-pill">คำสั่งซื้อ #<?= htmlspecialchars((string)$order['orders_no']) ?></div>
        <h1 class="pay-title">สแกน QR พร้อมเพย์เพื่อชำระเงิน</h1>
        <p class="pay-subtitle">บันทึก QR Code หรือสแกนผ่านแอปธนาคาร แล้วแนบสลิปด้านล่าง</p>
    </div>

    <!-- 1. PromptPay QR Card -->
    <div class="qr-payment-card">
        <div class="qr-banner-top">
            <div class="qr-banner-brand">
                <div class="promptpay-logo-box">
                    <span style="font-weight:800; color:#003D6B; font-size:0.85rem;">THAI QR</span>
                    <span class="promptpay-logo-text">PromptPay</span>
                </div>
                <span style="font-size:0.82rem; opacity:0.9;">พร้อมเพย์ รับเงินได้ทุกธนาคาร</span>
            </div>
            <i data-lucide="shield-check" style="width:20px; height:20px; color:#38BDF8;"></i>
        </div>

        <div class="qr-body">
            <!-- Exact Net Amount -->
            <div class="amount-box">
                <div class="amount-label">ยอดโอนชำระสุทธิ (ระบุยอดเงินใน QR อัตโนมัติ)</div>
                <div class="amount-value">฿<?= number_format($grandTotal, 2) ?></div>
            </div>

            <!-- QR Code Frame -->
            <div class="qr-canvas-frame">
                <div class="qr-frame-header">
                    <span class="qr-frame-badge">PROMPTPAY พร้อมเพย์</span>
                </div>
                <div id="qrcodeContainer"></div>
                <div class="qr-account-info">
                    <div class="qr-account-name"><?= htmlspecialchars($accountHolderName) ?></div>
                    <div class="qr-account-id">PromptPay ID: <?= htmlspecialchars($promptPayDisplay) ?></div>
                </div>
            </div>

            <div>
                <button type="button" class="btn-save-qr" onclick="downloadPromptPayCard()">
                    <i data-lucide="download" style="width:18px; height:18px;"></i>
                    <span>บันทึกภาพ QR พร้อมเพย์ลงเครื่อง</span>
                </button>
            </div>

            <?php if (!empty($banks)): ?>
            <div class="bank-list-box">
                <div style="font-size:0.8rem; font-weight:600; color:var(--text-muted);">หรือโอนผ่านเลขบัญชีธนาคารโดยตรง:</div>
                <?php foreach ($banks as $b): ?>
                <div class="bank-item">
                    <div class="bank-item-info">
                        <div style="font-weight:600;"><?= htmlspecialchars((string)$b['bank_name']) ?></div>
                        <div class="bank-number-text"><?= htmlspecialchars((string)$b['bank_number']) ?></div>
                        <div style="font-size:0.78rem; color:var(--text-muted);">ชื่อบัญชี: <?= htmlspecialchars((string)$b['bank_account']) ?></div>
                    </div>
                    <button type="button" class="btn-copy-sm" onclick="copyBankNo('<?= htmlspecialchars(preg_replace('/[^0-9]/', '', (string)$b['bank_number'])) ?>')">
                        คัดลอกเลขบัญชี
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Upload Payment Slip Section (Connected to Admin order_slips + orders_status = 3) -->
    <div class="slip-upload-card">
        <div class="slip-card-title">
            <i data-lucide="file-check-2" style="width:22px; height:22px;"></i>
            <span>แนบสลิปแจ้งโอนเงิน</span>
        </div>
        <p class="slip-card-desc">
            เมื่อโอนเงินเรียบร้อยแล้ว กรุณาอัปโหลดภาพสลิปเพื่อปรับสถานะคำสั่งซื้อเป็น <strong>"ชำระเงินแล้ว / เตรียมจัดส่ง"</strong> ทันที
        </p>

        <form id="uploadSlipForm" enctype="multipart/form-data">
            <input type="hidden" name="track_token" value="<?= htmlspecialchars($trackKey) ?>">
            <input type="hidden" name="order_id" value="<?= (int)$order['orders_id'] ?>">

            <label for="slipFileInput" class="slip-dropzone" id="slipDropzone">
                <i data-lucide="image-plus" style="width:36px; height:36px; color:var(--primary); margin-bottom:8px;"></i>
                <div style="font-weight:600; font-size:0.95rem; color:var(--text-dark);" id="slipFileLabel">
                    แตะที่นี่เพื่อเลือกรูปสลิปโอนเงิน
                </div>
                <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">
                    รองรับไฟล์ภาพ JPG, PNG, WEBP จากแอปธนาคารบนมือถือ
                </div>
                <input type="file" id="slipFileInput" name="slip_image" accept="image/*" style="display:none;" onchange="previewSlipImage(this)">
            </label>

            <div class="slip-preview-wrap" id="slipPreviewWrap">
                <img id="slipPreviewImg" src="" alt="ตัวอย่างสลิป" class="slip-preview-img">
                <div style="margin-top:6px;">
                    <button type="button" onclick="document.getElementById('slipFileInput').click()" style="background:none; border:none; color:var(--promptpay-blue); font-family:'Prompt',sans-serif; font-size:0.82rem; font-weight:600; cursor:pointer; text-decoration:underline;">
                        เปลี่ยนรูปสลิป
                    </button>
                </div>
            </div>

            <button type="submit" id="btnSubmitSlip" class="btn-submit-slip">
                <i data-lucide="check-circle-2" style="width:20px; height:20px;"></i>
                <span id="btnSubmitSlipText">ยืนยันการแจ้งโอนเงิน</span>
            </button>
        </form>

        <a href="<?= ROOT_URL ?>/track/<?= urlencode($trackKey) ?>" class="btn-skip-status">
            <span>ข้ามไปดูหน้าสถานะคำสั่งซื้อก่อน (แนบสลิปภายหลังได้)</span>
            <i data-lucide="chevron-right" style="width:16px; height:16px;"></i>
        </a>
    </div>

</div>

<script>
lucide.createIcons();

const promptPayPayload = <?= json_encode($promptPayPayload) ?>;
const orderNo = <?= json_encode((string)$order['orders_no']) ?>;
const netAmountFormatted = <?= json_encode('฿' . number_format($grandTotal, 2)) ?>;
const accountHolder = <?= json_encode($accountHolderName) ?>;
const promptPayDisplay = <?= json_encode($promptPayDisplay) ?>;

// Render QR Code on load
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('qrcodeContainer');
    if (container && typeof QRCode !== 'undefined') {
        new QRCode(container, {
            text: promptPayPayload,
            width: 210,
            height: 210,
            colorDark: "#00254D",
            colorLight: "#FFFFFF",
            correctLevel: QRCode.CorrectLevel.M
        });
    }
});

// Download complete PromptPay QR Card with Header, Amount, and Account Info as PNG
function downloadPromptPayCard() {
    const qrCanvas = document.querySelector('#qrcodeContainer canvas');
    const qrImg = document.querySelector('#qrcodeContainer img');
    const sourceEl = qrCanvas || qrImg;

    if (!sourceEl) {
        Swal.fire('แจ้งเตือน', 'กำลังสร้าง QR Code กรุณารอสักครู่', 'info');
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = 600;
    canvas.height = 760;
    const ctx = canvas.getContext('2d');

    // White Card Background
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Top Navy Banner
    ctx.fillStyle = '#003D6B';
    ctx.fillRect(0, 0, canvas.width, 95);

    // PromptPay Title in Banner
    ctx.fillStyle = '#FFFFFF';
    ctx.font = 'bold 28px Prompt, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('THAI QR PAYMENT • พร้อมเพย์', canvas.width / 2, 58);

    // Order Number & Account Name
    ctx.fillStyle = '#0F392B';
    ctx.font = 'bold 24px Prompt, sans-serif';
    ctx.fillText(accountHolder, canvas.width / 2, 145);

    ctx.fillStyle = '#64748B';
    ctx.font = '18px Prompt, sans-serif';
    ctx.fillText('PromptPay ID: ' + promptPayDisplay + '  |  Order #' + orderNo, canvas.width / 2, 180);

    // Border Box around QR
    ctx.strokeStyle = '#003D6B';
    ctx.lineWidth = 4;
    ctx.strokeRect(120, 205, 360, 360);

    // Draw QR inside Box
    ctx.drawImage(sourceEl, 140, 225, 320, 320);

    // Amount Display
    ctx.fillStyle = '#0F392B';
    ctx.font = 'bold 42px Prompt, sans-serif';
    ctx.fillText('ยอดชำระ: ' + netAmountFormatted, canvas.width / 2, 635);

    ctx.fillStyle = '#64748B';
    ctx.font = '17px Prompt, sans-serif';
    ctx.fillText('กรุณาตรวจสอบยอดเงินและชื่อบัญชีก่อนกดยืนยันโอนเงิน', canvas.width / 2, 680);

    ctx.fillStyle = '#10B981';
    ctx.font = 'bold 16px Prompt, sans-serif';
    ctx.fillText('Yak Pho Aroma (ยักษ์โพธิ์ อโรม่า)', canvas.width / 2, 718);

    const dataUrl = canvas.toDataURL('image/png');
    const a = document.createElement('a');
    a.href = dataUrl;
    a.download = 'PromptPay_Order_' + orderNo + '.png';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'บันทึกภาพ QR พร้อมเพย์เรียบร้อย',
        showConfirmButton: false,
        timer: 2200
    });
}

function copyBankNo(num) {
    navigator.clipboard.writeText(num).then(function() {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'คัดลอกเลขบัญชี ' + num + ' แล้ว',
            showConfirmButton: false,
            timer: 1800
        });
    });
}

function previewSlipImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('slipFileLabel').textContent = 'เลือกไฟล์แล้ว: ' + file.name;
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('slipPreviewImg');
            img.src = e.target.result;
            document.getElementById('slipPreviewWrap').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

document.getElementById('uploadSlipForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fileInput = document.getElementById('slipFileInput');
    if (!fileInput.files || !fileInput.files[0]) {
        Swal.fire({
            icon: 'warning',
            title: 'กรุณาแนบรูปสลิปโอนเงิน',
            text: 'แตะที่กรอบด้านบนเพื่อเลือกรูปภาพสลิปโอนเงินก่อนกดยืนยัน',
            confirmButtonColor: '#0F392B'
        });
        return;
    }

    const btn = document.getElementById('btnSubmitSlip');
    const btnText = document.getElementById('btnSubmitSlipText');
    btn.disabled = true;
    btnText.textContent = 'กำลังอัปโหลดสลิปและยืนยันยอด...';

    const formData = new FormData(this);

    try {
        const res = await fetch('<?= ROOT_URL ?>/api/upload_payment_slip.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success || data.status) {
            await Swal.fire({
                icon: 'success',
                title: 'แจ้งชำระเงินเรียบร้อย!',
                text: 'ระบบได้รับสลิปโอนเงินและปรับสถานะเป็น "ชำระแล้ว / เตรียมจัดส่ง" เรียบร้อยแล้ว',
                timer: 1600,
                showConfirmButton: false
            });
            window.location.href = data.track_url || '<?= ROOT_URL ?>/track/<?= urlencode($trackKey) ?>?paid=1';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'อัปโหลดไม่สำเร็จ',
                text: data.message || 'กรุณาลองใหม่อีกครั้ง',
                confirmButtonColor: '#0F392B'
            });
            btn.disabled = false;
            btnText.textContent = 'ยืนยันการแจ้งโอนเงิน';
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
            text: 'กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่อีกครั้ง',
            confirmButtonColor: '#0F392B'
        });
        btn.disabled = false;
        btnText.textContent = 'ยืนยันการแจ้งโอนเงิน';
    }
});
</script>
</body>
</html>
