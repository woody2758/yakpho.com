<?php
$page_title = isset($_GET['id']) ? 'แก้ไขจุดเด่นสินค้าราคาส่ง' : 'เพิ่มจุดเด่นสินค้าราคาส่ง';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$feature = null;

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

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM yakpho_pricing_features WHERE feature_id = ?");
    $stmt->execute([$id]);
    $feature = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$feature) {
        die("ไม่พบข้อมูล");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $feature_icon = trim($_POST['feature_icon'] ?? '');
        $feature_status = isset($_POST['feature_status']) ? 1 : 0;

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE yakpho_pricing_features SET feature_icon = ?, feature_status = ? WHERE feature_id = ?");
            $stmt->execute([$feature_icon, $feature_status, $id]);
            $feature_id = $id;
        } else {
            // Get max order
            $stmtMax = $db->query("SELECT MAX(feature_order) FROM yakpho_pricing_features");
            $max_order = (int)$stmtMax->fetchColumn();
            
            $stmt = $db->prepare("INSERT INTO yakpho_pricing_features (feature_icon, feature_order, feature_status) VALUES (?, ?, ?)");
            $stmt->execute([$feature_icon, $max_order + 1, $feature_status]);
            $feature_id = $db->lastInsertId();
        }

        // Handle Translations
        $translations = $_POST['translations'] ?? [];
        foreach ($translations as $lang_code => $trans) {
            $text = trim($trans['feature_text']);

            // Check existing
            $stmtCheck = $db->prepare("SELECT translation_id FROM yakpho_pricing_feature_translations WHERE feature_id = ? AND lang_code = ?");
            $stmtCheck->execute([$feature_id, $lang_code]);
            $transExists = $stmtCheck->fetchColumn();

            if ($transExists) {
                $stmtUpd = $db->prepare("UPDATE yakpho_pricing_feature_translations SET feature_text = ? WHERE translation_id = ?");
                $stmtUpd->execute([$text, $transExists]);
            } else {
                $stmtIns = $db->prepare("INSERT INTO yakpho_pricing_feature_translations (feature_id, lang_code, feature_text) VALUES (?, ?, ?)");
                $stmtIns->execute([$feature_id, $lang_code, $text]);
            }
        }

        $db->commit();
        $_SESSION['flash_msg'] = "บันทึกข้อมูลสำเร็จ";
        header("Location: pricing.php");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Load existing translations
$current_trans = [];
if ($id > 0) {
    $stmtTrans = $db->prepare("SELECT * FROM yakpho_pricing_feature_translations WHERE feature_id = ?");
    $stmtTrans->execute([$id]);
    while ($row = $stmtTrans->fetch(PDO::FETCH_ASSOC)) {
        $current_trans[$row['lang_code']] = $row;
    }
}

ob_start();
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1><i data-lucide="<?= $id ? 'edit' : 'plus' ?>"></i> <?= $page_title ?></h1>
    <a href="pricing.php" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> กลับ
    </a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="">
    <div class="row">
        <!-- Main Form -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลจุดเด่น (Features)</h5>
                </div>
                <div class="card-body">
                    
                    <div class="mb-4">
                        <label class="form-label">ไอคอน / Emoji</label>
                        <input type="text" name="feature_icon" 
                               class="form-control fs-4 w-25" 
                               value="<?= htmlspecialchars($feature['feature_icon'] ?? '') ?>" 
                               placeholder="เช่น 📦, 🌿, 🎨">
                        <small class="text-muted">ใส่อีโมจิที่ต้องการแสดงหน้าข้อความ</small>
                    </div>

                    <!-- Language Dropdown -->
                    <div class="language-dropdown-container mb-3" data-content-selector="#featureTabsContent"></div>

                    <div id="featureTabsContent">
                        <?php foreach ($languages as $code => $name): 
                            $tData = $current_trans[$code] ?? [];
                            $isRTL = in_array($code, ['ar', 'he']);
                            $dirAttr = $isRTL ? 'dir="rtl"' : '';
                        ?>
                            <div data-lang="<?= $code ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">ข้อความคำอธิบาย - <?= $name ?> <?= $code === 'th' ? '<span class="text-danger">*</span>' : '' ?></label>
                                    <input type="text" name="translations[<?= $code ?>][feature_text]" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($tData['feature_text'] ?? '') ?>" 
                                           <?= $code === 'th' ? 'required' : '' ?> <?= $dirAttr ?>>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer text-end bg-transparent border-top-0 pt-0 pb-3">
                    <button type="submit" class="btn btn-primary px-4"><i data-lucide="save"></i> บันทึกข้อมูล</button>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">การเผยแพร่</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="feature_status" name="feature_status" value="1" <?= (!$feature || $feature['feature_status'] == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="feature_status">เปิดใช้งาน</label>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

<!-- Load Language Dropdown JS -->
<script src="<?= ADMIN_URL ?>/assets/js/language-dropdown.js"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php';
?>
