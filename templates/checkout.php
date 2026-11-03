<?php
$currentLang = $SITE['lang'] ?? 'th';
$provinces = $CHECKOUT_DATA['provinces'] ?? [];
$banks = $CHECKOUT_DATA['banks'] ?? [];
$cart = $CHECKOUT_DATA['cart'] ?? ['items' => [], 'total_qty' => 0, 'total_1kg_qty' => 0, 'wholesale_kg_rate' => 690, 'subtotal' => 0, 'wholesale_savings' => 0, 'shipping_cost' => 0, 'grand_total' => 0];
$memberPoints = (int)($CHECKOUT_DATA['member_points'] ?? 0);
$loggedUser = $CHECKOUT_DATA['logged_user'] ?? null;
$affiliateCookie = (string)($CHECKOUT_DATA['affiliate_cookie'] ?? '');
?>

<div class="yp-container checkout-container">
    <!-- Top Back / Title -->
    <div class="checkout-header-bar">
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="checkout-back-btn" aria-label="Back to Products">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </a>
        <h1 class="checkout-page-title">
            <?= $currentLang === 'th' ? 'ยืนยันคำสั่งซื้อและชำระเงิน' : 'Checkout & Confirm Order' ?>
        </h1>
    </div>

    <?php if (!empty($affiliateCookie)): ?>
    <div class="checkout-affiliate-badge">
        <span>⭐</span>
        <div>
            สั่งซื้อผ่านคำแนะนำของ <strong>Yak Pho Partner (รหัส <?= htmlspecialchars($affiliateCookie) ?>)</strong>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($cart['items'])): ?>
        <div class="checkout-empty-box">
            <svg class="checkout-empty-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            <h2 class="checkout-empty-title">ยังไม่มีสินค้าในตะกร้า</h2>
            <p class="checkout-empty-desc">กรุณาเลือกผลิตภัณฑ์สมุนไพรหรือบาล์มสปาเพื่อดำเนินการสั่งซื้อ</p>
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="checkout-empty-btn">
                เลือกชมสินค้า
            </a>
        </div>
    <?php else: ?>
        <form id="checkoutOrderForm">
            <?php if (!empty($affiliateCookie)): ?>
            <input type="hidden" name="affiliate_code" value="<?= htmlspecialchars($affiliateCookie) ?>">
            <?php endif; ?>

            <!-- 1. Shipping Address Card -->
            <div class="checkout-card">
                <div class="checkout-card-header">
                    <svg class="checkout-card-header-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span>1. ข้อมูลผู้รับและที่อยู่จัดส่งพัสดุ</span>
                </div>

                <!-- Destination Selector: Domestic vs International -->
                <div class="checkout-dest-selector">
                    <label class="checkout-dest-btn active" id="destBtnDomestic">
                        <input type="radio" name="shipping_destination" value="domestic" checked onchange="handleDestinationChange('domestic')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span>จัดส่งในประเทศ (Thailand)</span>
                    </label>
                    <label class="checkout-dest-btn" id="destBtnInternational">
                        <input type="radio" name="shipping_destination" value="international" onchange="handleDestinationChange('international')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        <span>จัดส่งต่างประเทศ (International)</span>
                    </label>
                </div>

                <!-- International Destination Country Field -->
                <div id="internationalFields" class="d-none">
                    <div class="checkout-form-group">
                        <label class="checkout-label">ประเทศปลายทาง (Destination Country) *</label>
                        <input type="text" name="destination_country" id="destCountryInput" placeholder="ระบุประเทศ เช่น สหรัฐอเมริกา (USA), ญี่ปุ่น (Japan), ออสเตรเลีย..." class="checkout-input">
                        <div class="checkout-intl-notice">
                            ✈️ สำหรับการจัดส่งต่างประเทศ ระบบยังไม่คำนวณค่าจัดส่งอัตโนมัติ ทางร้านจะตรวจสอบพื้นที่ปลายทางและติดต่อแจ้งค่าจัดส่งให้ทราบภายหลังครับ
                        </div>
                    </div>
                </div>

                <div class="checkout-grid-2">
                    <div class="checkout-form-group">
                        <label class="checkout-label">ชื่อ - นามสกุล / ชื่อร้านสปา *</label>
                        <input type="text" name="customer_name" required value="<?= htmlspecialchars($loggedUser['user_name'] ?? '') ?>" placeholder="ระบุชื่อผู้รับสินค้า" class="checkout-input">
                    </div>
                    <div class="checkout-form-group">
                        <label class="checkout-label">เบอร์โทรศัพท์มือถือ *</label>
                        <input type="tel" name="customer_phone" required value="<?= htmlspecialchars($loggedUser['user_mobile'] ?? '') ?>" placeholder="08x-xxx-xxxx" class="checkout-input">
                    </div>
                </div>

                <div class="checkout-grid-2">
                    <div class="checkout-form-group">
                        <label class="checkout-label">LINE ID (สำหรับแจ้งสถานะพัสดุ)</label>
                        <input type="text" name="line_id" placeholder="@lineid หรือชื่อไอดี" class="checkout-input">
                    </div>
                    <div class="checkout-form-group">
                        <label class="checkout-label">อีเมล (สำหรับรับใบเสร็จดิจิทัล)</label>
                        <input type="email" name="customer_email" value="<?= htmlspecialchars($loggedUser['user_email'] ?? '') ?>" placeholder="you@example.com" class="checkout-input">
                    </div>
                </div>

                <div class="checkout-form-group">
                    <label class="checkout-label">ที่อยู่จัดส่ง (บ้านเลขที่ ซอย ถนน ตำบล/แขวง อำเภอ/เขต) *</label>
                    <textarea name="address_detail" rows="2" required placeholder="กรอกที่อยู่จัดส่งให้ครบถ้วน..." class="checkout-textarea"><?= htmlspecialchars($loggedUser['user_address'] ?? '') ?></textarea>
                </div>

                <div class="checkout-grid-2">
                    <div class="checkout-form-group" id="provinceGroup">
                        <label class="checkout-label">จังหวัด *</label>
                        <select name="province_id" id="provinceSelect" class="checkout-select">
                            <?php foreach ($provinces as $prov): 
                                $selected = (!empty($loggedUser['user_province']) && (int)$loggedUser['user_province'] === (int)$prov['provinces_id']) ? 'selected' : '';
                            ?>
                                <option value="<?= (int)$prov['provinces_id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($prov['name_th']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="checkout-form-group">
                        <label class="checkout-label">รหัสไปรษณีย์ (ZIP / Postcode) *</label>
                        <input type="text" name="postcode" required value="<?= htmlspecialchars($loggedUser['user_postcode'] ?? '') ?>" placeholder="เช่น 10510" class="checkout-input">
                    </div>
                </div>
            </div>

            <!-- 2. Payment Method Card -->
            <div class="checkout-card">
                <div class="checkout-card-header">
                    <svg class="checkout-card-header-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                    <span>2. เลือกช่องทางชำระเงิน</span>
                </div>

                <label class="checkout-pay-option active" id="payOptionBank">
                    <input type="radio" name="paycat_id" value="1" checked class="checkout-pay-radio" onchange="handlePaymentChange(this)">
                    <div class="checkout-pay-info">
                        <strong class="checkout-pay-title">โอนเงินผ่าน PromptPay QR / บัญชีธนาคาร (แนะนำ)</strong>
                        <p class="checkout-pay-desc">
                            ไม่มีค่าธรรมเนียม แนบสลิปง่าย ตรวจสอบยอดและออกใบเสร็จทันที
                        </p>
                        <?php foreach ($banks as $bItem): ?>
                            <div class="checkout-bank-item">
                                • <?= htmlspecialchars($bItem['bank_name']) ?>: <?= htmlspecialchars($bItem['bank_number']) ?> (<?= htmlspecialchars($bItem['bank_account']) ?>)
                            </div>
                        <?php endforeach; ?>
                    </div>
                </label>

                <label class="checkout-pay-option" id="payOptionCod">
                    <input type="radio" name="paycat_id" value="2" class="checkout-pay-radio" onchange="handlePaymentChange(this)">
                    <div class="checkout-pay-info">
                        <strong class="checkout-pay-title">เก็บเงินปลายทาง (COD +3%)</strong>
                        <p class="checkout-pay-desc">
                            ชำระเงินสดกับพนักงานส่งของเมื่อได้รับพัสดุ (เฉพาะจัดส่งในประเทศ)
                        </p>
                    </div>
                </label>
            </div>

            <!-- 3. Order Summary & Confirm -->
            <div class="checkout-card">
                <div class="checkout-card-header">
                    3. สรุปรายการสั่งซื้อ (<?= (int)$cart['total_qty'] ?> รายการ)
                </div>

                <div class="checkout-cart-list">
                    <?php foreach ($cart['items'] as $cItem): ?>
                        <div class="checkout-cart-item">
                            <div class="checkout-cart-item-left">
                                <img src="<?= htmlspecialchars($cItem['image']) ?>" alt="thumb" class="checkout-cart-thumb">
                                <div>
                                    <div class="checkout-cart-item-name"><?= htmlspecialchars($cItem['product_name']) ?></div>
                                    <div class="checkout-cart-item-sub"><?= (int)$cItem['qty'] ?> ชิ้น × ฿<?= number_format((float)$cItem['unit_price'], 0) ?> (<?= (int)($cItem['weight_each'] ?? 0) ?>g/ชิ้น)</div>
                                </div>
                            </div>
                            <strong class="checkout-cart-item-price">฿<?= number_format((float)$cItem['line_total'], 0) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary-line">
                    <span>ยอดรวมค่าสินค้า</span>
                    <strong>฿<?= number_format((float)$cart['subtotal'], 0) ?></strong>
                </div>

                <?php if (($cart['wholesale_savings'] ?? 0) > 0): ?>
                    <div class="cart-summary-line checkout-wholesale-saving">
                        <span>ส่วนลดเรทราคาส่งร้านนวด</span>
                        <strong>-฿<?= number_format((float)$cart['wholesale_savings'], 0) ?></strong>
                    </div>
                <?php endif; ?>

                <div class="cart-summary-line">
                    <span id="shippingLabel">ค่าจัดส่งพัสดุด่วน (น้ำหนัก <?= number_format($cart['total_weight_grams'] ?? 0) ?>g)</span>
                    <span class="checkout-free-shipping" id="shippingCostDisplay">
                        <?= (float)$cart['shipping_cost'] > 0 ? ('฿' . number_format((float)$cart['shipping_cost'], 0)) : 'ส่งฟรีทั่วประเทศ' ?>
                    </span>
                </div>

                <!-- Coupon Discount Code Input Box -->
                <div class="checkout-coupon-box">
                    <div class="checkout-coupon-input-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="coupon-icon"><path d="M20 12V8H4v4a2 2 0 0 1 0 4v4h16v-4a2 2 0 0 1 0-4Z"></path><path d="M10 8v8m4-8v8"></path></svg>
                        <input type="text" id="couponCodeInput" placeholder="กรอกโค้ดส่วนลด (ถ้ามี)" class="checkout-coupon-input" autocomplete="off">
                        <button type="button" class="btn-apply-coupon" onclick="applyCouponCode()">ใช้โค้ด</button>
                    </div>
                    <div id="couponFeedback" class="coupon-feedback d-none"></div>
                </div>

                <div class="cart-summary-line checkout-coupon-discount-row d-none" id="couponDiscountRow">
                    <span>ส่วนลดโค้ดคูปอง (<strong id="couponCodeLabel"></strong>)</span>
                    <strong id="couponDiscountLabel">-฿0</strong>
                </div>

                <!-- COD Fee Row -->
                <div class="cart-summary-line checkout-cod-row d-none" id="codFeeRow">
                    <span>ค่าธรรมเนียมปลายทาง (COD 3%)</span>
                    <strong id="codFeeLabel">+฿0</strong>
                </div>

                <!-- M-Stamp Points Redemption Box -->
                <?php if ($memberPoints > 0): 
                    $maxRedeem = min($memberPoints, (int)$cart['subtotal']);
                ?>
                <div class="checkout-mstamp-box">
                    <div class="checkout-mstamp-header">
                        <span class="checkout-mstamp-title">
                            🪙 สิทธิพิเศษ M-Stamp (มี <?= number_format($memberPoints) ?> แต้ม)
                        </span>
                        <label class="checkout-mstamp-toggle">
                            <input type="checkbox" id="useMStampCheckbox" onchange="toggleMStamp(this)">
                            <span>ใช้แต้มเป็นส่วนลด</span>
                        </label>
                    </div>
                    <div id="mStampInputRow" class="checkout-mstamp-input-wrap d-none">
                        <input type="number" name="redeem_stamps" id="redeemStampsInput" min="0" max="<?= $maxRedeem ?>" value="<?= $maxRedeem ?>" class="checkout-mstamp-input" oninput="recalcOrderTotal()">
                        <span class="checkout-mstamp-earn-notice">แต้ม (ลดได้สูงสุด ฿<?= $maxRedeem ?> บาท)</span>
                    </div>
                </div>

                <div class="cart-summary-line checkout-mstamp-discount-row d-none" id="stampDiscountRow">
                    <span>ส่วนลดแต้ม M-Stamp</span>
                    <strong id="stampDiscountLabel">-฿0</strong>
                </div>
                <?php endif; ?>

                <div class="cart-summary-line cart-summary-total">
                    <span>ยอดชำระสุทธิ</span>
                    <strong id="grandTotalDisplay" class="checkout-total-price">฿<?= number_format((float)$cart['grand_total'], 0) ?></strong>
                </div>

                <div class="checkout-mstamp-earn-notice">
                    🌿 ออเดอร์นี้จะได้รับสะสม <strong>+<?= (int)floor($cart['grand_total'] / 100) ?> M-Stamp</strong>
                </div>

                <button type="submit" id="btnSubmitOrder" class="btn-checkout-primary checkout-btn-submit">
                    <span class="spinner-snake d-none" id="checkoutBtnSpinner"></span>
                    <svg id="checkoutBtnIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span id="checkoutBtnText">ยืนยันการสั่งซื้อทันที (Confirm Order)</span>
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
const cartSubtotal = <?= (float)$cart['subtotal'] ?>;
const totalWeightGrams = <?= (int)($cart['total_weight_grams'] ?? 0) ?>;
const maxStampsAvailable = <?= $memberPoints ?? 0 ?>;

let currentDestination = 'domestic';
let appliedCoupon = null; // { code, discount }

function calcDomesticShipping(grams) {
    if (grams <= 0) return 0;
    if (grams > 12000) return 0;
    if (grams > 10000) return 90;
    if (grams > 8000) return 90;
    if (grams > 6000) return 80;
    if (grams > 4000) return 70;
    if (grams > 2000) return 67;
    return 50;
}

function handleDestinationChange(type) {
    currentDestination = type;
    document.querySelectorAll('.checkout-dest-btn').forEach(b => b.classList.remove('active'));
    const intlFields = document.getElementById('internationalFields');
    const provSelect = document.getElementById('provinceSelect');
    const destCountryInput = document.getElementById('destCountryInput');
    const codOption = document.getElementById('payOptionCod');

    if (type === 'domestic') {
        document.getElementById('destBtnDomestic')?.classList.add('active');
        intlFields?.classList.add('d-none');
        if (destCountryInput) destCountryInput.required = false;
        if (provSelect) provSelect.required = true;
        codOption?.classList.remove('d-none');
    } else {
        document.getElementById('destBtnInternational')?.classList.add('active');
        intlFields?.classList.remove('d-none');
        if (destCountryInput) destCountryInput.required = true;
        if (provSelect) provSelect.required = false;

        // Switch off COD if selected
        const codRadio = document.querySelector('input[name="paycat_id"][value="2"]');
        if (codRadio && codRadio.checked) {
            const bankRadio = document.querySelector('input[name="paycat_id"][value="1"]');
            if (bankRadio) {
                bankRadio.checked = true;
                handlePaymentChange(bankRadio);
            }
        }
        codOption?.classList.add('d-none');
    }
    recalcOrderTotal();
}

function handlePaymentChange(radio) {
    document.querySelectorAll('.checkout-pay-option').forEach(el => el.classList.remove('active'));
    radio.closest('.checkout-pay-option')?.classList.add('active');
    recalcOrderTotal();
}

function toggleMStamp(chk) {
    const row = document.getElementById('mStampInputRow');
    const discRow = document.getElementById('stampDiscountRow');
    if (chk.checked) {
        row?.classList.remove('d-none');
        discRow?.classList.remove('d-none');
        discRow?.classList.add('d-flex');
    } else {
        row?.classList.add('d-none');
        discRow?.classList.add('d-none');
        discRow?.classList.remove('d-flex');
        const input = document.getElementById('redeemStampsInput');
        if (input) input.value = 0;
    }
    recalcOrderTotal();
}

async function applyCouponCode() {
    const codeInput = document.getElementById('couponCodeInput');
    const feedback = document.getElementById('couponFeedback');
    const code = codeInput ? codeInput.value.trim().toUpperCase() : '';
    if (!code) {
        if (feedback) {
            feedback.textContent = 'กรุณาระบุโค้ดส่วนลด';
            feedback.className = 'coupon-feedback error';
            feedback.classList.remove('d-none');
        }
        return;
    }

    try {
        const res = await fetch('<?= URL_PATH ?>/api/apply_coupon.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ coupon_code: code, subtotal: cartSubtotal })
        });
        const data = await res.json();
        if (data.status && data.data) {
            appliedCoupon = data.data;
            if (feedback) {
                feedback.textContent = data.data.message;
                feedback.className = 'coupon-feedback success';
                feedback.classList.remove('d-none');
            }
            document.getElementById('couponDiscountRow')?.classList.remove('d-none');
            document.getElementById('couponDiscountRow')?.classList.add('d-flex');
            document.getElementById('couponCodeLabel').textContent = data.data.code;
            document.getElementById('couponDiscountLabel').textContent = '-฿' + data.data.discount.toLocaleString();
        } else {
            appliedCoupon = null;
            if (feedback) {
                feedback.textContent = data.message || 'โค้ดส่วนลดไม่ถูกต้อง';
                feedback.className = 'coupon-feedback error';
                feedback.classList.remove('d-none');
            }
            document.getElementById('couponDiscountRow')?.classList.add('d-none');
            document.getElementById('couponDiscountRow')?.classList.remove('d-flex');
        }
    } catch (err) {
        if (feedback) {
            feedback.textContent = 'ไม่สามารถตรวจสอบโค้ดได้ กรุณาลองใหม่';
            feedback.className = 'coupon-feedback error';
            feedback.classList.remove('d-none');
        }
    }
    recalcOrderTotal();
}

function recalcOrderTotal() {
    // 1. Shipping cost
    let shippingCost = 0;
    const shipDisplay = document.getElementById('shippingCostDisplay');
    const shipLabel = document.getElementById('shippingLabel');

    if (currentDestination === 'international') {
        shippingCost = 0;
        if (shipLabel) shipLabel.textContent = 'ค่าจัดส่งต่างประเทศ';
        if (shipDisplay) shipDisplay.textContent = 'รอแจ้งตามปลายทาง';
    } else {
        shippingCost = calcDomesticShipping(totalWeightGrams);
        if (shipLabel) shipLabel.textContent = `ค่าจัดส่งพัสดุด่วน (${totalWeightGrams.toLocaleString()}g)`;
        if (shipDisplay) {
            shipDisplay.textContent = shippingCost > 0 ? ('฿' + shippingCost.toLocaleString()) : 'ส่งฟรีทั่วประเทศ';
        }
    }

    // 2. COD fee
    const isCod = document.querySelector('input[name="paycat_id"]:checked')?.value === '2';
    let codFee = 0;
    const codRow = document.getElementById('codFeeRow');
    const codLabel = document.getElementById('codFeeLabel');
    if (isCod && currentDestination === 'domestic') {
        codFee = Math.round((cartSubtotal + shippingCost) * 0.03);
        if (codRow) {
            codRow.classList.remove('d-none');
            codRow.classList.add('d-flex');
        }
        if (codLabel) codLabel.textContent = '+฿' + codFee.toLocaleString();
    } else {
        if (codRow) {
            codRow.classList.add('d-none');
            codRow.classList.remove('d-flex');
        }
    }

    // 3. Coupon discount
    const couponDiscount = appliedCoupon ? parseFloat(appliedCoupon.discount || 0) : 0;

    // 4. M-Stamp discount
    const chk = document.getElementById('useMStampCheckbox');
    let stamps = 0;
    if (chk && chk.checked) {
        stamps = parseInt(document.getElementById('redeemStampsInput').value, 10) || 0;
        const maxUsable = Math.max(0, cartSubtotal + shippingCost + codFee - couponDiscount);
        stamps = Math.max(0, Math.min(stamps, maxStampsAvailable, maxUsable));
        document.getElementById('redeemStampsInput').value = stamps;
        document.getElementById('stampDiscountLabel').textContent = '-฿' + stamps.toLocaleString();
    }

    // 5. Grand Total
    const grandTotal = Math.max(0, (cartSubtotal + shippingCost + codFee) - couponDiscount - stamps);
    document.getElementById('grandTotalDisplay').textContent = '฿' + grandTotal.toLocaleString();
}

document.getElementById('checkoutOrderForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitOrder');
    const spinner = document.getElementById('checkoutBtnSpinner');
    const icon = document.getElementById('checkoutBtnIcon');
    const text = document.getElementById('checkoutBtnText');

    if (btn.disabled) return;

    btn.disabled = true;
    spinner?.classList.remove('d-none');
    icon?.classList.add('d-none');
    if (text) text.textContent = 'กำลังบันทึกคำสั่งซื้อ...';

    const formData = new FormData(this);
    const payload = {};
    formData.forEach((v, k) => payload[k] = v);

    try {
        const res = await fetch('<?= URL_PATH ?>/api/checkout.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.status && data.data) {
            // Google Analytics 4 (GA4) Purchase Conversion Event
            if (typeof gtag === 'function') {
                gtag('event', 'purchase', {
                    transaction_id: String(data.data.orders_no || ''),
                    value: Number(data.data.grandtotal || currentGrandTotal),
                    currency: 'THB',
                    shipping: Number(currentShippingCost)
                });
            }
            await Swal.fire({
                icon: 'success',
                title: 'สั่งซื้อสำเร็จ!',
                text: 'บันทึกคำสั่งซื้อเรียบร้อยแล้ว กำลังนำท่านไปยังหน้ารายละเอียด...',
                timer: 1600,
                showConfirmButton: false
            });
            window.location.href = data.data.track_url || ('<?= URL_PATH ?>/<?= $currentLang ?>/orders');
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถทำรายการได้ กรุณาลองใหม่อีกครั้ง'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'การเชื่อมต่อผิดพลาด',
            text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง'
        });
    } finally {
        btn.disabled = false;
        spinner?.classList.add('d-none');
        icon?.classList.remove('d-none');
        if (text) text.textContent = 'ยืนยันการสั่งซื้อทันที (Confirm Order)';
    }
});
</script>
