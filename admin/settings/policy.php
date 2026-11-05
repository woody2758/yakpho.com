<?php
$page_title = 'เธเธฑเธ”เธเธฒเธฃเธเนเธขเธเธฒเธขเนเธฅเธฐเธเนเธญเธเธณเธซเธเธ” (Terms & PDPA Policy)';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/policy_helper.php';

// Ensure policy tables & default seed exist
ensure_default_yakpho_policies($db);

$languages = [
    'th' => 'Thai (เธ เธฒเธฉเธฒเนเธ—เธข)',
    'en' => 'English (เธ เธฒเธฉเธฒเธญเธฑเธเธเธคเธฉ)',
    'zh' => 'Chinese (เธ เธฒเธฉเธฒเธเธตเธ)',
    'ja' => 'Japanese (เธ เธฒเธฉเธฒเธเธตเนเธเธธเนเธ)',
    'ko' => 'Korean (เธ เธฒเธฉเธฒเน€เธเธฒเธซเธฅเธต)',
    'de' => 'German (เธ เธฒเธฉเธฒเน€เธขเธญเธฃเธกเธฑเธ)',
    'fr' => 'French (เธ เธฒเธฉเธฒเธเธฃเธฑเนเธเน€เธจเธช)',
    'ru' => 'Russian (เธ เธฒเธฉเธฒเธฃเธฑเธชเน€เธเธตเธข)',
    'ar' => 'Arabic (เธ เธฒเธฉเธฒเธญเธฒเธซเธฃเธฑเธ)',
    'he' => 'Hebrew (เธ เธฒเธฉเธฒเธฎเธตเธเธฃเธน)'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $translations = $_POST['translations'] ?? [];
        foreach ($translations as $lang_code => $trans) {
            $termsTitle    = trim((string)($trans['terms_title'] ?? ''));
            $termsSubtitle = trim((string)($trans['terms_subtitle'] ?? ''));
            $termsHtml     = trim((string)($trans['terms_html'] ?? ''));

            $privacyTitle    = trim((string)($trans['privacy_title'] ?? ''));
            $privacySubtitle = trim((string)($trans['privacy_subtitle'] ?? ''));
            $privacyHtml     = trim((string)($trans['privacy_html'] ?? ''));

            if ($lang_code === 'th') {
                $stmtMainUpd = $db->prepare("
                    UPDATE yakpho_policies
                    SET terms_title = ?, terms_subtitle = ?, terms_html = ?,
                        privacy_title = ?, privacy_subtitle = ?, privacy_html = ?,
                        updated_at = NOW()
                    WHERE policy_id = 1
                ");
                $stmtMainUpd->execute([
                    $termsTitle, $termsSubtitle, $termsHtml,
                    $privacyTitle, $privacySubtitle, $privacyHtml
                ]);
            }

            $stmtCheck = $db->prepare("SELECT translation_id FROM yakpho_policies_translations WHERE policy_id = 1 AND lang_code = ?");
            $stmtCheck->execute([$lang_code]);
            $transId = $stmtCheck->fetchColumn();

            if ($transId) {
                $stmtUpd = $db->prepare("
                    UPDATE yakpho_policies_translations
                    SET terms_title = ?, terms_subtitle = ?, terms_html = ?,
                        privacy_title = ?, privacy_subtitle = ?, privacy_html = ?
                    WHERE translation_id = ?
                ");
                $stmtUpd->execute([
                    $termsTitle, $termsSubtitle, $termsHtml,
                    $privacyTitle, $privacySubtitle, $privacyHtml,
                    $transId
                ]);
            } else {
                $stmtIns = $db->prepare("
                    INSERT INTO yakpho_policies_translations
                    (policy_id, lang_code, terms_title, terms_subtitle, terms_html, privacy_title, privacy_subtitle, privacy_html)
                    VALUES (1, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtIns->execute([
                    $lang_code,
                    $termsTitle, $termsSubtitle, $termsHtml,
                    $privacyTitle, $privacySubtitle, $privacyHtml
                ]);
            }
        }

        $db->commit();
        $_SESSION['flash_msg'] = 'เธเธฑเธเธ—เธถเธเธเนเธญเธกเธนเธฅเธเนเธขเธเธฒเธขเนเธฅเธฐเธเนเธญเธเธณเธซเธเธ”เน€เธฃเธตเธขเธเธฃเนเธญเธขเนเธฅเนเธง';
        header('Location: policy.php');
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = 'Error: ' . $e->getMessage();
    }
}

// Fetch main & translations
$stmtMain = $db->query("SELECT * FROM yakpho_policies WHERE policy_id = 1 LIMIT 1");
$main_data = $stmtMain ? ($stmtMain->fetch(PDO::FETCH_ASSOC) ?: []) : [];

$stmtTrans = $db->query("SELECT * FROM yakpho_policies_translations WHERE policy_id = 1");
$current_trans = [];
if ($stmtTrans) {
    while ($row = $stmtTrans->fetch(PDO::FETCH_ASSOC)) {
        $current_trans[$row['lang_code']] = $row;
    }
}

ob_start();
?>

<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1"><i data-lucide="shield-check"></i> เธเธฑเธ”เธเธฒเธฃเธเนเธขเธเธฒเธขเนเธฅเธฐเธเนเธญเธเธณเธซเธเธ” (Terms &amp; PDPA Policy)</h1>
        <p class="text-muted small mb-0">เนเธเนเนเธเน€เธเธทเนเธญเธซเธฒเธซเธเนเธฒเธเนเธญเธเธณเธซเธเธ”เนเธฅเธฐเน€เธเธทเนเธญเธเนเธเธเธฒเธฃเนเธเนเธเธฃเธดเธเธฒเธฃ เนเธฅเธฐเธเนเธขเธเธฒเธขเธเธงเธฒเธกเน€เธเนเธเธชเนเธงเธเธ•เธฑเธง (PDPA) เนเธชเธ”เธเธเธฅเธ—เธตเนเธซเธเนเธฒเนเธเธฃเนเธเธฅเนเธฅเธนเธเธเนเธฒเนเธฅเธฐเธเธธเธ•เน€เธ•เธญเธฃเนเน€เธงเนเธเนเธเธ•เน</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/th/policy?tab=terms" target="_blank" class="btn btn-outline-success btn-sm">
            <i data-lucide="external-link" style="width:14px;height:14px;"></i> เธ”เธนเธซเธเนเธฒเธเนเธญเธเธณเธซเธเธ” (Terms)
        </a>
        <a href="<?= BASE_URL ?>/th/policy?tab=pdpa" target="_blank" class="btn btn-outline-primary btn-sm">
            <i data-lucide="external-link" style="width:14px;height:14px;"></i> เธ”เธนเธซเธเนเธฒเธเนเธขเธเธฒเธข (PDPA)
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['flash_msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_msg']); ?>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i data-lucide="file-text" class="me-1" style="width:18px;height:18px;"></i> เนเธเนเนเธเน€เธเธทเนเธญเธซเธฒเธเนเธขเธเธฒเธขเนเธเธเธซเธฅเธฒเธขเธ เธฒเธฉเธฒ</h5>
            <button type="submit" class="btn btn-primary px-4">
                <i data-lucide="save"></i> เธเธฑเธเธ—เธถเธเธเนเธญเธกเธนเธฅเธ—เธฑเนเธเธซเธกเธ”
            </button>
        </div>
        <div class="card-body">
            <!-- Language Selector Dropdown -->
            <div class="language-dropdown-container mb-3" data-content-selector="#policyLangTabsContent"></div>

            <div id="policyLangTabsContent">
                <?php foreach ($languages as $code => $name):
                    $tData = $current_trans[$code] ?? [];
                    if ($code === 'th' && empty($tData)) {
                        $tData = $main_data;
                    }
                    $isRTL = in_array($code, ['ar', 'he'], true);
                    $dirAttr = $isRTL ? 'dir="rtl"' : '';
                ?>
                    <div data-lang="<?= $code ?>">
                        <div class="row g-4">
                            <!-- Left/Top Section: Terms of Service -->
                            <div class="col-12">
                                <div class="p-3 rounded-3 border" style="background: rgba(16, 185, 129, 0.03);">
                                    <h6 class="fw-bold text-success mb-3">
                                        <i data-lucide="file-check-2" style="width:16px;height:16px;"></i>
                                        เธชเนเธงเธเธ—เธตเน 1: เธเนเธญเธเธณเธซเธเธ”เนเธฅเธฐเน€เธเธทเนเธญเธเนเธเธเธฒเธฃเนเธเนเธเธฃเธดเธเธฒเธฃ (Terms &amp; Conditions) โ€” <?= htmlspecialchars($name) ?>
                                    </h6>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">เธซเธฑเธงเธเนเธญเธซเธฅเธฑเธ (Terms Title) <?= $code === 'th' ? '<span class="text-danger">*</span>' : '' ?></label>
                                            <input type="text"
                                                   name="translations[<?= $code ?>][terms_title]"
                                                   class="form-control"
                                                   value="<?= htmlspecialchars($tData['terms_title'] ?? '') ?>"
                                                   placeholder="เน€เธเนเธ เธเนเธญเธเธณเธซเธเธ”เนเธฅเธฐเน€เธเธทเนเธญเธเนเธเธเธฒเธฃเนเธเนเธเธฃเธดเธเธฒเธฃ (Terms & Conditions)"
                                                   <?= $code === 'th' ? 'required' : '' ?> <?= $dirAttr ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">เธเธณเนเธเธฃเธขเธขเนเธญเธข (Terms Subtitle)</label>
                                            <input type="text"
                                                   name="translations[<?= $code ?>][terms_subtitle]"
                                                   class="form-control"
                                                   value="<?= htmlspecialchars($tData['terms_subtitle'] ?? '') ?>"
                                                   placeholder="เธเธณเธญเธเธดเธเธฒเธขเธชเธฑเนเธเน เนเธ•เนเธซเธฑเธงเธเนเธญ" <?= $dirAttr ?>>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label fw-semibold">เน€เธเธทเนเธญเธซเธฒเธเนเธญเธเธณเธซเธเธ”เนเธฅเธฐเน€เธเธทเนเธญเธเนเธ (HTML Content)</label>
                                        <textarea name="translations[<?= $code ?>][terms_html]"
                                                  class="form-control summernote"
                                                  rows="10" <?= $dirAttr ?>><?= htmlspecialchars($tData['terms_html'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Right/Bottom Section: Privacy Policy (PDPA) -->
                            <div class="col-12">
                                <div class="p-3 rounded-3 border" style="background: rgba(59, 130, 246, 0.03);">
                                    <h6 class="fw-bold text-primary mb-3">
                                        <i data-lucide="shield" style="width:16px;height:16px;"></i>
                                        เธชเนเธงเธเธ—เธตเน 2: เธเนเธขเธเธฒเธขเธเธงเธฒเธกเน€เธเนเธเธชเนเธงเธเธ•เธฑเธง (PDPA) &amp; เธกเธฒเธ•เธฃเธเธฒเธเธเธฒเธฃเธเธฃเธดเธเธฒเธฃ โ€” <?= htmlspecialchars($name) ?>
                                    </h6>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">เธซเธฑเธงเธเนเธญเธซเธฅเธฑเธ (Privacy / PDPA Title) <?= $code === 'th' ? '<span class="text-danger">*</span>' : '' ?></label>
                                            <input type="text"
                                                   name="translations[<?= $code ?>][privacy_title]"
                                                   class="form-control"
                                                   value="<?= htmlspecialchars($tData['privacy_title'] ?? '') ?>"
                                                   placeholder="เน€เธเนเธ เธเนเธขเธเธฒเธขเธเธงเธฒเธกเน€เธเนเธเธชเนเธงเธเธ•เธฑเธง (PDPA) & เธกเธฒเธ•เธฃเธเธฒเธเธเธฒเธฃเธเธฃเธดเธเธฒเธฃ"
                                                   <?= $code === 'th' ? 'required' : '' ?> <?= $dirAttr ?>>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">เธเธณเนเธเธฃเธขเธขเนเธญเธข (Privacy Subtitle)</label>
                                            <input type="text"
                                                   name="translations[<?= $code ?>][privacy_subtitle]"
                                                   class="form-control"
                                                   value="<?= htmlspecialchars($tData['privacy_subtitle'] ?? '') ?>"
                                                   placeholder="เธเธณเธญเธเธดเธเธฒเธขเธชเธฑเนเธเน เนเธ•เนเธซเธฑเธงเธเนเธญ" <?= $dirAttr ?>>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label fw-semibold">เน€เธเธทเนเธญเธซเธฒเธเนเธขเธเธฒเธขเธเธงเธฒเธกเน€เธเนเธเธชเนเธงเธเธ•เธฑเธง PDPA &amp; เธกเธฒเธ•เธฃเธเธฒเธเธเธฃเธดเธเธฒเธฃ (HTML Content)</label>
                                        <textarea name="translations[<?= $code ?>][privacy_html]"
                                                  class="form-control summernote"
                                                  rows="10" <?= $dirAttr ?>><?= htmlspecialchars($tData['privacy_html'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer text-end bg-transparent border-top pt-3 pb-3">
            <button type="submit" class="btn btn-primary px-5">
                <i data-lucide="save"></i> เธเธฑเธเธ—เธถเธเธเนเธญเธกเธนเธฅเธเนเธขเธเธฒเธข
            </button>
        </div>
    </div>
</form>

<script src="<?= ADMIN_URL ?>/assets/js/language-dropdown.js"></script>

<?php
$extra_scripts = "
<script>
$(document).ready(function() {
    $('.summernote').summernote({
        height: 280,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });
});
</script>
";

$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php';
?>

