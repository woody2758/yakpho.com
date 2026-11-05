<?php
$currentLang = $SITE['lang'] ?? 'th';
$galleryCategories = $STORES_DATA['gallery_categories'] ?? [];
$allImages = $STORES_DATA['gallery'] ?? [];
$ver = time();
?>

<!-- Fancybox 5 Local Assets (Same engine as /catalog/#gallery-1) -->
<link rel="stylesheet" href="<?= URL_PATH ?>/assets/vendor/fancybox/fancybox.css?v=<?= $ver ?>">
<script src="<?= URL_PATH ?>/assets/vendor/fancybox/fancybox.umd.js?v=<?= $ver ?>"></script>

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
                ? 'ภาพบรรยากาศการเพาะปลูกสมุนไพรในฟาร์มออร์แกนิก กระบวนการปรุงสูตรและเคี่ยวยาหม่องสมุนไพรแท้ และภาพผลิตภัณฑ์ยักษ์โพธิ์ อโรม่า'
                : 'Botanic herb gardens, authentic Thai herbal brewing processes, and signature Yak Pho Aroma collections.' ?>
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
                <?= htmlspecialchars((string)$cat['category_name']) ?> (<?= count($cat['images'] ?? []) ?>)
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Photos Grid (Fancybox 5 Gallery) -->
    <?php if (empty($allImages)): ?>
        <div class="content-page-hero text-center py-5">
            <p class="text-muted"><?= $currentLang === 'th' ? 'ยังไม่มีรูปภาพในแกลเลอรีขณะนี้' : 'No gallery images available.' ?></p>
        </div>
    <?php else: ?>
        <div class="gallery-photos-grid" id="galleryGrid">
            <?php foreach ($allImages as $idx => $img): ?>
                <?php $imgUrl = htmlspecialchars((string)$img['image_url']); ?>
                <a href="<?= $imgUrl ?>"
                   class="gallery-photo-card"
                   data-cat="<?= (int)$img['category_id'] ?>"
                   data-fancybox="gallery"
                   data-thumb="<?= $imgUrl ?>">
                    <img src="<?= $imgUrl ?>" alt="Yak Pho Gallery" class="gallery-photo-img" loading="lazy">
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function bindYakphoFancybox() {
    if (typeof Fancybox === 'undefined') return;
    Fancybox.unbind('[data-fancybox]');
    Fancybox.bind('[data-fancybox="gallery"]', {
        animated: true,
        showClass: "f-fadeIn",
        hideClass: "f-fadeOut",
        dragToClose: true,
        Carousel: {
            transition: "classic",
            friction: 0.88
        },
        Thumbs: {
            type: "classic",
            autoStart: true
        },
        Toolbar: {
            display: {
                left: ["infobar"],
                middle: ["zoomIn", "zoomOut"],
                right: ["thumbs", "close"]
            }
        },
        Image: {
            zoom: true
        }
    });
}

function filterGallery(catId, btn) {
    document.querySelectorAll('.gallery-filter-pill').forEach(b => b.classList.remove('is-active'));
    btn.classList.add('is-active');

    const cards = document.querySelectorAll('.gallery-photo-card');
    cards.forEach(card => {
        if (catId === 0 || parseInt(card.dataset.cat, 10) === catId) {
            card.style.display = 'block';
            card.setAttribute('data-fancybox', 'gallery');
        } else {
            card.style.display = 'none';
            card.removeAttribute('data-fancybox');
        }
    });

    bindYakphoFancybox();
}

document.addEventListener('DOMContentLoaded', bindYakphoFancybox);
if (document.readyState !== 'loading') {
    bindYakphoFancybox();
}
</script>
