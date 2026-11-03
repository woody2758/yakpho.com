<?php
$currentLang = $SITE['lang'] ?? 'th';
$contactInfo = $SITE['contact'] ?? [];
$faqs = $STORES_DATA['faqs'] ?? [];
$baseRoute = trim((string)($base ?? ''), '/');
$isFaqsRoute = ($baseRoute === 'faqs');

// Group FAQs by category (การ์ดใหญ่ เป็นหมวด)
$faqsByCategory = [];
foreach ($faqs as $faq) {
    $catName = trim((string)($faq['category_name'] ?? ''));
    if ($catName === '') {
        $catName = ($currentLang === 'th' ? 'คำถามทั่วไป' : 'General Questions');
    }
    $faqsByCategory[$catName][] = $faq;
}
?>

<div class="yp-container" style="padding-top: 16px; padding-bottom: 60px;">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" style="display:flex; align-items:center; gap:6px; font-size:0.8rem; color:var(--yp-text-muted); margin-bottom:16px;">
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>" style="color:inherit; text-decoration:none;">หน้าแรก</a>
        <span>/</span>
        <span style="color:var(--yp-text); font-weight:600;">
            <?= $isFaqsRoute ? ($currentLang === 'th' ? 'คำถามที่พบบ่อย (FAQs)' : 'Frequently Asked Questions') : ($currentLang === 'th' ? 'ติดต่อเรา & ศูนย์กระจายสินค้า' : 'Contact Us & Stores') ?>
        </span>
    </nav>

    <?php if ($isFaqsRoute): ?>
        <!-- Page Header -->
        <div style="margin-bottom: 22px;">
            <h1 style="font-family:var(--yp-font-display); font-size:1.35rem; font-weight:700; color:var(--yp-text); margin:0 0 6px 0;">
                <?= $currentLang === 'th' ? 'คำถามที่พบบ่อย (FAQs)' : 'Frequently Asked Questions' ?>
            </h1>
            <p style="font-size:0.86rem; color:var(--yp-text-secondary); margin:0;">
                <?= $currentLang === 'th' ? 'รวบรวมข้อสงสัยเกี่ยวกับผลิตภัณฑ์บาล์มสปา การสั่งซื้อราคาส่ง และการจัดส่งสินค้า ยักษ์โพธิ์ อโรม่า' : 'Answers regarding herbal products, B2B wholesale rates, ordering and delivery.' ?>
            </p>
        </div>

        <!-- 1. FAQs Grouped by Category as Big Cards (การ์ดใหญ่ เป็นหมวด) -->
        <?php if (empty($faqsByCategory)): ?>
            <div class="faq-category-card">
                <p style="font-size:0.88rem; color:var(--yp-text-muted); margin:0;">มีคำถามเพิ่มเติม? ติดต่อฝ่ายบริการลูกค้าทาง LINE ได้ตลอด 24 ชม.</p>
            </div>
        <?php else: ?>
            <?php foreach ($faqsByCategory as $catTitle => $catItems): ?>
                <div class="faq-category-card">
                    <!-- Category Header -->
                    <div class="faq-category-header">
                        <div class="faq-category-left">
                            <div class="faq-category-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            </div>
                            <h2 class="faq-category-title">
                                <?= htmlspecialchars($catTitle) ?>
                            </h2>
                        </div>
                        <span class="faq-count-pill">
                            <?= count($catItems) ?> ข้อ
                        </span>
                    </div>

                    <!-- Accordion List for this category (Accordion เป็นหัวข้อและคำตอบ) -->
                    <div class="faq-accordion-group">
                        <?php foreach ($catItems as $idx => $faq): ?>
                            <details class="faq-accordion-item" <?= ($idx === 0) ? 'open' : '' ?>>
                                <summary class="faq-accordion-summary">
                                    <div class="faq-question-text">
                                        <span class="faq-q-badge">Q:</span>
                                        <span><?= htmlspecialchars($faq['faqs_name']) ?></span>
                                    </div>
                                    <svg class="faq-accordion-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </summary>
                                <div class="faq-accordion-body">
                                    <div class="faq-answer-inner">
                                        <span class="faq-a-badge">A:</span>
                                        <div style="flex:1;"><?= nl2br(htmlspecialchars(strip_tags((string)$faq['faqs_detail']))) ?></div>
                                    </div>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- 2. Still have questions? Contact & Track Box below FAQs -->
        <div style="margin-top: 32px; margin-bottom: 12px;">
            <h3 style="font-family:var(--yp-font-display); font-size:1.1rem; font-weight:700; color:var(--yp-text); margin:0 0 14px 0;">
                ยังมีข้อสงสัยเพิ่มเติม หรือต้องการติดตามพัสดุ?
            </h3>
        </div>
    <?php endif; ?>

    <!-- Contact & Info Grid (2 Columns on Desktop) -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:16px; margin-bottom:24px;">
        <!-- Left: Headquarters & Channels -->
        <div style="background:var(--yp-card-bg); border-radius:var(--radius); padding:20px; box-shadow:var(--yp-shadow-sm); border:1px solid var(--yp-border-light);">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--yp-primary);"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <h2 style="font-family:var(--yp-font-display); font-size:1.1rem; font-weight:700; color:var(--yp-text); margin:0;">
                    สำนักงานใหญ่ &amp; ศูนย์กระจายสินค้า
                </h2>
            </div>

            <p style="font-size:0.85rem; color:var(--yp-text-secondary); line-height:1.6; margin-bottom:16px;">
                <strong><?= htmlspecialchars($contactInfo['company_name'] ?? 'บริษัท เอสเธอร์ อโรม่า จำกัด') ?></strong><br>
                <?= htmlspecialchars($contactInfo['address'] ?? 'ศูนย์กระจายสินค้าบาล์มนวดสมุนไพรและสปาอโรม่า') ?>
            </p>

            <div style="display:flex; flex-direction:column; gap:8px; font-size:0.84rem;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="width:28px; height:28px; border-radius:50%; background:var(--yp-primary-surface); color:var(--yp-primary); display:flex; align-items:center; justify-content:center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    </span>
                    <span><strong>โทรศัพท์:</strong> <?= htmlspecialchars($contactInfo['phone'] ?? '080-061-7073') ?></span>
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="width:28px; height:28px; border-radius:50%; background:#EBFBEE; color:#06C755; display:flex; align-items:center; justify-content:center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    </span>
                    <a href="https://line.me/R/ti/p/@yakpho" target="_blank" rel="noopener" style="color:var(--yp-text); text-decoration:none;">
                        <strong>LINE Official:</strong> <span style="color:#06C755; font-weight:700;"><?= htmlspecialchars($contactInfo['line_id'] ?? '@yakpho') ?></span>
                    </a>
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="width:28px; height:28px; border-radius:50%; background:var(--yp-primary-surface); color:var(--yp-primary); display:flex; align-items:center; justify-content:center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                    </span>
                    <span><strong>Email:</strong> <?= htmlspecialchars($contactInfo['email'] ?? 'thaiherbfc@gmail.com') ?></span>
                </div>
            </div>
        </div>

        <!-- Right: Order Tracking Card -->
        <div style="background:var(--yp-card-bg); border-radius:var(--radius); padding:20px; box-shadow:var(--yp-shadow-sm); border:1px solid var(--yp-border-light);">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--yp-primary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <h2 style="font-family:var(--yp-font-display); font-size:1.1rem; font-weight:700; color:var(--yp-text); margin:0;">
                    เช็คสถานะคำสั่งซื้อ &amp; เลขพัสดุ
                </h2>
            </div>
            <p style="font-size:0.84rem; color:var(--yp-text-secondary); margin-bottom:14px;">
                กรอกเลขที่คำสั่งซื้อ หรือเบอร์โทรศัพท์ที่ใช้สั่งซื้อ เพื่อตรวจสอบสถานะและติดตามพัสดุ
            </p>
            <div style="display:flex; gap:8px;">
                <input type="text" id="storeTrackQuery" placeholder="กรอกเลขคำสั่งซื้อ หรือเบอร์โทร..."
                       style="flex:1; height:40px; border-radius:var(--radius-pill); border:1px solid var(--yp-border); padding:0 14px; font-size:0.86rem; outline:none; background:var(--yp-surface);">
                <button type="button" onclick="lookupTrackDirect()" style="background:var(--yp-primary); color:#FFF; padding:0 18px; border-radius:var(--radius-pill); font-size:0.84rem; font-weight:600;">
                    ค้นหา
                </button>
            </div>
            <div id="storeTrackResult" style="display:none; margin-top:12px; font-size:0.82rem; padding:12px; border-radius:var(--radius-sm); background:var(--yp-surface); border:1px solid var(--yp-border);"></div>
            <script>
            function lookupTrackDirect() {
                const q = document.getElementById('storeTrackQuery').value.trim();
                const res = document.getElementById('storeTrackResult');
                if (!q) { showToast('กรุณากรอกเลขคำสั่งซื้อหรือเบอร์โทรศัพท์', 'error'); return; }
                res.style.display = 'block';
                res.innerHTML = 'กำลังค้นหาข้อมูลพัสดุ...';
                fetch(URL_PATH + '/api/cart.php?action=track&q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(data => {
                        if (data.status && data.order) {
                            res.innerHTML = '<strong style="color:var(--yp-primary);">พบคำสั่งซื้อ #' + data.order.order_id + '</strong><br>สถานะ: ' + (data.order.status_name || 'กำลังเตรียมพัสดุ') + (data.order.tracking_number ? '<br>เลขพัสดุ: <strong>' + data.order.tracking_number + '</strong>' : '');
                        } else {
                            res.innerHTML = '<span style="color:var(--yp-text-muted);">ไม่พบข้อมูลคำสั่งซื้อ กรุณาตรวจสอบความถูกต้อง</span>';
                        }
                    }).catch(() => {
                        res.innerHTML = '<span style="color:var(--yp-text-muted);">ไม่พบข้อมูลคำสั่งซื้อ กรุณาตรวจสอบความถูกต้อง</span>';
                    });
            }
            </script>
        </div>
    </div>

    <?php if (!$isFaqsRoute): ?>
        <!-- Frequently Asked Questions (FAQs) Accordion when on Contact page -->
        <div style="margin-top: 32px; margin-bottom: 16px;">
            <h2 style="font-family:var(--yp-font-display); font-size:1.2rem; font-weight:700; color:var(--yp-text); margin:0 0 6px 0;">
                คำถามที่พบบ่อย (FAQs)
            </h2>
        </div>

        <?php if (!empty($faqsByCategory)): ?>
            <?php foreach ($faqsByCategory as $catTitle => $catItems): ?>
                <div class="faq-category-card">
                    <div class="faq-category-header">
                        <div class="faq-category-left">
                            <div class="faq-category-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            </div>
                            <h3 class="faq-category-title">
                                <?= htmlspecialchars($catTitle) ?>
                            </h3>
                        </div>
                        <span class="faq-count-pill">
                            <?= count($catItems) ?> ข้อ
                        </span>
                    </div>

                    <div class="faq-accordion-group">
                        <?php foreach ($catItems as $idx => $faq): ?>
                            <details class="faq-accordion-item">
                                <summary class="faq-accordion-summary">
                                    <div class="faq-question-text">
                                        <span class="faq-q-badge">Q:</span>
                                        <span><?= htmlspecialchars($faq['faqs_name']) ?></span>
                                    </div>
                                    <svg class="faq-accordion-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </summary>
                                <div class="faq-accordion-body">
                                    <div class="faq-answer-inner">
                                        <span class="faq-a-badge">A:</span>
                                        <div style="flex:1;"><?= nl2br(htmlspecialchars(strip_tags((string)$faq['faqs_detail']))) ?></div>
                                    </div>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
