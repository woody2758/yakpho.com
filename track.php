Replaced step5_discount_breakdown (CRLF) Replaced step4_pay_btn (CRLF) Replaced step3_home_btn (CRLF) Replaced step2_home_css (CRLF) Replaced step1_cod_and_stamps (CRLF) <?php
require_once __DIR__ . "/admin/includes/config.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = trim($_GET['v'] ?? $_GET['no'] ?? '');
if (empty($token)) {
    die("<div style='font-family:sans-serif; text-align:center; padding:60px 20px;'>
            <h2 style='color:#0B3B35;'>ไม่พบลิงก์ติดตามพัสดุ</h2>
            <p style='color:#666;'>กรุณาระบุเลขคำสั่งซื้อหรือ Tracking Token ที่ถูกต้อง</p>
         </div>");
}

$lang = $_GET['lang'] ?? 'th';

// 1. ดึงข้อมูลออเดอร์ (รองรับทั้ง track_token และ orders_no)
$sql = "SELECT o.*, u.user_name, u.user_lastname, u.user_mobile as user_tel,
               os.orsts_id, os.orsts_detail as orsts_name, os.orsts_color, os.orsts_code, os.orsts_index,
               st.orsts_name as orsts_name_en,
               os.orsts_detail as status_th,
               t.transcat_name, t.transcat_nshort, t.transcat_link,
               a.addr_name, a.addr_mobile, a.addr_detail, a.addr_detail2, a.addr_postcode,
               p.name_th as province_name
        FROM orders o
        LEFT JOIN user u ON o.user_id = u.user_id
        LEFT JOIN orsts os ON o.orders_status = os.orsts_id
        LEFT JOIN orsts_translations st ON os.orsts_id = st.orsts_id AND st.lang_code = 'en'
        LEFT JOIN transcat t ON o.transcat_id = t.transcat_id
        LEFT JOIN addr a ON o.addr_id = a.addr_id
        LEFT JOIN provinces p ON a.provinces_id = p.provinces_id
        WHERE o.track_token = ? OR o.orders_no = ?
        LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$token, $token]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    ?>
    <!DOCTYPE html>
    <html lang="<?= htmlspecialchars($lang) ?>">
    <head>
    <!-- ✅ Google Analytics 4 (GA4) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-H8X1HT3GLQ"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-H8X1HT3GLQ');
    </script>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Not Found | Yak Pho Aroma</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://unpkg.com/lucide@latest"></script>
        <style>
            body { font-family: 'Prompt', sans-serif; background: #f4f6f8; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .card-404 { max-width: 450px; background: #fff; border-radius: 24px; padding: 40px 24px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.06); }
        </style>
    </head>
    <body>
        <div class="card-404">
            <div class="mb-3 text-muted">
                <i data-lucide="package-search" style="width: 64px; height: 64px; color: #0B3B35;"></i>
            </div>
            <h4 class="fw-bold text-dark">ไม่พบข้อมูลคำสั่งซื้อ</h4>
            <p class="text-muted small mb-4">ไม่พบข้อมูลคำสั่งซื้อหมายเลข <strong><?= htmlspecialchars($token) ?></strong> ในระบบ กรุณาตรวจสอบเลขที่ออเดอร์อีกครั้ง</p>
            <a href="<?= ROOT_URL ?>" class="btn btn-dark rounded-pill px-4">กลับหน้าหลัก</a>
        </div>
        <script>lucide.createIcons();</script>
    </body>
    </html>
    <?php
    exit;
}

// ล็อกเซสชันประวัติ
if (isset($_GET['lock']) && $_GET['lock'] == 1 && !empty($order['user_id'])) {
    unset($_SESSION['track_verified_' . $order['user_id']]);
}

// ตรวจสอบการยืนยันเบอร์โทรศัพท์เพื่อปลดล็อกประวัติ
$error_msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_phone'])) {
    $inputPhone = trim($_POST['verify_phone']);
    $cleanInput = preg_replace('/[^0-9]/', '', $inputPhone);
    $cleanDbPhone = preg_replace('/[^0-9]/', '', $order['user_tel'] ?: ($order['addr_mobile'] ?? ''));

    if (!empty($cleanInput) && !empty($cleanDbPhone) && 
        ($cleanInput === $cleanDbPhone || substr($cleanDbPhone, -4) === $cleanInput)) {
        $_SESSION['track_verified_' . $order['user_id']] = true;
        $redirectUrl = ROOT_URL . "/track/" . (!empty($order['track_token']) ? $order['track_token'] : $order['orders_no']) . "?lang=" . $lang . "&unlocked=1#historySection";
        header("Location: " . $redirectUrl);
        exit;
    } else {
        $error_msg = ($lang == 'en') ? "Incorrect phone number. Please try again." : "เบอร์โทรศัพท์ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
    }
}

// ประวัติคำสั่งซื้อ
$historyOrders = [];
$isVerified = !empty($order['user_id']) && isset($_SESSION['track_verified_' . $order['user_id']]) && $_SESSION['track_verified_' . $order['user_id']] === true;
if ($isVerified && !empty($order['user_id'])) {
    $histStmt = $db->prepare("
        SELECT o.orders_id, o.orders_no, o.orders_grandtotal, o.orders_date, o.track_token,
               os.orsts_detail as status_th, os.orsts_color,
               st.orsts_name as status_en,
               (
                   SELECT GROUP_CONCAT(COALESCE(NULLIF(p.product_picture, ''), p.product_img, '') SEPARATOR ',')
                   FROM ordetail od
                   JOIN product p ON od.product_id = p.product_id
                   WHERE od.orders_id = o.orders_id
               ) as product_images
        FROM orders o
        LEFT JOIN orsts os ON o.orders_status = os.orsts_id
        LEFT JOIN orsts_translations st ON os.orsts_id = st.orsts_id AND st.lang_code = ?
        WHERE o.user_id = ? AND o.orders_del = 0
        ORDER BY o.orders_id DESC
    ");
    $histStmt->execute([$lang, $order['user_id']]);
    $historyOrders = $histStmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. ดึงรายการสินค้า
$stmt = $db->prepare("
    SELECT d.*, 
           COALESCE(pt.product_name, p.product_name, '') as product_name,
           COALESCE(NULLIF(p.product_picture, ''), p.product_img, '') as product_picture,
           p.product_code
    FROM ordetail d 
    LEFT JOIN product p ON d.product_id = p.product_id 
    LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ? 
    WHERE d.orders_id = ?
");
$stmt->execute([$lang, $order['orders_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. ฟังก์ชันซ่อนข้อมูลส่วนตัว
function maskInfo($str, $type = 'name') {
    if (empty($str)) return '';
    if ($type == 'tel') {
        $str = str_replace(['-', ' ', '.'], '', $str);
        if (strlen($str) >= 10) {
            return substr($str, 0, 3) . "-" . substr($str, 3, 3) . "-****";
        }
        return substr($str, 0, 3) . "****";
    }
    if ($type == 'lastname') {
        return mb_substr($str, 0, 1, 'UTF-8') . "****";
    }
    return mb_substr($str, 0, 1, 'UTF-8') . "**** " . mb_substr($str, -1, 1, 'UTF-8');
}

$statusId = (int)($order['orders_status'] ?? 0);
$statusName = ($lang == 'en' && !empty($order['orsts_name_en'])) ? $order['orsts_name_en'] : ($order['orsts_name'] ?: $order['status_th']);

// เช็คสถานะยกเลิกหรือตีคืน
$isCancelled = in_array($statusId, [7, 8, 9]);
$isCodOrder = ((int)($order['paycat_id'] ?? 1) === 2) || ((float)($order['orders_cod'] ?? 0) > 0) || ($statusId === 4);

// Query E-Stamp points redeemed and earned for this order (from member_points_log)
$redeemedStamps = 0;
$earnedStamps = 0;
try {
    $stmtPts = $db->prepare("SELECT action_type, points_change FROM member_points_log WHERE reference_id = ?");
    $stmtPts->execute([(int)$order['orders_id']]);
    while ($pRow = $stmtPts->fetch(PDO::FETCH_ASSOC)) {
        if ($pRow['action_type'] === 'order_discount' && (int)$pRow['points_change'] < 0) {
            $redeemedStamps += abs((int)$pRow['points_change']);
        } elseif ($pRow['action_type'] === 'order_reward' && (int)$pRow['points_change'] > 0) {
            $earnedStamps += (int)$pRow['points_change'];
        }
    }
} catch (Throwable $e) {}
if ($redeemedStamps === 0 && !empty($order['orders_msg']) && preg_match('/E-Stamp:\s*(\d+)\s*แต้ม/u', (string)$order['orders_msg'], $mSt)) {
    $redeemedStamps = (int)$mSt[1];
}

// 4. แมปสถานะแอดมิน (10-11 สถานะ) สู่ 5 ขั้นตอนสำคัญสำหรับลูกค้า (Customer Milestones)
$currentStep = 1;
if ($statusId == 2) {
    $currentStep = $isCodOrder ? 3 : 2;
} elseif ($statusId == 1) {
    $currentStep = $isCodOrder ? 3 : 2;
} elseif (in_array($statusId, [3, 4, 10])) {
    $currentStep = 3;
} elseif (in_array($statusId, [5, 11])) {
    $currentStep = 4;
} elseif ($statusId == 6) {
    $currentStep = 5;
}

if (!empty($order['orders_slip']) && strtoupper((string)$order['orders_slip']) !== 'NULL' && $currentStep < 3 && !$isCancelled) {
    $currentStep = 3;
}
if (!empty($order['packing_image']) && $currentStep < 3 && !$isCancelled) {
    $currentStep = 3;
}
if (!empty($order['orders_tracking']) && $currentStep < 4 && !$isCancelled) {
    $currentStep = 4;
}

$step2Title = ($statusId == 2 ? ($lang == 'en' ? 'Awaiting Pay' : 'รอชำระเงิน') : ($lang == 'en' ? 'Confirmed' : 'ยืนยันชำระเงิน'));
$step2Desc  = ($statusId == 2 ? ($lang == 'en' ? 'Pending slip' : 'รอตรวจสลิป') : ($lang == 'en' ? 'Verified' : 'ชำระเงินแล้ว'));
if ($isCodOrder) {
    $step2Title = ($lang == 'en' ? 'COD' : 'เก็บปลายทาง (COD)');
    $step2Desc  = ($lang == 'en' ? 'Pay on delivery' : 'ชำระเมื่อรับของ');
}

$milestones = [
    1 => [
        'title' => ($lang == 'en' ? 'Order Placed' : 'สั่งซื้อสำเร็จ'),
        'desc'  => ($lang == 'en' ? 'Received' : 'รับออเดอร์แล้ว'),
        'icon'  => 'clipboard-check'
    ],
    2 => [
        'title' => $step2Title,
        'desc'  => $step2Desc,
        'icon'  => $isCodOrder ? 'banknote' : 'credit-card'
    ],
    3 => [
        'title' => ($lang == 'en' ? 'Packing' : 'กำลังแพ็ค'),
        'desc'  => (!empty($order['packing_image']) ? ($lang == 'en' ? 'Packed' : 'แพ็คเรียบร้อย') : ($lang == 'en' ? 'Preparing' : 'จัดเตรียมของ')),
        'icon'  => 'package'
    ],
    4 => [
        'title' => ($lang == 'en' ? 'In Transit' : 'อยู่ระหว่างจัดส่ง'),
        'desc'  => (!empty($order['orders_tracking']) ? ($lang == 'en' ? 'With Courier' : 'ส่งต่อขนส่งแล้ว') : ($lang == 'en' ? 'Dispatched' : 'เตรียมนำส่ง')),
        'icon'  => 'truck'
    ],
    5 => [
        'title' => ($lang == 'en' ? 'Delivered' : 'จัดส่งสำเร็จ'),
        'desc'  => ($statusId == 6 ? ($lang == 'en' ? 'Completed' : 'รับสินค้าแล้ว') : ($lang == 'en' ? 'Pending delivery' : 'ปลายทาง')),
        'icon'  => 'check-circle-2'
    ],
];

// คำนวณความยาวเส้น Progress Bar (%)
$progressPercent = $isCancelled ? 0 : (($currentStep - 1) / 4) * 100;

// URL รูปภาพแพ็คสินค้า
$packingImgUrl = '';
if (!empty($order['packing_image'])) {
    if (strpos($order['packing_image'], 'uploads/packing/') !== false) {
        $packingImgUrl = ROOT_URL . '/' . ltrim($order['packing_image'], '/');
    } else {
        $packingImgUrl = ROOT_URL . '/uploads/packing/' . $order['packing_image'];
    }
}

// Carrier Name
$carrierName = !empty($order['transcat_nshort']) ? $order['transcat_nshort'] : (!empty($order['transcat_name']) ? $order['transcat_name'] : 'ผู้ให้บริการขนส่ง');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <!-- ✅ Google Analytics 4 (GA4) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-H8X1HT3GLQ"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-H8X1HT3GLQ');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>สถานะคำสั่งซื้อ #<?= htmlspecialchars($order['orders_no']) ?> | Yak Pho Aroma</title>
    <meta property="og:title" content="ติดตามพัสดุ ออเดอร์ #<?= htmlspecialchars($order['orders_no']) ?>">
    <meta property="og:description" content="สถานะ: <?= htmlspecialchars($statusName) ?>">
    <meta property="og:url" content="<?= ROOT_URL ?>/track/<?= !empty($order['track_token']) ? $order['track_token'] : $order['orders_no'] ?>">
    <?php if (!empty($packingImgUrl)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($packingImgUrl) ?>">
    <?php else: ?>
    <meta property="og:image" content="<?= ROOT_URL ?>/catalog/assets/img/YakPhoAromaLogo.webp">
    <?php endif; ?>
    <meta property="og:type" content="website">

    <!-- Bootstrap 5 & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --primary: #0B3B35;
            --primary-dark: #072622;
            --primary-light: #145348;
            --accent-emerald: #10b981;
            --gold: #D1A55A;
            --bg-page: #f1f5f9;
            --card-border: rgba(11, 59, 53, 0.08);
        }

        body {
            font-family: 'Prompt', 'Sarabun', sans-serif;
            background-color: var(--bg-page);
            color: #1e293b;
            margin: 0;
            padding: 0 0 60px 0;
            -webkit-font-smoothing: antialiased;
        }

        .track-wrapper {
            max-width: 580px;
            margin: 0 auto;
        }

        /* Header Gradient Card (ชอบสไตล์นี้) */
        .header-card {
            background: linear-gradient(145deg, #072622 0%, #0B3B35 55%, #145348 100%);
            color: #ffffff;
            padding: 38px 20px 32px;
            border-radius: 0 0 32px 32px;
            text-align: center;
            position: relative;
            box-shadow: 0 12px 30px rgba(11, 59, 53, 0.25);
        }

        .header-home-profile-btn {
            position: absolute;
            top: 16px;
            left: 16px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.26);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
            z-index: 20;
        }
        .header-home-profile-btn:hover {
            background: rgba(255, 255, 255, 0.28);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .header-logo {
            height: 52px;
            object-fit: contain;
            margin-bottom: 12px;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
        }

        .header-title {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .header-orderno {
            font-size: 0.95rem;
            opacity: 0.88;
            letter-spacing: 0.8px;
            font-family: monospace, sans-serif;
            margin-bottom: 14px;
        }

        .current-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 8px 20px;
            border-radius: 99px;
            font-weight: 600;
            font-size: 0.92rem;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }

        /* Main Content Section */
        .content-container {
            padding: 0 16px;
            margin-top: -12px;
            position: relative;
            z-index: 10;
        }

        .section-card {
            background: #ffffff;
            border-radius: 22px;
            padding: 22px;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
            border: 1px solid var(--card-border);
        }

        /* Progress Stepper */
        .tracker-wrapper {
            position: relative;
            padding: 10px 0 6px;
        }

        .tracker-track {
            position: absolute;
            top: 26px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: #e2e8f0;
            border-radius: 99px;
            z-index: 1;
        }

        .tracker-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #0B3B35);
            border-radius: 99px;
            transition: width 0.4s ease;
        }

        .tracker-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .tracker-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 20%;
            text-align: center;
        }

        .step-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #94a3b8;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
            margin-bottom: 8px;
        }

        .tracker-step.passed .step-circle {
            background: #10b981;
            color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .tracker-step.active .step-circle {
            background: #0B3B35;
            color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.22), 0 4px 12px rgba(11, 59, 53, 0.3);
            transform: scale(1.08);
        }

        .step-title {
            font-size: 0.72rem;
            font-weight: 500;
            color: #94a3b8;
            line-height: 1.25;
            white-space: normal;
        }

        .tracker-step.passed .step-title {
            color: #0f172a;
            font-weight: 600;
        }

        .tracker-step.active .step-title {
            color: #0B3B35;
            font-weight: 700;
        }

        /* Tracking Number Box */
        .tracking-box-modern {
            background: #ecfdf5;
            border: 1.5px dashed #10b981;
            border-radius: 18px;
            padding: 18px;
            text-align: center;
            margin-bottom: 16px;
        }

        .tracking-num-text {
            font-family: monospace, sans-serif;
            font-size: 1.45rem;
            font-weight: 700;
            color: #065f46;
            letter-spacing: 1.5px;
            word-break: break-all;
        }

        .copy-btn {
            background: #ffffff;
            border: 1px solid #10b981;
            color: #065f46;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 99px;
            transition: all 0.2s;
        }
        .copy-btn:hover {
            background: #10b981;
            color: #ffffff;
        }

        /* Packing Image Box */
        .packing-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.03);
        }

        .packing-card-header {
            padding: 12px 18px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .packing-image-container {
            position: relative;
            background: #000;
            cursor: pointer;
            overflow: hidden;
        }

        .packing-img {
            width: 100%;
            height: auto;
            max-height: 480px;
            object-fit: cover;
            display: block;
            transition: transform 0.3s;
        }

        .packing-img:hover {
            transform: scale(1.015);
        }

        .packing-overlay-badge {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 0.75rem;
            backdrop-filter: blur(4px);
        }

        /* Order Items */
        .item-thumb {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            object-fit: contain;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            padding: 4px;
            flex-shrink: 0;
        }

        .item-title {
            font-size: 0.88rem;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.35;
        }

        .item-meta {
            font-size: 0.78rem;
            color: #64748b;
        }

        .item-price {
            font-size: 0.92rem;
            font-weight: 600;
            color: #0B3B35;
            text-align: right;
            white-space: nowrap;
        }

        /* Total breakdown */
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.88rem;
            color: #475569;
            margin-bottom: 8px;
        }

        .grandtotal-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1.5px dashed #cbd5e1;
        }

        .grandtotal-label {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary);
        }

        .grandtotal-val {
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--primary);
        }

        /* Contact support button */
        .btn-line-support {
            background-color: #06c755;
            color: #ffffff;
            font-weight: 600;
            border-radius: 99px;
            padding: 12px 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            width: 100%;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(6, 199, 85, 0.25);
            border: none;
        }
        .btn-line-support:hover {
            background-color: #05b04b;
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Modal image preview */
        .modal-packing-preview img {
            max-width: 100%;
            border-radius: 12px;
        }
    </style>
</head>
<body>

<div class="track-wrapper">

    <!-- Header Section (ชอบสไตล์นี้) -->
    <header class="header-card">
        <a href="<?= ROOT_URL ?>/<?= htmlspecialchars($lang) ?>/profile" class="header-home-profile-btn" title="ไปหน้าโปรไฟล์สมาชิก">
            <i data-lucide="home" style="width: 19px; height: 19px;"></i>
        </a>
        <div>
            <img src="<?= ROOT_URL ?>/catalog/assets/img/YakPhoAromaLogo.webp" 
                 alt="Yak Pho Aroma" 
                 class="header-logo"
                 onerror="this.src='<?= ROOT_URL ?>/uploads/shop/Esther-aroma-logo.png'">
        </div>
        <h1 class="header-title"><?= ($lang == 'en') ? 'Order Tracking' : 'สถานะคำสั่งซื้อ' ?></h1>
        <div class="header-orderno">Order No: #<?= htmlspecialchars($order['orders_no']) ?></div>

        <div class="current-status-pill">
            <i data-lucide="<?= $isCancelled ? 'alert-triangle' : 'package-check' ?>" style="width: 18px; height: 18px;"></i>
            <span><?= htmlspecialchars($statusName) ?></span>
        </div>
    </header>

    <main class="content-container">

        <!-- 1. Progress Tracker Stepper (5 ขั้นตอนสำคัญสำหรับลูกค้า) -->
        <section class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge rounded-pill px-3 py-1 fw-bold" 
                      style="background-color: <?= htmlspecialchars($order['orsts_color'] ?: '#0B3B35') ?>18; color: <?= htmlspecialchars($order['orsts_color'] ?: '#0B3B35') ?>; border: 1px solid <?= htmlspecialchars($order['orsts_color'] ?: '#0B3B35') ?>44; font-size: 0.8rem;">
                    <?= htmlspecialchars($statusName) ?>
                </span>
                <span class="text-muted" style="font-size: 0.75rem;">
                    อัปเดตล่าสุด: <?= date('d M Y H:i', strtotime($order['orders_update'] ?: $order['orders_date'])) ?>
                </span>
            </div>

            <?php if ($isCancelled): ?>
                <!-- กล่องแจ้งเตือนกรณียกเลิก / ตีคืน -->
                <div class="alert alert-danger rounded-4 py-3 px-3 d-flex align-items-center gap-3 mb-0" role="alert">
                    <i data-lucide="x-circle" style="width: 32px; height: 32px; flex-shrink: 0;"></i>
                    <div style="font-size: 0.88rem;">
                        <strong>คำสั่งซื้อนี้ถูกยกเลิกแล้ว</strong><br>
                        <span class="small text-muted">สถานะ: <?= htmlspecialchars($statusName) ?> หากมีข้อสงสัยโปรดติดต่อฝ่ายบริการลูกค้า</span>
                    </div>
                </div>
            <?php else: ?>
                <!-- Stepper 5 ขั้นตอน -->
                <div class="tracker-wrapper">
                    <div class="tracker-track">
                        <div class="tracker-progress-bar" style="width: <?= $progressPercent ?>%;"></div>
                    </div>
                    <div class="tracker-steps">
                        <?php foreach ($milestones as $stepNum => $ms): 
                            $stepClass = '';
                            if ($stepNum < $currentStep) {
                                $stepClass = 'passed';
                            } elseif ($stepNum == $currentStep) {
                                $stepClass = 'active';
                            }
                        ?>
                            <div class="tracker-step <?= $stepClass ?>">
                                <div class="step-circle">
                                    <?php if ($stepClass === 'passed'): ?>
                                        <i data-lucide="check" style="width: 18px; height: 18px;"></i>
                                    <?php else: ?>
                                        <i data-lucide="<?= $ms['icon'] ?>" style="width: 17px; height: 17px;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-title">
                                    <?= htmlspecialchars($ms['title']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php
                $trackKeyForPay = !empty($order['track_token']) ? $order['track_token'] : $order['orders_no'];
                $hasSlipAlready = !empty($order['orders_slip']) && strtoupper((string)$order['orders_slip']) !== 'NULL';
                if (!$isCodOrder && in_array($statusId, [1, 2]) && !$hasSlipAlready && (float)$order['orders_grandtotal'] > 0):
                ?>
                <div class="mt-3 pt-3 border-top text-center">
                    <a href="<?= ROOT_URL ?>/pay/<?= urlencode((string)$trackKeyForPay) ?>" class="btn w-100 rounded-pill py-2 fw-bold d-inline-flex align-items-center justify-content-center gap-2" style="background: linear-gradient(135deg, #003D6B 0%, #0066B2 100%); color: #fff; box-shadow: 0 4px 14px rgba(0, 61, 107, 0.25);">
                        <i data-lucide="qr-code" style="width: 18px; height: 18px;"></i>
                        <span>สแกน QR พร้อมเพย์ / แนบสลิปโอนเงิน (฿<?= number_format((float)$order['orders_grandtotal'], 2) ?>)</span>
                    </a>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- 2. ข้อมูลเลขพัสดุและลิงก์ติดตามขนส่ง (ถ้ามี) -->
        <?php if (!empty($order['orders_tracking'])): ?>
            <?php 
                $trackings = array_filter(array_map('trim', explode(',', $order['orders_tracking'])));
            ?>
            <section class="tracking-box-modern">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2 text-success fw-bold" style="font-size: 0.88rem;">
                    <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                    <span>พัสดุของคุณถูกจัดส่งแล้ว!</span>
                </div>

                <?php foreach ($trackings as $tk): 
                    $tkLink = '#';
                    if (!empty($order['transcat_link'])) {
                        $tkLink = str_replace('xxx', urlencode($tk), $order['transcat_link']);
                        if (strpos($order['transcat_link'], 'xxx') === false) {
                            $tkLink = $order['transcat_link'] . urlencode($tk);
                        }
                    } else {
                        $tkLink = 'https://track.thailandpost.co.th/?track=' . urlencode($tk);
                    }
                ?>
                    <div class="mb-3">
                        <div class="text-muted small mb-1"><?= htmlspecialchars($carrierName) ?></div>
                        <div class="tracking-num-text mb-2"><?= htmlspecialchars($tk) ?></div>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <button type="button" class="copy-btn d-flex align-items-center gap-1" onclick="copyTracking('<?= htmlspecialchars($tk) ?>', this)">
                                <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                                <span>คัดลอกเลขพัสดุ</span>
                            </button>
                            <a href="<?= htmlspecialchars($tkLink) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success rounded-pill px-3 py-1 d-flex align-items-center gap-1" style="font-size: 0.8rem; font-weight: 600;">
                                <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                                <span>เช็คสถานะกับ <?= htmlspecialchars($carrierName) ?></span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <!-- 3. ภาพสินค้าที่จัดเตรียมแล้ว (Packing Image) -->
        <?php if (!empty($packingImgUrl)): ?>
            <section class="packing-card">
                <div class="packing-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="camera" style="width: 16px; height: 16px; color: var(--primary);"></i>
                        <span>ภาพสินค้าที่จัดเตรียมแล้ว</span>
                    </div>
                    <?php if (!empty($order['packed_at'])): ?>
                        <span class="text-muted small" style="font-size: 0.72rem;">
                            แพ็คเมื่อ: <?= date('d/m/Y H:i', strtotime($order['packed_at'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="packing-image-container" onclick="openPackingModal('<?= htmlspecialchars($packingImgUrl) ?>')">
                    <img src="<?= htmlspecialchars($packingImgUrl) ?>" 
                         class="packing-img" 
                         alt="ภาพสินค้าที่จัดเตรียมแล้ว - ออเดอร์ #<?= htmlspecialchars($order['orders_no']) ?>"
                         loading="lazy">
                    <div class="packing-overlay-badge d-flex align-items-center gap-1">
                        <i data-lucide="zoom-in" style="width: 13px; height: 13px;"></i>
                        <span>คลิกเพื่อดูรูปเต็ม</span>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- 4. ข้อมูลการจัดส่งและผู้รับ (Shipping Details) -->
        <section class="section-card">
            <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3" style="font-size: 0.92rem;">
                <i data-lucide="map-pin" style="width: 17px; height: 17px; color: var(--primary);"></i>
                <span>ข้อมูลการจัดส่ง</span>
            </h6>

            <div class="d-flex justify-content-between mb-2" style="font-size: 0.88rem;">
                <span class="text-muted">ผู้รับ:</span>
                <span class="fw-bold text-dark">
                    <?= htmlspecialchars($order['user_name'] ?? ($order['addr_name'] ?? 'ลูกค้า')) ?> 
                    <?= maskInfo($order['user_lastname'] ?? '', 'lastname') ?>
                </span>
            </div>

            <div class="d-flex justify-content-between mb-2" style="font-size: 0.88rem;">
                <span class="text-muted">เบอร์โทรศัพท์:</span>
                <span class="fw-bold text-dark">
                    <?= maskInfo($order['user_tel'] ?: ($order['addr_mobile'] ?? ''), 'tel') ?>
                </span>
            </div>

            <div class="mt-2 p-3 bg-light rounded-3" style="font-size: 0.85rem; border: 1px solid #e2e8f0; line-height: 1.5;">
                <div class="text-muted small mb-1 fw-bold">ที่อยู่จัดส่ง:</div>
                <div class="text-dark">
                    <?php 
                    if (!empty($order['addr_detail'])) {
                        if (!empty($order['addr_name'])) {
                            echo '<span class="fw-bold">' . htmlspecialchars($order['addr_name']) . '</span>';
                            if (!empty($order['addr_mobile'])) {
                                echo ' <span class="text-muted">(' . htmlspecialchars($order['addr_mobile']) . ')</span><br>';
                            } else {
                                echo '<br>';
                            }
                        }
                        echo htmlspecialchars($order['addr_detail']);
                        if (!empty($order['addr_detail2'])) echo ' ' . htmlspecialchars($order['addr_detail2']);
                        if (!empty($order['province_name'])) echo ' ' . htmlspecialchars($order['province_name']);
                        if (!empty($order['addr_postcode'])) echo ' ' . htmlspecialchars($order['addr_postcode']);
                    } else {
                        echo '<span class="text-muted fst-italic">ข้อมูลที่อยู่ถูกบันทึกในระบบขนส่ง</span>';
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- 5. รายการสินค้า (Order Items) -->
        <section class="section-card">
            <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3" style="font-size: 0.92rem;">
                <i data-lucide="shopping-bag" style="width: 17px; height: 17px; color: var(--primary);"></i>
                <span>รายการสินค้า (<?= count($items) ?> รายการ)</span>
            </h6>

            <div class="d-flex flex-column gap-3">
                <?php foreach ($items as $item): ?>
                    <?php
                        $filename = $item['product_picture'] ?? '';
                        $img_url = ROOT_URL . '/catalog/assets/img/YakPhoAromaLogo.webp';
                        if (!empty($filename)) {
                            if (strpos($filename, 'uploads/products/') !== false) {
                                $img_url = ROOT_URL . '/' . ltrim($filename, '/');
                            } else {
                                $img_url = ROOT_URL . '/uploads/products/' . $filename;
                            }
                        }
                        $unitPrice = (float)(!empty($item['unit_price']) ? $item['unit_price'] : ($item['product_price'] ?? 0));
                        $qty = (int)$item['ordetail_qty'];
                        $itemSub = (float)(!empty($item['subtotal']) ? $item['subtotal'] : ($unitPrice * $qty));
                    ?>
                    <div class="d-flex align-items-center gap-3 pb-2 border-bottom">
                        <img src="<?= htmlspecialchars($img_url) ?>" 
                             class="item-thumb" 
                             alt="<?= htmlspecialchars($item['product_name']) ?>" 
                             onerror="this.src='<?= ROOT_URL ?>/catalog/assets/img/YakPhoAromaLogo.webp'">
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="item-title text-truncate"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="item-meta">
                                จำนวน: <?= $qty ?> ชิ้น 
                                <?php if ($unitPrice > 0): ?>
                                    <span class="mx-1">•</span> ฿<?= number_format($unitPrice, 2) ?>/ชิ้น
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="item-price">
                            ฿<?= number_format($itemSub, 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- สรุปยอดเงิน (Financial breakdown) -->
            <div class="mt-4 pt-2">
                <div class="total-row">
                    <span>ยอดรวมค่าสินค้า (Subtotal)</span>
                    <span>฿<?= number_format((float)$order['orders_total'], 2) ?></span>
                </div>
                <div class="total-row">
                    <span>ค่าจัดส่ง (Shipping)</span>
                    <span><?= (float)$order['orders_delicost'] > 0 ? '฿' . number_format((float)$order['orders_delicost'], 2) : '<span class="text-success fw-bold">ฟรี</span>' ?></span>
                </div>
                <?php
                $totalDiscountVal = (float)$order['orders_discount'];
                $otherDiscountVal = max(0.0, $totalDiscountVal - (float)$redeemedStamps);
                ?>
                <?php if ($redeemedStamps > 0): ?>
                    <div class="total-row text-danger">
                        <span>🪙 ใช้แต้มสะสม E-Stamp (<?= number_format($redeemedStamps) ?> แต้ม)</span>
                        <span>-฿<?= number_format((float)$redeemedStamps, 2) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($otherDiscountVal > 0): ?>
                    <div class="total-row text-danger">
                        <span><?= $redeemedStamps > 0 ? 'ส่วนลดเพิ่มเติม / คูปอง (Discount)' : 'ส่วนลด (Discount)' ?></span>
                        <span>-฿<?= number_format($otherDiscountVal, 2) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($order['orders_cod']) && (float)$order['orders_cod'] > 0): ?>
                    <div class="total-row">
                        <span>ค่าบริการเก็บเงินปลายทาง (COD)</span>
                        <span>฿<?= number_format((float)$order['orders_cod'], 2) ?></span>
                    </div>
                <?php endif; ?>

                <div class="grandtotal-row">
                    <span class="grandtotal-label">ยอดสุทธิ (Total Amount)</span>
                    <span class="grandtotal-val">฿<?= number_format((float)$order['orders_grandtotal'], 2) ?></span>
                </div>
                <?php if ($earnedStamps > 0): ?>
                <div class="text-end mt-1" style="font-size: 0.78rem; color: #059669; font-weight: 600;">
                    🌿 ได้รับแต้มสะสมจากออเดอร์นี้ +<?= number_format($earnedStamps) ?> E-Stamp
                </div>
                <?php endif; ?>
            </div>

            <!-- หลักฐานการชำระเงิน (ถ้ามี) -->
            <?php if (!empty($order['orders_slip']) && strtoupper($order['orders_slip']) !== 'NULL' && file_exists(__DIR__ . '/uploads/slips/' . $order['orders_slip'])): ?>
                <div class="mt-4 pt-3 border-top text-center">
                    <div class="text-muted small mb-2 fw-bold d-flex align-items-center justify-content-center gap-1">
                        <i data-lucide="file-check-2" style="width: 15px; height: 15px; color: var(--primary);"></i>
                        <span>หลักฐานการชำระเงิน (Payment Slip)</span>
                    </div>
                    <div class="rounded-3 overflow-hidden border mx-auto" style="max-width: 220px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); cursor: pointer;" onclick="openPackingModal('<?= ROOT_URL ?>/uploads/slips/<?= htmlspecialchars($order['orders_slip']) ?>')">
                        <img src="<?= ROOT_URL ?>/uploads/slips/<?= htmlspecialchars($order['orders_slip']) ?>" class="img-fluid w-100" alt="Slip">
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- 6. ส่วนตรวจสอบประวัติสั่งซื้อย้อนหลัง (Phone Unlock History) -->
        <section id="historySection" class="section-card">
            <?php if ($isVerified): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size: 0.92rem;">
                        <i data-lucide="history" style="width: 17px; height: 17px; color: var(--primary);"></i>
                        <span>ประวัติการสั่งซื้อของคุณ</span>
                    </h6>
                    <a href="<?= ROOT_URL ?>/track/<?= !empty($order['track_token']) ? $order['track_token'] : $order['orders_no'] ?>?lang=<?= $lang ?>&lock=1" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1" style="font-size: 0.75rem;">
                        <i data-lucide="lock" style="width: 12px; height: 12px; vertical-align: middle;"></i>
                        <span>ล็อกประวัติ</span>
                    </a>
                </div>

                <div class="list-group list-group-flush">
                    <?php if (count($historyOrders) <= 1): ?>
                        <div class="py-3 text-center text-muted small">
                            ไม่พบประวัติการสั่งซื้ออื่นในระบบ
                        </div>
                    <?php else: ?>
                        <?php foreach ($historyOrders as $hist): ?>
                            <?php if ($hist['orders_id'] == $order['orders_id']) continue; ?>
                            <div class="list-group-item px-0 py-3 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small text-dark">#<?= htmlspecialchars($hist['orders_no']) ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <?= date('d M Y', strtotime($hist['orders_date'])) ?> 
                                        <span class="mx-1">•</span> 
                                        ฿<?= number_format((float)$hist['orders_grandtotal'], 2) ?>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill px-2 py-1 small" style="background-color: <?= htmlspecialchars($hist['orsts_color'] ?: '#0B3B35') ?>; font-size: 0.72rem;">
                                        <?= htmlspecialchars(($lang == 'en' && !empty($hist['status_en'])) ? $hist['status_en'] : $hist['status_th']) ?>
                                    </span>
                                    <a href="<?= ROOT_URL ?>/track/<?= !empty($hist['track_token']) ? $hist['track_token'] : $hist['orders_no'] ?>?lang=<?= $lang ?>" class="btn btn-sm btn-dark rounded-pill px-3 py-1" style="font-size: 0.75rem;">
                                        ดูบิล
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- แบบฟอร์มขอปลดล็อกประวัติคำสั่งซื้อ -->
                <div class="text-center py-2">
                    <div class="mb-2 mx-auto d-flex align-items-center justify-content-center bg-light rounded-circle" style="width: 48px; height: 48px;">
                        <i data-lucide="lock" class="text-secondary" style="width: 22px; height: 22px;"></i>
                    </div>
                    <div class="fw-bold text-dark mb-1" style="font-size: 0.92rem;">ดูประวัติการสั่งซื้อย้อนหลัง</div>
                    <p class="text-muted mb-3" style="font-size: 0.78rem;">
                        กรอกเบอร์โทรศัพท์ของคุณเพื่อดูประวัติและบิลสั่งซื้อทั้งหมดในระบบ
                    </p>
                    <form method="POST" class="d-flex gap-2 mx-auto" style="max-width: 320px;">
                        <input type="text" 
                               name="verify_phone" 
                               class="form-control rounded-pill px-3 text-center" 
                               style="font-size: 0.85rem;" 
                               placeholder="กรอกเบอร์โทร หรือ 4 ตัวท้าย" 
                               required>
                        <button type="submit" class="btn btn-dark rounded-pill px-3 py-2 flex-shrink-0 fw-bold" style="font-size: 0.8rem;">
                            ปลดล็อก
                        </button>
                    </form>
                    <?php if ($error_msg): ?>
                        <div class="text-danger small mt-2 fw-bold" style="font-size: 0.75rem;"><?= htmlspecialchars($error_msg) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- 7. ฝ่ายบริการลูกค้า (LINE Support) -->
        <div class="text-center mt-4">
            <p class="text-muted mb-2" style="font-size: 0.8rem;">หากท่านมีข้อสงสัย หรือต้องการความช่วยเหลือ ติดต่อเจ้าหน้าที่ได้ทันที</p>
            <a href="https://lin.ee/kGIUwnf2" target="_blank" rel="noopener noreferrer" class="btn-line-support">
                <i data-lucide="message-circle" style="width: 18px; height: 18px;"></i>
                <span>ติดต่อฝ่ายบริการลูกค้าทาง LINE</span>
            </a>
            <div class="mt-4 text-muted small" style="font-size: 0.75rem;">
                © Yak Pho Aroma • บริษัท เอสเธอร์ อโรม่า จำกัด
            </div>
        </div>

    </main>

</div>

<!-- Modal สำหรับดูภาพสินค้า/สลิปแบบขยายเต็มจอ -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 overflow-hidden bg-dark">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3 modal-packing-preview">
                <img id="modalPreviewImg" src="" alt="ภาพขยาย" class="img-fluid rounded-3">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // ฟังก์ชันคัดลอกเลขพัสดุ
    function copyTracking(trackingNumber, btn) {
        if (!trackingNumber) return;
        navigator.clipboard.writeText(trackingNumber).then(() => {
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="check" style="width: 14px; height: 14px;"></i><span>คัดลอกแล้ว!</span>';
            btn.classList.add('btn-success', 'text-white');
            btn.classList.remove('copy-btn');
            if (typeof lucide !== 'undefined') lucide.createIcons();

            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-success', 'text-white');
                btn.classList.add('copy-btn');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 2000);
        }).catch(err => {
            alert('คัดลอก: ' + trackingNumber);
        });
    }

    // ฟังก์ชันเปิด Modal รูปภาพเต็มจอ
    function openPackingModal(imageUrl) {
        if (!imageUrl) return;
        const img = document.getElementById('modalPreviewImg');
        img.src = imageUrl;
        const modal = new bootstrap.Modal(document.getElementById('imageModal'));
        modal.show();
    }
</script>
</body>
</html>
