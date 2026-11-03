<?php
$page_title = 'จัดการส่วนหัว (Intro) กลิ่นยอดนิยม';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$intro_id = 1;
$translations = [];

// Active languages (from esther.rest)
$active_languages = [];
try {
    $langStmt = $db->query("SELECT lang_code, name FROM languages WHERE status = 1 ORDER BY is_default DESC");
    while ($row = $langStmt->fetch(PDO::FETCH_ASSOC)) {
        $active_languages[$row['lang_code']] = $row['name'];
    }
} catch (Exception $e) {
    $active_languages = ['th' => 'Thai', 'en' => 'English'];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $intro_id = 1;
        // Check if intro exists
        $chk = $db->prepare("SELECT intro_id FROM yakpho_scent_intro WHERE intro_id = ?");
        $chk->execute([$intro_id]);
        if (!$chk->fetch()) {
            $db->prepare("INSERT INTO yakpho_scent_intro (intro_id) VALUES (?)")->execute([$intro_id]);
        }

        // Save Translations
        $stmtTrans = $db->prepare("
            INSERT INTO yakpho_scent_intro_translations (intro_id, lang_code, intro_title, intro_subtitle, intro_description)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                intro_title = VALUES(intro_title),
                intro_subtitle = VALUES(intro_subtitle),
                intro_description = VALUES(intro_description)
        ");

        if (isset($_POST['trans']) && is_array($_POST['trans'])) {
            foreach ($_POST['trans'] as $lang => $data) {
                $stmtTrans->execute([
                    $intro_id,
                    $lang,
                    $data['title'] ?? '',
                    $data['subtitle'] ?? '',
                    $data['description'] ?? ''
                ]);
            }
        }

        $db->commit();
        $_SESSION['success'] = "บันทึกข้อมูลส่วนหัวเรียบร้อยแล้ว";
        header("Location: scents_intro.php");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// Load Existing Data
try {
    $stmt = $db->prepare("SELECT lang_code, intro_title, intro_subtitle, intro_description FROM yakpho_scent_intro_translations WHERE intro_id = ?");
    $stmt->execute([$intro_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $translations[$row['lang_code']] = [
            'title' => $row['intro_title'],
            'subtitle' => $row['intro_subtitle'],
            'description' => $row['intro_description']
        ];
    }
} catch (Exception $e) {
    $error = "ไม่สามารถดึงข้อมูลได้: " . $e->getMessage();
}

ob_start();
?>

<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0">จัดการส่วนหัว (Intro) กลิ่นยอดนิยม</h1>
            <p class="text-muted">Yakpho Aroma Balm</p>
        </div>
        <a href="scents.php" class="btn btn-secondary">
            <i data-lucide="arrow-left" class="me-2"></i> กลับหน้ารวม
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" id="langTabs" role="tablist">
                    <?php $first = true; foreach ($active_languages as $code => $name): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $first ? 'active' : '' ?>" 
                                    id="tab-<?= $code ?>" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#pane-<?= $code ?>" 
                                    type="button" role="tab">
                                <img src="<?= ROOT_URL ?>/assets/img/flags/<?= $code ?>.png" width="20" class="me-1" onerror="this.style.display='none'"> 
                                <?= htmlspecialchars($name) ?>
                            </button>
                        </li>
                    <?php $first = false; endforeach; ?>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="langTabsContent">
                    <?php $first = true; foreach ($active_languages as $code => $name): 
                        $t = $translations[$code] ?? ['title' => '', 'subtitle' => '', 'description' => ''];
                    ?>
                        <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="pane-<?= $code ?>" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">หัวข้อหลัก (Title)</label>
                                <input type="text" name="trans[<?= $code ?>][title]" class="form-control" 
                                       value="<?= htmlspecialchars($t['title']) ?>" placeholder="เช่น กลิ่นยอดนิยม (12 แบบ)">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">คำโปรย (Subtitle)</label>
                                <input type="text" name="trans[<?= $code ?>][subtitle]" class="form-control" 
                                       value="<?= htmlspecialchars($t['subtitle']) ?>" placeholder="เช่น ค้นพบกลิ่นที่ถูกใจ">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">รายละเอียด (Description) - เว้นว่างได้</label>
                                <textarea name="trans[<?= $code ?>][description]" class="form-control" rows="3"><?= htmlspecialchars($t['description']) ?></textarea>
                            </div>
                        </div>
                    <?php $first = false; endforeach; ?>
                </div>
            </div>
            <div class="card-footer bg-light text-end">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" class="me-2"></i> บันทึกข้อมูล
                </button>
            </div>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
