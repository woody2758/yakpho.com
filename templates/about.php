<?php
$currentLang = $SITE['lang'] ?? 'th';
$contactInfo = $SITE['contact'] ?? [];
$brandStory = $HOME_DATA['brand_story'] ?? [];
$formulas = $HOME_DATA['formulas'] ?? [];
?>

<div class="yp-container content-page-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="content-breadcrumb">
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>"><?= $currentLang === 'th' ? 'หน้าแรก' : 'Home' ?></a>
        <span>/</span>
        <span><?= $currentLang === 'th' ? 'เกี่ยวกับเรา (About Us)' : 'About Us' ?></span>
    </nav>

    <!-- 1. Heritage Hero Card -->
    <div class="content-page-hero">
        <span class="content-tag-gold">
            <?= htmlspecialchars($contactInfo['company_name'] ?? 'ESTHER AROMA CO., LTD.') ?>
        </span>
        <h1 class="content-page-title">
            <?= htmlspecialchars($contactInfo['about_title'] ?: ($brandStory['title'] ?? 'YakPho: ศิลปะแห่งการบำบัด... ผสานความลับจาก Tarragon Infused Oil')) ?>
        </h1>
        <p class="content-page-desc">
            <?= $currentLang === 'th'
                ? 'ภายใต้การวิจัยและพัฒนาโดย บริษัท เอสเธอร์ อโรม่า จำกัด (Esther Aroma Co., Ltd.) เรายกระดับภูมิปัญญาสมุนไพรไทยสู่ผลิตภัณฑ์เพื่อการฟื้นฟูร่างกายและสปาอโรม่า “ยักษ์โพธิ์ (Yak Pho Aroma)” บาล์มนวดสมุนไพรและน้ำมันนวดสูตรเข้มข้นที่ร้านนวดไทยและสปาชั้นนำกว่า 10 ประเทศทั่วโลกให้ความไว้วางใจ'
                : 'Developed by Esther Aroma Co., Ltd., Yak Pho Aroma crafts concentrated Thai herbal massage balms and therapeutic spa oils trusted across more than 10 countries worldwide.' ?>
        </p>

        <!-- Signature Badges Row -->
        <div class="about-badges-row">
            <span class="about-badge-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                <?= $currentLang === 'th' ? 'มาตรฐานส่งออก GMP' : 'GMP Certified' ?>
            </span>
            <span class="about-badge-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2a10 10 0 1 0 10 10H12V2z"></path></svg>
                <?= $currentLang === 'th' ? 'สูตรลับ Tarragon Infused Oil' : 'Tarragon Infused Secret' ?>
            </span>
            <span class="about-badge-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                <?= $currentLang === 'th' ? 'ส่งออก 10+ ประเทศทั่วโลก' : 'Exporting to 10+ Countries' ?>
            </span>
            <span class="about-badge-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
                <?= $currentLang === 'th' ? 'สมุนไพรธรรมชาติ 100%' : '100% Pure Herbs' ?>
            </span>
        </div>
    </div>

    <!-- 2. The Secret Harmonizer Section -->
    <div class="about-story-card">
        <div class="about-story-grid">
            <div>
                <span class="content-tag-gold">THE SECRET HARMONIZER</span>
                <h2 class="content-page-title">
                    <?= $currentLang === 'th' ? 'ความลับจาก “Tarragon Infused Oil” ผสานสมุนไพรไทยคัดพิเศษ' : 'The Secret of Tarragon Infused Oil' ?>
                </h2>
                <div class="content-page-desc mb-3">
                    <?= !empty($contactInfo['about_html']) ? $contactInfo['about_html'] : '
                    <p>ทุกสูตรของยักษ์โพธิ์ผสาน “Tarragon Infused Oil” (น้ำมันสกัดทาร์รากอน) สูตรลับเฉพาะจากเอสเธอร์ ทาร์รากอนฟาร์ม ทำหน้าที่เป็นตัวประสาน (Harmonizer) เชื่อมโยงสมุนไพรไทยอย่าง ไพล เสลดพังพอน และหญ้าเอ็นยืด เข้ากับความหอมละมุนระดับสากล มอบสัมผัสที่ลึกซึ้งและผ่อนคลายในทุกการนวด</p>' ?>
                </div>

                <div class="d-flex gap-2 flex-wrap mt-3">
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="profile-btn-sm-primary">
                        <?= $currentLang === 'th' ? 'เลือกชมสินค้าทั้งหมด ›' : 'Explore All Products ›' ?>
                    </a>
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/corporate-gifts" class="profile-btn-sm-outline">
                        <?= $currentLang === 'th' ? 'เรทราคาส่งสปา 1 กก.' : 'Spa Wholesale 1kg' ?>
                    </a>
                </div>
            </div>

            <div>
                <img src="<?= URL_PATH ?>/uploads/yakpho/<?= !empty($contactInfo['about_image']) ? htmlspecialchars($contactInfo['about_image']) : 'tarragon.webp' ?>" 
                     alt="Tarragon Farm & Thai Herbal" 
                     class="about-story-img"
                     loading="lazy"
                     onerror="this.onerror=null; this.src='<?= URL_PATH ?>/uploads/yakpho/tarragon.webp';">
            </div>
        </div>
    </div>

    <!-- 3. Core Product Categories -->
    <div class="content-page-hero">
        <span class="content-tag-gold">PRODUCT CATEGORIES</span>
        <h2 class="content-page-title">
            <?= $currentLang === 'th' ? '4 กลุ่มผลิตภัณฑ์สปาและสมุนไพร ยักษ์โพธิ์' : '4 Core Spa & Wellness Collections' ?>
        </h2>
        <div class="about-cat-grid">
            <div class="about-cat-card">
                <span class="content-tag-gold">01 • PROFESSIONAL 1 KG</span>
                <h3 class="article-title">บาล์มนวดถุงรีฟิล 1 กก.</h3>
                <p class="content-page-desc">
                    บาล์มนวดสมุนไพรถุงซีลสุญญากาศ 1 กก. ครบทั้งสูตรร้อน สูตรเย็น และสูตรนวดตัว (12 กลิ่น) สำหรับร้านนวดไทยและสปา
                </p>
            </div>

            <div class="about-cat-card">
                <span class="content-tag-gold">02 • RETAIL 40G &amp; 15G</span>
                <h3 class="article-title">บาล์มอโรม่าขวดพกพา</h3>
                <p class="content-page-desc">
                    บาล์มอโรม่าขนาดพกพา 40 กรัม และ 15 กรัม สำหรับใช้บรรเทาอาการปวดเมื่อยในชีวิตประจำวัน หรือวางจำหน่ายหน้าร้านสปา
                </p>
            </div>

            <div class="about-cat-card">
                <span class="content-tag-gold">03 • MASSAGE &amp; EXTRACT OILS</span>
                <h3 class="article-title">น้ำมันนวด &amp; น้ำมันสกัดสมุนไพร</h3>
                <p class="content-page-desc">
                    น้ำมันนวดยักษ์โพธิ์อโรม่า และน้ำมันสกัดสมุนไพรเข้มข้น ซึมซาบเร็ว บำรุงผิวและคลายกล้ามเนื้ออย่างล้ำลึก
                </p>
            </div>

            <div class="about-cat-card">
                <span class="content-tag-gold">04 • ROLLER &amp; FOOT CARE</span>
                <h3 class="article-title">ลูกกลิ้งอโรม่า &amp; ครีมนวดเท้า</h3>
                <p class="content-page-desc">
                    ยักษ์โพธิ์ อโรม่าเย็นสดชื่นแบบลูกกลิ้ง 8 มล. พกพาสะดวก และครีมนวดเท้าสมุนไพรสูตรเข้มข้น 1 กก. สำหรับสปา
                </p>
            </div>
        </div>
    </div>
</div>
