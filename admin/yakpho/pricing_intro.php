<?php
$page_title = 'จัดการส่วนหัว เรทราคาส่ง';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Available languages
$languages = [
    'th' => 'Thai',
    'en' => 'English',
    'de' => 'German',
    'fr' => 'French',
    'zh' => 'Chinese',
    'ko' => 'Korean',
    'ja' => 'Japanese',
    'ru' => 'Russian',
    'ar' => 'Arabic',
    'he' => 'Hebrew'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $translations = $_POST['translations'] ?? [];
        foreach ($translations as $lang_code => $trans) {
            $title = trim($trans['intro_title']);
            $subtitle = trim($trans['intro_subtitle']);

            // Check if translation exists
            $stmtCheck = $db->prepare("SELECT translation_id FROM yakpho_pricing_intro_translations WHERE intro_id = 1 AND lang_code = ?");
            $stmtCheck->execute([$lang_code]);
            $transExists = $stmtCheck->fetchColumn();

            if ($transExists) {
                $stmtUpd = $db->prepare("UPDATE yakpho_pricing_intro_translations SET intro_title = ?, intro_subtitle = ? WHERE translation_id = ?");
                $stmtUpd->execute([$title, $subtitle, $transExists]);
            } else {
                $stmtIns = $db->prepare("INSERT INTO yakpho_pricing_intro_translations (intro_id, lang_code, intro_title, intro_subtitle) VALUES (1, ?, ?, ?)");
                $stmtIns->execute([$lang_code, $title, $subtitle]);
            }
        }

        // Trigger timestamp update
        $db->exec("UPDATE yakpho_pricing_intro SET updated_at = NOW() WHERE intro_id = 1");

        $db->commit();
        $_SESSION['flash_msg'] = "บันทึกข้อมูลเรียบร้อยแล้ว";
        header("Location: pricing.php");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch current data
$stmtTrans = $db->prepare("SELECT * FROM yakpho_pricing_intro_translations WHERE intro_id = 1");
$stmtTrans->execute();
$current_trans = [];
while ($row = $stmtTrans->fetch(PDO::FETCH_ASSOC)) {
    $current_trans[$row['lang_code']] = $row;
}

ob_start();
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1><i data-lucide="align-center"></i> จัดการส่วนหัว (Pricing Intro)</h1>
    <a href="pricing.php" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> กลับไปหน้าหลัก
    </a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">แก้ไขข้อมูลส่วนหัว (Title & Subtitle)</h5>
        </div>
        <div class="card-body">
            
            <!-- Language Dropdown -->
            <div class="language-dropdown-container mb-3" data-content-selector="#langTabsContent"></div>

            <div id="langTabsContent">
                <?php foreach ($languages as $code => $name): 
                    $tData = $current_trans[$code] ?? [];
                    $isRTL = in_array($code, ['ar', 'he']);
                    $dirAttr = $isRTL ? 'dir="rtl"' : '';
                ?>
                    <div data-lang="<?= $code ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">หัวข้อหลัก (Title) - <?= $name ?> <?= $code === 'th' ? '<span class="text-danger">*</span>' : '' ?></label>
                            <input type="text" name="translations[<?= $code ?>][intro_title]" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($tData['intro_title'] ?? '') ?>" 
                                   <?= $code === 'th' ? 'required' : '' ?> <?= $dirAttr ?>>
                            <small class="text-muted">เช่น: เรทราคาส่ง YakPho Aroma</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">คำโปรย (Subtitle) - <?= $name ?></label>
                            <input type="text" name="translations[<?= $code ?>][intro_subtitle]" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($tData['intro_subtitle'] ?? '') ?>" <?= $dirAttr ?>>
                            <small class="text-muted">เช่น: ราคาต่อกิโลกรัม สำหรับทุกสูตรและทุกกลิ่น</small>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
            
        </div>
        <div class="card-footer text-end bg-transparent border-top-0 pt-0 pb-3">
            <button type="submit" class="btn btn-primary px-4">
                <i data-lucide="save"></i> บันทึกข้อมูล
            </button>
        </div>
    </div>
</form>

<!-- Load Language Dropdown JS -->
<script src="<?= ADMIN_URL ?>/assets/js/language-dropdown.js"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php';
?>
