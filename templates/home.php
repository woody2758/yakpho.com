<?php
$currentLang = $SITE['lang'] ?? 'th';
$heroSlides = $HOME_DATA['hero_slides'] ?? [];
if (empty($heroSlides)) {
    $heroSlides = [
        [
            'slide_title' => $currentLang === 'th' ? 'ยาหม่องกิโล ยาหม่องถุง บาล์มนวดสปาอโรม่า' : 'Thai Herbal Massage Balms & Spa Aromatherapy',
            'slide_subtitle' => $currentLang === 'th' ? 'สมุนไพรไทยแท้ ปรุงสดใหม่ทุกออเดอร์ เรทส่งร้านนวด 6 กก. เพียง ฿590.-/กก.' : 'Authentic Thai Herbs. Wholesale from 6 kg at ฿590/kg.',
            'image_url' => URL_PATH . '/catalog/assets/img/brand_story.webp',
            'badge' => 'Factory Direct',
            'link' => URL_PATH . '/' . $currentLang . '/corporate-gifts',
            'btn_text' => 'สั่งซื้อราคาส่งร้านนวด'
        ],
        [
            'slide_title' => $currentLang === 'th' ? '3 สูตรลับ 12 กลิ่นซิกเนเจอร์ ผสาน Tarragon' : '3 Core Formulas & 12 Signature Scents',
            'slide_subtitle' => $currentLang === 'th' ? 'สูตรร้อน พริก-ไพล, สูตรเย็น ยูคาลิปตัส, สูตรนวดตัว อโรม่าสปา ลื่นมือ ซึมไว' : 'Infused with Tarragon Extract & pure botanical essential oils.',
            'image_url' => URL_PATH . '/catalog/assets/img/best_h001.webp',
            'badge' => 'Signature Blend',
            'link' => URL_PATH . '/' . $currentLang . '/products',
            'btn_text' => 'เลือกชมสินค้า'
        ]
    ];
}
$featuredProducts = $HOME_DATA['featured_products'] ?? [];
$formulas = $HOME_DATA['formulas'] ?? [];
$pricingTiers = $HOME_DATA['pricing_tiers'] ?? [];
?>

<!-- ============================================================
     1. FULL-WIDTH HERO TOUCH CAROUSEL (ภาพเต็มจอ)
     ============================================================ -->
<section class="hero-slider-section hero-fullwidth">
    <div class="hero-slider-wrap" id="heroSliderWrap">
        <div class="hero-slider-track" id="heroSliderTrack">
            <?php foreach ($heroSlides as $idx => $slide): ?>
                <div class="hero-slide-item">
                    <div class="hero-slide-inner yp-container">
                        <div class="hero-slide-content">
                            <span class="hero-slide-tag"><?= htmlspecialchars($slide['badge'] ?? 'Thai Herbal Craft') ?></span>
                            <h1 class="hero-slide-title"><?= htmlspecialchars($slide['slide_title'] ?? $slide['title'] ?? '') ?></h1>
                            <p class="hero-slide-desc"><?= htmlspecialchars($slide['slide_subtitle'] ?? $slide['subtitle'] ?? '') ?></p>
                            <a href="<?= htmlspecialchars($slide['link'] ?? (URL_PATH . '/' . $currentLang . '/products')) ?>" class="hero-slide-btn">
                                <span><?= htmlspecialchars($slide['btn_text'] ?? 'สั่งซื้อทันที') ?></span>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        </div>
                    </div>
                    <div class="hero-slide-visual">
                        <img src="<?= htmlspecialchars($slide['image_url'] ?? (URL_PATH . '/catalog/assets/img/brand_story.webp')) ?>" alt="Hero Banner" width="800" height="400" <?= $idx === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Pagination Dots -->
        <div class="hero-dots-wrap" id="heroDotsWrap">
            <?php foreach ($heroSlides as $idx => $slide): ?>
                <div class="hero-dot <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>"></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="yp-container" style="padding-top: 20px;">
    <!-- ============================================================
         5. HIGHLIGHT LIVE FEED / 3 SIGNATURE FORMULAS (Thaimart style)
         ============================================================ -->
    <div class="section-headline">
        <h2 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-accent-red"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg>
            <span>3 สูตรยอดนิยม มาตรฐานร้านสปา</span>
        </h2>
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="section-more-link">
            <span>ดูทั้งหมด</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </div>

    <div class="highlight-scroll-cards">
        <div class="highlight-card">
            <div class="highlight-img-wrap">
                <img src="<?= URL_PATH ?>/catalog/assets/img/best_h001.webp" alt="สูตรร้อน พริก-ไพล" width="220" height="138" loading="lazy">
                <div class="highlight-live-pill">
                    <span class="highlight-live-dot"></span>
                    <span>สูตรเข้มข้น</span>
                </div>
            </div>
            <div class="highlight-info">
                <div class="highlight-title">สูตรร้อน พริก-ไพล (1 กก.)</div>
                <div class="highlight-sub">คลายปวดเมื่อยลึก คลายเส้นตึง ออฟฟิศซินโดรม ร้อนลึกยาวนาน</div>
            </div>
        </div>

        <div class="highlight-card">
            <div class="highlight-img-wrap">
                <img src="<?= URL_PATH ?>/catalog/assets/img/best_h002.webp" alt="สูตรเย็น ยูคาลิปตัส" width="220" height="138" loading="lazy">
                <div class="highlight-live-pill highlight-pill-primary">
                    <span>สูตรผ่อนคลาย</span>
                </div>
            </div>
            <div class="highlight-info">
                <div class="highlight-title">สูตรเย็น ยูคาลิปตัส (1 กก.)</div>
                <div class="highlight-sub">ผ่อนคลายกล้ามเนื้อ สดชื่นเบาสบาย หายใจโล่ง อโรม่าสปา</div>
            </div>
        </div>

        <div class="highlight-card">
            <div class="highlight-img-wrap">
                <img src="<?= URL_PATH ?>/catalog/assets/img/best_h003.webp" alt="สูตรนวดตัว อโรม่า" width="220" height="138" loading="lazy">
                <div class="highlight-live-pill highlight-pill-gold">
                    <span>สูตรซิกเนเจอร์</span>
                </div>
            </div>
            <div class="highlight-info">
                <div class="highlight-title">สูตรนวดตัว อโรม่า (1 กก.)</div>
                <div class="highlight-sub">ลื่นมือ นวดง่าย ซึมไว ไม่เหนอะหนะ บำรุงผิวนุ่มชุ่มชื้น</div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         6. PRODUCT CATALOG FEED (2 Columns Mobile, 4 Columns Desktop)
         ============================================================ -->
    <div class="section-headline">
        <h2 class="section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-accent-gold"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            <span>สินค้าแนะนำ &amp; ขายดี</span>
        </h2>
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="section-more-link">
            <span>ดูทั้งหมด (<?= count($featuredProducts) ?>+)</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </div>

    <div class="products-grid-2col">
        <?php foreach ($featuredProducts as $idx => $prod): ?>
            <?php
            $prodId = (int)$prod['product_id'];
            $price = (float)($prod['product_price'] ?? 0) > 0 ? (float)$prod['product_price'] : 690.0;
            $nprice = (float)($prod['product_nprice'] ?? 0);
            $prodName = $prod['product_name'] ?? ('ผลิตภัณฑ์ ยักษ์โพธิ์ อโรม่า #' . $prodId);
            $isWholesale1kg = in_array((int)($prod['productcat_id'] ?? 0), [32, 38, 40], true);
            $img = !empty($prod['image_url']) ? $prod['image_url'] : (URL_PATH . '/catalog/assets/img/best_h00' . (($idx % 6) + 1) . '.webp');
            ?>
            <article class="product-item-card" data-product-id="<?= $prodId ?>">
                <a href="<?= URL_PATH ?>/<?= $currentLang ?>/product/<?= $prodId ?>" class="product-thumb-wrap" aria-label="<?= htmlspecialchars($prodName) ?>">
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($prodName) ?>" class="product-thumb-img" width="250" height="250" loading="lazy">
                    <?php if ($isWholesale1kg): ?>
                        <span class="product-badge-tag badge-wholesale">เรทส่ง ฿590</span>
                    <?php elseif ($idx === 0): ?>
                        <span class="product-badge-tag">ยอดนิยม</span>
                    <?php endif; ?>
                </a>

                <div class="product-card-body">
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/product/<?= $prodId ?>" class="product-card-title">
                        <?= htmlspecialchars($prodName) ?>
                    </a>

                    <div class="product-card-meta">
                        <span class="product-size-pill"><?= !empty($prod['prod_size']) ? htmlspecialchars($prod['prod_size']) : ($isWholesale1kg ? '1 กก.' : 'มาตรฐาน') ?></span>
                        <span class="product-rating-pill">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <span>4.9</span>
                        </span>
                    </div>

                    <div class="product-card-footer">
                        <div class="product-price-block">
                            <span class="product-price-curr">฿</span>
                            <span class="product-price-main"><?= number_format($price, 0) ?></span>
                            <?php if ($nprice > $price): ?>
                                <span class="product-price-old">฿<?= number_format($nprice, 0) ?></span>
                            <?php endif; ?>
                        </div>

                        <button type="button" class="product-add-cart-btn" data-action="add-to-cart" data-product-id="<?= $prodId ?>" data-qty="1" title="หยิบใส่ตะกร้า" aria-label="Add to cart">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        </button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- ============================================================
         7. WHOLESALE RATE BANNER (CLEAN & MINIMAL CTA)
         ============================================================ -->
    <section style="background:var(--yp-card-bg); border-radius:var(--radius); padding:16px 18px; margin-top:20px; border:1px solid var(--yp-border-light); box-shadow:var(--yp-shadow-sm); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <h3 style="font-family:var(--yp-font-display); font-size:1.05rem; font-weight:700; color:var(--yp-text); margin:0 0 4px 0;">เรทราคาส่ง</h3>
            <p style="font-size:0.8rem; color:var(--yp-text-secondary); margin:0;">คละได้ครบทั้ง 3 สูตร 12 กลิ่น</p>
        </div>
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>/corporate-gifts" style="background:var(--yp-primary); color:#FFF; padding:8px 18px; border-radius:var(--radius-pill); font-size:0.84rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <span>ซื้อทันที</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </section>
</div>
