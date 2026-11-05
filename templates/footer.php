<?php
$currentLang = $SITE['lang'] ?? 'th';
$contactInfo = $SITE['contact'] ?? [];
$cartSummary = $SITE['cart'] ?? ['total_qty' => 0, 'wholesale_kg_rate' => 690, 'items' => [], 'subtotal' => 0, 'wholesale_savings' => 0, 'grand_total' => 0];
$baseRoute = trim((string)($base ?? ''), '/');
$ver = time();
?>
    </main>

    <!-- ============================================================
         1. FIXED BOTTOM NAVIGATION DOCK (Thaimart 5-Tab Bar)
         ============================================================ -->
    <nav class="bottom-nav-dock" aria-label="App Navigation">
        <div class="bottom-nav-dock-inner">
            <!-- Tab 1: Home -->
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/" class="dock-tab-item <?= ($baseRoute === '' || $baseRoute === 'home') ? 'active' : '' ?>">
                <div class="dock-tab-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                </div>
                <span>หน้าแรก</span>
            </a>

            <!-- Tab 2: Products / Shop -->
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="dock-tab-item <?= in_array($baseRoute, ['products', 'product'], true) ? 'active' : '' ?>">
                <div class="dock-tab-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"></rect><rect width="7" height="7" x="14" y="3" rx="1"></rect><rect width="7" height="7" x="14" y="14" rx="1"></rect><rect width="7" height="7" x="3" y="14" rx="1"></rect></svg>
                </div>
                <span>สินค้า</span>
            </a>

            <!-- Tab 3: Orders / Tracking -->
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/orders" class="dock-tab-item <?= in_array($baseRoute, ['orders', 'track'], true) ? 'active' : '' ?>">
                <div class="dock-tab-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </div>
                <span>คำสั่งซื้อ</span>
            </a>

            <!-- Tab 4: Wholesale / VIP -->
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/corporate-gifts" class="dock-tab-item <?= in_array($baseRoute, ['corporate-gifts', 'wholesale', 'vip'], true) ? 'active' : '' ?>">
                <div class="dock-tab-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                </div>
                <span>ราคาส่ง</span>
            </a>

            <!-- Tab 5: Profile / Contact -->
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/profile" class="dock-tab-item <?= in_array($baseRoute, ['profile', 'account', 'contact', 'stores'], true) ? 'active' : '' ?>">
                <div class="dock-tab-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <span>โปรไฟล์</span>
            </a>
        </div>
    </nav>

    <!-- ============================================================
         2. MINIMALIST CLEAN APP FOOTER (SEO & Brand Heritage)
         ============================================================ -->
    <?php 
    // Hide multi-column link grid on Checkout and Transactional/Commerce pages per user request
    $hideFooterLinks = in_array($baseRoute, ['checkout', 'products', 'product', 'orders', 'corporate-gifts', 'wholesale', 'profile', 'account', 'gift-sets'], true);
    ?>
    <footer class="app-footer">
        <div class="yp-container">
            <?php if (!$hideFooterLinks): ?>
            <div class="footer-grid">
                <div>
                    <div class="footer-title">YAK PHO AROMA (ยักษ์โพธิ์)</div>
                    <p style="line-height: 1.6; margin-bottom: 0;">
                        <?= $currentLang === 'th'
                            ? 'กลุ่มสุขภาพและเครื่องหอม (From Soil To Soul) โดย ' . htmlspecialchars($contactInfo['company_name'] ?? 'บริษัท เอสเธอร์ อโรม่า จำกัด') . ' ผู้เชี่ยวชาญด้านบาล์มนวดสมุนไพรสูตรเข้มข้น และผลิตภัณฑ์สปาอโรม่า'
                            : 'Authentic Thai Herbal Massage Balms & Spa Aromatherapy (From Soil To Soul) by Esther Aroma Co., Ltd.' ?>
                    </p>
                </div>

                <div>
                    <div class="footer-title">สูตรยอดนิยม</div>
                    <ul class="footer-links">
                        <li><a href="<?= URL_PATH ?>/<?= $currentLang ?>/products?cat_id=32">สูตรร้อน บาล์มนวด 1 กก.</a></li>
                        <li><a href="<?= URL_PATH ?>/<?= $currentLang ?>/products?cat_id=38">สูตรเย็น บาล์มอโรม่า 1 กก.</a></li>
                        <li><a href="<?= URL_PATH ?>/<?= $currentLang ?>/products?cat_id=40">สูตรนวดตัว สปาอโรม่า 1 กก.</a></li>
                        <li><a href="<?= URL_PATH ?>/<?= $currentLang ?>/products?cat_id=36">พิมเสนน้ำ & น้ำมันนวด</a></li>
                        <li><a href="<?= URL_PATH ?>/<?= $currentLang ?>/corporate-gifts">เรทราคาส่งร้านสปา (Wholesale)</a></li>
                    </ul>
                </div>

                <div>
                    <div class="footer-title"><?= $currentLang === 'th' ? 'บริการลูกค้า & ช่วยเหลือ' : 'Customer Service & Help' ?></div>
                    <ul class="footer-links">
                        <li><a href="<?= URL_PATH ?>/<?= $currentLang ?>/about"><?= $currentLang === 'th' ? 'เกี่ยวกับเรา' : 'About Us' ?></a></li>
                        <li><a href="<?= URL_PATH ?>/<?= $currentLang ?>/brandstory"><?= $currentLang === 'th' ? 'เรื่องราวแบรนด์ยักษ์โพธิ์' : 'Brand Story' ?></a></li>
                        <li><a href="<?= URL_PATH ?>/<?= $currentLang ?>/blog"><?= $currentLang === 'th' ? 'บทความสมุนไพรนวดสปา' : 'Spa & Herb Articles' ?></a></li>
                        <li><a href="<?= URL_PATH ?>/<?= $currentLang ?>/gallery"><?= $currentLang === 'th' ? 'ภาพแกลอรี่' : 'Photo Gallery' ?></a></li>
                        <li><a href="<?= URL_PATH ?>/<?= $currentLang ?>/faqs"><?= $currentLang === 'th' ? 'คำถามที่ถูกถามบ่อย' : 'FAQs' ?></a></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <div class="footer-bottom">
                © <?= date('Y') ?> <?= htmlspecialchars($contactInfo['company_name'] ?? 'Esther Aroma Co., Ltd.') ?> • Yak Pho Aroma. All Rights Reserved.
                <div class="footer-policy-links">
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/policy?tab=terms"><?= $currentLang === 'th' ? 'ข้อกำหนดและเงื่อนไขการใช้บริการ' : 'Terms of Service' ?></a>
                    <span class="footer-policy-sep">•</span>
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/policy?tab=pdpa"><?= $currentLang === 'th' ? 'นโยบายความเป็นส่วนตัว (PDPA)' : 'Privacy Policy (PDPA)' ?></a>
                    <span class="footer-policy-sep">•</span>
                    <a href="<?= URL_PATH ?>/<?= $currentLang ?>/faqs"><?= $currentLang === 'th' ? 'คำถามที่ถูกถามบ่อย (FAQs)' : 'FAQs' ?></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- SweetAlert2 (Global for interactive alerts & dialogs per md/AGENT.md) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Zero External Dependencies: Ultra-Fast Vanilla Core Script -->
    <script src="<?= URL_PATH ?>/assets/js/yakpho.js?v=<?= $ver ?>"></script>
</body>
</html>
