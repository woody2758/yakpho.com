<?php
$page_title = 'จัดการเรทราคาส่ง Yakpho.com';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Handle Toggle Status Request for Tiers
if (isset($_GET['action']) && $_GET['action'] == 'toggle_tier_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("UPDATE yakpho_pricing_tiers SET tier_status = IF(tier_status = 1, 0, 1) WHERE tier_id = ?")->execute([$id]);
    header("Location: pricing.php");
    exit;
}

// Handle Delete Request for Tiers
if (isset($_GET['action']) && $_GET['action'] == 'delete_tier' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("DELETE FROM yakpho_pricing_tiers WHERE tier_id = ?")->execute([$id]);
    $_SESSION['flash_msg'] = "ลบข้อมูลสำเร็จ";
    header("Location: pricing.php");
    exit;
}

// Handle Toggle Status Request for Features
if (isset($_GET['action']) && $_GET['action'] == 'toggle_feature_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("UPDATE yakpho_pricing_features SET feature_status = IF(feature_status = 1, 0, 1) WHERE feature_id = ?")->execute([$id]);
    header("Location: pricing.php");
    exit;
}

// Handle Delete Request for Features
if (isset($_GET['action']) && $_GET['action'] == 'delete_feature' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("DELETE FROM yakpho_pricing_features WHERE feature_id = ?")->execute([$id]);
    $_SESSION['flash_msg'] = "ลบข้อมูลสำเร็จ";
    header("Location: pricing.php");
    exit;
}

// Handle AJAX Order Update for Tiers
if (isset($_POST['action']) && $_POST['action'] === 'update_tier_order') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $db->beginTransaction();
        $order = $_POST['order'] ?? [];
        foreach ($order as $index => $id) {
            $stmt = $db->prepare("UPDATE yakpho_pricing_tiers SET tier_order = ? WHERE tier_id = ?");
            $stmt->execute([(int)$index + 1, (int)$id]);
        }
        $db->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX Order Update for Features
if (isset($_POST['action']) && $_POST['action'] === 'update_feature_order') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $db->beginTransaction();
        $order = $_POST['order'] ?? [];
        foreach ($order as $index => $id) {
            $stmt = $db->prepare("UPDATE yakpho_pricing_features SET feature_order = ? WHERE feature_id = ?");
            $stmt->execute([(int)$index + 1, (int)$id]);
        }
        $db->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch existing tiers
$stmtTiers = $db->query("SELECT * FROM yakpho_pricing_tiers ORDER BY tier_order ASC, tier_id ASC");
$tiers = $stmtTiers->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing features
$stmtFeatures = $db->query("SELECT * FROM yakpho_pricing_features ORDER BY feature_order ASC, feature_id ASC");
$features = $stmtFeatures->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1><i data-lucide="tag"></i> จัดการเรทราคาส่ง Yakpho.com</h1>
    <div>
        <a href="pricing_intro.php" class="btn btn-outline-info me-2">
            <i data-lucide="align-center"></i> จัดการส่วนหัว (Intro)
        </a>
    </div>
</div>

<div class="row">
    <!-- Tiers Section -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">ตารางเรทราคา</h5>
                <button class="btn btn-sm btn-primary" onclick="window.location.href='pricing_tier_edit.php'">
                    <i data-lucide="plus"></i> เพิ่มเรทราคา
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th width="60">ลำดับ</th>
                                <th>ปริมาณ (TH)</th>
                                <th>ราคา (TH)</th>
                                <th width="80">สถานะ</th>
                                <th width="120" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-tiers">
                            <?php foreach ($tiers as $index => $tier): 
                                $stmt_th = $db->prepare("SELECT tier_volume, tier_price FROM yakpho_pricing_tier_translations WHERE tier_id = ? AND lang_code = 'th'");
                                $stmt_th->execute([$tier['tier_id']]);
                                $trans_th = $stmt_th->fetch(PDO::FETCH_ASSOC);
                                $volume_th = $trans_th ? $trans_th['tier_volume'] : '-';
                                $price_th = $trans_th ? $trans_th['tier_price'] : '-';
                            ?>
                            <tr data-id="<?= $tier['tier_id'] ?>">
                                <td class="text-center">
                                    <i data-lucide="grip-vertical" class="text-muted" style="cursor: grab;"></i>
                                </td>
                                <td><?= htmlspecialchars($volume_th) ?></td>
                                <td><?= htmlspecialchars($price_th) ?></td>
                                <td>
                                    <a href="pricing.php?action=toggle_tier_status&id=<?= $tier['tier_id'] ?>" style="text-decoration: none;">
                                        <?php if ($tier['tier_status'] == 1): ?>
                                            <span class="badge bg-success">ใช้งาน</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">ซ่อน</span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="pricing_tier_edit.php?id=<?= $tier['tier_id'] ?>" class="btn btn-outline-primary" title="แก้ไข"><i data-lucide="edit"></i></a>
                                        <a href="pricing.php?action=delete_tier&id=<?= $tier['tier_id'] ?>" class="btn btn-outline-danger" title="ลบ" onclick="return confirm('ยืนยันการลบ?');"><i data-lucide="trash-2"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">จุดเด่นสินค้าราคาส่ง</h5>
                <button class="btn btn-sm btn-primary" onclick="window.location.href='pricing_feature_edit.php'">
                    <i data-lucide="plus"></i> เพิ่มจุดเด่น
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th width="60">ลำดับ</th>
                                <th width="60">ไอคอน</th>
                                <th>ข้อความ (TH)</th>
                                <th width="80">สถานะ</th>
                                <th width="120" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-features">
                            <?php foreach ($features as $index => $feature): 
                                $stmt_th = $db->prepare("SELECT feature_text FROM yakpho_pricing_feature_translations WHERE feature_id = ? AND lang_code = 'th'");
                                $stmt_th->execute([$feature['feature_id']]);
                                $text_th = $stmt_th->fetchColumn() ?: '-';
                            ?>
                            <tr data-id="<?= $feature['feature_id'] ?>">
                                <td class="text-center">
                                    <i data-lucide="grip-vertical" class="text-muted" style="cursor: grab;"></i>
                                </td>
                                <td class="text-center h4 mb-0"><?= htmlspecialchars($feature['feature_icon']) ?></td>
                                <td><?= htmlspecialchars($text_th) ?></td>
                                <td>
                                    <a href="pricing.php?action=toggle_feature_status&id=<?= $feature['feature_id'] ?>" style="text-decoration: none;">
                                        <?php if ($feature['feature_status'] == 1): ?>
                                            <span class="badge bg-success">ใช้งาน</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">ซ่อน</span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="pricing_feature_edit.php?id=<?= $feature['feature_id'] ?>" class="btn btn-outline-primary" title="แก้ไข"><i data-lucide="edit"></i></a>
                                        <a href="pricing.php?action=delete_feature&id=<?= $feature['feature_id'] ?>" class="btn btn-outline-danger" title="ลบ" onclick="return confirm('ยืนยันการลบ?');"><i data-lucide="trash-2"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Tiers Sortable
    const tbodyTiers = document.getElementById('sortable-tiers');
    if (tbodyTiers) {
        new Sortable(tbodyTiers, {
            animation: 150,
            handle: '.lucide-grip-vertical',
            ghostClass: 'bg-light',
            onEnd: function () {
                const order = [];
                tbodyTiers.querySelectorAll('tr').forEach(row => {
                    const id = row.getAttribute('data-id');
                    if (id) order.push(id);
                });
                const params = new URLSearchParams();
                params.append('action', 'update_tier_order');
                order.forEach(id => params.append('order[]', id));
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
            }
        });
    }

    // Features Sortable
    const tbodyFeatures = document.getElementById('sortable-features');
    if (tbodyFeatures) {
        new Sortable(tbodyFeatures, {
            animation: 150,
            handle: '.lucide-grip-vertical',
            ghostClass: 'bg-light',
            onEnd: function () {
                const order = [];
                tbodyFeatures.querySelectorAll('tr').forEach(row => {
                    const id = row.getAttribute('data-id');
                    if (id) order.push(id);
                });
                const params = new URLSearchParams();
                params.append('action', 'update_feature_order');
                order.forEach(id => params.append('order[]', id));
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
            }
        });
    }
});
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php';
?>
