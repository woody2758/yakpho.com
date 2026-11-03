<?php
$currentLang = $SITE['lang'] ?? 'th';
?>
<div class="yp-container error-404-container">
    <div class="content-page-hero error-404-card">
        <!-- 404 Icon -->
        <div class="error-404-icon-wrap">
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>

        <span class="content-tag-gold">
            ERROR 404 • PAGE NOT FOUND
        </span>

        <h1 class="content-page-title">
            <?= $currentLang === 'th' ? 'ไม่พบหน้าที่คุณกำลังค้นหา' : 'The Page You Requested Was Not Found' ?>
        </h1>

        <p class="content-page-desc">
            <?= $currentLang === 'th'
                ? 'ลิงก์ที่คุณเรียกอาจมีการเปลี่ยนแปลง หรือสินค้าที่คุณกำลังค้นหาอาจถูกย้ายหมวดหมู่ คุณสามารถกลับสู่หน้าหลักหรือเลือกชมสินค้าทั้งหมดได้ครับ'
                : 'The link you followed may be broken, or the page may have been removed. You can return home or explore our product collections.' ?>
        </p>

        <div class="error-404-actions">
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/" class="profile-btn-sm-primary error-404-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span><?= $currentLang === 'th' ? 'กลับหน้าแรก' : 'Back to Home' ?></span>
            </a>
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="profile-btn-sm-outline error-404-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="7" height="7" x="3" y="3" rx="1"></rect><rect width="7" height="7" x="14" y="3" rx="1"></rect><rect width="7" height="7" x="14" y="14" rx="1"></rect><rect width="7" height="7" x="3" y="14" rx="1"></rect></svg>
                <span><?= $currentLang === 'th' ? 'เลือกชมสินค้าทั้งหมด' : 'All Products' ?></span>
            </a>
        </div>
    </div>
</div>
