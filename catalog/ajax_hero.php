<?php
include __DIR__ . "/includes/config.php";
include __DIR__ . "/includes/api_helper.php";

$heroRes = fetch_esther_api('hero.php', ['lang' => CURRENT_LANG]);
$heroSlides = $heroRes ? ($heroRes['data']['items'] ?? []) : [];

if (!empty($heroSlides)): ?>
    <!-- Swiper Container -->
    <div class="swiper heroSwiper" style="width: 100%; height: auto;">
      <div class="swiper-wrapper">
        <?php foreach ($heroSlides as $slide): ?>
          <div class="swiper-slide">
            <div class="wrap hero-grid" style="padding: 40px 20px;">
              <!-- 🖼️ Hero Image -->
              <div class="hero-image reveal show">
                <div class="card-hero fade-image">
                  <img src="<?= htmlspecialchars($slide['image_url'] ?? 'assets/img/hero.webp') ?>" alt="YakPho Aroma Banner">
                  <div class="fade-overlay"></div>
                </div>
              </div>

              <!-- ✨ Hero Text -->
              <div class="hero-text reveal show">
                <h1 class="h1"><?= htmlspecialchars($slide['slide_title'] ?? '') ?></h1>
                <p class="p" style="white-space: pre-line;"><?= htmlspecialchars($slide['slide_subtitle'] ?? '') ?></p>

                <?php if (!empty($slide['badges_array'])): ?>
                <div class="lead-badges">
                  <?php foreach ($slide['badges_array'] as $badge): ?>
                    <span class="badge-h"><?= htmlspecialchars($badge) ?></span>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
                  <?php 
                    $btn1_text = !empty($slide['btn1_text']) ? $slide['btn1_text'] : 'ดูเรทราคาส่ง';
                    $btn1_url = !empty($slide['btn1_url']) ? $slide['btn1_url'] : '#pricing';
                    $btn2_text = !empty($slide['btn2_text']) ? $slide['btn2_text'] : 'สั่งซื้อตอนนี้';
                    $btn2_url = !empty($slide['btn2_url']) ? $slide['btn2_url'] : 'order/';
                  ?>
                  <a href="<?= htmlspecialchars($btn1_url) ?>" class="btn secondary">
                    <i data-lucide="tags"></i> <?= htmlspecialchars($btn1_text) ?>
                  </a>
                  <a href="<?= htmlspecialchars($btn2_url) ?>" class="btn order-btn">
                    <i data-lucide="shopping-cart"></i> <?= htmlspecialchars($btn2_text) ?>
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if (count($heroSlides) > 1): ?>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-next" style="color: #2E5A38;"></div>
        <div class="swiper-button-prev" style="color: #2E5A38;"></div>
      <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Fallback Content -->
    <div class="wrap center py-5">
        <h2 class="h2">No Banners Found</h2>
    </div>
<?php endif; ?>
