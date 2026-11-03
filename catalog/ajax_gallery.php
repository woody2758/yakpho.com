<?php
include_once __DIR__ . "/includes/config.php";
include_once __DIR__ . "/includes/api_helper.php";

$lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'th';
$gRes = fetch_esther_api('gallery_content.php?lang=' . urlencode($lang));
$categories = $gRes ? ($gRes['data'] ?? []) : [];

?>
<div class="wrap">
  <h2 class="h2 center">Gallery</h2>
  
  <?php if (!empty($categories)): ?>
      <?php foreach ($categories as $cat): ?>
          <?php if (!empty($cat['images'])): ?>
              <h3 class="h3 center" style="margin-top: 40px; margin-bottom: 20px; font-weight: normal; color: #2E5A38;"><?= htmlspecialchars($cat['category_name']) ?></h3>
              <div class="gallery reveal">
                  <?php foreach ($cat['images'] as $img): ?>
                      <a href="<?= htmlspecialchars($img['image_url']) ?>" data-fancybox="gallery" data-no-loader="true" class="fade-in">
                          <img class="lazy" data-src="<?= htmlspecialchars($img['image_url']) ?>" alt="YakPho Gallery Image" loading="lazy">
                      </a>
                  <?php endforeach; ?>
              </div>
          <?php endif; ?>
      <?php endforeach; ?>
  <?php else: ?>
      <p style="text-align: center; color: #666;">No images found.</p>
  <?php endif; ?>
</div>
