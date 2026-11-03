<?php
$page_title = 'จัดการกลิ่นยอดนิยม';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Get image file
        $stmt = $db->prepare("SELECT image_file FROM yakpho_scent_items WHERE item_id = ?");
        $stmt->execute([$id]);
        $image = $stmt->fetchColumn();

        if ($image) {
            $image_path = __DIR__ . '/../../uploads/yakpho/' . $image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Delete from DB
        $db->beginTransaction();
        $db->prepare("DELETE FROM yakpho_scent_translations WHERE item_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM yakpho_scent_items WHERE item_id = ?")->execute([$id]);
        $db->commit();

        $_SESSION['success'] = "ลบรายการเรียบร้อยแล้ว";
        header("Location: scents.php");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = "ไม่สามารถลบรายการได้: " . $e->getMessage();
    }
}

// Handle AJAX Order Update
if (isset($_POST['action']) && $_POST['action'] === 'update_order') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $db->beginTransaction();
        $order = $_POST['order'] ?? [];
        foreach ($order as $index => $id) {
            $stmt = $db->prepare("UPDATE yakpho_scent_items SET item_order = ? WHERE item_id = ?");
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

// Fetch Items with Thai translation as primary
try {
    $sql = "
        SELECT i.*, t.title 
        FROM yakpho_scent_items i
        LEFT JOIN yakpho_scent_translations t ON i.item_id = t.item_id AND t.lang_code = 'th'
        ORDER BY i.item_order ASC, i.item_id ASC
    ";
    $items = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "ไม่สามารถดึงข้อมูลได้: " . $e->getMessage();
    $items = [];
}

ob_start();
?>

<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0">จัดการกลิ่นยอดนิยม</h1>
            <p class="text-muted">Yakpho Aroma Balm</p>
        </div>
        <div>
            <a href="scents_intro.php" class="btn btn-info text-white me-2">
                <i data-lucide="layout-template" class="me-2"></i> จัดการส่วนหัว (Intro)
            </a>
            <a href="scents_edit.php" class="btn btn-primary">
                <i data-lucide="plus" class="me-2"></i> เพิ่มกลิ่นใหม่
            </a>
        </div>
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

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="80" class="text-center">ลำดับ</th>
                                <th width="120">รูปภาพ</th>
                                <th>ชื่อกลิ่น (TH)</th>
                                <th width="100" class="text-center">สถานะ</th>
                                <th width="150" class="text-end">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-table">
                            <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">ยังไม่มีรายการข้อมูล</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                <tr data-id="<?= $item['item_id'] ?>">
                                    <td class="text-center">
                                        <i data-lucide="grip-vertical" class="text-muted" style="cursor: grab;"></i>
                                    </td>
                                    <td>
                                        <?php if ($item['image_file']): ?>
                                            <img src="<?= ROOT_URL ?>/uploads/yakpho/<?= htmlspecialchars($item['image_file']) ?>" 
                                                 class="rounded" style="width: 100px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 100px; height: 50px;">
                                                <i data-lucide="image" style="width: 20px; height: 20px;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($item['title'] ?: 'ยังไม่มีชื่อ (TH)') ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item['item_status']): ?>
                                            <span class="badge bg-success">แสดง</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">ซ่อน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="scents_edit.php?id=<?= $item['item_id'] ?>" class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                            <i data-lucide="edit-2" style="width: 16px; height: 16px;"></i>
                                        </a>
                                        <a href="?action=delete&id=<?= $item['item_id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="ลบ"
                                           onclick="return confirm('ยืนยันการลบรายการนี้ใช่หรือไม่?');">
                                            <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const tbody = document.getElementById('sortable-table');
    if (tbody) {
        new Sortable(tbody, {
            animation: 150,
            handle: '.lucide-grip-vertical',
            ghostClass: 'bg-light',
            onEnd: function (evt) {
                const order = [];
                tbody.querySelectorAll('tr').forEach(row => {
                    const id = row.getAttribute('data-id');
                    if (id) order.push(id);
                });
                
                // Update Order via AJAX
                const params = new URLSearchParams();
                params.append('action', 'update_order');
                order.forEach(id => params.append('order[]', id));
                
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                }).then(res => res.json()).then(data => {
                    if(data.status !== 'success') {
                        alert('Error updating order: ' + data.message);
                    }
                }).catch(err => {
                    console.error('Error:', err);
                });
            }
        });
    }
});
</script>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
