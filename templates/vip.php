<?php
$currentLang = $SITE['lang'] ?? 'th';
$c_code = htmlspecialchars($_GET['c'] ?? '');
$token = htmlspecialchars($_GET['token'] ?? '');
?>

<section class="vip-portal-section" id="vipPortalSection">
    <div class="vip-container">
        
        <!-- Loading State Card -->
        <div id="vipLoadingCard" class="vip-glass-card text-center py-5">
            <div class="vip-spinner mb-3"></div>
            <h2 class="vip-title"><?= $currentLang === 'th' ? 'กำลังตรวจสอบสิทธิ์ VIP ของท่าน...' : 'Verifying your VIP membership...' ?></h2>
            <p class="vip-desc"><?= $currentLang === 'th' ? 'ระบบกำลังเตรียมข้อมูลสิทธิพิเศษและคำนวณแต้มสะสม กรุณารอสักครู่' : 'Retrieving your rewards and benefits...' ?></p>
        </div>

        <!-- Invalid / Expired State Card -->
        <div id="vipErrorCard" class="vip-glass-card text-center py-5 d-none">
            <div class="vip-error-icon mb-3">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <h2 class="vip-title"><?= $currentLang === 'th' ? 'ลิงก์สิทธิพิเศษหมดอายุหรือไม่ถูกต้อง' : 'Invalid or Expired VIP Link' ?></h2>
            <p class="vip-desc mb-4" id="vipErrorMessage">
                <?= $currentLang === 'th'
                    ? 'ลิงก์สิทธิพิเศษนี้อาจหมดอายุแล้ว (เกิน 30 วัน) หรือรหัสไม่ถูกต้อง ท่านสามารถติดต่อแอดมินทาง LINE เพื่อขอรับลิงก์ใหม่ได้ทันทีค่ะ'
                    : 'This VIP invitation link may have expired or is invalid. Please contact support via LINE to receive a new link.' ?>
            </p>
            <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                <a href="https://lin.ee/vyUtLZ6" target="_blank" rel="noopener" class="vip-btn-primary" style="background:#06C755; border-color:#06C755; display:inline-flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.085.495.234l2.468 3.348V8.108c0-.345.28-.63.629-.63.347 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"></path></svg>
                    <span><?= $currentLang === 'th' ? 'ติดต่อแอดมิน LINE' : 'Contact via LINE' ?></span>
                </a>
                <a href="https://m.me/YakPho.Aroma" target="_blank" rel="noopener" class="vip-btn-primary" style="background:#0084FF; border-color:#0084FF; display:inline-flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.093.304 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111C24 4.974 18.627 0 12 0zm1.191 14.963l-3.056-3.259-5.963 3.259 6.559-6.963 3.13 3.259 5.89-3.259-6.56 6.963z"></path></svg>
                    <span><?= $currentLang === 'th' ? 'ติดต่อ Messenger' : 'Contact via Messenger' ?></span>
                </a>
            </div>
        </div>

        <!-- Active VIP Member Portal Card -->
        <div id="vipActiveCard" class="d-none">
            
            <!-- 1. VIP Heritage Header Banner -->
            <div class="vip-glass-card vip-header-card mb-4 text-center">
                <div class="vip-brand-pill">
                    <span class="vip-sparkle">👑</span>
                    <span>EXCLUSIVE VIP PORTAL • YAK PHO HERBAL WELLNESS</span>
                </div>
                <h1 class="vip-main-title">
                    <?= $currentLang === 'th' ? 'ยินดีต้อนรับท่านสมาชิกระดับ VIP' : 'Welcome to Yak Pho VIP Club' ?>
                </h1>
                <p class="vip-greeting-text mb-4" id="vipGreetingText">
                    กำลังโหลดข้อมูลสิทธิประโยชน์...
                </p>
                
                <!-- VIP Badge Grid (3 Column) -->
                <div class="vip-stats-grid mb-3">
                    <div class="vip-stat-box">
                        <span class="vip-stat-label"><?= $currentLang === 'th' ? 'รหัสสมาชิกประจำตัว' : 'Member ID' ?></span>
                        <strong class="vip-stat-value text-gold" id="vipMemberCode">--</strong>
                    </div>
                    <div class="vip-stat-box">
                        <span class="vip-stat-label"><?= $currentLang === 'th' ? 'คำสั่งซื้อสะสม' : 'Total Orders' ?></span>
                        <strong class="vip-stat-value text-emerald" id="vipOrderCount">0 ครั้ง</strong>
                    </div>
                    <div class="vip-stat-box vip-stat-highlight">
                        <span class="vip-stat-label"><?= $currentLang === 'th' ? 'แต้มสะสมพร้อมใช้' : 'Reward Points' ?></span>
                        <strong class="vip-stat-value text-gold-glow" id="vipPoints">0 แต้ม</strong>
                    </div>
                </div>

                <div class="vip-meta-since">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span><?= $currentLang === 'th' ? 'เป็นลูกค้าคนสำคัญกับเราตั้งแต่:' : 'Valued Customer Since:' ?> <strong id="vipMemberSince">--</strong></span>
                </div>
            </div>

            <!-- 2. 50 E-Stamp Incentive Banner -->
            <div class="vip-reward-banner mb-4" id="vipRewardBanner">
                <div class="vip-reward-glow"></div>
                <div class="vip-reward-content">
                    <div class="vip-reward-icon">🎁</div>
                    <div class="vip-reward-info">
                        <div class="vip-reward-pill">+50 E-Stamp สะสมพิเศษ</div>
                        <h3 class="vip-reward-headline">ร่วมตอบข้อมูลสั้นๆ 1 นาที รับฟรีทันที 50 แต้ม!</h3>
                        <p class="vip-reward-sub">เพื่อให้ทีมงานปรุงสูตรและดูแลมอบสิทธิพิเศษได้ตรงใจท่านที่สุด (แต้มใช้เป็นส่วนลดได้ทันที)</p>
                    </div>
                </div>
            </div>

            <!-- 3. Interactive CRM Questionnaire Form (Fast & Frictionless) -->
            <div class="vip-glass-card vip-form-card">
                <form id="vipProfileForm">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="c" value="<?= $c_code ?>">
                    <input type="hidden" name="token" value="<?= $token ?>">

                    <!-- Section 1: Contact & Personalization -->
                    <div class="vip-section-block">
                        <div class="vip-section-heading">
                            <span class="vip-section-badge">1</span>
                            <div>
                                <h3 class="vip-section-title">ข้อมูลเพื่อการติดต่อ &amp; การดูแลเป็นพิเศษ</h3>
                                <p class="vip-section-sub">เพื่อให้ทีมงานและผู้ปรุงยาหม่องทักทายและดูแลท่านได้อย่างอบอุ่น</p>
                            </div>
                        </div>

                        <div class="vip-form-row-2">
                            <div class="vip-field">
                                <label class="vip-label">ชื่อจริง *</label>
                                <input type="text" name="name" id="inp_name" class="vip-input" required placeholder="ชื่อจริง">
                            </div>
                            <div class="vip-field">
                                <label class="vip-label">นามสกุล</label>
                                <input type="text" name="lastname" id="inp_lastname" class="vip-input" placeholder="นามสกุล">
                            </div>
                        </div>

                        <div class="vip-form-row-2" style="margin-top: 14px;">
                            <div class="vip-field">
                                <label class="vip-label">ชื่อเล่น หรือคำที่สะดวกให้แอดมินเรียก</label>
                                <input type="text" name="nickname" id="inp_nickname" class="vip-input" placeholder="เช่น พี่นิด, หมออุไร, คุณแอน">
                                <span class="vip-input-hint">💡 ทีมงานจะใช้เรียกขานเวลาแชตดูแลท่านอย่างเป็นกันเอง</span>
                            </div>
                            <div class="vip-field">
                                <label class="vip-label">วัน-เดือนเกิด (เพื่อรับของขวัญวันเกิด 🎂)</label>
                                <input type="date" name="birthdate" id="inp_birthdate" class="vip-input">
                                <span class="vip-input-hint">🎁 รับของขวัญพิเศษและแต้มคูณสองในเดือนเกิดของท่าน</span>
                            </div>
                        </div>

                        <div class="vip-field" style="margin-top: 14px;">
                            <label class="vip-label">LINE ID สำหรับรับสิทธิพิเศษและโปรลับ</label>
                            <input type="text" name="line_id" id="inp_line_id" class="vip-input" placeholder="เช่น @line_id หรือเบอร์โทรศัพท์">
                            <span class="vip-input-hint">💬 สำหรับรับแจ้งเตือนแต้มสะสมและสิทธิประโยชน์ก่อนใคร</span>
                        </div>
                    </div>

                    <!-- Section 2: Usage / Business Type (Chip Selection) -->
                    <div class="vip-section-block">
                        <div class="vip-section-heading">
                            <span class="vip-section-badge">2</span>
                            <div>
                                <h3 class="vip-section-title">รูปแบบการใช้งานยาหม่อง / ธุรกิจของท่าน</h3>
                                <p class="vip-section-sub">ช่วยให้เราแนะนำเรทราคาส่งและโปรโมชั่นที่คุ้มค่าที่สุดสำหรับท่าน (แตะเลือก 1 ข้อ)</p>
                            </div>
                        </div>

                        <input type="hidden" name="business_type" id="inp_business_type" value="">
                        <div class="vip-chips-grid" id="businessTypeChips">
                            <button type="button" class="vip-chip-btn" data-value="ร้านนวดแผนไทย / สปา">💆‍♀️ ร้านนวดแผนไทย / สปา</button>
                            <button type="button" class="vip-chip-btn" data-value="หมอนวดอิสระ / ฟรีแลนซ์">🌿 หมอนวดอิสระ / รับงานนอกสถานที่</button>
                            <button type="button" class="vip-chip-btn" data-value="คลินิกกายภาพ / จัดกระดูก">🏥 คลินิกกายภาพ / จัดกระดูก</button>
                            <button type="button" class="vip-chip-btn" data-value="โรงแรม / รีสอร์ท">🏨 โรงแรม / รีสอร์ท</button>
                            <button type="button" class="vip-chip-btn" data-value="ซื้อใช้เองในครอบครัว">🏠 ใช้เองในบ้าน / ผู้สูงอายุ</button>
                            <button type="button" class="vip-chip-btn" data-value="ของฝาก / ของชำร่วย">🎁 ของฝาก / ของชำร่วยงานบุญ</button>
                        </div>

                        <div class="vip-form-row-2" style="margin-top: 16px;">
                            <div class="vip-field">
                                <label class="vip-label">ชื่อร้านนวด / สปา / คลินิก (ถ้ามี)</label>
                                <input type="text" name="shop_name" id="inp_shop_name" class="vip-input" placeholder="เช่น ยักษ์โพธิ์ สปา แอนด์ มาสสาจ">
                            </div>
                            <div class="vip-field">
                                <label class="vip-label">เว็บไซต์ / เพจ Facebook ร้าน (ถ้ามี)</label>
                                <input type="text" name="user_website" id="inp_user_website" class="vip-input" placeholder="เช่น facebook.com/yourspa">
                            </div>
                        </div>

                        <div class="vip-field" style="margin-top: 14px;">
                            <label class="vip-label">ที่อยู่ร้าน / สถานประกอบการ (เพื่อให้เราแนะนำลูกค้าในพื้นที่ให้รู้จักร้านท่าน)</label>
                            <textarea name="shop_address" id="inp_shop_address" class="vip-textarea" rows="2" placeholder="ระบุที่ตั้งร้านนวด / สปา ของท่าน..."></textarea>
                        </div>

                        <div class="vip-field" style="margin-top: 14px;">
                            <label class="vip-label">📍 ลิงก์แผนที่ร้าน Google Maps (วางลิงก์พิกัดร้าน เพื่อรับสิทธิพิเศษแนะนำร้านและแต้มโบนัส)</label>
                            <input type="url" name="user_google_map" id="inp_user_google_map" class="vip-input" placeholder="เช่น https://maps.app.goo.gl/... หรือ https://goo.gl/maps/...">
                            <span class="vip-input-hint">🗺️ นำลิงก์แชร์จากแอป Google Maps มาวางได้เลยค่ะ ข้อมูลนี้จะซิงก์ไปยังหน้าโปรไฟล์ของท่านด้วย</span>
                        </div>
                    </div>

                    <!-- Section 3: Favorite Aromas / Scents (Chip Multi-Selection) -->
                    <div class="vip-section-block">
                        <div class="vip-section-heading">
                            <span class="vip-section-badge">3</span>
                            <div>
                                <h3 class="vip-section-title">สูตรและกลิ่นสมุนไพรที่ท่านประทับใจเป็นพิเศษ</h3>
                                <p class="vip-section-sub">แตะเลือกกลิ่นที่ชอบ (เลือกได้หลายข้อ เพื่อเตรียมของแถมทดลองให้ตรงใจ)</p>
                            </div>
                        </div>

                        <input type="hidden" name="favorite_scents" id="inp_favorite_scents" value="">
                        <div class="vip-chips-grid" id="favoriteScentsChips">
                            <button type="button" class="vip-chip-btn multi" data-value="สูตรร้อน กลิ่นต้นตำรับ">🔥 สูตรร้อน กลิ่นต้นตำรับ (คลายเส้นลึก)</button>
                            <button type="button" class="vip-chip-btn multi" data-value="สูตรร้อน กลิ่นไพล">🌾 สูตรร้อน กลิ่นไพล (คลายเส้น ยอดนิยม)</button>
                            <button type="button" class="vip-chip-btn multi" data-value="สูตรเย็น เสลดพังพอน">❄️ สูตรเย็น เสลดพังพอน (แก้คัน ถอนพิษ)</button>
                            <button type="button" class="vip-chip-btn multi" data-value="สูตรเย็น ตะไคร้หอม">🍃 สูตรเย็น ตะไคร้หอม (สดชื่น ไล่ยุง)</button>
                            <button type="button" class="vip-chip-btn multi" data-value="สูตรนวดตัว อโรม่ามะลิ">🌸 สูตรนวดตัว อโรม่ามะลิ (หอมละมุน สปา)</button>
                            <button type="button" class="vip-chip-btn multi" data-value="สูตรนวดตัว ลาเวนเดอร์">💜 สูตรนวดตัว ลาเวนเดอร์ (หลับสบาย)</button>
                            <button type="button" class="vip-chip-btn multi" data-value="สูตรเย็น ยูคาลิปตัส">🌿 สูตรเย็น ยูคาลิปตัส (โล่งจมูก)</button>
                            <button type="button" class="vip-chip-btn multi" data-value="สูตรร้อน ขิงมินท์">⚡ สูตรร้อน ขิงมินท์ (อุ่นสบาย)</button>
                        </div>
                    </div>

                    <!-- Section 4: Feedback / Wishlist (Optional) -->
                    <div class="vip-section-block" style="border-bottom:none; margin-bottom:0; padding-bottom:0;">
                        <div class="vip-section-heading">
                            <span class="vip-section-badge">4</span>
                            <div>
                                <h3 class="vip-section-title">สิ่งที่อยากบอกทีมปรุงยาหม่องยักษ์โพธิ์ (ถ้ามี)</h3>
                                <p class="vip-section-sub">เช่น กลิ่นที่อยากให้ทำเพิ่ม หรือสิ่งที่ท่านอยากให้ปรับปรุง</p>
                            </div>
                        </div>
                        <textarea name="feedback" id="inp_feedback" class="vip-textarea" rows="2" placeholder="เช่น อยากให้มีกลิ่นส้มโอ, อยากได้ขวดหัวลูกกลิ้งแบบพกพา..."></textarea>
                    </div>

                    <!-- Submit Actions -->
                    <div class="vip-submit-wrap">
                        <button type="submit" class="vip-submit-btn" id="btnSaveVip">
                            <span class="vip-btn-icon">🎁</span>
                            <span class="vip-btn-text">บันทึกข้อมูล &amp; รับ 50 E-Stamp ทันที</span>
                        </button>
                        <p class="vip-submit-note">✓ รับแต้มสะสม 50 E-Stamp ทันทีหลังกดบันทึก</p>

                        <div class="mt-3 text-center">
                            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="vip-link-store">
                                <span>เข้าสู่หน้าร้านค้า เพื่อชมสินค้าทั้งหมด</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

        </div>

    </div>
</section>

<!-- Dedicated VIP Portal Interactive Styles -->


<script>
document.addEventListener("DOMContentLoaded", function() {
    const cCode = "<?= $c_code ?>";
    const token = "<?= $token ?>";
    const currentLang = "<?= $currentLang ?>";
    
    if (!cCode || !token) {
        document.getElementById("vipLoadingCard").classList.add("d-none");
        document.getElementById("vipErrorCard").classList.remove("d-none");
        return;
    }

    // Call API to validate
    fetch("<?= URL_PATH ?>/api/vip_customer.php?action=validate_token&c=" + encodeURIComponent(cCode) + "&token=" + encodeURIComponent(token))
        .then(res => res.json())
        .then(res => {
            document.getElementById("vipLoadingCard").classList.add("d-none");
            
            if (res.status !== 'success') {
                document.getElementById("vipErrorCard").classList.remove("d-none");
                if (res.message) {
                    document.getElementById("vipErrorMessage").textContent = res.message;
                }
                return;
            }

            // Populate data
            const cust = res.customer;
            document.getElementById("vipActiveCard").classList.remove("d-none");
            
            const fullName = (cust.name + " " + cust.lastname).trim() || (cust.nickname || "ท่านสมาชิกคนสำคัญ");
            document.getElementById("vipGreetingText").textContent = currentLang === 'th' 
                ? "ขอขอบพระคุณ " + fullName + " ที่ให้ความไว้วางใจในผลิตภัณฑ์สปาและสมุนไพรยักษ์โพธิ์มาโดยตลอด"
                : "Thank you, " + fullName + ", for your continued trust in Yak Pho Aroma therapeutic products.";

            document.getElementById("vipMemberCode").textContent = cust.c_code || cCode;
            document.getElementById("vipOrderCount").textContent = Number(cust.order_count || 0) + " ครั้ง";
            document.getElementById("vipPoints").textContent = Number(cust.points || 0) + " แต้ม";
            document.getElementById("vipMemberSince").textContent = cust.member_since || "--";

            // If already received bonus, update banner
            if (cust.already_rewarded) {
                const banner = document.getElementById("vipRewardBanner");
                if (banner) {
                    banner.innerHTML = `
                        <div class="vip-reward-content">
                            <div class="vip-reward-icon">✓</div>
                            <div class="vip-reward-info">
                                <div class="vip-reward-pill" style="background:#10B981;">สิทธิ์รับแต้มแล้ว</div>
                                <h3 class="vip-reward-headline">ท่านได้รับสิทธิ์ 50 E-Stamp พิเศษเรียบร้อยแล้วค่ะ</h3>
                                <p class="vip-reward-sub">สามารถอัปเดตข้อมูลเพิ่มเติมเพื่อรับของขวัญวันเกิดและสิทธิพิเศษใหม่ๆ ได้ตลอดเวลา</p>
                            </div>
                        </div>
                    `;
                }
                const btnSave = document.getElementById("btnSaveVip");
                if (btnSave) {
                    btnSave.querySelector(".vip-btn-text").textContent = "บันทึกอัปเดตข้อมูล VIP";
                }
            }

            // Pre-fill inputs
            document.getElementById("inp_name").value = cust.name || "";
            document.getElementById("inp_lastname").value = cust.lastname || "";
            document.getElementById("inp_nickname").value = cust.nickname || "";
            document.getElementById("inp_birthdate").value = cust.birthdate || "";
            document.getElementById("inp_line_id").value = cust.line_id || "";
            if (document.getElementById("inp_shop_name")) document.getElementById("inp_shop_name").value = cust.shop_name || "";
            if (document.getElementById("inp_shop_address")) document.getElementById("inp_shop_address").value = cust.shop_address || "";
            if (document.getElementById("inp_user_google_map")) document.getElementById("inp_user_google_map").value = cust.user_google_map || "";
            if (document.getElementById("inp_user_website")) document.getElementById("inp_user_website").value = cust.user_website || "";
            document.getElementById("inp_feedback").value = cust.feedback || "";

            // Pre-select Business Type chip
            if (cust.business_type) {
                document.getElementById("inp_business_type").value = cust.business_type;
                const bBtns = document.querySelectorAll("#businessTypeChips .vip-chip-btn");
                bBtns.forEach(btn => {
                    if (btn.dataset.value === cust.business_type) {
                        btn.classList.add("active");
                    }
                });
            }

            // Pre-select Favorite Scents chips
            if (Array.isArray(cust.favorite_scents) && cust.favorite_scents.length > 0) {
                document.getElementById("inp_favorite_scents").value = cust.favorite_scents.join(",");
                const sBtns = document.querySelectorAll("#favoriteScentsChips .vip-chip-btn");
                sBtns.forEach(btn => {
                    if (cust.favorite_scents.includes(btn.dataset.value)) {
                        btn.classList.add("active");
                    }
                });
            }
        })
        .catch(err => {
            document.getElementById("vipLoadingCard").classList.add("d-none");
            document.getElementById("vipErrorCard").classList.remove("d-none");
        });

    // 1. Single Select Chip for Business Type
    const bChips = document.querySelectorAll("#businessTypeChips .vip-chip-btn");
    bChips.forEach(btn => {
        btn.addEventListener("click", function() {
            bChips.forEach(b => b.classList.remove("active"));
            this.classList.add("active");
            document.getElementById("inp_business_type").value = this.dataset.value;
        });
    });

    // 2. Multi Select Chips for Favorite Scents
    const sChips = document.querySelectorAll("#favoriteScentsChips .vip-chip-btn");
    sChips.forEach(btn => {
        btn.addEventListener("click", function() {
            this.classList.toggle("active");
            const activeValues = [];
            document.querySelectorAll("#favoriteScentsChips .vip-chip-btn.active").forEach(ab => {
                activeValues.push(ab.dataset.value);
            });
            document.getElementById("inp_favorite_scents").value = activeValues.join(",");
        });
    });

    // 3. Handle Form Submit
    const form = document.getElementById("vipProfileForm");
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        const btn = document.getElementById("btnSaveVip");
        btn.disabled = true;
        const origText = btn.innerHTML;
        btn.innerHTML = '<span>กำลังบันทึกข้อมูล...</span>';

        const formData = new FormData(form);

        fetch("<?= URL_PATH ?>/api/vip_customer.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = origText;

            if (data.status === 'success') {
                if (data.new_total_points !== undefined) {
                    const ptsEl = document.getElementById("vipPoints");
                    if (ptsEl) ptsEl.textContent = data.new_total_points + " แต้ม";
                }

                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: data.awarded_bonus ? '🎉 รับฟรี 50 E-Stamp สำเร็จ!' : 'บันทึกสำเร็จ!',
                        text: data.message,
                        confirmButtonText: 'เยี่ยมเลย ขอบคุณค่ะ',
                        confirmButtonColor: '#0B3B35',
                        background: '#0B2016',
                        color: '#F8FAFC'
                    });
                } else {
                    alert(data.message);
                }
            } else {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message || 'ไม่สามารถบันทึกข้อมูลได้',
                        confirmButtonColor: '#0B3B35'
                    });
                } else {
                    alert(data.message);
                }
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = origText;
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
        });
    });
});
</script>
