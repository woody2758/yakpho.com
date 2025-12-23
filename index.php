<?php
/**
 * Esther Aroma - Homepage
 * Biblical Wellness E-Commerce
 */

// Start session
session_start();

// Include configuration
require_once __DIR__ . '/includes/config.php';

// Page configuration
$page_title = 'Esther Aroma - Biblical Wellness from Ancient Soil to Modern Healing';
$page_description = 'Premium aromatherapy and wellness products from biblical herbs. Features Esther, Yak Pho, Boaz & Asher brands. GMP certified, exported to 50+ countries.';
$page_keywords = 'esther aroma, yak pho, biblical wellness, aromatherapy, natural products, herbal remedies';
$additional_css = ['homepage', 'hero-slider'];
$additional_js = ['homepage', 'hero-slider'];

// Include header
include __DIR__ . '/includes/layout/header.php';
?>

<!-- Hero Section (Dynamic Slider) -->
<section class="hero">
    <!-- Hero Slider will be loaded via JavaScript -->
    <div class="container">
        <div class="hero-content">
            <div class="spinner" style="margin: 0 auto;"></div>
            <p style="text-align: center; color: var(--esther-cream); margin-top: var(--space-4);">Loading...</p>
        </div>
    </div>
</section>

<!-- Brands Section -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">4 แบรนด์ความงามจากธรรมชาติ</h2>
            <p class="section-subtitle">
                เลือกแบรนด์ที่เหมาะกับความต้องการของคุณ
            </p>
        </div>
        
        <div class="brands-grid">
            <!-- Esther -->
            <a href="<?= BASE_URL ?>/shop/?brand=esther" class="brand-card">
                <div class="brand-icon">
                    <i data-lucide="sparkles" width="40" height="40"></i>
                </div>
                <h3 class="brand-name">Esther</h3>
                <p class="brand-description">
                    ผลิตภัณฑ์สกินแคร์และน้ำหอมระดับพรีเมียม 
                    สำหรับทุกเพศทุกวัย
                </p>
            </a>
            
            <!-- Yak Pho -->
            <a href="<?= BASE_URL ?>/shop/?brand=yakpho" class="brand-card">
                <div class="brand-icon">
                    <i data-lucide="package" width="40" height="40"></i>
                </div>
                <h3 class="brand-name">Yak Pho</h3>
                <p class="brand-description">
                    น้ำมันนวดบำรุงกล้ามเนื้อ 3 สูตร 
                    (ร้อน เย็น นวดตัว)
                </p>
            </a>
            
            <!-- Boaz -->
            <a href="<?= BASE_URL ?>/shop/?brand=boaz" class="brand-card">
                <div class="brand-icon">
                    <i data-lucide="shield" width="40" height="40"></i>
                </div>
                <h3 class="brand-name">Boaz</h3>
                <p class="brand-description">
                    ผลิตภัณฑ์บำรุงสำหรับผู้ชาย 
                    เสริมสร้างความมั่นใจ
                </p>
            </a>
            
            <!-- Asher -->
            <a href="<?= BASE_URL ?>/shop/?brand=asher" class="brand-card">
                <div class="brand-icon">
                    <i data-lucide="heart" width="40" height="40"></i>
                </div>
                <h3 class="brand-name">Asher</h3>
                <p class="brand-description">
                    ผลิตภัณฑ์สำหรับวัยรุ่น 
                    ดูแลผิวพรรณให้สดใส
                </p>
            </a>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="section" style="background-color: var(--esther-cream-dark);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">สินค้ายอดนิยม</h2>
            <p class="section-subtitle">
                คัดสรรผลิตภัณฑ์ขายดีประจำเดือน
            </p>
        </div>
        
        <div class="products-grid" id="featured-products">
            <!-- Products will be loaded via AJAX -->
            <div class="text-center" style="grid-column: 1 / -1;">
                <div class="spinner"></div>
                <p class="mt-4">กำลังโหลดสินค้า...</p>
            </div>
        </div>
        
        <div class="text-center mt-12">
            <a href="<?= BASE_URL ?>/shop/" class="btn btn-primary btn-lg">
                ดูผลิตภัณฑ์ทั้งหมด
                <i data-lucide="arrow-right" width="20" height="20"></i>
            </a>
        </div>
    </div>
</section>

<!-- Trust Section -->
<section class="trust-section">
    <div class="container">
        <h2 class="section-title" style="color: var(--esther-gold);">
            ความไว้วางใจที่คุณมอบให้
        </h2>
        <div class="trust-items">
            <div class="trust-item">
                <div class="trust-icon">
                    <i data-lucide="award" width="32" height="32"></i>
                </div>
                <h3 class="trust-title">ได้รับมาตรฐาน GMP & FDA</h3>
                <p class="trust-description">
                    ผลิตในโรงงานที่ได้มาตรฐาน GMP และผ่านการรับรอง FDA
                </p>
            </div>
            
            <div class="trust-item">
                <div class="trust-icon">
                    <i data-lucide="globe" width="32" height="32"></i>
                </div>
                <h3 class="trust-title">ส่งออกทั่วโลก 50+ ประเทศ</h3>
                <p class="trust-description">
                    ผลิตภัณฑ์คุณภาพระดับสากล ส่งออกไปยังประเทศต่างๆ ทั่วโลก
                </p>
            </div>
            
            <div class="trust-item">
                <div class="trust-icon">
                    <i data-lucide="leaf" width="32" height="32"></i>
                </div>
                <h3 class="trust-title">100% จากธรรมชาติ</h3>
                <p class="trust-description">
                    ส่วนผสมจากสมุนไพรธรรมชาติ ไม่มีสารเคมีที่เป็นอันตราย
                </p>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include __DIR__ . '/includes/layout/footer.php';
?>
