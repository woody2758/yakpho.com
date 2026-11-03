<?php
$currentLang = $SITE['lang'] ?? 'th';
$contactInfo = $SITE['contact'] ?? [];
$brandStory = $HOME_DATA['brand_story'] ?? [];
?>

<div class="yp-container content-page-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="content-breadcrumb">
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>"><?= $currentLang === 'th' ? 'หน้าแรก' : 'Home' ?></a>
        <span>/</span>
        <span><?= $currentLang === 'th' ? 'เรื่องราวแบรนด์ยักษ์โพธิ์' : 'Yak Pho Brand Story' ?></span>
    </nav>

    <!-- 1. Hero Brand Card -->
    <div class="content-page-hero">
        <span class="content-tag-gold">YAK PHO BRAND STORY • GUARDIAN OF WELLNESS</span>
        <h1 class="content-page-title">
            <?= htmlspecialchars($brandStory['title'] ?: 'เรื่องราวแบรนด์ ยักษ์โพธิ์ อโรม่า (Yak Pho Aroma)') ?>
        </h1>
        <p class="content-page-desc">
            <?= htmlspecialchars($brandStory['subtitle'] ?: 'Time-Honored Wisdom from the Purest Soil — ภูมิปัญญาอันทรงคุณค่า... จากผืนดินบริสุทธิ์') ?>
        </p>
    </div>

    <!-- 2. Heritage & Concept Story -->
    <div class="about-story-card">
        <div class="about-story-grid">
            <div>
                <span class="content-tag-gold">FROM SOIL TO SOUL</span>
                <h2 class="content-page-title">
                    <?= $currentLang === 'th' ? 'ตำนานผู้พิทักษ์ความปวดเมื่อย สู่ศาสตร์ความผ่อนคลายระดับสากล' : 'Guardian of Thai Massage & Soul Relief' ?>
                </h2>
                <div class="content-page-desc mb-3">
                    <?= !empty($brandStory['html_content']) ? $brandStory['html_content'] : '
                    <p>“ยักษ์โพธิ์” กำเนิดขึ้นจากความตั้งใจในการอนุรักษ์และต่อยอดภูมิปัญญาการนวดแผนไทยโบราณที่สืบทอดกันมากว่า 40 ปี ผสานเข้ากับศาสตร์เครื่องหอมและนวัตกรรมการสกัดน้ำมันธรรมชาติจาก บริษัท เอสเธอร์ อโรม่า จำกัด</p>
                    <p>สัญลักษณ์ “ยักษ์” ในคติความเชื่อไทยโบราณ คือผู้พิทักษ์ปกป้องสิ่งศักดิ์สิทธิ์และขจัดปัดเป่าสิ่งไม่ดี “ยักษ์โพธิ์” จึงเปรียบเสมือน <strong>“ผู้พิทักษ์ความปวดเมื่อย”</strong> ที่คอยปกป้องร่างกายของคุณจากความเหนื่อยล้า ความตึงเครียดสะสม และคืนสมดุลให้ร่างกายและจิตใจกลับมาสดชื่นมีพลังอีกครั้ง</p>
                    <p>เราคัดสรรสมุนไพรสดแท้จากแหล่งปลูกที่สะอาด ปราศจากสารเคมี ผสานกับ Tarragon Infused Oil สูตรลับเฉพาะ เพื่อให้ได้เนื้อบาล์มที่นุ่มละมุน ซึมลึก ไม่เหนียวเหนอะหนะ และมีกลิ่นหอมผ่อนคลายอย่างแท้จริง</p>' ?>
                </div>

                <div class="d-flex gap-2 flex-wrap mt-3">
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="profile-btn-sm-primary">
                        <?= $currentLang === 'th' ? 'เลือกชมบาล์มยักษ์โพธิ์ ›' : 'Shop Balms ›' ?>
                    </a>
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/gallery" class="profile-btn-sm-outline">
                        <?= $currentLang === 'th' ? 'ดูภาพบรรยากาศแกลเลอรี' : 'View Gallery' ?>
                    </a>
                </div>
            </div>

            <div>
                <img src="<?= !empty($brandStory['image_url']) ? htmlspecialchars($brandStory['image_url']) : (URL_PATH . '/uploads/yakpho/brand_story.webp') ?>" 
                     alt="Yak Pho Brand Story" 
                     class="about-story-img"
                     loading="lazy"
                     onerror="this.onerror=null; this.src='<?= URL_PATH ?>/uploads/yakpho/brand_story.webp';">
            </div>
        </div>
    </div>

    <!-- 3. The 3 Brand Pillars -->
    <div class="content-page-hero">
        <span class="content-tag-gold">THE 3 PILLARS OF YAK PHO</span>
        <h2 class="content-page-title">
            <?= $currentLang === 'th' ? '3 หัวใจหลักของยักษ์โพธิ์' : 'Our 3 Guiding Principles' ?>
        </h2>
        <div class="about-cat-grid">
            <div class="about-cat-card">
                <span class="content-tag-gold">01 • PURITY (บริสุทธิ์)</span>
                <h3 class="article-title">สมุนไพรธรรมชาติแท้ 100%</h3>
                <p class="content-page-desc">
                    คัดสรรวัตถุดิบสมุนไพรเกรดพรีเมียมจากแหล่งปลูกออร์แกนิก ปราศจากสารสเตียรอยด์และสารเคมีตกค้าง ปลอดภัยต่อทั้งผู้รับบริการและผู้ให้บริการนวด
                </p>
            </div>

            <div class="about-cat-card">
                <span class="content-tag-gold">02 • EFFICACY (ทรงพลัง)</span>
                <h3 class="article-title">ซึมซาบลึก คลายเมื่อยตรงจุด</h3>
                <p class="content-page-desc">
                    ด้วยนวัตกรรม Tarragon Infused Oil ช่วยนำพาสารสำคัญเข้าสู่ชั้นกล้ามเนื้อได้อย่างรวดเร็ว ให้ความอบอุ่นลึกยาวนานโดยไม่ระคายเคืองผิว
                </p>
            </div>

            <div class="about-cat-card">
                <span class="content-tag-gold">03 • GLOBAL STANDARD (มาตรฐานสากล)</span>
                <h3 class="article-title">พิถีพิถันทุกขั้นตอนการปรุงสูตร</h3>
                <p class="content-page-desc">
                    ปรุงสูตรด้วยความสะอาด ปลอดภัย คัดสรรสมุนไพรธรรมชาติแท้เกรดพรีเมียม บรรจุถุงซีลสุญญากาศสดใหม่ทุกออเดอร์ เพื่อการใช้งานในร้านนวดและสปาชั้นนำ
                </p>
            </div>
        </div>
    </div>
</div>

