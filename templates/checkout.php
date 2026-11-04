<?php
$currentLang = $SITE['lang'] ?? 'th';
$provinces = $CHECKOUT_DATA['provinces'] ?? [];
$countries = $CHECKOUT_DATA['countries'] ?? [];
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

        <!-- Member Quick Access Banner -->
        <?php if (!empty($loggedUser)): ?>
            <div class="checkout-member-portal-banner logged-in">
                <div class="cmp-left">
                    <span class="cmp-icon">👑</span>
                    <div>
                        <div class="cmp-title">ยินดีต้อนรับท่านสมาชิก VIP: <strong><?= htmlspecialchars($loggedUser['user_name']) ?></strong> (รหัส <?= 'C' . $loggedUser['user_id'] ?>)</div>
                        <div class="cmp-sub">ระบบดึงที่อยู่จัดส่งล่าสุดเรียบร้อยแล้ว | แต้มสะสมปัจจุบัน: <strong class="text-gold" id="userHeaderPoints"><?= number_format($memberPoints) ?></strong> E-Stamp</div>
                    </div>
                </div>
                <div class="cmp-right">
                    <span class="badge-vip-status">เข้าสู่ระบบแล้ว</span>
                </div>
            </div>
        <?php else: ?>
            <div class="checkout-member-portal-banner">
                <div class="cmp-left">
                    <span class="cmp-icon">👑</span>
                    <div>
                        <div class="cmp-title">สำหรับลูกค้าที่มีบัญชีสมาชิกอยู่แล้ว</div>
                        <div class="cmp-sub">คลิกเพื่อเข้าสู่ระบบ ดึงข้อมูลที่อยู่อัตโนมัติและใช้แต้ม E-Stamp หรือสั่งซื้อครั้งแรกกรอกข้อมูลด้านล่างได้ทันที</div>
                    </div>
                </div>
                <div class="cmp-right">
                    <button type="button" class="cmp-btn-login" onclick="openCheckoutLoginModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                        <span>คลิกเพื่อล็อกอิน</span>
                    </button>
                    <div class="cmp-chat-group">
                        <span class="cmp-chat-label">ไม่แน่ใจ ติดต่อแอดมิน:</span>
                        <a href="https://lin.ee/vyUtLZ6" target="_blank" rel="noopener" class="cmp-chat-pill cmp-line" title="สอบถามแอดมินทาง LINE">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.085.495.234l2.468 3.348V8.108c0-.345.28-.63.629-.63.347 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"></path></svg>
                            <span>LINE</span>
                        </a>
                        <a href="https://m.me/YakPho.Aroma" target="_blank" rel="noopener" class="cmp-chat-pill cmp-messenger" title="สอบถามแอดมินทาง Messenger">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.093.304 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.056-3.259-5.963 3.259 6.559-6.963 3.13 3.259 5.89-3.259-6.56 6.963z"></path></svg>
                            <span>Messenger</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Customer Match Notification Banner (Dynamic when typing mobile) -->
        <div id="customerMatchAlert" class="checkout-match-alert d-none">
            <span class="cma-icon">✨</span>
            <div class="cma-text">
                พบข้อมูลสมาชิกคุณ <strong id="cmaName"></strong> (รหัส <span id="cmaCode"></span>) มีแต้มสะสม <strong class="text-gold" id="cmaPoints"></strong> E-Stamp
            </div>
            <button type="button" class="cma-btn" onclick="applyMatchedCustomer()">
                ดึงที่อยู่ & ใช้แต้มทันที
            </button>
        </div>

        <form id="checkoutOrderForm">
            <?php if (!empty($affiliateCookie)): ?>
            <input type="hidden" name="affiliate_code" value="<?= htmlspecialchars($affiliateCookie) ?>">
            <?php endif; ?>
            <input type="hidden" name="matched_user_id" id="matchedUserId" value="<?= (int)($loggedUser['user_id'] ?? 0) ?>">

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

                <!-- Domestic Form Fields -->
                <div id="domesticFields">
                    <div class="checkout-grid-2">
                        <div class="checkout-form-group">
                            <label class="checkout-label">ชื่อ - นามสกุล / ชื่อร้านสปา *</label>
                            <input type="text" name="customer_name" id="custNameInput" required value="<?= htmlspecialchars($loggedUser['user_name'] ?? '') ?>" placeholder="ระบุชื่อผู้รับสินค้า" class="checkout-input">
                        </div>
                        <div class="checkout-form-group">
                            <label class="checkout-label">เบอร์โทรศัพท์มือถือ *</label>
                            <input type="tel" name="customer_phone" id="custPhoneInput" required value="<?= htmlspecialchars($loggedUser['user_mobile'] ?? '') ?>" placeholder="08x-xxx-xxxx" class="checkout-input" oninput="handlePhoneLookup(this.value)">
                        </div>
                    </div>

                    <div class="checkout-grid-2">
                        <div class="checkout-form-group">
                            <label class="checkout-label">LINE ID (สำหรับแจ้งสถานะพัสดุ)</label>
                            <input type="text" name="line_id" id="custLineInput" placeholder="@lineid หรือชื่อไอดี" class="checkout-input">
                        </div>
                        <div class="checkout-form-group">
                            <label class="checkout-label">อีเมล (สำหรับรับใบเสร็จดิจิทัล)</label>
                            <input type="email" name="customer_email" id="custEmailInput" value="<?= htmlspecialchars($loggedUser['user_email'] ?? '') ?>" placeholder="you@example.com" class="checkout-input">
                        </div>
                    </div>

                    <div class="checkout-form-group">
                        <label class="checkout-label">ที่อยู่จัดส่ง (บ้านเลขที่ / หมู่ / อาคาร / ซอย / ถนน) *</label>
                        <textarea name="address_detail" id="addrDetailInput" rows="2" required placeholder="เช่น 97/28 อาคารปาร์คเอ็กโซ ถนนประเสริฐมนูกิจ..." class="checkout-textarea"><?= htmlspecialchars($loggedUser['user_address'] ?? '') ?></textarea>
                    </div>

                    <div class="checkout-grid-2">
                        <div class="checkout-form-group">
                            <label class="checkout-label">รหัสไปรษณีย์ (ZIP / Postcode) *</label>
                            <input type="text" name="postcode" id="addrPostcodeInput" required value="<?= htmlspecialchars($loggedUser['user_postcode'] ?? '') ?>" placeholder="เช่น 10510 (กรอกแล้วระบบจะค้นหาตำบล อำเภอ จังหวัดทันที)" class="checkout-input" maxlength="5" oninput="handleZipcodeLookup(this.value)">
                            <div id="zipSuggestionsWrap" class="checkout-zip-suggestions d-none">
                                <span class="zip-sug-title">แตะเพื่อเลือก ตำบล/อำเภอ:</span>
                                <div id="zipSuggestionsList" class="zip-sug-list"></div>
                            </div>
                        </div>
                        <div class="checkout-form-group">
                            <label class="checkout-label">ตำบล / แขวง และ อำเภอ / เขต *</label>
                            <input type="text" name="address_detail2" id="addrDetail2Input" value="<?= htmlspecialchars($loggedUser['user_address_detail2'] ?? '') ?>" placeholder="เช่น แขวงสามวาตะวันตก เขตคลองสามวา" class="checkout-input" required>
                        </div>
                    </div>

                    <div class="checkout-form-group" id="provinceGroup">
                        <label class="checkout-label">จังหวัด *</label>
                        <select name="province_id" id="provinceSelect" class="checkout-select" required>
                            <option value="">-- เลือกจังหวัด --</option>
                            <?php foreach ($provinces as $prov): 
                                if ((int)$prov['provinces_id'] === 79) continue; // Skip foreign country in domestic
                                $selected = (!empty($loggedUser['user_province']) && (int)$loggedUser['user_province'] === (int)$prov['provinces_id']) ? 'selected' : '';
                            ?>
                                <option value="<?= (int)$prov['provinces_id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($prov['name_th']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- International Destination Fields (Image 2) -->
                <div id="internationalFields" class="d-none">
                    <div class="checkout-intl-notice">
                        ✈️ สำหรับการจัดส่งต่างประเทศ ทางร้านจัดส่งด้วย DHL Express / EMS World เมื่อกดสั่งซื้อเรียบร้อย แอดมินจะตรวจสอบพื้นที่ปลายทางและติดต่อแจ้งสรุปค่าจัดส่งให้ทราบทาง LINE / Email ก่อนทำการจัดส่งครับ
                    </div>

                    <div class="checkout-form-group">
                        <label class="checkout-label">ประเทศปลายทาง (Destination Country) *</label>
                        <select name="destination_country_id" id="destCountrySelect" class="checkout-select" onchange="handleCountrySelect(this)">
                            <option value="">-- เลือกประเทศปลายทาง (Select Country) --</option>
                            <optgroup label="🌟 ประเทศยอดนิยมที่มีการสั่งซื้อจริง (Popular Destinations)">
                                <option value="64" data-country-name="Germany">🇩🇪 Germany (เยอรมนี)</option>
                                <option value="9" data-country-name="Australia">🇦🇺 Australia (ออสเตรเลีย)</option>
                                <option value="183" data-country-name="United Kingdom">🇬🇧 United Kingdom (สหราชอาณาจักร)</option>
                                <option value="184" data-country-name="United States">🇺🇸 United States (สหรัฐอเมริกา)</option>
                                <option value="124" data-country-name="New Zealand">🇳🇿 New Zealand (นิวซีแลนด์)</option>
                                <option value="123" data-country-name="Netherlands">🇳🇱 Netherlands (เนเธอร์แลนด์)</option>
                                <option value="36" data-country-name="China">🇨🇳 China (จีน)</option>
                                <option value="13" data-country-name="Bahrain">🇧🇭 Bahrain (บาห์เรน)</option>
                                <option value="84" data-country-name="Japan">🇯🇵 Japan (ญี่ปุ่น)</option>
                                <option value="60" data-country-name="France">🇫🇷 France (ฝรั่งเศส)</option>
                                <option value="138" data-country-name="Portugal">🇵🇹 Portugal (โปรตุเกส)</option>
                                <option value="82" data-country-name="Italy">🇮🇹 Italy (อิตาลี)</option>
                                <option value="74" data-country-name="Hungary">🇭🇺 Hungary (ฮังการี)</option>
                                <option value="159" data-country-name="South Africa">🇿🇦 South Africa (แอฟริกาใต้)</option>
                                <option value="46" data-country-name="Denmark">🇩🇰 Denmark (เดนมาร์ก)</option>
                                <option value="32" data-country-name="Canada">🇨🇦 Canada (แคนาดา)</option>
                                <option value="45" data-country-name="Czech Republic">🇨🇿 Czech Republic (เช็ก)</option>
                                <option value="10" data-country-name="Austria">🇦🇹 Austria (ออสเตรีย)</option>
                                <option value="90" data-country-name="Korea (South)">🇰🇷 South Korea (เกาหลีใต้)</option>
                                <option value="165" data-country-name="Sweden">🇸🇪 Sweden (สวีเดน)</option>
                                <option value="128" data-country-name="Norway">🇳🇴 Norway (นอร์เวย์)</option>
                                <option value="153" data-country-name="Singapore">🇸🇬 Singapore (สิงคโปร์)</option>
                                <option value="104" data-country-name="Malaysia">🇲🇾 Malaysia (มาเลเซีย)</option>
                            </optgroup>
                            <optgroup label="🌐 ทุกประเทศทั่วโลก (All Countries)">
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?= (int)$c['country_id'] ?>" data-country-name="<?= htmlspecialchars($c['country_name']) ?>">
                                        <?= htmlspecialchars($c['country_name']) ?> (<?= htmlspecialchars($c['country_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                        <input type="hidden" name="destination_country" id="destCountryNameInput" value="">
                    </div>

                    <div class="checkout-grid-2">
                        <div class="checkout-form-group">
                            <label class="checkout-label">Full Name / Contact Person *</label>
                            <input type="text" name="intl_customer_name" id="intlCustName" placeholder="Recipient's Name" class="checkout-input">
                        </div>
                        <div class="checkout-form-group">
                            <label class="checkout-label">Phone Number (with Country Code) *</label>
                            <input type="tel" name="intl_customer_phone" id="intlCustPhone" placeholder="+1 555-123-4567" class="checkout-input">
                        </div>
                    </div>

                    <div class="checkout-grid-2">
                        <div class="checkout-form-group">
                            <label class="checkout-label">Email Address *</label>
                            <input type="email" name="intl_customer_email" id="intlCustEmail" placeholder="yourname@gmail.com" class="checkout-input">
                        </div>
                        <div class="checkout-form-group">
                            <label class="checkout-label">WhatsApp / LINE ID</label>
                            <input type="text" name="intl_line_id" id="intlCustLine" placeholder="WhatsApp Number or LINE ID" class="checkout-input">
                        </div>
                    </div>

                    <div class="checkout-form-group">
                        <label class="checkout-label">Street Address (Building, House Number, Street) *</label>
                        <input type="text" name="intl_address_line1" id="intlAddr1" placeholder="e.g. 123 Maple Street, Suite 4B" class="checkout-input">
                    </div>

                    <div class="checkout-grid-3">
                        <div class="checkout-form-group">
                            <label class="checkout-label">City / Town *</label>
                            <input type="text" name="intl_city" id="intlCity" placeholder="e.g. Los Angeles" class="checkout-input">
                        </div>
                        <div class="checkout-form-group">
                            <label class="checkout-label">State / Province / Region *</label>
                            <input type="text" name="intl_state" id="intlState" placeholder="e.g. California" class="checkout-input">
                        </div>
                        <div class="checkout-form-group">
                            <label class="checkout-label">Postal Code / ZIP *</label>
                            <input type="text" name="intl_postcode" id="intlPostcode" placeholder="e.g. 90001" class="checkout-input">
                        </div>
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

                <!-- E-Stamp Points Toggle Switch Card (Image 3) -->
                <div class="checkout-estamp-card" id="estampCard">
                    <div class="checkout-estamp-header">
                        <div class="checkout-estamp-left">
                            <span class="estamp-coin-icon">🪙</span>
                            <div>
                                <strong class="estamp-title">ใช้ E-Stamp เป็นส่วนลด</strong>
                                <div class="estamp-desc" id="estampBalanceNotice">
                                    <?php if ($memberPoints > 0): ?>
                                        ท่านมีแต้มสะสม: <strong class="text-gold" id="estampAvailablePoints"><?= number_format($memberPoints) ?></strong> แต้ม (1 แต้ม = ฿1)
                                    <?php else: ?>
                                        <span id="estampAvailablePointsText">มีแต้มสะสม? <a href="javascript:void(0)" onclick="openCheckoutLoginModal()" class="text-gold underline">ล็อกอิน</a> หรือกรอกเบอร์โทรเพื่อใช้แต้ม</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <label class="yp-switch-toggle" title="เปิด/ปิดการใช้แต้ม E-Stamp">
                            <input type="checkbox" id="useEStampToggle" <?= ($memberPoints > 0) ? '' : 'disabled' ?> onchange="toggleEStampSwitch(this)">
                            <span class="yp-switch-slider"></span>
                        </label>
                    </div>

                    <!-- Drawer when Toggle is ON -->
                    <div id="estampInputWrap" class="checkout-estamp-drawer d-none">
                        <div class="estamp-drawer-inner">
                            <span class="estamp-drawer-lbl">จำนวนแต้มที่ต้องการนำไปหักลบ:</span>
                            <div class="estamp-input-box">
                                <input type="number" name="redeem_stamps" id="redeemStampsInput" min="0" max="<?= $memberPoints ?>" value="<?= $memberPoints ?>" class="checkout-estamp-input" oninput="recalcOrderTotal()">
                                <span class="estamp-unit">แต้ม</span>
                            </div>
                            <button type="button" class="btn-estamp-max" onclick="setMaxEStamp()">ใช้เต็มจำนวน</button>
                        </div>
                    </div>
                </div>

                <!-- E-Stamp Discount Row in Breakdown -->
                <div class="cart-summary-line checkout-estamp-discount-row d-none" id="estampDiscountRow">
                    <span>🪙 ส่วนลดแต้ม E-Stamp (<span id="estampUsedCountLabel">0</span> แต้ม)</span>
                    <strong id="estampDiscountLabel" class="text-emerald">-฿0</strong>
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

                <div class="cart-summary-line cart-summary-total">
                    <span>ยอดชำระสุทธิ</span>
                    <strong id="grandTotalDisplay" class="checkout-total-price">฿<?= number_format((float)$cart['grand_total'], 0) ?></strong>
                </div>

                <div class="checkout-mstamp-earn-notice">
                    🌿 ออเดอร์นี้จะได้รับสะสม <strong>+<span id="earnedPointsEstimate"><?= (int)floor($cart['grand_total'] / 100) ?></span> E-Stamp</strong>
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
let maxStampsAvailable = <?= $memberPoints ?? 0 ?>;
let matchedCustomer = null;

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
    const domesticFields = document.getElementById('domesticFields');
    const intlFields = document.getElementById('internationalFields');
    const codOption = document.getElementById('payOptionCod');

    if (type === 'domestic') {
        document.getElementById('destBtnDomestic')?.classList.add('active');
        domesticFields?.classList.remove('d-none');
        intlFields?.classList.add('d-none');
        codOption?.classList.remove('d-none');

        // Required toggles
        document.getElementById('custNameInput').required = true;
        document.getElementById('custPhoneInput').required = true;
        document.getElementById('addrDetailInput').required = true;
        document.getElementById('addrPostcodeInput').required = true;
        document.getElementById('addrDetail2Input').required = true;
        document.getElementById('provinceSelect').required = true;

        document.getElementById('destCountrySelect').required = false;
        document.getElementById('intlCustName').required = false;
        document.getElementById('intlCustPhone').required = false;
        document.getElementById('intlCustEmail').required = false;
        document.getElementById('intlAddr1').required = false;
        document.getElementById('intlCity').required = false;
        document.getElementById('intlState').required = false;
        document.getElementById('intlPostcode').required = false;
    } else {
        document.getElementById('destBtnInternational')?.classList.add('active');
        domesticFields?.classList.add('d-none');
        intlFields?.classList.remove('d-none');

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

        // Required toggles
        document.getElementById('custNameInput').required = false;
        document.getElementById('custPhoneInput').required = false;
        document.getElementById('addrDetailInput').required = false;
        document.getElementById('addrPostcodeInput').required = false;
        document.getElementById('addrDetail2Input').required = false;
        document.getElementById('provinceSelect').required = false;

        document.getElementById('destCountrySelect').required = true;
        document.getElementById('intlCustName').required = true;
        document.getElementById('intlCustPhone').required = true;
        document.getElementById('intlCustEmail').required = true;
        document.getElementById('intlAddr1').required = true;
        document.getElementById('intlCity').required = true;
        document.getElementById('intlState').required = true;
        document.getElementById('intlPostcode').required = true;
    }
    recalcOrderTotal();
}

function handleCountrySelect(sel) {
    const selectedOpt = sel.options[sel.selectedIndex];
    const name = selectedOpt ? (selectedOpt.getAttribute('data-country-name') || selectedOpt.text.split('(')[0].trim()) : '';
    document.getElementById('destCountryNameInput').value = name;
}

function handlePaymentChange(radio) {
    document.querySelectorAll('.checkout-pay-option').forEach(el => el.classList.remove('active'));
    radio.closest('.checkout-pay-option')?.classList.add('active');
    recalcOrderTotal();
}

// -------------------------------------------------------------
// Auto-Lookup Thailand Zipcode -> Province & Tambon/Amphoe
// -------------------------------------------------------------
let zipDebounceTimer = null;
function handleZipcodeLookup(val) {
    clearTimeout(zipDebounceTimer);
    const clean = val.replace(/[^0-9]/g, '');
    if (clean.length === 5) {
        zipDebounceTimer = setTimeout(() => {
            fetch(`<?= URL_PATH ?>/api/address_lookup.php?action=postcode&code=${clean}`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        // 1. Auto select province
                        if (data.province_id > 0) {
                            const provSelect = document.getElementById('provinceSelect');
                            if (provSelect) {
                                provSelect.value = data.province_id;
                            }
                        }

                        // 2. Suggestions chips
                        const wrap = document.getElementById('zipSuggestionsWrap');
                        const list = document.getElementById('zipSuggestionsList');
                        const detail2 = document.getElementById('addrDetail2Input');
                        if (data.suggestions && data.suggestions.length > 0) {
                            list.innerHTML = '';
                            if (detail2 && (!detail2.value.trim() || detail2.value.trim() === '')) {
                                detail2.value = data.suggestions[0];
                            }
                            data.suggestions.forEach(s => {
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'zip-chip' + (detail2 && detail2.value.trim() === s ? ' active' : '');
                                btn.textContent = '📍 ' + s;
                                btn.onclick = () => {
                                    if (detail2) detail2.value = s;
                                    list.querySelectorAll('.zip-chip').forEach(b => b.classList.remove('active'));
                                    btn.classList.add('active');
                                };
                                list.appendChild(btn);
                            });
                            wrap?.classList.remove('d-none');
                        } else {
                            wrap?.classList.add('d-none');
                        }
                    }
                })
                .catch(() => {});
        }, 150);
    } else {
        document.getElementById('zipSuggestionsWrap')?.classList.add('d-none');
    }
}

// -------------------------------------------------------------
// Customer Auto-Match on typing phone number
// -------------------------------------------------------------
let phoneDebounceTimer = null;
function handlePhoneLookup(val) {
    clearTimeout(phoneDebounceTimer);
    const clean = val.replace(/[^0-9]/g, '');
    if (clean.length === 10) {
        phoneDebounceTimer = setTimeout(() => {
            fetch(`<?= URL_PATH ?>/api/address_lookup.php?action=check_customer&phone=${clean}`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success' && data.found) {
                        matchedCustomer = data;
                        document.getElementById('cmaName').textContent = data.name;
                        document.getElementById('cmaCode').textContent = data.c_code;
                        document.getElementById('cmaPoints').textContent = (data.m_stamp_points || 0).toLocaleString();
                        document.getElementById('customerMatchAlert')?.classList.remove('d-none');
                    } else {
                        document.getElementById('customerMatchAlert')?.classList.add('d-none');
                    }
                })
                .catch(() => {});
        }, 300);
    } else {
        document.getElementById('customerMatchAlert')?.classList.add('d-none');
    }
}

function applyMatchedCustomer() {
    if (!matchedCustomer) return;
    document.getElementById('custNameInput').value = matchedCustomer.name;
    if (matchedCustomer.email) document.getElementById('custEmailInput').value = matchedCustomer.email;
    document.getElementById('matchedUserId').value = matchedCustomer.user_id;

    if (matchedCustomer.address) {
        if (matchedCustomer.address.detail) document.getElementById('addrDetailInput').value = matchedCustomer.address.detail;
        if (matchedCustomer.address.detail2) document.getElementById('addrDetail2Input').value = matchedCustomer.address.detail2;
        if (matchedCustomer.address.postcode) document.getElementById('addrPostcodeInput').value = matchedCustomer.address.postcode;
        if (matchedCustomer.address.province_id) document.getElementById('provinceSelect').value = matchedCustomer.address.province_id;
    }

    // Enable E-Stamp Points
    maxStampsAvailable = matchedCustomer.m_stamp_points || 0;
    updateEStampCardUI();

    document.getElementById('customerMatchAlert')?.classList.add('d-none');

    Swal.fire({
        icon: 'success',
        title: 'ดึงข้อมูลสำเร็จ!',
        text: `เชื่อมต่อบัญชี ${matchedCustomer.name} (แต้มสะสม ${maxStampsAvailable.toLocaleString()} แต้ม)`,
        timer: 1500,
        showConfirmButton: false
    });
}

function updateEStampCardUI() {
    const toggle = document.getElementById('useEStampToggle');
    const notice = document.getElementById('estampBalanceNotice');
    const input = document.getElementById('redeemStampsInput');

    if (maxStampsAvailable > 0) {
        toggle.disabled = false;
        notice.innerHTML = `ท่านมีแต้มสะสม: <strong class="text-gold" id="estampAvailablePoints">${maxStampsAvailable.toLocaleString()}</strong> แต้ม (1 แต้ม = ฿1)`;
        if (input) input.max = maxStampsAvailable;
    }
}

// -------------------------------------------------------------
// E-Stamp Toggle Switch
// -------------------------------------------------------------
function toggleEStampSwitch(chk) {
    const drawer = document.getElementById('estampInputWrap');
    const discRow = document.getElementById('estampDiscountRow');
    const input = document.getElementById('redeemStampsInput');

    if (chk.checked) {
        if (maxStampsAvailable <= 0) {
            chk.checked = false;
            openCheckoutLoginModal();
            return;
        }
        drawer?.classList.remove('d-none');
        discRow?.classList.remove('d-none');
        discRow?.classList.add('d-flex');
        if (input) input.value = Math.min(maxStampsAvailable, cartSubtotal);
    } else {
        drawer?.classList.add('d-none');
        discRow?.classList.add('d-none');
        discRow?.classList.remove('d-flex');
        if (input) input.value = 0;
    }
    recalcOrderTotal();
}

function setMaxEStamp() {
    const input = document.getElementById('redeemStampsInput');
    if (input) {
        input.value = Math.min(maxStampsAvailable, cartSubtotal);
        recalcOrderTotal();
    }
}

// -------------------------------------------------------------
// Recalculate Order Total
// -------------------------------------------------------------
function recalcOrderTotal() {
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

    // COD fee
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

    // Coupon discount
    const couponDiscount = appliedCoupon ? parseFloat(appliedCoupon.discount || 0) : 0;

    // E-Stamp discount
    const stampToggle = document.getElementById('useEStampToggle');
    let stamps = 0;
    if (stampToggle && stampToggle.checked) {
        stamps = parseInt(document.getElementById('redeemStampsInput').value, 10) || 0;
        const maxUsable = Math.max(0, cartSubtotal + shippingCost + codFee - couponDiscount);
        stamps = Math.max(0, Math.min(stamps, maxStampsAvailable, maxUsable));
        document.getElementById('redeemStampsInput').value = stamps;
        document.getElementById('estampUsedCountLabel').textContent = stamps.toLocaleString();
        document.getElementById('estampDiscountLabel').textContent = '-฿' + stamps.toLocaleString();
    }

    const grandTotal = Math.max(0, (cartSubtotal + shippingCost + codFee) - couponDiscount - stamps);
    document.getElementById('grandTotalDisplay').textContent = '฿' + grandTotal.toLocaleString();
    document.getElementById('earnedPointsEstimate').textContent = Math.floor(grandTotal / 100);
}

// -------------------------------------------------------------
// Quick Login Modal for Checkout
// -------------------------------------------------------------
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass
        ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
        : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
}

async function openCheckoutLoginModal() {
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
        window.location.reload();
    }
}

// -------------------------------------------------------------
// Coupon Code
// -------------------------------------------------------------
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

// -------------------------------------------------------------
// Checkout Form Submission
// -------------------------------------------------------------
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

    // If international destination, map fields
    if (currentDestination === 'international') {
        payload['customer_name'] = payload['intl_customer_name'];
        payload['customer_phone'] = payload['intl_customer_phone'];
        payload['customer_email'] = payload['intl_customer_email'];
        payload['line_id'] = payload['intl_line_id'];
        payload['address_detail'] = payload['intl_address_line1'];
        payload['address_detail2'] = `${payload['intl_city']}, ${payload['intl_state']}`;
        payload['postcode'] = payload['intl_postcode'];
    }

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
                    value: Number(data.data.grandtotal || 0),
                    currency: 'THB',
                    shipping: Number(data.data.delicost || 0)
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
