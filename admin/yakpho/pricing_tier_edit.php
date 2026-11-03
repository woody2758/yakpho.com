<?php
$page_title = isset($_GET['id']) ? 'แก้ไขเรทราคา' : 'เพิ่มเรทราคา';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tier = null;

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
    $stmt = $db->prepare("SELECT * FROM yakpho_pricing_tiers WHERE tier_id = ?");
    $stmt->execute([$id]);
    $tier = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tier) {
        die("ไม่พบข้อมูล");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $tier_status = isset($_POST['tier_status']) ? 1 : 0;

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE yakpho_pricing_tiers SET tier_status = ? WHERE tier_id = ?");
            $stmt->execute([$tier_status, $id]);
            $tier_id = $id;
        } else {
            // Get max order
            $stmtMax = $db->query("SELECT MAX(tier_order) FROM yakpho_pricing_tiers");
            $max_order = (int)$stmtMax->fetchColumn();
            
            $stmt = $db->prepare("INSERT INTO yakpho_pricing_tiers (tier_order, tier_status) VALUES (?, ?)");
            $stmt->execute([$max_order + 1, $tier_status]);
            $tier_id = $db->lastInsertId();
        }

        // Handle Translations
        $translations = $_POST['translations'] ?? [];
        foreach ($translations as $lang_code => $trans) {
            $volume = trim($trans['tier_volume']);
            $price = trim($trans['tier_price']);

            // Check existing
            $stmtCheck = $db->prepare("SELECT translation_id FROM yakpho_pricing_tier_translations WHERE tier_id = ? AND lang_code = ?");
            $stmtCheck->execute([$tier_id, $lang_code]);
            $transExists = $stmtCheck->fetchColumn();

            if ($transExists) {
                $stmtUpd = $db->prepare("UPDATE yakpho_pricing_tier_translations SET tier_volume = ?, tier_price = ? WHERE translation_id = ?");
                $stmtUpd->execute([$volume, $price, $transExists]);
            } else {
                $stmtIns = $db->prepare("INSERT INTO yakpho_pricing_tier_translations (tier_id, lang_code, tier_volume, tier_price) VALUES (?, ?, ?, ?)");
                $stmtIns->execute([$tier_id, $lang_code, $volume, $price]);
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
    $stmtTrans = $db->prepare("SELECT * FROM yakpho_pricing_tier_translations WHERE tier_id = ?");
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
        <!-- Main Form (Translations) -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลปริมาณและราคา</h5>
                </div>
                <div class="card-body">
                    
                    <!-- Language Dropdown -->
                    <div class="language-dropdown-container mb-3" data-content-selector="#tierTabsContent"></div>

                    <div id="tierTabsContent">
                        <?php foreach ($languages as $code => $name): 
                            $tData = $current_trans[$code] ?? [];
                            $isRTL = in_array($code, ['ar', 'he']);
                            $dirAttr = $isRTL ? 'dir="rtl"' : '';
                        ?>
                            <div data-lang="<?= $code ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">ปริมาณ (เช่น 1 กก., 1 kg) - <?= $name ?> <?= $code === 'th' ? '<span class="text-danger">*</span>' : '' ?></label>
                                    <input type="text" name="translations[<?= $code ?>][tier_volume]" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($tData['tier_volume'] ?? '') ?>" 
                                           <?= $code === 'th' ? 'required' : '' ?> <?= $dirAttr ?>>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ราคา (เช่น 690 บาท, 690 THB) - <?= $name ?> <?= $code === 'th' ? '<span class="text-danger">*</span>' : '' ?></label>
                                    <input type="text" name="translations[<?= $code ?>][tier_price]" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($tData['tier_price'] ?? '') ?>" 
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
                        <input class="form-check-input" type="checkbox" role="switch" id="tier_status" name="tier_status" value="1" <?= (!$tier || $tier['tier_status'] == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="tier_status">เปิดใช้งาน</label>
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
