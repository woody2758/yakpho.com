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

    <!-- Photos Grid -->
    <?php if (empty($allImages)): ?>
        <div class="content-page-hero text-center py-5">
            <p class="text-muted"><?= $currentLang === 'th' ? 'ยังไม่มีรูปภาพในแกลเลอรีขณะนี้' : 'No gallery images available.' ?></p>
        </div>
    <?php else: ?>
        <div class="gallery-photos-grid" id="galleryGrid">
            <?php foreach ($allImages as $idx => $img): ?>
                <div class="gallery-photo-card" data-cat="<?= (int)$img['category_id'] ?>" data-url="<?= htmlspecialchars((string)$img['image_url']) ?>" onclick="openGalleryLightbox(this)">
                    <img src="<?= htmlspecialchars((string)$img['image_url']) ?>" alt="Yak Pho Gallery" class="gallery-photo-img" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Fullscreen Lightbox with Left/Right Arrows, Touch Swipe & Thumbnail Strip -->
<div class="yp-lightbox-backdrop d-none" id="ypGalleryLightbox" role="dialog" aria-modal="true">
    <button type="button" class="yp-lightbox-close" onclick="closeGalleryLightbox()" aria-label="Close">✕</button>

    <div class="yp-lightbox-stage" id="ypLightboxStage">
        <button type="button" class="yp-lightbox-nav yp-lightbox-prev" onclick="stepGalleryLightbox(-1)" aria-label="Previous photo">
            ‹
        </button>

        <div class="yp-lightbox-main-wrap" onclick="if(event.target === this) closeGalleryLightbox()">
            <img src="" alt="Gallery Preview" id="ypLightboxMainImg" class="yp-lightbox-main-img">
            <div class="yp-lightbox-counter" id="ypLightboxCounter">1 / 1</div>
        </div>

        <button type="button" class="yp-lightbox-nav yp-lightbox-next" onclick="stepGalleryLightbox(1)" aria-label="Next photo">
            ›
        </button>
    </div>

    <!-- Bottom Thumbnail Strip -->
    <div class="yp-lightbox-thumbs-bar" id="ypLightboxThumbs"></div>
</div>

<script>
let activeGalleryList = [];
let activeGalleryIndex = 0;
let touchStartX = 0;
let touchEndX = 0;

function filterGallery(catId, btn) {
    document.querySelectorAll('.gallery-filter-pill').forEach(b => b.classList.remove('is-active'));
    btn.classList.add('is-active');

    const cards = document.querySelectorAll('.gallery-photo-card');
    cards.forEach(card => {
        if (catId === 0 || parseInt(card.dataset.cat, 10) === catId) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function getVisibleGalleryUrls() {
    const cards = Array.from(document.querySelectorAll('.gallery-photo-card'));
    return cards
        .filter(c => c.style.display !== 'none')
        .map(c => c.getAttribute('data-url'))
        .filter(Boolean);
}

function openGalleryLightbox(cardEl) {
    const clickedUrl = cardEl.getAttribute('data-url');
    activeGalleryList = getVisibleGalleryUrls();
    if (!activeGalleryList.length) return;

    activeGalleryIndex = activeGalleryList.indexOf(clickedUrl);
    if (activeGalleryIndex < 0) activeGalleryIndex = 0;

    renderLightboxThumbnails();
    updateLightboxSlide(0, true);

    const modal = document.getElementById('ypGalleryLightbox');
    modal.classList.remove('d-none');
    requestAnimationFrame(() => modal.classList.add('is-open'));
    document.body.style.overflow = 'hidden';
}

function closeGalleryLightbox() {
    const modal = document.getElementById('ypGalleryLightbox');
    if (modal) {
        modal.classList.remove('is-open');
        setTimeout(() => modal.classList.add('d-none'), 200);
    }
    document.body.style.overflow = '';
}

function stepGalleryLightbox(delta) {
    if (!activeGalleryList.length) return;
    activeGalleryIndex = (activeGalleryIndex + delta + activeGalleryList.length) % activeGalleryList.length;
    updateLightboxSlide(delta, false);
}

function selectGalleryThumbnail(idx) {
    if (idx < 0 || idx >= activeGalleryList.length || idx === activeGalleryIndex) return;
    const dir = idx > activeGalleryIndex ? 1 : -1;
    activeGalleryIndex = idx;
    updateLightboxSlide(dir, false);
}

function renderLightboxThumbnails() {
    const bar = document.getElementById('ypLightboxThumbs');
    if (!bar) return;
    bar.innerHTML = activeGalleryList.map((url, idx) => `
        <button type="button" class="yp-lightbox-thumb-btn ${idx === activeGalleryIndex ? 'is-active' : ''}" data-idx="${idx}" onclick="selectGalleryThumbnail(${idx})">
            <img src="${url}" alt="Thumb ${idx + 1}" class="yp-lightbox-thumb-img" loading="lazy">
        </button>
    `).join('');
}

let galleryFadeTimer = null;
function updateLightboxSlide(direction = 0, instant = false) {
    const mainImg = document.getElementById('ypLightboxMainImg');
    const counter = document.getElementById('ypLightboxCounter');
    const url = activeGalleryList[activeGalleryIndex] || '';

    if (mainImg) {
        if (galleryFadeTimer) clearTimeout(galleryFadeTimer);
        if (instant) {
            mainImg.classList.remove('is-switching', 'is-switching-next', 'is-switching-prev');
            mainImg.src = url;
        } else {
            const fadeClass = direction > 0 ? 'is-switching-next' : (direction < 0 ? 'is-switching-prev' : 'is-switching');
            mainImg.classList.remove('is-switching', 'is-switching-next', 'is-switching-prev');
            mainImg.classList.add(fadeClass);

            galleryFadeTimer = setTimeout(() => {
                const preload = new Image();
                preload.onload = () => {
                    mainImg.src = url;
                    requestAnimationFrame(() => {
                        mainImg.classList.remove('is-switching', 'is-switching-next', 'is-switching-prev');
                    });
                };
                preload.onerror = () => {
                    mainImg.src = url;
                    mainImg.classList.remove('is-switching', 'is-switching-next', 'is-switching-prev');
                };
                preload.src = url;
            }, 140);
        }
    }
    if (counter) {
        counter.textContent = (activeGalleryIndex + 1) + ' / ' + activeGalleryList.length;
    }

    const thumbBtns = document.querySelectorAll('#ypLightboxThumbs .yp-lightbox-thumb-btn');
    thumbBtns.forEach((btn, idx) => {
        if (idx === activeGalleryIndex) {
            btn.classList.add('is-active');
            btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        } else {
            btn.classList.remove('is-active');
        }
    });
}

// Keyboard navigation (Left / Right / Escape)
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('ypGalleryLightbox');
    if (!modal || modal.classList.contains('d-none')) return;
    if (e.key === 'ArrowLeft') {
        e.preventDefault();
        stepGalleryLightbox(-1);
    } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        stepGalleryLightbox(1);
    } else if (e.key === 'Escape') {
        e.preventDefault();
        closeGalleryLightbox();
    }
});

// Touch Swipe Left / Right for Mobile
(function initGallerySwipe() {
    const stage = document.getElementById('ypLightboxStage');
    if (!stage) return;

    stage.addEventListener('touchstart', function(e) {
        if (e.changedTouches && e.changedTouches.length > 0) {
            touchStartX = e.changedTouches[0].screenX;
        }
    }, { passive: true });

    stage.addEventListener('touchend', function(e) {
        if (e.changedTouches && e.changedTouches.length > 0) {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 42) {
                if (diff > 0) {
                    // Swipe Left -> Next
                    stepGalleryLightbox(1);
                } else {
                    // Swipe Right -> Previous
                    stepGalleryLightbox(-1);
                }
            }
        }
    }, { passive: true });
})();
</script>
