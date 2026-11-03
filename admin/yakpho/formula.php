<?php
$page_title = 'จัดการสูตรนวดพิเศษ Yakpho.com';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("DELETE FROM yakpho_formula_items WHERE item_id = ?")->execute([$id]);
    $_SESSION['flash_msg'] = "ลบข้อมูลสำเร็จ";
    header("Location: formula.php");
    exit;
}

// Handle Toggle Status Request
if (isset($_GET['action']) && $_GET['action'] == 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("UPDATE yakpho_formula_items SET item_status = IF(item_status = 1, 0, 1) WHERE item_id = ?")->execute([$id]);
    header("Location: formula.php");
    exit;
}

// Handle AJAX Order Update
if (isset($_POST['action']) && $_POST['action'] === 'update_order') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $db->beginTransaction();
        $order = $_POST['order'] ?? [];
        foreach ($order as $index => $id) {
            $stmt = $db->prepare("UPDATE yakpho_formula_items SET item_order = ? WHERE item_id = ?");
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

// Fetch existing items
$stmt = $db->query("SELECT * FROM yakpho_formula_items ORDER BY item_order ASC, item_id ASC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1><i data-lucide="list"></i> จัดการสูตรนวดพิเศษ Yakpho.com</h1>
    <div>
        <a href="formula_intro.php" class="btn btn-outline-info me-2">
            <i data-lucide="align-center"></i> จัดการส่วนหัว (Intro)
        </a>
        <button class="btn btn-primary" onclick="openFormulaModal()">
            <i data-lucide="plus"></i> เพิ่มสูตรใหม่
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th width="80">ลำดับ</th>
                        <th width="120">รูปภาพ</th>
                        <th>ชื่อสูตร (TH)</th>
                        <th width="100">สี (CSS)</th>
                        <th width="100">สถานะ</th>
                        <th width="150" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="sortable-formula">
                    <?php foreach ($items as $index => $item): 
                        // Fetch Thai translation for preview
                        $stmt_th = $db->prepare("SELECT title FROM yakpho_formula_translations WHERE item_id = ? AND lang_code = 'th'");
                        $stmt_th->execute([$item['item_id']]);
                        $th_title = $stmt_th->fetchColumn() ?: '- ไม่มีชื่อสูตร -';
                        
                        // Fetch English translation for preview
                        $stmt_en = $db->prepare("SELECT title FROM yakpho_formula_translations WHERE item_id = ? AND lang_code = 'en'");
                        $stmt_en->execute([$item['item_id']]);
                        $en_title = $stmt_en->fetchColumn() ?: '- No English Title -';
                        
                        $img_url = !empty($item['image_file']) ? BASE_URL . '/uploads/yakpho/' . $item['image_file'] : BASE_URL . '/uploads/products/no-image.png';
                    ?>
                    <tr data-id="<?= $item['item_id'] ?>">
                        <td class="text-center">
                            <i data-lucide="grip-vertical" class="text-muted" style="cursor: grab;"></i>
                        </td>
                        <td><img src="<?= $img_url ?>" style="width: 80px; height: auto; border-radius: 4px;" alt="formula"></td>
                        <td>
                            <div><strong><?= htmlspecialchars($th_title) ?></strong></div>
                            <div class="text-muted small"><?= htmlspecialchars($en_title) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= htmlspecialchars($item['css_class']) ?></span>
                        </td>
                        <td>
                            <a href="formula.php?action=toggle_status&id=<?= $item['item_id'] ?>" style="text-decoration: none;">
                                <?php if ($item['item_status'] == 1): ?>
                                    <span class="badge bg-success">ใช้งาน</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">ซ่อน</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="formula_edit.php?id=<?= $item['item_id'] ?>" class="btn btn-outline-primary" title="แก้ไข">
                                    <i data-lucide="edit"></i> แก้ไข
                                </a>
                                <a href="formula.php?action=delete&id=<?= $item['item_id'] ?>" class="btn btn-outline-danger" title="ลบ" onclick="return confirm('ยืนยันการลบรายการนี้?');">
                                    <i data-lucide="trash-2"></i> ลบ
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($items) == 0): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">ยังไม่มีข้อมูลสูตรนวด</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openFormulaModal() {
    window.location.href = 'formula_edit.php';
}

document.addEventListener("DOMContentLoaded", function() {
    const tbody = document.getElementById('sortable-formula');
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

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
