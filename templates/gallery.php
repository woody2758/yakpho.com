<?php
$currentLang = $SITE['lang'] ?? 'th';
$galleryCategories = $STORES_DATA['gallery_categories'] ?? [];
$allImages = $STORES_DATA['gallery'] ?? [];
?>

<div class="yp-container content-page-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="content-breadcrumb">
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>"><?= $currentLang === 'th' ? 'หน้าแรก' : 'Home' ?></a>
        <span>/</span>
        <span><?= $currentLang === 'th' ? 'ภาพแกลเลอรี (Gallery)' : 'Gallery' ?></span>
    </nav>

    <!-- Hero Header -->
    <div class="content-page-hero">
        <span class="content-tag-gold">ESTHER AROMA &amp; YAK PHO GALLERY</span>
        <h1 class="content-page-title">
            <?= $currentLang === 'th' ? 'ภาพบรรยากาศแกลเลอรี' : 'Photo Gallery' ?>
        </h1>
        <p class="content-page-desc">
            <?= $currentLang === 'th' 
                ? 'ภาพบรรยากาศการเพาะปลูกสมุนไพรในฟาร์มออร์แกนิก กระบวนการผลิตมาตรฐาน GMP และภาพผลิตภัณฑ์ยักษ์โพธิ์ อโรม่า'
                : 'Botanic herb gardens, international GMP certified manufacturing, and signature Yak Pho Aroma collections.' ?>
        </p>
    </div>

    <!-- Category Filter Bar -->
    <?php if (!empty($galleryCategories)): ?>
    <div class="gallery-filter-bar">
        <button type="button" class="gallery-filter-pill is-active" onclick="filterGallery(0, this)">
            <?= $currentLang === 'th' ? 'ทั้งหมด' : 'All' ?> (<?= count($allImages) ?>)
        </button>
        <?php foreach ($galleryCategories as $cat): ?>
            <button type="button" class="gallery-filter-pill" onclick="filterGallery(<?= (int)$cat['category_id'] ?>, this)">
                <?= htmlspecialchars($cat['category_name']) ?> (<?= count($cat['images'] ?? []) ?>)
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Photos Grid -->
    <?php if (empty($allImages)): ?>
        <div class="content-page-hero text-center py-5">
            <p class="text-muted"><?= $currentLang === 'th' ? 'ยังไม่มีรูปภาพในแกลเลอรีขณะนี้' : 'No gallery images available.' ?></p>
        </div>
    <?php else: ?>
        <div class="gallery-photos-grid" id="galleryGrid">
            <?php foreach ($allImages as $img): ?>
                <div class="gallery-photo-card" data-cat="<?= (int)$img['category_id'] ?>" onclick="viewPhoto('<?= htmlspecialchars($img['image_url']) ?>')">
                    <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Yak Pho Gallery" class="gallery-photo-img" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function filterGallery(catId, btn) {
    document.querySelectorAll('.gallery-filter-pill').forEach(b => b.classList.remove('is-active'));
    btn.classList.add('is-active');

    const cards = document.querySelectorAll('.gallery-photo-card');
    cards.forEach(card => {
        if (catId === 0 || parseInt(card.dataset.cat) === catId) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function viewPhoto(url) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            imageUrl: url,
            imageAlt: 'Gallery Photo',
            showConfirmButton: false,
            showCloseButton: true,
            background: 'rgba(12, 40, 35, 0.96)',
            customClass: { popup: 'gallery-swal-modal' }
        });
    } else {
        window.open(url, '_blank');
    }
}
</script>

