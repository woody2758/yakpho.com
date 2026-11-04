<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — Member Profile & Orders (Zero-DB View Layer)
 * ============================================================
 * Pure Presentation Template per md/AGENT.md rules:
 * - ZERO database queries in View (All data from $PROFILE_DATA)
 * - ZERO inline styles (All classes defined in assets/css/yakpho.css)
 * - SweetAlert2 only for interactive alerts
 * - Button double-submit lock with spinner
 */

$currentLang   = $SITE['lang'] ?? 'th';
$contactInfo   = $SITE['contact'] ?? [];
$prof          = $PROFILE_DATA ?? [];

$userId        = (int)($prof['user_id'] ?? 0);
$userName      = (string)($prof['user_name'] ?? 'ลูกค้าทั่วไป (Guest)');
$userTel       = (string)($prof['user_tel'] ?? '');
$userEmail     = (string)($prof['user_email'] ?? '');
$emailVerified = (bool)($prof['email_verified'] ?? false);
$memberTier    = (string)($prof['member_tier'] ?? 'general');
$tierInfo      = $prof['tier_info'] ?? ['badge' => '🌿', 'short_name' => 'General'];
$mStampPoints  = (int)($prof['m_stamp_points'] ?? 0);
$lineUserId    = (string)($prof['line_user_id'] ?? '');
$lineName      = (string)($prof['line_name'] ?? '');
$linePic       = (string)($prof['line_pic'] ?? '');
$affiliateCode = (string)($prof['affiliate_code'] ?? '');
$affiliateData = $prof['affiliate_data'] ?? null;
$referralUrl   = (string)($prof['referral_url'] ?? '');
$shippingAddress = $prof['shipping_address'] ?? ['name' => '', 'mobile' => '', 'detail' => '', 'postcode' => ''];
$orderCounts     = $prof['order_counts'] ?? ['to_pay' => 0, 'to_ship' => 0, 'to_receive' => 0, 'completed' => 0];
$userOrders      = $prof['user_orders'] ?? [];
$selectedStatus  = (string)($prof['selected_status'] ?? 'all');
$flashSuccess  = $prof['flash_success'] ?? null;
$flashError    = $prof['flash_error'] ?? null;

$baseRoute     = trim((string)($base ?? 'profile'), '/');
$isOrdersPage  = ($baseRoute === 'orders');

$filteredOrders = array_filter($userOrders, function($o) use ($selectedStatus) {
    $st = (int)($o['orders_status'] ?? 0);
    if ($selectedStatus === 'to_pay') {
        return in_array($st, [1, 2], true);
    } elseif ($selectedStatus === 'to_ship') {
        return in_array($st, [3, 4, 10], true);
    } elseif ($selectedStatus === 'to_receive') {
        return in_array($st, [5, 11], true);
    } elseif ($selectedStatus === 'completed') {
        return $st === 6;
    }
    return true; // 'all'
});

$statusTitles = [
    'all'        => 'รายการคำสั่งซื้อทั้งหมด',
    'to_pay'     => 'คำสั่งซื้อที่ต้องชำระเงิน',
    'to_ship'    => 'คำสั่งซื้อที่ต้องจัดส่ง (กำลังเตรียมสินค้า)',
    'to_receive' => 'คำสั่งซื้อที่ต้องได้รับ (อยู่ระหว่างจัดส่ง)',
    'completed'  => 'คำสั่งซื้อที่สำเร็จแล้ว',
];
$currentSectionTitle = $statusTitles[$selectedStatus] ?? 'รายการคำสั่งซื้อ';

$renderOrderStatusGrid = function(bool $isOrdersView = false) use ($currentLang, $selectedStatus, $orderCounts, $userOrders) {
    $linkPrefix = URL_PATH . '/' . $currentLang . '/orders';
?>
    <div class="order-status-card">
        <div class="order-status-card-header">
            <span class="order-status-title">สถานะคำสั่งซื้อ</span>
            <a href="<?= $linkPrefix ?>?status=all" class="order-status-all-link <?= ($isOrdersView && $selectedStatus === 'all') ? 'is-active' : '' ?>">
                ดูคำสั่งซื้อทั้งหมด (<?= count($userOrders) ?>) &gt;
            </a>
        </div>
        <div class="order-status-grid">
            <a href="<?= $linkPrefix ?>?status=to_pay" class="order-status-item <?= ($isOrdersView && $selectedStatus === 'to_pay') ? 'is-active' : '' ?>">
                <div class="order-status-icon-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                    <?php if (($orderCounts['to_pay'] ?? 0) > 0): ?>
                    <span class="order-status-badge badge-active"><?= $orderCounts['to_pay'] > 99 ? '99+' : (int)$orderCounts['to_pay'] ?></span>
                    <?php endif; ?>
                </div>
                <span class="order-status-label">ที่ต้องชำระ</span>
            </a>

            <a href="<?= $linkPrefix ?>?status=to_ship" class="order-status-item <?= ($isOrdersView && $selectedStatus === 'to_ship') ? 'is-active' : '' ?>">
                <div class="order-status-icon-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                    <?php if (($orderCounts['to_ship'] ?? 0) > 0): ?>
                    <span class="order-status-badge badge-active"><?= $orderCounts['to_ship'] > 99 ? '99+' : (int)$orderCounts['to_ship'] ?></span>
                    <?php endif; ?>
                </div>
                <span class="order-status-label">ที่ต้องจัดส่ง</span>
            </a>

            <a href="<?= $linkPrefix ?>?status=to_receive" class="order-status-item <?= ($isOrdersView && $selectedStatus === 'to_receive') ? 'is-active' : '' ?>">
                <div class="order-status-icon-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="13" x="1" y="6" rx="2"></rect><circle cx="5" cy="18" r="2"></circle><circle cx="13" cy="18" r="2"></circle><polyline points="17 9 20 9 23 13 23 18 19 18"></polyline></svg>
                    <?php if (($orderCounts['to_receive'] ?? 0) > 0): ?>
                    <span class="order-status-badge badge-active"><?= $orderCounts['to_receive'] > 99 ? '99+' : (int)$orderCounts['to_receive'] ?></span>
                    <?php endif; ?>
                </div>
                <span class="order-status-label">ที่ต้องได้รับ</span>
            </a>

            <a href="<?= $linkPrefix ?>?status=completed" class="order-status-item <?= ($isOrdersView && $selectedStatus === 'completed') ? 'is-active' : '' ?>">
                <div class="order-status-icon-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?php if (($orderCounts['completed'] ?? 0) > 0): ?>
                    <span class="order-status-badge badge-active"><?= $orderCounts['completed'] > 99 ? '99+' : (int)$orderCounts['completed'] ?></span>
                    <?php endif; ?>
                </div>
                <span class="order-status-label">สำเร็จ</span>
            </a>
        </div>
    </div>
<?php
};
?>

<div class="yp-container profile-container">

    <?php if ($flashSuccess): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: <?= json_encode($flashSuccess) ?>,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    });
    </script>
    <?php endif; ?>

    <?php if ($flashError): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: <?= json_encode($flashError) ?>,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
        }
    });
    </script>
    <?php endif; ?>

    <?php if ($isOrdersPage): ?>
    <!-- ============================================================
         DEDICATED ORDERS & TRACKING VIEW (/th/orders)
         Fits screen immediately without bulky profile cards per md/AGENT.md
         ============================================================ -->
    <div class="orders-page-layout">
        <!-- 1. 4-Box Order Status Grid -->
        <?php $renderOrderStatusGrid(true); ?>

        <!-- 2. Dynamic Orders List Section -->
        <div class="order-list-container" id="orderListSection">
            <div class="order-list-header">
                <span class="order-list-title">
                    📦 <?= htmlspecialchars($currentSectionTitle) ?>
                    <span class="text-muted fw-normal small">(<?= count($filteredOrders) ?>)</span>
                </span>
                <?php if ($selectedStatus !== 'all'): ?>
                <a href="<?= URL_PATH ?>/<?= $currentLang ?>/orders?status=all" class="small text-primary text-decoration-none">
                    ดูทั้งหมด &gt;
                </a>
                <?php endif; ?>
            </div>

            <?php if (empty($filteredOrders)): ?>
            <div class="order-empty-wrap">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-muted"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                <div class="order-empty-title">ไม่มีรายการคำสั่งซื้อในหมวดนี้</div>
                <div class="order-empty-desc">คุณสามารถเลือกดูหมวดอื่น หรือเลือกช้อปสินค้าเพื่อสะสมแต้ม M-Stamp</div>
                <div class="d-flex gap-2 justify-content-center flex-wrap mt-3">
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="profile-btn-sm-primary">
                        ช้อปสินค้าทันที
                    </a>
                    <?php if ($userId <= 0): ?>
                    <button type="button" onclick="openManualLoginModal()" class="profile-btn-sm-outline">
                        เข้าสู่ระบบสมาชิก
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($filteredOrders as $ord): ?>
            <?php
            $stVal = (int)($ord['orders_status'] ?? 0);
            $stsClass = 'order-status-pill';
            if (in_array($stVal, [1, 2], true)) $stsClass .= ' status-pending';
            elseif (in_array($stVal, [3, 4, 5, 10, 11], true)) $stsClass .= ' status-shipping';
            elseif ($stVal === 6) $stsClass .= ' status-completed';
            elseif (in_array($stVal, [7, 8, 9], true)) $stsClass .= ' status-cancelled';

            $orderItems = $ord['items'] ?? [];
            $itemCount = count($orderItems);
            $sAddr = $ord['shipping_addr'] ?? null;
            $rName = !empty($sAddr['addr_name']) ? $sAddr['addr_name'] : ($userName !== 'ลูกค้าทั่วไป (Guest)' ? $userName : 'ลูกค้าผู้มีอุปการคุณ');
            $rTel = !empty($sAddr['addr_mobile']) ? $sAddr['addr_mobile'] : ($userTel ?: '-');
            $rAddr = trim(($sAddr['addr_detail'] ?? '') . ' ' . ($sAddr['province_name'] ?? '') . ' ' . ($sAddr['addr_postcode'] ?? ''));
            if (empty($rAddr) && !empty($shippingAddress['detail'])) {
                $rAddr = trim($shippingAddress['detail'] . ' ' . ($shippingAddress['postcode'] ?? ''));
            }
            ?>
            <div class="order-item-card order-expandable-card" id="order-card-<?= (int)$ord['orders_id'] ?>">
                <!-- Clickable Header: Summary Bill (บิลสรุป) -->
                <div class="order-card-header" onclick="toggleOrderBill(<?= (int)$ord['orders_id'] ?>)" role="button" tabindex="0">
                    <div class="order-header-left">
                        <div class="order-no-wrap">
                            <span class="order-no-label">คำสั่งซื้อ</span>
                            <span class="order-no-value">#<?= htmlspecialchars((string)$ord['orders_no']) ?></span>
                        </div>
                        <div class="order-date-wrap">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <span><?= date('d/m/Y H:i น.', strtotime($ord['orders_date'])) ?></span>
                        </div>
                    </div>
                    <div class="order-header-right">
                        <span class="<?= $stsClass ?>"><?= htmlspecialchars((string)($ord['orsts_detail'] ?? 'รอดำเนินการ')) ?></span>
                    </div>
                </div>

                <!-- Summary Row (คลิกที่รายการ คลี่รายละเอียด) -->
                <div class="order-card-summary-bar" onclick="toggleOrderBill(<?= (int)$ord['orders_id'] ?>)" role="button" tabindex="0">
                    <div class="order-summary-counts">
                        <span class="order-items-badge"><?= $itemCount ?> รายการ</span>
                        <span class="order-summary-dot">•</span>
                        <span class="order-grandtotal-highlight">฿<?= number_format((float)($ord['orders_grandtotal'] ?? 0), 2) ?></span>
                    </div>
                    <div class="order-accordion-indicator">
                        <span class="accordion-hint-text" id="toggle-hint-<?= (int)$ord['orders_id'] ?>">ดูรายละเอียด</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="accordion-chevron-icon" id="toggle-icon-<?= (int)$ord['orders_id'] ?>"><path d="m6 9 6 6 6-6"></path></svg>
                    </div>
                </div>

                <!-- Collapsible Details Body (คลี่รายละเอียดให้ดูทั้งหมด) -->
                <div class="order-card-details-drawer is-collapsed" id="order-drawer-<?= (int)$ord['orders_id'] ?>">
                    <div class="order-drawer-inner">
                        <!-- 1. Order Items List -->
                        <div class="order-drawer-section">
                            <div class="order-drawer-section-title">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path></svg>
                                <span>รายการสินค้าในคำสั่งซื้อ</span>
                            </div>
                            <div class="order-items-list-box">
                                <?php foreach ($orderItems as $it): ?>
                                <?php
                                $thumb = !empty($it['product_picture']) ? (URL_PATH . '/uploads/products/' . htmlspecialchars($it['product_picture'])) : (URL_PATH . '/catalog/assets/img/YakPhoAromaLogo.webp');
                                $pPrice = (float)($it['product_price'] ?? 0);
                                $pQty = (int)($it['ordetail_qty'] ?? 1);
                                $pTotal = (float)($it['ordetail_total'] ?? ($pPrice * $pQty));
                                ?>
                                <div class="order-item-row">
                                    <img src="<?= $thumb ?>" alt="Product" width="52" height="52" class="order-item-img" onerror="this.onerror=null; this.src='<?= URL_PATH ?>/catalog/assets/img/YakPhoAromaLogo.webp';">
                                    <div class="order-item-info">
                                        <div class="order-item-name"><?= htmlspecialchars((string)($it['product_name'] ?? 'สินค้า')) ?></div>
                                        <div class="order-item-sub">
                                            <?php if (!empty($it['product_code'])): ?>
                                            <span>รหัส: <?= htmlspecialchars($it['product_code']) ?></span>
                                            <span class="mx-1">•</span>
                                            <?php endif; ?>
                                            <span>฿<?= number_format($pPrice, 2) ?> × <?= $pQty ?></span>
                                        </div>
                                    </div>
                                    <div class="order-item-price-col">
                                        ฿<?= number_format($pTotal, 2) ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 2. Financial Summary Breakdown -->
                        <div class="order-drawer-section">
                            <div class="order-drawer-section-title">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                                <span>สรุปยอดเงินใบเสร็จ</span>
                            </div>
                            <div class="order-cost-breakdown-card">
                                <div class="order-cost-row">
                                    <span>รวมค่าสินค้า (Subtotal)</span>
                                    <span>฿<?= number_format((float)($ord['orders_total'] ?? 0), 2) ?></span>
                                </div>
                                <div class="order-cost-row">
                                    <span>ค่าจัดส่งพัสดุ</span>
                                    <span><?= (float)($ord['orders_delicost'] ?? 0) > 0 ? ('฿' . number_format((float)$ord['orders_delicost'], 2)) : '<span class="text-success fw-bold">ฟรี</span>' ?></span>
                                </div>
                                <?php if ((float)($ord['orders_discount'] ?? 0) > 0): ?>
                                <div class="order-cost-row text-danger">
                                    <span>ส่วนลดพิเศษ</span>
                                    <span>-฿<?= number_format((float)$ord['orders_discount'], 2) ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="order-cost-total-row">
                                    <span>ยอดชำระสุทธิ</span>
                                    <span class="order-cost-total-val">฿<?= number_format((float)($ord['orders_grandtotal'] ?? 0), 2) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Delivery & Recipient Details -->
                        <div class="order-drawer-section">
                            <div class="order-drawer-section-title">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span>ที่อยู่จัดส่งและขนส่ง</span>
                            </div>
                            <div class="order-delivery-box">
                                <div class="order-delivery-item">
                                    <span class="order-delivery-label">ผู้รับ:</span>
                                    <span class="order-delivery-value"><?= htmlspecialchars($rName) ?> (<?= htmlspecialchars($rTel) ?>)</span>
                                </div>
                                <div class="order-delivery-item">
                                    <span class="order-delivery-label">ที่อยู่:</span>
                                    <span class="order-delivery-value"><?= !empty($rAddr) ? htmlspecialchars($rAddr) : 'บันทึกในระบบขนส่ง' ?></span>
                                </div>
                                <div class="order-delivery-item">
                                    <span class="order-delivery-label">การชำระเงิน:</span>
                                    <span class="order-delivery-value"><?= htmlspecialchars((string)($ord['paycat_nshort'] ?: ($ord['paycat_name'] ?: 'โอนเงิน'))) ?></span>
                                </div>
                                <?php if (!empty($ord['orders_tracking'])): ?>
                                <div class="order-delivery-item order-tracking-item">
                                    <span class="order-delivery-label">เลขพัสดุ:</span>
                                    <div class="order-tracking-action-wrap">
                                        <span class="order-tracking-code"><?= htmlspecialchars($ord['orders_tracking']) ?></span>
                                        <button type="button" class="order-btn-copy" onclick="copyTrackingCode('<?= htmlspecialchars($ord['orders_tracking']) ?>', this)">คัดลอก</button>
                                        <?php if (!empty($ord['transcat_link'])): ?>
                                        <a href="<?= htmlspecialchars(str_replace('xxx', $ord['orders_tracking'], $ord['transcat_link'])) ?>" target="_blank" rel="noopener" class="order-btn-tracklink">ติดตามขนส่ง ↗</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- 4. Action Buttons -->
                            <div class="order-card-actions-bar">
                                <?php
                                $trackKey = !empty($ord['track_token']) ? $ord['track_token'] : (string)$ord['orders_no'];
                                $ordIsCod = ((int)($ord['paycat_id'] ?? 1) === 2) || ((float)($ord['orders_cod'] ?? 0) > 0) || ($stVal === 4);
                                $ordHasSlip = !empty($ord['orders_slip']) && strtoupper((string)$ord['orders_slip']) !== 'NULL';
                                if (!$ordIsCod && in_array($stVal, [1, 2], true) && !$ordHasSlip && (float)($ord['orders_grandtotal'] ?? 0) > 0):
                                ?>
                                <a href="<?= URL_PATH ?>/pay/<?= urlencode($trackKey) ?>" class="profile-btn-sm-primary">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="5" height="5" x="3" y="3" rx="1"></rect><rect width="5" height="5" x="16" y="3" rx="1"></rect><rect width="5" height="5" x="3" y="16" rx="1"></rect><path d="M21 16h-3a2 2 0 0 0-2 2v3"></path><path d="M21 21v.01"></path><path d="M12 7v3a2 2 0 0 1-2 2H7"></path><path d="M3 12h.01"></path><path d="M12 3h.01"></path><path d="M12 16v.01"></path><path d="M16 12h1"></path><path d="M21 12v.01"></path><path d="M12 21v-1"></path></svg>
                                    สแกน QR ชำระเงิน / แนบสลิป
                                </a>
                                <?php endif; ?>
                                <a href="<?= URL_PATH ?>/receipt/<?= urlencode($trackKey) ?>" target="_blank" rel="noopener" class="profile-btn-sm-outline">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    ใบเสร็จรับเงิน
                                </a>
                                <a href="<?= URL_PATH ?>/track/<?= urlencode($trackKey) ?>" target="_blank" rel="noopener" class="profile-btn-sm-primary">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                                    ดูสถานะคำสั่งซื้อ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="profile-orders-grid">
        <!-- Left Column -->
        <div class="profile-orders-col">
            <!-- 1. Header Profile Card with Tier Badge -->
            <div class="profile-header-card">
                <div class="profile-avatar-wrap">
                    <?php if (!empty($linePic)): ?>
                    <img src="<?= htmlspecialchars($linePic) ?>" alt="Avatar" class="profile-avatar-img">
                    <?php else: ?>
                    <img src="<?= URL_PATH ?>/catalog/assets/img/YakPhoAromaLogo.webp" alt="Avatar" class="profile-avatar-logo">
                    <?php endif; ?>
                    <div>
                        <div class="profile-user-title">
                            <span><?= htmlspecialchars($userName) ?></span>
                            <span class="profile-user-badge">
                                <?= $tierInfo['badge'] ?> <?= $tierInfo['short_name'] ?>
                            </span>
                        </div>
                        <div class="profile-user-id">
                            <?= $userId > 0 ? "C" . $userId : "GUEST MEMBER" ?>
                            <?php if (!empty($lineName)): ?>
                            • LINE: <?= htmlspecialchars($lineName) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. M-Stamp Points Card -->
            <div class="profile-points-card">
                <div class="profile-points-header">
                    <div class="profile-points-left">
                        <div class="profile-points-title">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="profile-mono-icon"><circle cx="12" cy="12" r="9"></circle><path d="M14.8 9A2 2 0 0 0 13 8h-2a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4h-2a2 2 0 0 1-1.8-1"></path><path d="M12 6v2m0 8v2"></path></svg>
                            ยอดแต้มสะสม M-Stamp
                        </div>
                        <div class="profile-points-val">
                            <?= number_format($mStampPoints) ?> <span class="profile-points-unit">แต้ม</span>
                        </div>
                    </div>
                    <div class="profile-points-right">
                        <span class="profile-points-badge">
                            ใช้เป็นส่วนลด ฿<?= number_format($mStampPoints) ?> บาท
                        </span>
                        <div class="profile-points-sub">1 แต้ม = 1 บาทที่ Checkout</div>
                    </div>
                </div>
                <div class="profile-points-footer">
                    <span>🌿 ทุกยอดซื้อ 100 บาท = รับ 1 M-Stamp</span>
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="profile-points-shop-link">ช้อปสินค้า &gt;</a>
                </div>
            </div>

            <!-- 3. LINE Account Integration Status -->
            <div class="profile-service-card">
                <?php if (!empty($lineUserId)): ?>
                <div class="profile-service-wrap">
                    <div class="profile-service-left">
                        <div class="profile-service-icon profile-icon-line">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        </div>
                        <div>
                            <div class="profile-service-title">เชื่อมต่อบัญชี LINE เรียบร้อย</div>
                            <div class="profile-service-desc">รับแจ้งเตือนเลขพัสดุและแต้ม M-Stamp เข้า LINE</div>
                        </div>
                    </div>
                    <span class="profile-status-active">
                        Active ✅
                    </span>
                </div>
                <?php else: ?>
                <div class="profile-service-wrap">
                    <div class="profile-service-left">
                        <div class="profile-service-icon profile-icon-line">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        </div>
                        <div>
                            <div class="profile-service-title">ผูกบัญชี LINE เพื่อรับแจ้งเตือนสถานะพัสดุ (+20 M-Stamp)</div>
                            <div class="profile-service-desc">รับแจ้งเตือนเลขพัสดุและแต้ม M-Stamp เข้า LINE ทันที</div>
                        </div>
                    </div>
                    <a href="<?= URL_PATH ?>/api/line_auth.php?action=login" class="profile-btn-line">
                        ผูกบัญชี LINE
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- 4. Email Binding (Resend) -->
            <div class="profile-service-card">
                <?php if ($emailVerified && !empty($userEmail)): ?>
                <div class="profile-service-wrap">
                    <div class="profile-service-left">
                        <div class="profile-service-icon profile-icon-email">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                        </div>
                        <div>
                            <div class="profile-service-title"><?= htmlspecialchars($userEmail) ?></div>
                            <div class="profile-service-desc">รับใบเสร็จภาษีดิจิทัลและข้อมูลคำสั่งซื้อแล้ว</div>
                        </div>
                    </div>
                    <span class="profile-status-verified">
                        ยืนยันแล้ว ✅
                    </span>
                </div>
                <?php else: ?>
                <div>
                    <div class="profile-service-left">
                        <div class="profile-service-icon profile-icon-gold">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                        </div>
                        <div>
                            <div class="profile-service-title">ผูกอีเมลเพื่อรับใบเสร็จดิจิทัล (+20 M-Stamp)</div>
                            <div class="profile-service-desc">รับหลักฐานการชำระเงินและสิทธิพิเศษทางอีเมล</div>
                        </div>
                    </div>
                    <form onsubmit="handleBindEmail(event)" class="profile-form-row">
                        <input type="email" id="bindEmailInput" value="<?= htmlspecialchars($userEmail) ?>" placeholder="กรอกอีเมลของคุณ..." required class="profile-input-field">
                        <button type="submit" id="btnBindEmail" class="profile-btn-action">
                            <span class="spinner-snake d-none" id="bindEmailSpinner"></span>
                            <span id="bindEmailText">ส่งลิงก์ยืนยัน</span>
                        </button>
                    </form>
                    <div id="bindEmailMsg" class="d-none mt-2 small"></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- 5. Therapist Partner Section (Affiliate) -->
            <?php if ($memberTier === 'therapist_partner'): ?>
            <div class="profile-partner-card">
                <div class="profile-partner-header">
                    <div class="d-flex align-items-center gap-2">
                        <span>⭐</span>
                        <div>
                            <div class="fw-bold fs-6">แดชบอร์ด Yak Pho Partner</div>
                            <div class="small text-warning">หมอนวด Affiliate รหัส: <?= htmlspecialchars($affiliateCode) ?></div>
                        </div>
                    </div>
                    <span class="profile-partner-badge">
                        คอมมิชชัน 10-15%
                    </span>
                </div>

                <div class="profile-partner-box">
                    <div class="small text-white-50 mb-1">ลิงก์แนะนำประจำตัวของคุณ (ลูกค้าคลิกสั่ง รับคอมมิชชันทันที):</div>
                    <div class="d-flex gap-2">
                        <input type="text" value="<?= htmlspecialchars($referralUrl) ?>" readonly id="affLinkInput" class="profile-partner-link-input">
                        <button type="button" onclick="copyReferralLink()" class="profile-btn-gold">
                            คัดลอก
                        </button>
                    </div>
                </div>

                <div class="profile-partner-stats">
                    <span>ยอดขายแนะนำ: <strong><?= number_format($affiliateData['total_shops'] ?? 0) ?> ออเดอร์</strong></span>
                    <span>คอมมิชชันสะสม: <strong class="text-warning">฿<?= number_format($affiliateData['total_commission'] ?? 0, 2) ?> บาท</strong></span>
                </div>
            </div>
            <?php else: ?>
            <!-- Card to invite joining Yak Pho Partner -->
            <div class="profile-service-card">
                <div class="profile-service-wrap">
                    <div class="profile-service-left">
                        <div class="profile-service-icon profile-icon-mono">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                        <div>
                            <div class="profile-service-title">สมัครเป็น Yak Pho Partner (หมอนวด Affiliate)</div>
                            <div class="profile-service-desc">รับค่าคอมมิชชัน 10-15% ไม่ต้องสต็อกสินค้า ทางเราจัดส่งให้</div>
                        </div>
                    </div>
                    <button type="button" onclick="applyTherapistPartner(this)" class="profile-btn-action">
                        <span class="spinner-snake d-none" id="partnerSpinner"></span>
                        <span id="partnerText">สมัครใน 1 คลิก</span>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- 6. 4-Box Order Status Grid with Database Badges (Links to dedicated /orders) -->
            <?php $renderOrderStatusGrid(false); ?>
        </div>

<!-- Right Column -->
        <div class="profile-orders-col">
            <!-- 8. Section: การตั้งค่า -->
            <div class="setting-group-card">
                <div class="setting-group-title">การตั้งค่า</div>

                <!-- ตั้งค่าที่อยู่จัดส่ง (อ้างอิงจากฐานข้อมูล addr & user) -->
                <div class="setting-menu-item" onclick="openAddressModal()" role="button" tabindex="0">
                    <div class="setting-item-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <div>
                            <div class="setting-item-title-wrap">ตั้งค่าที่อยู่จัดส่ง</div>
                            <div class="setting-item-subtitle" id="displayShippingSub">
                                <?= !empty($shippingAddress['detail']) ? htmlspecialchars(mb_strimwidth($shippingAddress['detail'], 0, 32, '...')) : 'ยังไม่ได้ระบุที่อยู่จัดส่ง' ?>
                            </div>
                        </div>
                    </div>
                    <span class="setting-arrow">›</span>
                </div>

                <div class="setting-menu-item" onclick="window.toggleTheme();" role="button" tabindex="0">
                    <div class="setting-item-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                        <span>ธีมแสดงผล (สว่าง / Emerald Dark)</span>
                    </div>
                    <span class="setting-arrow">›</span>
                </div>
            </div>

            <!-- 9. Section: นโยบายและเงื่อนไข -->
            <div class="setting-group-card">
                <div class="setting-group-title">นโยบายและเงื่อนไข</div>

                <a href="<?= URL_PATH ?>/<?= $currentLang ?>/about" class="setting-menu-item">
                    <div class="setting-item-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>ข้อกำหนดและเงื่อนไขการใช้บริการ</span>
                    </div>
                    <span class="setting-arrow">›</span>
                </a>

                <a href="<?= URL_PATH ?>/<?= $currentLang ?>/about" class="setting-menu-item">
                    <div class="setting-item-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="m9 12 2 2 4-4"></path></svg>
                        <span>นโยบายความเป็นส่วนตัว (PDPA) &amp; มาตรฐานการบริการ</span>
                    </div>
                    <span class="setting-arrow">›</span>
                </a>
            </div>

            <!-- 10. Section: ติดต่อเรา -->
            <div class="setting-group-card">
                <div class="setting-group-title">ติดต่อเรา</div>

                <a href="https://lin.ee/vyUtLZ6" target="_blank" rel="noopener" class="setting-menu-item text-decoration-none">
                    <div class="setting-item-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        <span>ติดต่อทีมงานแอดมิน</span>
                    </div>
                    <span class="setting-arrow">›</span>
                </a>

                <div class="setting-menu-item">
                    <div class="setting-item-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                        <span><?= htmlspecialchars($contactInfo['email'] ?? 'orders@yakpho.com') ?></span>
                    </div>
                </div>
            </div>

            <?php if ($userId > 0): ?>
            <!-- Logout Button with Anti-Double Click & Spinner per md/AGENT.md -->
            <button type="button" class="logout-action-btn" id="btnLogoutAction" onclick="confirmResetSession(this)">
                <span class="spinner-border spinner-border-sm d-none me-1" id="logoutSpinner" role="status" aria-hidden="true"></span>
                <svg width="18" height="18" id="logoutSvg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span id="logoutBtnText">ออกจากระบบ</span>
            </button>
            <?php else: ?>
            <!-- Guest Member: Call to Action to Login via LINE -->
            <a href="<?= URL_PATH ?>/api/line_auth.php?action=login" class="profile-guest-login-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                <span>เข้าสู่ระบบสมาชิกผ่าน LINE</span>
            </a>

            <!-- Manual Username / Password Login Button -->
            <button type="button" onclick="openManualLoginModal()" class="profile-manual-login-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                <span>มีบัญชีอยู่แล้ว? เข้าสู่ระบบด้วยชื่อผู้ใช้ / รหัสผ่าน</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// SweetAlert2 + Double-Submit Spinner Implementation per md/AGENT.md

function copyReferralLink() {
    const input = document.getElementById('affLinkInput');
    if (!input) return;
    navigator.clipboard.writeText(input.value);
    Swal.fire({
        icon: 'success',
        title: 'คัดลอกลิงก์สำเร็จ',
        text: 'นำลิงก์นี้ไปส่งให้ลูกค้า เมื่อมีคนสั่งซื้อ คุณจะได้รับค่าคอมมิชชันทันที',
        timer: 2000,
        showConfirmButton: false
    });
}

async function handleBindEmail(e) {
    e.preventDefault();
    const currentUserId = <?= (int)$userId ?>;
    if (currentUserId <= 0) {
        Swal.fire({
            icon: 'info',
            title: 'เข้าสู่ระบบเพื่อสะสมแต้ม',
            text: 'กรุณาเข้าสู่ระบบผ่าน LINE ก่อนทำการผูกอีเมล เพื่อให้ระบบสามารถบันทึกแต้มสะสม 20 M-Stamp เข้าสู่บัญชีของคุณได้ทันที',
            showCancelButton: true,
            confirmButtonColor: '#00B900',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'เข้าสู่ระบบด้วย LINE (คลิกเดียว)',
            cancelButtonText: 'ไว้ภายหลัง'
        }).then((res) => {
            if (res.isConfirmed) {
                window.location.href = '<?= URL_PATH ?>/api/line_auth.php?action=login';
            }
        });
        return;
    }

    const btn = document.getElementById('btnBindEmail');
    const spinner = document.getElementById('bindEmailSpinner');
    const btnText = document.getElementById('bindEmailText');
    const email = document.getElementById('bindEmailInput').value.trim();

    if (btn.disabled) return;
    btn.disabled = true;
    spinner?.classList.remove('d-none');
    if (btnText) btnText.textContent = 'กำลังส่ง...';

    try {
        const formData = new FormData();
        formData.append('action', 'bind_email');
        formData.append('email', email);

        const res = await fetch('<?= URL_PATH ?>/api/member.php', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.status) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: json.message
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'ไม่สำเร็จ',
                text: json.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'ข้อผิดพลาด',
            text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง'
        });
    } finally {
        btn.disabled = false;
        spinner?.classList.add('d-none');
        if (btnText) btnText.textContent = 'ส่งลิงก์ยืนยัน';
    }
}

async function applyTherapistPartner(btn) {
    if (btn.disabled) return;

    const currentUserId = <?= (int)$userId ?>;
    if (currentUserId <= 0) {
        Swal.fire({
            icon: 'info',
            title: 'เข้าสู่ระบบเพื่อสมัคร Partner',
            text: 'กรุณาเข้าสู่ระบบผ่าน LINE ก่อน เพื่อเปิดใช้งานระบบหมอนวด Affiliate และรับรหัสแนะนำประจำตัว',
            showCancelButton: true,
            confirmButtonColor: '#00B900',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'เข้าสู่ระบบด้วย LINE (คลิกเดียว)',
            cancelButtonText: 'ยกเลิก'
        }).then((res) => {
            if (res.isConfirmed) {
                window.location.href = '<?= URL_PATH ?>/api/line_auth.php?action=login';
            }
        });
        return;
    }

    const confirmRes = await Swal.fire({
        title: 'สมัครเป็น Yak Pho Partner?',
        text: 'ระบบจะสร้างรหัส Affiliate และเปิดการรับค่าคอมมิชชัน 10-15% ให้คุณทันที',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0c3b2e',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันการสมัคร',
        cancelButtonText: 'ยกเลิก'
    });

    if (!confirmRes.isConfirmed) return;

    btn.disabled = true;
    const spinner = document.getElementById('partnerSpinner');
    const btnText = document.getElementById('partnerText');
    spinner?.classList.remove('d-none');
    if (btnText) btnText.textContent = 'กำลังสมัคร...';

    try {
        const res = await fetch('<?= URL_PATH ?>/api/member.php?action=apply_affiliate');
        const json = await res.json();

        if (json.status) {
            await Swal.fire({
                icon: 'success',
                title: 'ยินดีต้อนรับ!',
                text: json.message,
                timer: 2000,
                showConfirmButton: false
            });
            window.location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: json.message
            });
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
        });
    } finally {
        btn.disabled = false;
        spinner?.classList.add('d-none');
        if (btnText) btnText.textContent = 'สมัครใน 1 คลิก';
    }
}

async function openAddressModal() {
    const currentUserId = <?= (int)$userId ?>;
    if (currentUserId <= 0) {
        Swal.fire({
            icon: 'info',
            title: 'เข้าสู่ระบบสมาชิก',
            text: 'กรุณาเข้าสู่ระบบผ่าน LINE เพื่อตั้งค่าที่อยู่จัดส่งของคุณ',
            showCancelButton: true,
            confirmButtonColor: '#00B900',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'เข้าสู่ระบบ LINE',
            cancelButtonText: 'ปิด'
        }).then(r => { if (r.isConfirmed) window.location.href = '<?= URL_PATH ?>/api/line_auth.php?action=login'; });
        return;
    }

    const currName = <?= json_encode($shippingAddress['name'] ?? '') ?>;
    const currMobile = <?= json_encode($shippingAddress['mobile'] ?? '') ?>;
    const currDetail = <?= json_encode($shippingAddress['detail'] ?? '') ?>;
    const currPostcode = <?= json_encode($shippingAddress['postcode'] ?? '') ?>;

    const { value: formValues } = await Swal.fire({
        title: 'ตั้งค่าที่อยู่จัดส่ง',
        html: `
            <div class="text-start mb-2">
                <label class="form-label small fw-bold mb-1">ชื่อ-นามสกุล ผู้รับ:</label>
                <input id="swalAddrName" class="swal2-input m-0 mb-3 w-100 fs-6" value="${currName}" placeholder="ชื่อ-นามสกุล ผู้รับ">
                
                <label class="form-label small fw-bold mb-1">เบอร์โทรศัพท์ติดต่อ:</label>
                <input id="swalAddrMobile" class="swal2-input m-0 mb-3 w-100 fs-6" value="${currMobile}" placeholder="08xxxxxxxx">
                
                <label class="form-label small fw-bold mb-1">ที่อยู่จัดส่ง (บ้านเลขที่, ถนน, ตำบล, อำเภอ, จังหวัด):</label>
                <textarea id="swalAddrDetail" class="swal2-textarea m-0 mb-3 w-100 fs-6" rows="3" placeholder="ระบุที่อยู่จัดส่งโดยละเอียด...">${currDetail}</textarea>
                
                <label class="form-label small fw-bold mb-1">รหัสไปรษณีย์:</label>
                <input id="swalAddrPostcode" class="swal2-input m-0 w-100 fs-6" value="${currPostcode}" placeholder="10xxx">
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'บันทึกที่อยู่จัดส่ง',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#0B3B35',
        cancelButtonColor: '#6c757d',
        preConfirm: () => {
            const name = document.getElementById('swalAddrName').value.trim();
            const mobile = document.getElementById('swalAddrMobile').value.trim();
            const detail = document.getElementById('swalAddrDetail').value.trim();
            const postcode = document.getElementById('swalAddrPostcode').value.trim();
            if (!name || !mobile || !detail) {
                Swal.showValidationMessage('กรุณากรอกชื่อผู้รับ เบอร์โทรศัพท์ และที่อยู่จัดส่งให้ครบถ้วน');
                return false;
            }
            return { addr_name: name, addr_mobile: mobile, addr_detail: detail, addr_postcode: postcode };
        }
    });

    if (formValues) {
        Swal.fire({
            title: 'กำลังบันทึก...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        try {
            const res = await fetch('<?= URL_PATH ?>/api/member.php?action=save_address', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formValues)
            });
            const json = await res.json();
            if (json.status) {
                await Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: json.message,
                    timer: 1800,
                    showConfirmButton: false
                });
                const sub = document.getElementById('displayShippingSub');
                if (sub) {
                    sub.textContent = formValues.addr_detail.substring(0, 32) + (formValues.addr_detail.length > 32 ? '...' : '');
                }
            } else {
                Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: json.message || 'บันทึกไม่สำเร็จ' });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้' });
        }
    }
}

async function lookupOrderTrack(btn) {
    const q = document.getElementById('trackQueryInput').value.trim();
    const box = document.getElementById('trackResultBox');

    if (!q) {
        Swal.fire({
            icon: 'warning',
            title: 'กรุณากรอกข้อมูล',
            text: 'กรุณากรอกเลขคำสั่งซื้อ หรือเบอร์โทรศัพท์ที่ใช้สั่งซื้อ'
        });
        return;
    }

    if (btn.disabled) return;
    btn.disabled = true;
    const spinner = document.getElementById('trackSpinner');
    const btnText = document.getElementById('trackBtnText');
    spinner?.classList.remove('d-none');
    if (btnText) btnText.textContent = 'กำลังค้นหา...';

    box.classList.remove('d-none');
    box.innerHTML = '<span class="text-muted">กำลังค้นหาข้อมูลพัสดุ...</span>';

    try {
        const res = await fetch('<?= URL_PATH ?>/api/track_lookup.php?q=' + encodeURIComponent(q));
        const json = await res.json();

        if (json.status && json.data) {
            box.innerHTML = `
                <div class="fw-bold text-success mb-1">พบข้อมูลคำสั่งซื้อ #${json.data.orders_no}</div>
                <div class="d-flex gap-2 mt-2">
                    <a href="${json.data.track_url}" class="btn btn-sm btn-success py-1 px-3 fw-bold text-decoration-none">ดูสถานะพัสดุ</a>
                    <a href="${json.data.receipt_url}" class="btn btn-sm btn-outline-secondary py-1 px-3 fw-bold text-decoration-none">ใบเสร็จ</a>
                </div>
            `;
        } else {
            box.innerHTML = `<span class="text-danger">${json.message || 'ไม่พบข้อมูลคำสั่งซื้อ'}</span>`;
        }
    } catch (e) {
        box.innerHTML = '<span class="text-danger">เกิดข้อผิดพลาดในการเชื่อมต่อ</span>';
    } finally {
        btn.disabled = false;
        spinner?.classList.add('d-none');
        if (btnText) btnText.textContent = 'ค้นหา';
    }
}

async function confirmResetSession(btn) {
    if (!btn) btn = document.getElementById('btnLogoutAction');
    const spinner = document.getElementById('logoutSpinner');
    const svg = document.getElementById('logoutSvg');
    const btnText = document.getElementById('logoutBtnText');

    let isConfirmed = false;
    if (typeof Swal !== 'undefined') {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const res = await Swal.fire({
            title: 'ยืนยันการออกจากระบบ?',
            text: 'คุณต้องการออกจากระบบบนอุปกรณ์นี้ใช่หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ออกจากระบบ',
            cancelButtonText: 'ยกเลิก',
            customClass: {
                confirmButton: 'swal2-confirm-danger'
            }
        });
        isConfirmed = res.isConfirmed;
    } else {
        isConfirmed = confirm('คุณต้องการออกจากระบบบนอุปกรณ์นี้ใช่หรือไม่?');
    }

    if (isConfirmed) {
        if (btn) btn.disabled = true;
        spinner?.classList.remove('d-none');
        svg?.classList.add('d-none');
        if (btnText) btnText.textContent = 'กำลังออกจากระบบ...';

        try {
            await fetch('<?= URL_PATH ?>/api/member.php?action=logout', { method: 'POST' });
            await fetch('<?= URL_PATH ?>/api/cart.php?action=clear');
            await reloadProfileContainer();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'ออกจากระบบเรียบร้อยแล้ว',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } catch (e) {
            console.error('Logout error:', e);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถออกจากระบบได้ กรุณาลองใหม่อีกครั้ง'
                });
            }
        } finally {
            if (btn) btn.disabled = false;
            spinner?.classList.add('d-none');
            svg?.classList.remove('d-none');
            if (btnText) btnText.textContent = 'ออกจากระบบ';
        }
    }
}

function togglePasswordVisibility(inputId, btnEl) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btnEl.innerHTML = isPass 
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
}


async function reloadProfileContainer() {
    try {
        const res = await fetch(window.location.href, { cache: 'no-store' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const html = await res.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newCont = doc.querySelector('.profile-container');
        const curCont = document.querySelector('.profile-container');
        if (newCont && curCont) {
            newCont.classList.add('profile-container-updated');
            curCont.replaceWith(newCont);
            if (window.lucide) {
                lucide.createIcons();
            }
            return true;
        }
    } catch (e) {
        console.error('AJAX container update error:', e);
    }
    return false;
}

async function openManualLoginModal() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark' || (document.body && document.body.getAttribute('data-theme') === 'dark');
    const themeBg = isDark ? '#0C2823' : '#FFFFFF';
    const themeFg = isDark ? '#F3F4F6' : '#1F2937';

    const { value: loginResult } = await Swal.fire({
        title: `<div class="member-login-header"><span class="member-login-header-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></span><span>เข้าสู่ระบบสมาชิก</span></div>`,
        html: `
            <div class="member-login-form">
                <div class="member-login-field">
                    <label for="swalLoginUser" class="member-login-label">ชื่อผู้ใช้ / เบอร์โทรศัพท์ / อีเมล <span class="member-login-required">*</span></label>
                    <div class="member-login-input-wrap">
                        <input id="swalLoginUser" class="member-login-input" placeholder="กรอกชื่อผู้ใช้ หรือเบอร์โทร" autocomplete="username">
                    </div>
                </div>
                <div class="member-login-field">
                    <label for="swalLoginPass" class="member-login-label">รหัสผ่าน <span class="member-login-required">*</span></label>
                    <div class="member-login-input-wrap">
                        <input id="swalLoginPass" type="password" class="member-login-input member-login-input-has-btn" placeholder="กรอกรหัสผ่านของคุณ" autocomplete="current-password">
                        <button type="button" class="member-login-toggle-btn" onclick="togglePasswordVisibility('swalLoginPass', this)">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
                <label class="member-login-remember-row" for="swalRememberMe">
                    <input type="checkbox" id="swalRememberMe" class="member-login-checkbox" checked>
                    <span class="member-login-remember-text">จดจำการเข้าสู่ระบบ 1 ปี</span>
                </label>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'เข้าสู่ระบบ',
        cancelButtonText: 'ยกเลิก',
        buttonsStyling: false,
        customClass: {
            popup: 'member-login-popup',
            actions: 'member-login-actions',
            confirmButton: 'member-login-btn-submit',
            cancelButton: 'member-login-btn-cancel',
            validationMessage: 'member-login-val-msg'
        },
        showLoaderOnConfirm: true,
        didOpen: () => {
            const uInput = document.getElementById('swalLoginUser');
            if (uInput) uInput.focus();
        },
        preConfirm: async () => {
            const u = document.getElementById('swalLoginUser')?.value.trim();
            const p = document.getElementById('swalLoginPass')?.value.trim();
            const rem = document.getElementById('swalRememberMe')?.checked;

            if (!u || !p) {
                Swal.showValidationMessage('กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน');
                return false;
            }

            try {
                const res = await fetch('<?= URL_PATH ?>/api/member.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: u, password: p, remember_me: rem })
                });
                const json = await res.json();
                if (!json.status) {
                    Swal.showValidationMessage(json.message || 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
                    return false;
                }
                return json;
            } catch (err) {
                Swal.showValidationMessage('ไม่สามารถเชื่อมต่อระบบได้ กรุณาลองใหม่อีกครั้ง');
                return false;
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (loginResult && loginResult.status) {
        const welcomeName = loginResult.data?.name || 'สมาชิก Yak Pho';

        // Seamless AJAX update of the container (No full page reload!)
        await reloadProfileContainer();

        await Swal.fire({
            icon: 'success',
            title: `ยินดีต้อนรับคุณ ${welcomeName}`,
            text: 'เข้าสู่ระบบสำเร็จ ข้อมูลของคุณได้รับการอัปเดตเรียบร้อยแล้ว',
            showConfirmButton: false,
            timer: 1600,
            timerProgressBar: true,
            background: themeBg,
            color: themeFg
        });
    }
}

// Automatically guide user if LINE Login credentials are not configured yet
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('line_unconfigured')) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'LINE Login บน Localhost',
                html: `
                    <div class="text-start small text-secondary">
                        <p class="mb-2">ระบบ LINE Login ยังไม่ได้ระบุ <strong>Channel ID</strong> ในฐานข้อมูล</p>
                        <p class="mb-1"><strong>คุณสามารถเลือกดำเนินการได้ทันที:</strong></p>
                        <ul class="mb-0 ps-3">
                            <li>เลือก <strong>"เข้าสู่ระบบทดสอบ (Demo)"</strong> เพื่อทดลองใช้งานระบบสมาชิก, สะสมแต้ม M-Stamp และแดชบอร์ด Affiliate ทันทีบน Localhost</li>
                            <li>หรือเลือก <strong>"ไปหน้าตั้งค่า Admin"</strong> เพื่อระบุ Channel ID จาก LINE Developers</li>
                        </ul>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#00B900',
                denyButtonColor: '#0B3B35',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'เข้าสู่ระบบทดสอบ (Demo)',
                denyButtonText: 'ไปหน้าตั้งค่า Admin',
                cancelButtonText: 'ปิด'
            }).then((res) => {
                if (res.isConfirmed) {
                    window.location.href = '<?= URL_PATH ?>/api/line_auth.php?action=dev_login';
                } else if (res.isDenied) {
                    window.open('<?= URL_PATH ?>/admin/settings/line_broadcast.php', '_blank');
                }
            });
        }
    }
});

function toggleOrderBill(orderId) {
    const card = document.getElementById('order-card-' + orderId);
    if (!card) return;
    const isOpen = card.classList.toggle('is-open');
    const hint = document.getElementById('toggle-hint-' + orderId);
    if (hint) { hint.textContent = isOpen ? 'ย่อรายละเอียด' : 'ดูรายละเอียด'; }
}

function copyTrackingCode(code, btn) {
    if (!code) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(() => {
            const old = btn.textContent;
            btn.textContent = 'คัดลอกแล้ว!';
            setTimeout(() => { btn.textContent = old; }, 2000);
        }).catch(() => {
            fallbackCopy(code, btn);
        });
    } else {
        fallbackCopy(code, btn);
    }
}

function fallbackCopy(code, btn) {
    const inp = document.createElement('input');
    inp.value = code;
    document.body.appendChild(inp);
    inp.select();
    try {
        document.execCommand('copy');
        btn.textContent = 'คัดลอกแล้ว!';
        setTimeout(() => { btn.textContent = 'คัดลอก'; }, 2000);
    } catch(e) {}
    document.body.removeChild(inp);
}
</script>