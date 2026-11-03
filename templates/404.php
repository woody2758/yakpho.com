<?php
$currentLang = $SITE['lang'] ?? 'th';
?>
<section class="section-pad bg-surface-secondary">
    <div class="anona-container-narrow">
        <div class="checkout-card-box text-center section-pad">
            <span class="section-eyebrow">ERROR 404</span>
            <div class="section-script-title">Page Not Found</div>
            <h1 class="section-main-title">
                <?= $currentLang === 'th' ? 'ไม่พบหน้าที่คุณกำลังค้นหา' : 'The Page You Requested Could Not Be Found' ?>
            </h1>
            <p class="section-subtitle mb-4">
                <?= $currentLang === 'th'
                    ? 'ลิงก์อาจมีการเปลี่ยนแปลง หรือสินค้าที่คุณค้นหาอาจถูกย้ายหมวดหมู่'
                    : 'The link may have changed or the product you are looking for has moved.' ?>
            </p>
            <div class="d-flex justify-center flex-wrap gap-2">
                <a href="<?= URL_PATH ?>/<?= $currentLang ?>/" class="btn-anona-primary">
                    <i data-lucide="home" width="16" height="16"></i>
                    <span><?= __t('nav_home', $currentLang) ?></span>
                </a>
                <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products" class="btn-anona-outline">
                    <i data-lucide="shopping-bag" width="16" height="16"></i>
                    <span><?= __t('nav_products', $currentLang) ?></span>
                </a>
            </div>
        </div>
    </div>
</section>
