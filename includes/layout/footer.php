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
                    <h4>เกี่ยวกับเรา</h4>
                    <p style="font-size: var(--font-size-sm); line-height: var(--line-height-relaxed); color: rgba(245, 241, 232, 0.8);">
                        Esther Aroma นำเสนอผลิตภัณฑ์สุขภาพและความงามจากสมุนไพรตามคัมภีร์ไบเบิล 
                        มาตรฐาน GMP และ FDA ส่งออกทั่วโลก
                    </p>
                    <div class="footer-social">
                        <a href="https://facebook.com/estheraroma" class="social-icon" target="_blank" aria-label="Facebook">
                            <i data-lucide="facebook" width="20" height="20"></i>
                        </a>
                        <a href="https://instagram.com/estheraroma" class="social-icon" target="_blank" aria-label="Instagram">
                            <i data-lucide="instagram" width="20" height="20"></i>
                        </a>
                        <a href="https://line.me/R/ti/p/@estheraroma" class="social-icon" target="_blank" aria-label="LINE">
                            <i data-lucide="message-circle" width="20" height="20"></i>
                        </a>
                        <a href="mailto:contact@esther.co.th" class="social-icon" aria-label="Email">
                            <i data-lucide="mail" width="20" height="20"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="footer-column">
                    <h4>ลิงก์ด่วน</h4>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/shop/">ผลิตภัณฑ์ทั้งหมด</a></li>
                        <li><a href="<?= BASE_URL ?>/shop/?brand=yakpho">ยักโพธิ์</a></li>
                        <li><a href="<?= BASE_URL ?>/shop/?brand=esther">เอสเธอร์</a></li>
                        <li><a href="<?= BASE_URL ?>/shop/?brand=boaz">โบอาส</a></li>
                        <li><a href="<?= BASE_URL ?>/shop/?brand=asher">อาเชอร์</a></li>
                        <li><a href="<?= BASE_URL ?>/blog/">บล็อก</a></li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div class="footer-column">
                    <h4>ช่วยเหลือ</h4>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/faqs/">คำถามที่พบบ่อย</a></li>
                        <li><a href="<?= BASE_URL ?>/shipping/">การจัดส่ง</a></li>
                        <li><a href="<?= BASE_URL ?>/returns/">การคืนสินค้า</a></li>
                        <li><a href="<?= BASE_URL ?>/privacy/">นโยบายความเป็นส่วนตัว</a></li>
                        <li><a href="<?= BASE_URL ?>/terms/">ข้อกำหนดการใช้งาน</a></li>
                        <li><a href="<?= BASE_URL ?>/contact/">ติดต่อเรา</a></li>
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
            <div class="footer-bottom">
                <!-- Trust Badges -->
                <div class="trust-badges">
                    <div class="trust-badge">
                        <img src="<?= BASE_URL ?>/assets/images/badges/gmp.png" alt="GMP Certified">
                    </div>
                    <div class="trust-badge">
                        <img src="<?= BASE_URL ?>/assets/images/badges/fda.png" alt="FDA Approved">
                    </div>
                    <div class="trust-badge" style="color: var(--esther-gold); font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold);">
                        <i data-lucide="globe" width="20" height="20" style="margin-right: 8px;"></i>
                        ส่งออก 50+ ประเทศ
                    </div>
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
