<?php
$currentLang = $SITE['lang'] ?? 'th';
$scents = $HOME_DATA['scents'] ?? [];
?>

<!-- 1. PERSONALISED GIFT SETS HERO & 3 ARCHED CARDS (Yak Pho Spa Only) -->
<section class="section-pad bg-surface-secondary">
    <div class="anona-container">
        <div class="section-header-center" data-aos="fade-up">
            <span class="section-eyebrow">YAK PHO AROMA • SPA GIFT COLLECTIONS</span>
            <div class="section-script-title">Personalised Gift Sets</div>
            <h1 class="section-main-title">
                <?= $currentLang === 'th' ? 'ชุดของขวัญสมุนไพรยักษ์โพธิ์ & เซ็ตสปาพรีเมียม' : 'Yak Pho Personalised Thai Herbal & Spa Gift Sets' ?>
            </h1>
            <p class="section-subtitle">
                <?= $currentLang === 'th'
                    ? 'ส่งมอบความหอมผ่อนคลายจากธรรมชาติ (From Soil To Soul) ด้วยชุดของขวัญผลิตภัณฑ์ยักษ์โพธิ์ อโรม่า เลือกจับคู่กลิ่นและสั่งทำสายคาดชื่อหรือโลโก้ได้ตามต้องการ'
                    : 'Share the touch of Thai herbal wellness (From Soil To Soul) with custom-curated Yak Pho Aroma gift boxes.' ?>
            </p>
            <div class="section-divider-gold"></div>
        </div>

        <div class="arch-sets-grid">
            <div class="arch-gift-card" data-aos="fade-up" data-aos-delay="50">
                <div class="arch-image-box">
                    <img src="<?= URL_PATH ?>/catalog/assets/img/about1.webp" alt="Set A - Yak Pho Signature Trio">
                </div>
                <span class="arch-pill-label">SET A • YAK PHO SIGNATURE</span>
                <p class="arch-gift-desc">
                    <?= $currentLang === 'th'
                        ? 'ชุดของขวัญผู้บริหารและแขก VIP ประกอบด้วย บาล์มอโรม่าขวดแก้ว 40g, น้ำมันสกัดสมุนไพรยักษ์โพธิ์ 30ml และอโรม่าลูกกลิ้งเย็นสดชื่น 8ml บรรจุกล่องของขวัญหรูหรา'
                        : 'Executive VIP Gift Box featuring a 40g Yak Pho aroma balm, 30ml concentrated herbal oil, and 8ml refreshing roller oil.' ?>
                </p>
                <a href="#gift-inquiry-form" class="btn-anona-primary">
                    <span><?= $currentLang === 'th' ? 'สั่งจัดเซ็ตนี้ / ขอใบเสนอราคา' : 'Customize Set A' ?></span>
                </a>
            </div>

            <div class="arch-gift-card" data-aos="fade-up" data-aos-delay="100">
                <div class="arch-image-box">
                    <img src="<?= URL_PATH ?>/catalog/assets/img/about2.webp" alt="Set B - 12 Scents Thai Souvenir">
                </div>
                <span class="arch-pill-label">SET B • 12 SCENTS GALLERY</span>
                <p class="arch-gift-desc">
                    <?= $currentLang === 'th'
                        ? 'ชุดรวม 12 กลิ่นซิกเนเจอร์ ยักษ์โพธิ์ (ขนาด 15g หรือ 40g) ของฝากจากประเทศไทยยอดนิยมสำหรับชาวต่างชาติ และชุดเทสเตอร์ประจำเคาน์เตอร์ต้อนรับร้านนวดสปา'
                        : 'Complete 12-scent Yak Pho collection—perfect as luxury Thai souvenirs for international visitors or spa reception scent bars.' ?>
                </p>
                <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products?size=40g" class="btn-anona-primary">
                    <span><?= $currentLang === 'th' ? 'เลือกซื้อขนาด 40g รายขวด' : 'Shop 40g Jars' ?></span>
                </a>
            </div>

            <div class="arch-gift-card" data-aos="fade-up" data-aos-delay="150">
                <div class="arch-image-box">
                    <img src="<?= URL_PATH ?>/catalog/assets/img/tarragon.webp" alt="Set C - Wedding & Corporate Souvenirs">
                </div>
                <span class="arch-pill-label">SET C • SOUVENIRS &amp; EVENTS</span>
                <p class="arch-gift-desc">
                    <?= $currentLang === 'th'
                        ? 'ของชำร่วยงานมงคล งานเกษียณอายุ และของที่ระลึกองค์กร (ยักษ์โพธิ์ 8ml / 15g / 40g) พร้อมบริการออกแบบฉลากชื่อหรือโลโก้ฟรีเมื่อสั่งครบจำนวน'
                        : 'Bespoke event favors and corporate wellness giveaways (8ml / 15g / 40g) with complimentary custom label design.' ?>
                </p>
                <a href="#gift-inquiry-form" class="btn-anona-primary">
                    <span><?= $currentLang === 'th' ? 'ปรึกษาทีมออกแบบของขวัญ' : 'Request Custom Label' ?></span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. MIX & MATCH 12 SCENTS PALETTE -->
<section class="section-pad bg-surface-primary">
    <div class="anona-container">
        <div class="section-header-center" data-aos="fade-up">
            <span class="section-eyebrow">MIX &amp; MATCH AROMAS</span>
            <div class="section-script-title">12 Signature Scents</div>
            <h2 class="section-main-title">
                <?= $currentLang === 'th' ? 'เลือกจับคู่ 12 กลิ่นหอมเอกลักษณ์สำหรับชุดของขวัญยักษ์โพธิ์' : 'Choose From 12 Signature Scents for Your Gift Box' ?>
            </h2>
            <div class="section-divider-gold"></div>
        </div>

        <div class="scents-grid-12">
            <?php foreach ($scents as $sItem): ?>
                <div class="scent-item-card" data-aos="fade-up">
                    <img src="<?= htmlspecialchars($sItem['image_url']) ?>" alt="<?= htmlspecialchars($sItem['title']) ?>" class="scent-thumb-circle" loading="lazy">
                    <div class="scent-info-col">
                        <h4><?= htmlspecialchars($sItem['title']) ?></h4>
                        <p><?= htmlspecialchars($sItem['subtitle']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 3. CUSTOM GIFT SET INQUIRY FORM -->
<section class="section-pad bg-surface-secondary" id="gift-inquiry-form">
    <div class="anona-container-narrow">
        <div class="checkout-card-box" data-aos="fade-up">
            <div class="section-header-center">
                <span class="section-eyebrow">CUSTOM ORDER &amp; QUOTATION</span>
                <h2 class="section-main-title">
                    <?= $currentLang === 'th' ? 'แจ้งความต้องการจัดชุดของขวัญ / ของชำร่วย ยักษ์โพธิ์' : 'Request a Personalised Yak Pho Gift Set Quotation' ?>
                </h2>
                <p class="section-subtitle">
                    <?= $currentLang === 'th'
                        ? 'กรอกรายละเอียดด้านล่าง ทีมงานของเราจะจัดทำใบเสนอราคาและตัวอย่างแบบฉลากส่งให้พิจารณาอย่างรวดเร็ว'
                        : 'Share your event details and quantity below. Our gifting specialists will prepare a tailored proposal.' ?>
                </p>
            </div>

            <form class="js-inquiry-form">
                <input type="hidden" name="subject" value="Yak Pho Personalised Gift Set Inquiry">
                <div class="grid-2-cols">
                    <div class="form-group-anona">
                        <label class="form-label-anona"><?= $currentLang === 'th' ? 'ชื่อ - นามสกุล / ชื่อหน่วยงาน *' : 'Full Name / Organization *' ?></label>
                        <input type="text" name="name" class="form-input-anona" required>
                    </div>
                    <div class="form-group-anona">
                        <label class="form-label-anona"><?= $currentLang === 'th' ? 'เบอร์โทรศัพท์ / LINE ID *' : 'Phone Number / LINE / WhatsApp *' ?></label>
                        <input type="text" name="phone" class="form-input-anona" required>
                    </div>
                </div>
                <div class="form-group-anona">
                    <label class="form-label-anona"><?= $currentLang === 'th' ? 'อีเมล (ถ้ามี)' : 'Email Address' ?></label>
                    <input type="email" name="email" class="form-input-anona">
                </div>
                <div class="form-group-anona">
                    <label class="form-label-anona"><?= $currentLang === 'th' ? 'รายละเอียดชุดของขวัญ จำนวนที่ต้องการ และงบประมาณโดยประมาณ *' : 'Preferred Gift Set, Quantity & Target Date *' ?></label>
                    <textarea name="message" rows="4" class="form-textarea-anona" required
                              placeholder="<?= $currentLang === 'th' ? 'เช่น สนใจ Set A จำนวน 100 ชุด พร้อมพิมพ์โลโก้...' : 'e.g., 100 boxes of Set A with custom logo ribbon...' ?>"></textarea>
                </div>
                <button type="submit" class="btn-anona-primary btn-block-full">
                    <i data-lucide="send" width="17" height="17"></i>
                    <span><?= $currentLang === 'th' ? 'ส่งข้อมูลขอใบเสนอราคาชุดของขวัญ' : 'Submit Gift Set Inquiry' ?></span>
                </button>
            </form>
        </div>
    </div>
</section>
