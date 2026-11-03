<?php
$page_title = 'จัดการส่วนหัว (Intro) สูตรนวดพิเศษ';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$intro_id = 1;
$translations = [];

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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($languages as $code => $name) {
        $intro_title = $_POST['intro_title_'.$code] ?? '';
        $intro_subtitle = $_POST['intro_subtitle_'.$code] ?? '';
        $intro_description = $_POST['intro_description_'.$code] ?? '';
        
        $stmt = $db->prepare("SELECT translation_id FROM yakpho_formula_intro_translations WHERE intro_id = ? AND lang_code = ?");
        $stmt->execute([$intro_id, $code]);
        if ($stmt->fetch()) {
            $update = $db->prepare("UPDATE yakpho_formula_intro_translations SET intro_title=?, intro_subtitle=?, intro_description=? WHERE intro_id=? AND lang_code=?");
            $update->execute([$intro_title, $intro_subtitle, $intro_description, $intro_id, $code]);
        } else {
            $insert = $db->prepare("INSERT INTO yakpho_formula_intro_translations (intro_id, lang_code, intro_title, intro_subtitle, intro_description) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$intro_id, $code, $intro_title, $intro_subtitle, $intro_description]);
        }
    }
    
    $_SESSION['flash_msg'] = "บันทึกข้อมูลสำเร็จ";
    header("Location: formula.php");
    exit;
}

// Fetch existing data
$stmt_tr = $db->prepare("SELECT * FROM yakpho_formula_intro_translations WHERE intro_id = ?");
$stmt_tr->execute([$intro_id]);
while ($row = $stmt_tr->fetch(PDO::FETCH_ASSOC)) {
    $translations[$row['lang_code']] = $row;
}

ob_start();
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1><i data-lucide="align-center"></i> จัดการส่วนหัว (Intro) สูตรนวด</h1>
    <a href="formula.php" class="btn btn-outline-secondary">
        <i data-lucide="arrow-left"></i> กลับไปหน้ารายการ
    </a>
</div>

<form method="POST">
    <div class="card">
        <div class="card-header"><h5 class="mb-0">ข้อมูลหลายภาษา</h5></div>
        <div class="card-body">
            <!-- Language Dropdown -->
            <div class="language-dropdown-container mb-3" data-content-selector="#langTabsContent"></div>
            
            <div id="langTabsContent">
                <?php foreach($languages as $code => $name): 
                    $tr = $translations[$code] ?? [];
                    $isRTL = in_array($code, ['ar', 'he']);
                    $dirAttr = $isRTL ? 'dir="rtl"' : '';
                ?>
                <div data-lang="<?= $code ?>">
                    <div class="mb-3">
                        <label class="form-label">หัวข้อหลัก (Title) - <?= $name ?></label>
                        <input type="text" name="intro_title_<?= $code ?>" class="form-control" value="<?= htmlspecialchars($tr['intro_title'] ?? '') ?>" <?= $dirAttr ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">คำโปรย (Subtitle) - <?= $name ?></label>
                        <input type="text" name="intro_subtitle_<?= $code ?>" class="form-control" value="<?= htmlspecialchars($tr['intro_subtitle'] ?? '') ?>" <?= $dirAttr ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">คำอธิบายเพิ่มเติม (Description) - <?= $name ?></label>
                        <textarea name="intro_description_<?= $code ?>" class="form-control" rows="4" <?= $dirAttr ?>><?= htmlspecialchars($tr['intro_description'] ?? '') ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer text-end bg-transparent border-top-0 pt-0 pb-3">
            <button type="submit" class="btn btn-primary px-4"><i data-lucide="save"></i> บันทึกข้อมูล</button>
        </div>
    </div>
</form>

<!-- Load Language Dropdown JS -->
<script src="<?= ADMIN_URL ?>/assets/js/language-dropdown.js"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
