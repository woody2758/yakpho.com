<?php
$currentLang = $SITE['lang'] ?? 'th';
$policyData  = $POLICY_DATA ?? [];
$activeTab   = $policyData['active_tab'] ?? 'terms';

$termsTitle    = $policyData['terms_title'] ?? ($currentLang === 'th' ? 'ข้อกำหนดและเงื่อนไขการใช้บริการ' : 'Terms & Conditions');
$termsSubtitle = $policyData['terms_subtitle'] ?? '';
$termsHtml     = $policyData['terms_html'] ?? '';

$privacyTitle    = $policyData['privacy_title'] ?? ($currentLang === 'th' ? 'นโยบายความเป็นส่วนตัว (PDPA) & มาตรฐานการบริการ' : 'Privacy Policy (PDPA) & Service Standards');
$privacySubtitle = $policyData['privacy_subtitle'] ?? '';
$privacyHtml     = $policyData['privacy_html'] ?? '';

$updatedDate = !empty($policyData['updated_at']) ? date('d/m/Y', strtotime((string)$policyData['updated_at'])) : date('d/m/Y');
?>

<div class="yp-container content-page-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="content-breadcrumb">
        <a href="<?= URL_PATH ?>/<?= $currentLang ?>"><?= $currentLang === 'th' ? 'หน้าแรก' : 'Home' ?></a>
        <span>/</span>
        <span><?= $currentLang === 'th' ? 'นโยบายและข้อกำหนดการใช้บริการ' : 'Policies & Terms of Service' ?></span>
    </nav>

    <!-- Hero Header -->
    <div class="content-page-hero">
        <span class="content-tag-gold">ESTHER AROMA &amp; YAK PHO LEGAL &amp; COMPLIANCE</span>
        <h1 class="content-page-title">
            <?= $currentLang === 'th' ? 'นโยบายและข้อกำหนดการใช้บริการ' : 'Terms of Service & Privacy Policy (PDPA)' ?>
        </h1>
        <p class="content-page-desc">
            <?= $currentLang === 'th'
                ? 'ข้อกำหนดและเงื่อนไขการสั่งซื้อ การคุ้มครองข้อมูลส่วนบุคคล (PDPA) และมาตรฐานการรับประกันคุณภาพผลิตภัณฑ์โดย บริษัท เอสเธอร์ อโรม่า จำกัด'
                : 'Official Terms & Conditions, Personal Data Protection Act (PDPA) policy, and quality assurance standards by Esther Aroma Co., Ltd.' ?>
        </p>

        <!-- Tab Switcher Pills -->
        <div class="policy-tabs-bar">
            <button type="button"
                    class="policy-tab-btn<?= $activeTab === 'terms' ? ' is-active' : '' ?>"
                    id="btnPolicyTabTerms"
                    onclick="switchPolicyTab('terms')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <span><?= $currentLang === 'th' ? 'ข้อกำหนดและเงื่อนไขการใช้บริการ' : 'Terms & Conditions' ?></span>
            </button>

            <button type="button"
                    class="policy-tab-btn<?= $activeTab === 'pdpa' ? ' is-active' : '' ?>"
                    id="btnPolicyTabPdpa"
                    onclick="switchPolicyTab('pdpa')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
                <span><?= $currentLang === 'th' ? 'นโยบายความเป็นส่วนตัว (PDPA) & มาตรฐานบริการ' : 'Privacy Policy (PDPA) & Standards' ?></span>
            </button>
        </div>
    </div>

    <!-- Tab 1: Terms & Conditions -->
    <div class="policy-content-card<?= $activeTab === 'terms' ? ' is-active' : '' ?>" id="policyPanelTerms">
        <div class="policy-card-head">
            <div class="policy-card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            </div>
            <div>
                <h2 class="policy-card-title"><?= htmlspecialchars($termsTitle) ?></h2>
                <?php if ($termsSubtitle !== ''): ?>
                    <p class="policy-card-sub"><?= htmlspecialchars($termsSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <span class="policy-updated-badge">
                <?= $currentLang === 'th' ? 'อัปเดตล่าสุด: ' : 'Updated: ' ?><?= htmlspecialchars($updatedDate) ?>
            </span>
        </div>

        <div class="policy-rich-body">
            <?= $termsHtml ?>
        </div>
    </div>

    <!-- Tab 2: Privacy Policy (PDPA) & Service Standards -->
    <div class="policy-content-card<?= $activeTab === 'pdpa' ? ' is-active' : '' ?>" id="policyPanelPdpa">
        <div class="policy-card-head">
            <div class="policy-card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
            </div>
            <div>
                <h2 class="policy-card-title"><?= htmlspecialchars($privacyTitle) ?></h2>
                <?php if ($privacySubtitle !== ''): ?>
                    <p class="policy-card-sub"><?= htmlspecialchars($privacySubtitle) ?></p>
                <?php endif; ?>
            </div>
            <span class="policy-updated-badge">
                <?= $currentLang === 'th' ? 'อัปเดตล่าสุด: ' : 'Updated: ' ?><?= htmlspecialchars($updatedDate) ?>
            </span>
        </div>

        <div class="policy-rich-body">
            <?= $privacyHtml ?>
        </div>
    </div>

    <!-- Bottom Support & Quick Links Card -->
    <div class="content-page-hero d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
        <div>
            <h3 class="article-title mb-1"><?= $currentLang === 'th' ? 'มีข้อสงสัยเกี่ยวกับนโยบายหรือการสั่งซื้อ?' : 'Questions about our policies or wholesale orders?' ?></h3>
            <p class="content-page-desc"><?= $currentLang === 'th' ? 'ดูคำถามที่ถูกถามบ่อย (FAQs) หรือพูดคุยกับทีมงานแอดมินได้โดยตรง' : 'Browse our FAQs or chat directly with our support team.' ?></p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/faqs" class="profile-btn-sm-outline">
                <?= $currentLang === 'th' ? 'ดูคำถามที่ถูกถามบ่อย (FAQs)' : 'View FAQs' ?>
            </a>
            <a href="https://lin.ee/vyUtLZ6" target="_blank" rel="noopener" class="profile-btn-sm-primary">
                <?= $currentLang === 'th' ? 'ติดต่อแอดมินผ่าน LINE' : 'Chat via LINE' ?>
            </a>
        </div>
    </div>
</div>

<script>
function switchPolicyTab(tab) {
    const isPdpa = (tab === 'pdpa' || tab === 'privacy');
    const btnTerms = document.getElementById('btnPolicyTabTerms');
    const btnPdpa = document.getElementById('btnPolicyTabPdpa');
    const panelTerms = document.getElementById('policyPanelTerms');
    const panelPdpa = document.getElementById('policyPanelPdpa');

    if (btnTerms) btnTerms.classList.toggle('is-active', !isPdpa);
    if (btnPdpa) btnPdpa.classList.toggle('is-active', isPdpa);
    if (panelTerms) panelTerms.classList.toggle('is-active', !isPdpa);
    if (panelPdpa) panelPdpa.classList.toggle('is-active', isPdpa);

    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', isPdpa ? 'pdpa' : 'terms');
        window.history.replaceState({}, '', url.toString());
    } catch (e) {}
}
</script>

