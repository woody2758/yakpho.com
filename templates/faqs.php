<?php
$currentLang = $SITE['lang'] ?? 'th';
$faqCategories = $STORES_DATA['faq_categories'] ?? [];
$allFaqs = $STORES_DATA['faqs'] ?? [];
?>

<div class="yp-container content-page-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="content-breadcrumb">
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>"><?= $currentLang === 'th' ? 'หน้าแรก' : 'Home' ?></a>
        <span>/</span>
        <span><?= $currentLang === 'th' ? 'คำถามที่ถูกถามบ่อย (FAQs)' : 'Frequently Asked Questions' ?></span>
    </nav>

    <!-- Hero Header -->
    <div class="content-page-hero">
        <span class="content-tag-gold">ESTHER AROMA &amp; YAK PHO HELP CENTER</span>
        <h1 class="content-page-title">
            <?= $currentLang === 'th' ? 'คำถามที่ถูกถามบ่อย (FAQs)' : 'Frequently Asked Questions' ?>
        </h1>
        <p class="content-page-desc">
            <?= $currentLang === 'th' 
                ? 'รวบรวมคำตอบข้อสงสัยเกี่ยวกับการสั่งซื้อ เรทราคาส่งร้านนวด สรรพคุณบาล์มสูตรต่างๆ และบริการจัดส่งพัสดุทั้งในและต่างประเทศ'
                : 'Find answers about ordering, spa wholesale tiers, balm ingredients, and domestic & international delivery.' ?>
        </p>
    </div>

    <?php if (empty($faqCategories)): ?>
        <div class="content-page-hero text-center py-5">
            <p class="text-muted"><?= $currentLang === 'th' ? 'ยังไม่มีข้อมูลคำถามในขณะนี้' : 'No FAQs available at the moment.' ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($faqCategories as $cat): ?>
            <?php $catItems = $cat['items'] ?? []; ?>
            <?php if (empty($catItems)) continue; ?>
            <div class="faq-group-box">
                <div class="faq-group-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span><?= htmlspecialchars($cat['category_name']) ?></span>
                </div>

                <div>
                    <?php foreach ($catItems as $faq): ?>
                        <div class="faq-accordion-item" id="faq-item-<?= (int)$faq['faq_id'] ?>">
                            <button type="button" class="faq-question-btn" onclick="toggleFaq(<?= (int)$faq['faq_id'] ?>)">
                                <span><?= htmlspecialchars($faq['question']) ?></span>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="faq-chevron"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>
                            <div class="faq-answer-collapse">
                                <?= nl2br(htmlspecialchars($faq['answer'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Still Have Questions Banner -->
    <div class="content-page-hero d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
        <div>
            <h3 class="article-title mb-1"><?= $currentLang === 'th' ? 'ยังมีข้อสงสัยเพิ่มเติม?' : 'Still have questions?' ?></h3>
            <p class="content-page-desc"><?= $currentLang === 'th' ? 'ติดต่อทีมงานแอดมินโรงงานและฝ่ายบริการลูกค้าได้โดยตรง' : 'Contact our dedicated spa wholesale support team.' ?></p>
        </div>
        <div>
            <a href="https://line.me/ti/p/@thaiherb.online" target="_blank" rel="noopener" class="profile-btn-sm-primary">
                <?= $currentLang === 'th' ? 'ติดต่อแอดมินผ่าน LINE' : 'Chat via LINE' ?>
            </a>
        </div>
    </div>
</div>

<script>
function toggleFaq(id) {
    const el = document.getElementById('faq-item-' + id);
    if (!el) return;
    el.classList.toggle('is-open');
}
</script>

