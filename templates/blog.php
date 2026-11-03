<?php
$currentLang = $SITE['lang'] ?? 'th';
$posts = $BLOG_DATA['posts'] ?? [];
$categories = $BLOG_DATA['categories'] ?? [];
?>

<div class="yp-container content-page-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="content-breadcrumb">
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>"><?= $currentLang === 'th' ? 'หน้าแรก' : 'Home' ?></a>
        <span>/</span>
        <span><?= $currentLang === 'th' ? 'บทความสมุนไพรนวดสปา (Articles & News)' : 'Articles & News' ?></span>
    </nav>

    <!-- Hero Header -->
    <div class="content-page-hero">
        <span class="content-tag-gold">WELLNESS KNOWLEDGE &amp; SPA INSIGHTS</span>
        <h1 class="content-page-title">
            <?= $currentLang === 'th' ? 'บทความสมุนไพรนวดสปา &amp; สาระน่ารู้' : 'Herbal Spa Articles & Insights' ?>
        </h1>
        <p class="content-page-desc">
            <?= $currentLang === 'th' 
                ? 'เรื่องราวความรู้เกี่ยวกับสมุนไพรไทย ศาสตร์การบำบัดกลิ่น (Aromatherapy) เคล็ดลับการบริหารร้านสปา และมาตรฐานบาล์มส่งออกสากล'
                : 'Ancient Thai herbal wisdom, aromatherapy science, spa business insights, and international export standards.' ?>
        </p>
    </div>

    <?php if (empty($posts)): ?>
        <div class="content-page-hero text-center py-5">
            <p class="text-muted"><?= $currentLang === 'th' ? 'ยังไม่มีบทความในขณะนี้' : 'No articles available at the moment.' ?></p>
        </div>
    <?php else: ?>
        <div class="article-grid">
            <?php foreach ($posts as $post): ?>
                <article class="article-card">
                    <div class="article-thumb-wrap">
                        <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="<?= htmlspecialchars($post['blog_name']) ?>" class="article-thumb-img" loading="lazy">
                        <span class="article-cat-badge">
                            <?= htmlspecialchars($post['blogcat_name']) ?>
                        </span>
                    </div>

                    <div class="article-body">
                        <div class="article-meta-row">
                            <span><?= date('d M Y', strtotime($post['blog_date'] ?? 'now')) ?></span>
                            <span>•</span>
                            <span><?= number_format((int)($post['blog_view'] ?? 0)) ?> views</span>
                        </div>
                        <h2 class="article-title"><?= htmlspecialchars($post['blog_name']) ?></h2>
                        <p class="article-excerpt-text"><?= htmlspecialchars($post['blog_excerpt'] ?: strip_tags($post['blog_detail'] ?? '')) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
