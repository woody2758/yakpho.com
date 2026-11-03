<?php
$currentLang = $SITE['lang'] ?? 'th';
$products = $CATALOG_DATA['products'] ?? [];
$pagination = $CATALOG_DATA['pagination'] ?? ['total_items' => 0, 'total_pages' => 1, 'current_page' => 1];
$productCats = $SITE['product_categories'] ?? [];

$activeCat = (int)($CATALOG_FILTERS['cat_id'] ?? 0);
$activeFormula = (string)($CATALOG_FILTERS['formula'] ?? '');
$activeSize = (string)($CATALOG_FILTERS['size'] ?? '');
$activeSearch = (string)($CATALOG_FILTERS['q'] ?? '');

// 🌿 Clean URL Generator (Omit redundant page=1, cat_id=0, and empty q)
$buildCatalogUrl = function (int $targetPage = 1, ?int $targetCat = null, ?string $targetQ = null) use ($currentLang, $activeCat, $activeSearch): string {
    $c = $targetCat !== null ? $targetCat : $activeCat;
    $q = $targetQ !== null ? trim($targetQ) : trim($activeSearch);

    $params = [];
    if ($c > 0) {
        $params['cat_id'] = $c;
    }
    if ($targetPage > 1) {
        $params['page'] = $targetPage;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }

    $qs = !empty($params) ? '?' . http_build_query($params) : '';
    return URL_PATH . '/' . $currentLang . '/products' . $qs;
};
?>

<div class="yp-container" style="padding-top: 14px;">
    <!-- Catalog Header & Search Filter Bar -->
    <div style="background:var(--yp-card-bg); border-radius:var(--radius); padding:14px; box-shadow:var(--yp-shadow-sm); border:1px solid var(--yp-border-light); margin-bottom:12px;">
        <h1 style="font-family:var(--yp-font-display); font-size:1.15rem; font-weight:700; color:var(--yp-text); margin-bottom:6px;">
            <?= $currentLang === 'th' ? 'ผลิตภัณฑ์สปาและสมุนไพรทั้งหมด' : 'All Herbal Spa Products' ?>
            <span style="font-size:0.8rem; font-weight:500; color:var(--yp-text-secondary);">(<?= (int)$pagination['total_items'] ?> รายการ)</span>
        </h1>

        <!-- Filter Pill Tabs (Horizontal Scroll - Dynamic from Admin productcat) -->
        <div style="display:flex; gap:8px; overflow-x:auto; padding:6px 0 10px 0; scrollbar-width:none;">
            <a href="<?= $buildCatalogUrl(1, 0, '') ?>"
               style="padding:6px 14px; border-radius:var(--radius-pill); font-size:0.8rem; font-weight:600; white-space:nowrap; border:1px solid <?= ($activeCat === 0 && $activeFormula === '' && $activeSize === '') ? 'var(--yp-primary); background:var(--yp-primary); color:#FFF;' : 'var(--yp-border); background:var(--yp-surface); color:var(--yp-text);' ?>">
               ทั้งหมด
            </a>
            <?php foreach (($productCats ?? []) as $pCat): ?>
                <a href="<?= $buildCatalogUrl(1, (int)$pCat['productcat_id'], '') ?>"
                   style="padding:6px 14px; border-radius:var(--radius-pill); font-size:0.8rem; font-weight:600; white-space:nowrap; border:1px solid <?= $activeCat === (int)$pCat['productcat_id'] ? 'var(--yp-primary); background:var(--yp-primary); color:#FFF;' : 'var(--yp-border); background:var(--yp-surface); color:var(--yp-text);' ?>">
                   <?= htmlspecialchars($pCat['productcat_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php
        $activeCatName = 'ทุกหมวดหมู่';
        foreach (($productCats ?? []) as $pCat) {
            if ($activeCat === (int)$pCat['productcat_id']) {
                $activeCatName = $pCat['productcat_name'];
                break;
            }
        }
        ?>
        <!-- Search Bar with Live Category Selector (Clean URL on Submit) -->
        <form action="<?= URL_PATH ?>/<?= $currentLang ?>/products" method="get" onsubmit="cleanFormSubmit(this)" class="catalog-search-form">
            <div class="catalog-search-input-wrap">
                <input type="text" name="q" id="catalogSearchInput" value="<?= htmlspecialchars($activeSearch) ?>" placeholder="ค้นหาชื่อสินค้า, กลิ่น หรือรหัส..." class="catalog-search-input">
            </div>
            
            <!-- Category Side Drawer Trigger Button -->
            <button type="button" class="btn-cat-drawer-trigger <?= $activeCat > 0 ? 'has-active-cat' : '' ?>" onclick="openCategoryDrawer()" title="เลือกหมวดหมู่">
                <svg class="cat-trigger-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
                <span class="cat-trigger-text"><?= htmlspecialchars($activeCatName) ?></span>
                <svg class="cat-trigger-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <input type="hidden" name="cat_id" id="catalogCatInput" value="<?= $activeCat > 0 ? $activeCat : '' ?>">

            <button type="submit" class="btn-catalog-search-submit">
                <span>ค้นหา</span>
            </button>
        </form>

        <!-- Offcanvas Category Side Drawer -->
        <div class="category-drawer-overlay" id="categoryDrawerOverlay" onclick="closeCategoryDrawer(event)">
            <aside class="category-drawer-panel" onclick="event.stopPropagation()">
                <div class="category-drawer-header">
                    <div class="category-drawer-title">
                        <svg class="cat-drawer-title-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        <span>หมวดหมู่ผลิตภัณฑ์</span>
                    </div>
                    <button type="button" class="category-drawer-close" onclick="closeCategoryDrawer()" aria-label="Close">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <div class="category-drawer-body">
                    <div class="category-drawer-group-title">เลือกหมวดหมู่เพื่อกรองสินค้า</div>
                    <div class="category-drawer-list">
                        <a href="<?= $buildCatalogUrl(1, 0, $activeSearch) ?>" class="category-drawer-item <?= $activeCat === 0 ? 'active' : '' ?>">
                            <div class="cat-drawer-item-left">
                                <span class="cat-drawer-icon">🌿</span>
                                <span class="cat-drawer-name">สินค้าทั้งหมด</span>
                            </div>
                            <?php if ($activeCat === 0): ?>
                            <span class="cat-drawer-active-check">✓</span>
                            <?php else: ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="cat-drawer-arrow"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            <?php endif; ?>
                        </a>

                        <?php foreach (($productCats ?? []) as $pCat): 
                            $catId = (int)$pCat['productcat_id'];
                            $isActive = ($activeCat === $catId);
                        ?>
                        <a href="<?= $buildCatalogUrl(1, $catId, $activeSearch) ?>" class="category-drawer-item <?= $isActive ? 'active' : '' ?>">
                            <div class="cat-drawer-item-left">
                                <span class="cat-drawer-icon">
                                    <?= match($catId) {
                                        32 => '🔥', // สูตรร้อน
                                        38 => '❄️', // สูตรเย็น
                                        40 => '💆', // สูตรนวดตัว
                                        36 => '💧', // พิมเสนน้ำ
                                        default => '🍃',
                                    } ?>
                                </span>
                                <span class="cat-drawer-name"><?= htmlspecialchars($pCat['productcat_name']) ?></span>
                            </div>
                            <?php if ($isActive): ?>
                            <span class="cat-drawer-active-check">✓</span>
                            <?php else: ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="cat-drawer-arrow"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- 2-Column Product Grid -->
    <?php if (empty($products)): ?>
        <div style="background:var(--yp-card-bg); border-radius:var(--radius); padding:40px 14px; text-align:center; color:var(--yp-text-muted);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 10px auto; opacity: 0.5;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <h3 style="color:var(--yp-text); font-size:1rem; margin-bottom:4px;">ไม่พบสินค้าที่ตรงกับคำค้นหา</h3>
            <p style="font-size:0.82rem;">กรุณาลองเปลี่ยนคำค้นหา หรือเลือกหมวดหมู่อื่น</p>
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" style="display:inline-block; margin-top:14px; background:var(--yp-primary); color:#FFF; padding:8px 18px; border-radius:var(--radius-pill); font-size:0.82rem; font-weight:600;">
                ดูสินค้าทั้งหมด
            </a>
        </div>
    <?php else: ?>
        <div class="products-grid-2col">
            <?php foreach ($products as $idx => $prod): ?>
                <?php
                $prodId = (int)$prod['product_id'];
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
                $prodName = $prod['product_name'] ?? ('ผลิตภัณฑ์ ยักษ์โพธิ์ อโรม่า #' . $prodId);
                $img = !empty($prod['image_url']) ? $prod['image_url'] : (URL_PATH . '/catalog/assets/img/best_h00' . (($idx % 6) + 1) . '.webp');
                ?>
                <article class="product-item-card" data-product-id="<?= $prodId ?>">
                    <div class="product-thumb-container">
                        <a href="<?= URL_PATH ?>/<?= $currentLang ?>/product/<?= $prodId ?>" class="product-thumb-wrap" aria-label="<?= htmlspecialchars($prodName) ?>">
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($prodName) ?>" class="product-thumb-img" width="250" height="250" loading="lazy">
                            <?php if ($isWholesale1kg): ?>
                                <span class="product-badge-tag badge-wholesale">เรทส่ง ฿590</span>
                            <?php endif; ?>
                        </a>
                        <button type="button" class="product-like-btn <?= !empty($prod['is_liked']) ? 'is-liked' : '' ?>" onclick="toggleProductLike(event, <?= $prodId ?>, this)" title="ถูกใจสินค้านี้" aria-label="Like product">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path></svg>
                            <span class="product-like-count"><?= number_format((int)($prod['product_likes'] ?? $prod['likes_count'] ?? 0)) ?></span>
                        </button>
                    </div>

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

        <!-- Clean, Responsive Pagination with Sliding Window -->
        <?php
        $curPage = (int)($pagination['current_page'] ?? 1);
        $totalPages = (int)($pagination['total_pages'] ?? 1);
        if ($totalPages > 1):
            $pagesToShow = [];
            if ($totalPages <= 7) {
                for ($i = 1; $i <= $totalPages; $i++) $pagesToShow[] = $i;
            } else {
                $pagesToShow[] = 1;
                $start = max(2, $curPage - 1);
                $end = min($totalPages - 1, $curPage + 1);
                if ($curPage <= 3) {
                    $start = 2;
                    $end = 4;
                } elseif ($curPage >= $totalPages - 2) {
                    $start = $totalPages - 3;
                    $end = $totalPages - 1;
                }
                if ($start > 2) $pagesToShow[] = '...';
                for ($i = $start; $i <= $end; $i++) $pagesToShow[] = $i;
                if ($end < $totalPages - 1) $pagesToShow[] = '...';
                $pagesToShow[] = $totalPages;
            }
        ?>
            <nav aria-label="Catalog pagination" style="display:flex; justify-content:center; align-items:center; gap:6px; margin:28px 0 16px 0; flex-wrap:wrap;">
                <?php if ($curPage > 1): ?>
                    <a href="<?= $buildCatalogUrl($curPage - 1) ?>"
                       style="padding:0 12px; height:36px; border-radius:var(--radius-pill); display:inline-flex; align-items:center; justify-content:center; font-weight:600; font-size:0.85rem; border:1px solid var(--yp-border); background:var(--yp-card-bg); color:var(--yp-text); gap:4px; text-decoration:none;"
                       title="หน้าก่อนหน้า" aria-label="Previous Page">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        <span>ก่อนหน้า</span>
                    </a>
                <?php endif; ?>

                <?php foreach ($pagesToShow as $p): ?>
                    <?php if ($p === '...'): ?>
                        <span style="width:32px; height:36px; display:inline-flex; align-items:center; justify-content:center; color:var(--yp-text-muted); font-weight:600; font-size:0.9rem;">&hellip;</span>
                    <?php else: ?>
                        <?php $isActive = ((int)$p === $curPage); ?>
                        <a href="<?= $buildCatalogUrl((int)$p) ?>"
                           style="min-width:36px; height:36px; padding:0 10px; border-radius:var(--radius-pill); display:inline-flex; align-items:center; justify-content:center; font-weight:600; font-size:0.86rem; text-decoration:none; transition:all 0.15s ease; border:1px solid <?= $isActive ? 'var(--yp-primary); background:var(--yp-primary); color:#FFF; box-shadow:var(--yp-shadow-sm);' : 'var(--yp-border); background:var(--yp-card-bg); color:var(--yp-text);' ?>">
                            <?= $p ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($curPage < $totalPages): ?>
                    <a href="<?= $buildCatalogUrl($curPage + 1) ?>"
                       style="padding:0 12px; height:36px; border-radius:var(--radius-pill); display:inline-flex; align-items:center; justify-content:center; font-weight:600; font-size:0.85rem; border:1px solid var(--yp-border); background:var(--yp-card-bg); color:var(--yp-text); gap:4px; text-decoration:none;"
                       title="หน้าถัดไป" aria-label="Next Page">
                        <span>ถัดไป</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
