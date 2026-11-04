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
            <a href="https://line.me/ti/p/~@yakpho" target="_blank" rel="noopener" class="vip-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                <span><?= $currentLang === 'th' ? 'ติดต่อแอดมินเพื่อขอรับลิงก์ใหม่' : 'Contact Support via LINE' ?></span>
            </a>
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
<style>
.vip-portal-section {
    padding: 30px 14px 60px;
    background: radial-gradient(circle at 50% 10%, rgba(13, 40, 24, 0.6) 0%, rgba(5, 20, 14, 0.98) 100%);
    min-height: 85vh;
}
.vip-container {
    max-width: 680px;
    margin: 0 auto;
}
.vip-glass-card {
    background: rgba(11, 32, 22, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(212, 175, 55, 0.25);
    border-radius: 16px;
    padding: 24px 20px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
}
.vip-brand-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(212, 175, 55, 0.15);
    color: #F59E0B;
    border: 1px solid rgba(212, 175, 55, 0.3);
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}
.vip-main-title {
    font-size: 1.45rem;
    font-weight: 700;
    color: #F8FAFC;
    margin-bottom: 8px;
}
.vip-greeting-text {
    font-size: 0.88rem;
    color: #94A3B8;
    line-height: 1.5;
}
.vip-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}
.vip-stat-box {
    background: rgba(7, 25, 17, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 12px 8px;
    text-align: center;
}
.vip-stat-highlight {
    border-color: rgba(245, 158, 11, 0.4);
    background: rgba(245, 158, 11, 0.08);
}
.vip-stat-label {
    display: block;
    font-size: 0.7rem;
    color: #94A3B8;
    margin-bottom: 4px;
}
.vip-stat-value {
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1.2;
}
.text-gold {
    color: #F59E0B;
}
.text-emerald {
    color: #10B981;
}
.text-gold-glow {
    color: #FBBF24;
    text-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
}
.vip-meta-since {
    font-size: 0.78rem;
    color: #64748B;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
}
.vip-meta-since strong {
    color: #CBD5E1;
}

/* Reward Banner */
.vip-reward-banner {
    position: relative;
    background: linear-gradient(135deg, rgba(180, 83, 9, 0.35) 0%, rgba(13, 40, 24, 0.9) 100%);
    border: 1px solid rgba(245, 158, 11, 0.45);
    border-radius: 16px;
    padding: 18px 20px;
    box-shadow: 0 8px 24px rgba(180, 83, 9, 0.2);
    overflow: hidden;
}
.vip-reward-glow {
    position: absolute;
    top: -20px;
    right: -20px;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(245, 158, 11, 0.3) 0%, transparent 70%);
    pointer-events: none;
}
.vip-reward-content {
    display: flex;
    align-items: center;
    gap: 14px;
    position: relative;
    z-index: 1;
}
.vip-reward-icon {
    font-size: 2.2rem;
    line-height: 1;
    flex-shrink: 0;
}
.vip-reward-info {
    flex: 1;
}
.vip-reward-pill {
    display: inline-block;
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: #FFFFFF;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 2px 10px;
    border-radius: 12px;
    margin-bottom: 4px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
}
.vip-reward-headline {
    font-size: 1.05rem;
    font-weight: 700;
    color: #F8FAFC;
    margin: 0 0 4px 0;
}
.vip-reward-sub {
    font-size: 0.8rem;
    color: #CBD5E1;
    margin: 0;
    line-height: 1.4;
}

/* Sections & Fields */
.vip-section-block {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 22px;
    margin-bottom: 22px;
}
.vip-section-heading {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}
.vip-section-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgba(245, 158, 11, 0.2);
    color: #F59E0B;
    border: 1px solid rgba(245, 158, 11, 0.4);
    font-size: 0.8rem;
    font-weight: 800;
    flex-shrink: 0;
    margin-top: 2px;
}
.vip-section-title {
    font-size: 0.98rem;
    font-weight: 700;
    color: #F1F5F9;
    margin: 0 0 2px 0;
}
.vip-section-sub {
    font-size: 0.78rem;
    color: #94A3B8;
    margin: 0;
}
.vip-form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
@media (max-width: 540px) {
    .vip-form-row-2 {
        grid-template-columns: 1fr;
    }
    .vip-stats-grid {
        grid-template-columns: 1fr 1fr 1fr;
        gap: 6px;
    }
    .vip-stat-box {
        padding: 8px 4px;
    }
    .vip-stat-value {
        font-size: 0.98rem;
    }
}
.vip-field {
    display: flex;
    flex-direction: column;
}
.vip-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #CBD5E1;
    margin-bottom: 6px;
}
.vip-input-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.vip-input, .vip-textarea {
    width: 100%;
    background: rgba(7, 25, 17, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.88rem;
    color: #F8FAFC;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}
.vip-input:focus, .vip-textarea:focus {
    border-color: #F59E0B;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    background: rgba(11, 35, 24, 0.95);
}
.vip-input-hint {
    font-size: 0.72rem;
    color: #64748B;
    margin-top: 4px;
    line-height: 1.4;
}

/* Chips Grid */
.vip-chips-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.vip-chip-btn {
    background: rgba(7, 25, 17, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 20px;
    padding: 8px 14px;
    font-size: 0.82rem;
    font-weight: 500;
    color: #CBD5E1;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    user-select: none;
}
.vip-chip-btn:hover {
    border-color: rgba(245, 158, 11, 0.4);
    color: #FFFFFF;
}
.vip-chip-btn.active {
    background: rgba(245, 158, 11, 0.18);
    border-color: #F59E0B;
    color: #FBBF24;
    font-weight: 700;
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.25);
}

/* Submit Area */
.vip-submit-wrap {
    text-align: center;
    padding-top: 24px;
}
.vip-submit-btn {
    width: 100%;
    max-width: 420px;
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
    color: #FFFFFF;
    border: none;
    border-radius: 30px;
    padding: 14px 28px;
    font-size: 1.02rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(217, 119, 6, 0.4);
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.vip-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(217, 119, 6, 0.55);
}
.vip-submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.vip-submit-note {
    font-size: 0.78rem;
    color: #10B981;
    font-weight: 600;
    margin: 8px 0 0 0;
}
.vip-link-store {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #94A3B8;
    font-size: 0.84rem;
    text-decoration: none;
    transition: color 0.2s ease;
}
.vip-link-store:hover {
    color: #F59E0B;
}

/* Spinner */
.vip-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(245, 158, 11, 0.2);
    border-top-color: #F59E0B;
    border-radius: 50%;
    animation: vipSpin 0.8s linear infinite;
    margin: 0 auto;
}
@keyframes vipSpin {
    to { transform: rotate(360deg); }
}
</style>

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
