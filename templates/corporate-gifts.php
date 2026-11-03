<?php
$currentLang = $SITE['lang'] ?? 'th';
$matrixRows = $WHOLESALE_DATA['matrix'] ?? [];
$tiers = $WHOLESALE_DATA['tiers'] ?? [];
$pricingIntro = $HOME_DATA['pricing_intro'] ?? [];
$pricingFeatures = $HOME_DATA['pricing_features'] ?? [];
?>

<!-- 1. B2B WHOLESALE & SPA PARTNER HEADER (COMPACT & MINIMAL) -->
<section class="wholesale-section">
    <div class="yp-container">
        <!-- Compact Header & Realtime Tier Rate Pills -->
        <div class="wholesale-header-wrap">
            <div class="wholesale-header-flex">
                <div class="wholesale-title-group">
                    <h1 class="wholesale-matrix-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span><?= $currentLang === 'th' ? 'เลือกสินค้าจากตารางเรทราคาส่ง' : 'Wholesale Order Matrix' ?></span>
                    </h1>
                    <div class="wholesale-matrix-subtitle">
                        <?= $currentLang === 'th' ? '(คละได้ 3 สูตร 12 กลิ่น • ถุง 1 กก.)' : '(Mix & Match 3 formulas 12 scents • 1 kg pouch)' ?>
                    </div>
                </div>
                <div class="wholesale-matrix-help">
                    <?= $currentLang === 'th' ? 'คลิกปุ่ม + / − เพื่อเลือกจำนวน ระบบคำนวณเรทราคาส่งให้อัตโนมัติ' : 'Adjust quantities below, wholesale tiers update automatically' ?>
                </div>
            </div>

            <!-- Compact Minimal Tier Rate Pills Strip -->
            <div class="tier-cards-strip">
                <span class="wholesale-rate-label"><?= $currentLang === 'th' ? 'เรทส่ง/กก.:' : 'Rate/kg:' ?></span>
                <?php foreach ($tiers as $tIdx => $tier): ?>
                    <div class="tier-rate-box <?= $tIdx === 0 ? 'is-active-tier' : '' ?>"
                         data-tier-min="<?= (int)$tier['min_kg'] ?>"
                         data-tier-max="<?= (int)$tier['max_kg'] ?>">
                        <span class="tier-vol-label"><?= htmlspecialchars($currentLang === 'th' ? $tier['label_th'] : $tier['label_en']) ?></span>
                        <strong class="tier-price-val">฿<?= (int)$tier['rate'] ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 3. INTERACTIVE 12 SCENTS x 3 FORMULAS MATRIX TABLE -->
        <div class="wholesale-matrix-wrapper" id="wholesaleMatrixWrapper" data-aos="fade-up">
            <table id="orderTable" aria-describedby="tableHelp">
                <thead>
                    <tr class="head">
                        <th><?= $currentLang === 'th' ? 'ลำดับ / กลิ่น (TH/EN)' : 'No. / Scent (TH/EN)' ?></th>
                        <th class="hide-sm"><?= $currentLang === 'th' ? 'สูตรร้อน<br><small>Hot (H)</small>' : 'Hot Formula<br><small>(H)</small>' ?></th>
                        <th class="hide-sm"><?= $currentLang === 'th' ? 'สูตรเย็น<br><small>Cool (C)</small>' : 'Cool Formula<br><small>(C)</small>' ?></th>
                        <th class="hide-sm"><?= $currentLang === 'th' ? 'สูตรนวดตัว<br><small>Balanced (B)</small>' : 'Body Massage<br><small>(B)</small>' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matrixRows as $row): ?>
                        <tr>
                            <th scope="row" class="scent-th">
                                <?= htmlspecialchars($row['name_th']) ?>
                                <span class="scent-sub"><?= htmlspecialchars($row['name_en']) ?></span>
                            </th>
                            <?php
                                $formulaList = [
                                    'H' => ['label' => $currentLang === 'th' ? 'สูตรร้อน' : 'Hot (H)', 'data' => $row['hot']],
                                    'C' => ['label' => $currentLang === 'th' ? 'สูตรเย็น' : 'Cool (C)', 'data' => $row['cool']],
                                    'B' => ['label' => $currentLang === 'th' ? 'สูตรนวดตัว' : 'Body (B)', 'data' => $row['massage']],
                                ];
                                foreach ($formulaList as $f => $fInfo):
                                    $cellData = $fInfo['data'];
                                    $sku = $cellData['sku'];
                                    $pId = (int)$cellData['product_id'];
                                    $pCode = $cellData['product_code'];
                                    $label = $fInfo['label'];
                            ?>
                                <td data-label="<?= $label ?>">
                                    <div class="cell" 
                                         data-formula="<?= htmlspecialchars($f) ?>" 
                                         data-sku="<?= htmlspecialchars($sku) ?>"
                                         data-product-id="<?= $pId ?>"
                                         data-product-code="<?= htmlspecialchars($pCode) ?>"
                                         data-name="<?= htmlspecialchars($cellData['name']) ?>"
                                         data-scent="<?= htmlspecialchars($row['core_th']) ?>">
                                        <button type="button" class="btn minus btn-disabled" disabled aria-label="ลดจำนวน">−</button>
                                        <span class="qty" aria-live="polite">0</span>
                                        <button type="button" class="btn plus" aria-label="เพิ่มจำนวน">+</button>
                                        <span class="sku">SKU: <?= htmlspecialchars($sku) ?></span>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Live Calculator & Action Footer (Cart Only) -->
            <div class="matrix-sticky-bar" id="wholesaleStickyFooter">
                <div class="matrix-stats-group">
                    <div class="matrix-stat-item">
                        <span><?= $currentLang === 'th' ? 'น้ำหนักรวมทั้งหมด' : 'Total Volume' ?></span>
                        <strong id="matrixTotalKg">0 kg</strong>
                    </div>
                    <div class="matrix-stat-item">
                        <span><?= $currentLang === 'th' ? 'เรทราคาส่งโรงงาน' : 'Wholesale Rate' ?></span>
                        <strong id="matrixCurrentRate">฿690 / kg</strong>
                    </div>
                    <div class="matrix-stat-item">
                        <span><?= $currentLang === 'th' ? 'ประหยัดจากราคาปกติ' : 'Total Savings' ?></span>
                        <strong id="matrixTotalSaved">฿0</strong>
                    </div>
                    <div class="matrix-stat-item">
                        <span><?= $currentLang === 'th' ? 'ยอดรวมสุทธิ' : 'Estimated Total' ?></span>
                        <strong id="matrixTotalPrice">฿0</strong>
                    </div>
                </div>

                <div class="matrix-actions-group">
                    <button type="button" class="btn-matrix-order" id="btnMatrixAddToCart">
                        <i data-lucide="shopping-bag" width="18" height="18"></i>
                        <span><?= $currentLang === 'th' ? 'หยิบทั้งหมดใส่ตะกร้าและสั่งซื้อ' : 'Add All to Cart & Checkout' ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>



