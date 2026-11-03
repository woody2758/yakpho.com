<?php
/**
 * Site Footer Component
 * Reusable footer with links, newsletter, social media
 */
?>
    </main>
    <!-- End Main Content -->
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-main">
                
                <!-- Company Info -->
                <div class="footer-column">
                    <h4 id="footer-company-title">เกี่ยวกับเรา</h4>
                    <p style="font-size: var(--font-size-sm); line-height: var(--line-height-relaxed); color: rgba(245, 241, 232, 0.8);">
                        Esther Aroma นำเสนอผลิตภัณฑ์สุขภาพและความงามจากสมุนไพรตามคัมภีร์ไบเบิล 
                        มาตรฐาน GMP และ FDA ส่งออกทั่วโลก
                    </p>
                    <div class="footer-social" id="footer-company-social">
                        <!-- Loaded dynamically or shared from followus -->
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="footer-column">
                    <h4 id="footer-quicklinks-title">ลิงก์ด่วน</h4>
                    <ul class="footer-links" id="footer-quicklinks-menu">
                        <!-- Loaded dynamically -->
                    </ul>
                </div>
                
                <!-- Support -->
                <div class="footer-column">
                    <h4 id="footer-support-title">ช่วยเหลือ</h4>
                    <ul class="footer-links" id="footer-support-menu">
                        <!-- Loaded dynamically -->
                        <li><a href="<?= BASE_URL ?>/faqs/">คำถามที่พบบ่อย</a></li>
                    </ul>
                </div>
                
                <!-- Newsletter -->
                <div class="footer-column">
                    <h4>รับข่าวสาร</h4>
                    <p style="font-size: var(--font-size-sm); color: rgba(245, 241, 232, 0.8); margin-bottom: var(--space-4);">
                        สมัครรับข้อมูลโปรโมชัน ผลิตภัณฑ์ใหม่ และบทความสุขภาพ
                    </p>
                    <form class="newsletter-form" id="newsletter-form" onsubmit="App.subscribeNewsletter(event)">
                        <input 
                            type="email" 
                            class="newsletter-input" 
                            placeholder="อีเมลของคุณ" 
                            required
                            name="email"
                        >
                        <button type="submit" class="btn btn-accent btn-sm">
                            สมัคร
                        </button>
                    </form>
                </div>
                
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom border-top" style="border-color: rgba(255, 255, 255, 0.1) !important;">
                <!-- Trust Badges -->
                <div class="trust-badges">
                    <div class="trust-badge">
                        <img src="<?= BASE_URL ?>/assets/images/badges/gmp.png" alt="GMP Certified" onerror="this.style.display='none'">
                    </div>
                    <div class="trust-badge">
                        <img src="<?= BASE_URL ?>/assets/images/badges/fda.png" alt="FDA Approved" onerror="this.style.display='none'">
                    </div>
                    <div class="trust-badge" style="color: var(--esther-gold); font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold);">
                        <i data-lucide="globe" width="20" height="20" style="margin-right: 8px;"></i>
                        ส่งออก 50+ ประเทศ
                    </div>
                </div>
                
                <!-- Social Links (Follow Us Dynamic) -->
                <div class="footer-social-bottom" id="footer-followus-menu">
                    <!-- Loaded dynamically -->
                </div>

                <!-- Copyright -->
                <p class="footer-copyright">
                    &copy; <?= date('Y') ?> Esther Aroma (Yak Pho). All rights reserved. | 
                    <a href="<?= BASE_URL ?>/sitemap.xml" style="color: var(--esther-gold);">Sitemap</a>
                </p>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/cart.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/footer-menus.js"></script>
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?= BASE_URL ?>/assets/js/<?= $js ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
