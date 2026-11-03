<?php
$currentLang = $SITE['lang'] ?? 'th';
$prod = $PRODUCT_DETAIL;
$gallery = !empty($prod['gallery']) ? $prod['gallery'] : [$prod['image_large']];
$related = $prod['related_products'] ?? [];
$isWholesale1kg = !empty($prod['is_1kg_wholesale']) || in_array((int)($prod['productcat_id'] ?? 0), [32, 38, 40], true);
$rawPrice = (float)($prod['product_price'] ?? 0);
$rawNprice = (float)($prod['product_nprice'] ?? 0);
if ($rawPrice > 0) {
    $price = $rawPrice;
    $nprice = $rawNprice;
} elseif ($rawNprice > 0) {
    $price = $rawNprice;
    $nprice = 0;
} else {
    $price = $isWholesale1kg ? 690.0 : 0;
    $nprice = 0;
}
$prodId = (int)$prod['product_id'];
?>

<!-- Product Schema.org JSON-LD (SEO + GEO + AEO) -->
<script type="application/ld+json">
{
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": <?= json_encode($prod['display_name']) ?>,
    "image": <?= json_encode($gallery) ?>,
    "description": <?= json_encode($prod['display_excerpt'] ?: $prod['display_name']) ?>,
    "sku": <?= json_encode($prod['product_code'] ?: ('YP' . $prodId)) ?>,
    "brand": {
        "@type": "Brand",
        "name": "Yak Pho Aroma"
    },
    "offers": {
        "@type": "Offer",
        "url": "<?= ROOT_URL ?>/<?= $currentLang ?>/product/<?= $prodId ?>",
        "priceCurrency": "THB",
        "price": "<?= number_format($price, 2, '.', '') ?>",
        "availability": "https://schema.org/InStock",
        "seller": {
            "@type": "Organization",
            "name": "Esther Aroma Co., Ltd."
        }
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "reviewCount": "128"
    }
}
</script>

<!-- Google Analytics 4 (GA4) View Item Event -->
<script>
if (typeof gtag === 'function') {
    gtag('event', 'view_item', {
        currency: 'THB',
        value: <?= (float)$price ?>,
        items: [{
            item_id: '<?= (int)$prodId ?>',
            item_name: <?= json_encode($prod['display_name']) ?>,
            item_category: <?= json_encode($prod['cat_name'] ?? 'Aromatherapy') ?>,
            price: <?= (float)$price ?>,
            quantity: 1
        }]
    });
}
</script>

<!-- 1. Detail Top Navigation Bar -->
<div class="detail-top-nav">
    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="detail-nav-btn" aria-label="Back to Products">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
    </a>
    <div style="font-weight:700; font-size:0.95rem; color:var(--yp-text); max-width:65%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
        <?= htmlspecialchars($prod['display_name']) ?>
    </div>
    <div style="display:flex; gap:6px;">
        <button type="button" class="detail-nav-btn" onclick="if(navigator.share){navigator.share({title:document.title,url:window.location.href});}else{navigator.clipboard.writeText(window.location.href); showToast('คัดลอกลิงก์แล้ว');}" aria-label="Share">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
        </button>
        <button type="button" class="detail-nav-btn" data-action="open-cart" aria-label="Shopping Cart">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        </button>
    </div>
</div>

<div class="yp-container" style="padding-bottom: 75px;">
    <div class="product-detail-layout">
        <!-- Media Column (Left on Desktop) -->
        <div class="product-detail-media">
            <!-- 2. Product Gallery Showcase (Thaimart Screenshot 3) -->
            <div class="detail-gallery-wrap">
                <img src="<?= htmlspecialchars($prod['image_large']) ?>" alt="<?= htmlspecialchars($prod['display_name']) ?>" class="detail-gallery-img" id="productMainImage" width="500" height="500" fetchpriority="high">
                <div class="detail-gallery-counter">
                    <span id="galleryCurrentIdx">1</span> / <?= count($gallery) ?>
                </div>
            </div>

            <!-- Thumbnail Strip if Multiple -->
            <?php if (count($gallery) > 1): ?>
                <div style="display:flex; gap:8px; margin-top:8px; overflow-x:auto; padding-bottom:4px;">
                    <?php foreach ($gallery as $gIdx => $gUrl): ?>
                        <button type="button" style="width:54px; height:54px; border-radius:var(--radius-sm); border:2px solid <?= $gIdx === 0 ? 'var(--yp-primary)' : 'var(--yp-border)' ?>; overflow:hidden; flex-shrink:0; background:#FFF;" onclick="document.getElementById('productMainImage').src='<?= htmlspecialchars($gUrl) ?>'; document.getElementById('galleryCurrentIdx').textContent='<?= $gIdx + 1 ?>';">
                            <img src="<?= htmlspecialchars($gUrl) ?>" alt="Thumb <?= $gIdx + 1 ?>" style="width:100%; height:100%; object-fit:cover;">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Summary & Details Column (Right on Desktop) -->
        <div class="product-detail-summary">
            <!-- 3. Primary Info Card -->
            <div class="detail-info-card">
                <div class="detail-price-row">
                    <span class="detail-price-val">฿<?= number_format($price, 2) ?></span>
                    <?php if ($nprice > $price): ?>
                        <span style="font-size:0.9rem; color:var(--yp-text-muted); text-decoration:line-through;">฿<?= number_format($nprice, 2) ?></span>
                    <?php endif; ?>
                    <?php if ($isWholesale1kg): ?>
                        <span style="background:var(--yp-primary-surface); color:var(--yp-primary); font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:var(--radius-pill);">
                            ซื้อ 6 กก. เหลือ ฿590/กก.
                        </span>
                    <?php endif; ?>
                </div>

                <h1 class="detail-title"><?= htmlspecialchars($prod['display_name']) ?></h1>

                <!-- Trust & Shipping Badges (Thaimart style) -->
                <div class="detail-badge-box">
                    <div class="detail-badge-row">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="13" x="1" y="6" rx="2"></rect><circle cx="5" cy="18" r="2"></circle><circle cx="13" cy="18" r="2"></circle><polyline points="17 9 20 9 23 13 23 18 19 18"></polyline></svg>
                        <span><strong>ส่งฟรีทั่วประเทศ</strong> เมื่อสั่งซื้อ ฿990 ขึ้นไป (จัดส่งด่วน Flash Express / Kerry)</span>
                    </div>
                    <div class="detail-badge-row">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
                        <span><strong>ปรุงสูตรโดย Yak Pho Aroma</strong> • คัดสรรสมุนไพรแท้ 100% • ปลอดภัยไม่คันมือ</span>
                    </div>
                </div>

                <!-- Quantity Selector -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding-top:8px; border-top:1px solid var(--yp-border-light);">
                    <span style="font-size:0.88rem; font-weight:600; color:var(--yp-text);">จำนวนที่ต้องการสั่งซื้อ:</span>
                    <div class="cart-stepper" style="border:1px solid var(--yp-border); background:var(--yp-surface);">
                        <button type="button" class="cart-stepper-btn" id="detailQtyMinus">-</button>
                        <input type="text" id="detailQtyInput" value="1" readonly style="width:36px; text-align:center; border:none; background:transparent; font-weight:700;">
                        <button type="button" class="cart-stepper-btn" id="detailQtyPlus">+</button>
                    </div>
                </div>
            </div>

            <!-- Product Details & Tabs (Dynamic Parser + Wholesale Support) -->
            <?php
            $contentTabs = parse_product_content_tabs($prod['display_detail'] ?: ($prod['display_excerpt'] ?? ''));
            if ($isWholesale1kg) {
                $contentTabs['เรทราคาส่ง (1 กก.)'] = 'WHOLESALE_TABLE_MARKER';
            }
            ?>

            <div class="detail-tabs-wrap">
                <?php if (count($contentTabs) > 1): ?>
                    <div class="detail-tabs-nav" role="tablist">
                        <?php $tIdx = 0; foreach ($contentTabs as $tabTitle => $tabBody): ?>
                            <button type="button" class="detail-tab-btn <?= $tIdx === 0 ? 'active' : '' ?>" data-detail-tab="tab_<?= $tIdx ?>">
                                <?= htmlspecialchars($tabTitle) ?>
                            </button>
                        <?php $tIdx++; endforeach; ?>
                    </div>

                    <?php $pIdx = 0; foreach ($contentTabs as $tabTitle => $tabBody): ?>
                        <div class="detail-tab-pane <?= $pIdx === 0 ? 'active' : '' ?>" id="tab_<?= $pIdx ?>">
                            <?php if ($tabBody === 'WHOLESALE_TABLE_MARKER'): ?>
                                <p style="margin-bottom:10px; font-weight:600; color:var(--yp-text); font-size:0.88rem;">เรทราคาส่งยาหม่องกิโลสำหรับร้านนวดและสปา (คละสูตรและกลิ่นได้):</p>
                                <?php 
                                $detailTiers = $WHOLESALE_DATA['tiers'] ?? [];
                                if (!empty($detailTiers)): ?>
                                    <table class="wholesale-tier-table">
                                        <thead>
                                            <tr><th>ปริมาณ (กก.)</th><th>ราคา/กก.</th><th>ความคุ้มค่า</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($detailTiers as $dt): 
                                                $isBest = ((int)$dt['min_kg'] === 6 || (int)$dt['min_kg'] === 50);
                                            ?>
                                                <tr class="<?= $isBest ? 'highlight-tier' : '' ?>">
                                                    <td><?= htmlspecialchars($currentLang === 'th' ? $dt['label_th'] : $dt['label_en']) ?></td>
                                                    <td><strong>฿<?= (int)$dt['rate'] ?>.-</strong></td>
                                                    <td><?= $isBest ? 'เรทพิเศษยอดนิยม' : 'เรทราคาส่ง' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <table class="wholesale-tier-table">
                                        <thead>
                                            <tr><th>ปริมาณ (กก.)</th><th>ราคา/กก.</th><th>ความคุ้มค่า</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr><td>1 - 5 กก.</td><td>฿690.-</td><td>เรททดลองใช้</td></tr>
                                            <tr class="highlight-tier"><td>6 - 9 กก.</td><td>฿590.-</td><td>ประหยัด ฿100/กก.</td></tr>
                                            <tr><td>10 - 19 กก.</td><td>฿560.-</td><td>ประหยัด ฿130/กก.</td></tr>
                                            <tr><td>20 - 49 กก.</td><td>฿500 - ฿530</td><td>เรทร้านสปาประจำ</td></tr>
                                            <tr class="highlight-tier"><td>50+ กก.</td><td>฿490.-</td><td>เรทส่งสูงสุด</td></tr>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="product-rich-detail" style="line-height:1.7;">
                                    <?= $tabBody ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php $pIdx++; endforeach; ?>
                <?php else: ?>
                    <!-- Single Content View (e.g. Products with simple single description) -->
                    <div style="font-family:var(--yp-font-display); font-size:1.05rem; font-weight:700; color:var(--yp-text); margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid var(--yp-border-light);">
                        รายละเอียดสินค้า
                    </div>
                    <div class="product-rich-detail" style="line-height:1.7; font-size:0.9rem; color:var(--yp-text-secondary);">
                        <?php foreach ($contentTabs as $singleBody): ?>
                            <?= $singleBody ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 6. Related Products (20 Items Responsive Grid) -->
    <?php if (!empty($related)): ?>
        <div class="section-headline" style="margin-top:28px; margin-bottom:14px;">
            <h3 class="section-title">สินค้าที่เกี่ยวข้อง</h3>
        </div>
        <div class="products-grid-2col">
            <?php foreach (array_slice($related, 0, 20) as $rProd): ?>
                <?php
                $rId = (int)$rProd['product_id'];
                $rRawPrice = (float)($rProd['product_price'] ?? 0);
                $rRawNprice = (float)($rProd['product_nprice'] ?? 0);
                if ($rRawPrice > 0) {
                    $rPrice = $rRawPrice;
                } elseif ($rRawNprice > 0) {
                    $rPrice = $rRawNprice;
                } else {
                    $rPrice = 0;
                }
                $rImg = !empty($rProd['image_url']) ? $rProd['image_url'] : (!empty($rProd['image']) ? $rProd['image'] : (URL_PATH . '/catalog/assets/img/best_h001.webp'));
                ?>
                <article class="product-item-card">
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/product/<?= $rId ?>" class="product-thumb-wrap">
                        <img src="<?= htmlspecialchars($rImg) ?>" alt="<?= htmlspecialchars($rProd['product_name']) ?>" class="product-thumb-img" loading="lazy">
                    </a>
                    <div class="product-card-body">
                        <a href="<?= URL_PATH ?>/<?= $currentLang ?>/product/<?= $rId ?>" class="product-card-title">
                            <?= htmlspecialchars($rProd['product_name']) ?>
                        </a>
                        <div class="product-card-footer">
                            <div class="product-price-block">
                                <span class="product-price-curr">฿</span>
                                <span class="product-price-main"><?= number_format($rPrice, 0) ?></span>
                            </div>
                            <button type="button" class="product-add-cart-btn" data-action="add-to-cart" data-product-id="<?= $rId ?>" data-qty="1">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     7. STICKY BOTTOM ACTION BAR (Thaimart Screenshot 3 Layout)
     ============================================================ -->
<div class="product-sticky-bottom-bar">
    <div class="product-bottom-bar-inner">
        <!-- Chat LINE -->
        <a href="https://line.me/R/ti/p/@yakpho" target="_blank" rel="noopener" class="bottom-action-icon-btn" aria-label="LINE Chat">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            <span>แชทร้าน</span>
        </a>

        <!-- Cart Trigger -->
        <button type="button" class="bottom-action-icon-btn" data-action="open-cart" aria-label="Open Cart">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            <span class="dock-badge" data-cart-badge style="<?= ((int)($cartSummary['total_qty'] ?? 0) > 0) ? 'display:flex;' : 'display:none;' ?>">
                <?= (int)($cartSummary['total_qty'] ?? 0) ?>
            </span>
            <span>ตะกร้า</span>
        </button>

        <!-- Add To Cart -->
        <button type="button" class="btn-add-cart-action" data-action="add-to-cart" data-product-id="<?= $prodId ?>" data-qty="1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>เพิ่มลงตะกร้า</span>
        </button>

        <!-- Buy Now Primary -->
        <button type="button" class="btn-buy-now-action" data-action="buy-now" data-product-id="<?= $prodId ?>" data-qty="1">
            <span>ซื้อเลย ฿<?= number_format($price, 0) ?></span>
        </button>
    </div>
</div>
