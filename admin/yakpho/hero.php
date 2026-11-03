<?php
$page_title = 'จัดการแบนเนอร์ Yakpho.com';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    // Handle Delete Request
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $db->prepare("DELETE FROM yakpho_hero_slides WHERE slide_id = ?")->execute([$id]);
        header("Location: hero.php");
        exit;
    }

    // Handle Toggle Status Request
    if (isset($_GET['action']) && $_GET['action'] == 'toggle_status' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $db->prepare("UPDATE yakpho_hero_slides SET slide_status = IF(slide_status = 1, 0, 1) WHERE slide_id = ?")->execute([$id]);
        header("Location: hero.php");
        exit;
    }

    // Handle AJAX Order Update
    if (isset($_POST['action']) && $_POST['action'] === 'update_order') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db->beginTransaction();
            $order = $_POST['order'] ?? [];
            foreach ($order as $index => $id) {
                $stmt = $db->prepare("UPDATE yakpho_hero_slides SET slide_order = ? WHERE slide_id = ?");
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

    // Fetch existing slides
    $stmt = $db->query("SELECT * FROM yakpho_hero_slides ORDER BY slide_order ASC");
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_msg = "เกิดข้อผิดพลาดกับฐานข้อมูล: " . $e->getMessage();
    $slides = [];
}

ob_start();
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <h1><i data-lucide="image"></i> จัดการแบนเนอร์ Yakpho.com</h1>
    <button class="btn btn-primary" onclick="openHeroModal()">
        <i data-lucide="plus"></i> เพิ่ม Slide ใหม่
    </button>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th width="80">ลำดับ</th>
                        <th width="150">รูปภาพ</th>
                        <th>หัวข้อ (TH)</th>
                        <th width="100">สถานะ</th>
                        <th width="150" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="sortable-hero">
                    <?php foreach ($slides as $index => $slide): 
                        // Fetch Thai translation for preview
                        $stmt_th = $db->prepare("SELECT slide_title FROM yakpho_hero_translations WHERE slide_id = ? AND lang_code = 'th'");
                        $stmt_th->execute([$slide['slide_id']]);
                        $th_title = $stmt_th->fetchColumn() ?: '- ไม่มีหัวข้อ -';
                        
                        // Fetch English translation for preview
                        $stmt_en = $db->prepare("SELECT slide_title FROM yakpho_hero_translations WHERE slide_id = ? AND lang_code = 'en'");
                        $stmt_en->execute([$slide['slide_id']]);
                        $en_title = $stmt_en->fetchColumn() ?: '- No English Title -';
                        
                        $img_url = !empty($slide['slide_image']) ? BASE_URL . '/uploads/yakpho/' . $slide['slide_image'] : BASE_URL . '/uploads/products/no-image.png';
                    ?>
                    <tr data-id="<?= $slide['slide_id'] ?>">
                        <td class="text-center">
                            <i data-lucide="grip-vertical" class="text-muted" style="cursor: grab;"></i>
                        </td>
                        <td><img src="<?= $img_url ?>" style="width: 100px; height: auto; border-radius: 4px;" alt="hero"></td>
                        <td>
                            <div><?= htmlspecialchars($th_title) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($en_title) ?></div>
                        </td>
                        <td>
                            <a href="hero.php?action=toggle_status&id=<?= $slide['slide_id'] ?>" style="text-decoration: none;">
                                <?php if ($slide['slide_status'] == 1): ?>
                                    <span class="badge bg-success">ใช้งาน</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">ซ่อน</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="hero_edit.php?id=<?= $slide['slide_id'] ?>" class="btn btn-outline-primary" title="แก้ไข">
                                    <i data-lucide="edit"></i> แก้ไข
                                </a>
                                <a href="hero.php?action=delete&id=<?= $slide['slide_id'] ?>" class="btn btn-outline-danger" title="ลบ" onclick="return confirm('ยืนยันการลบแบนเนอร์นี้?');">
                                    <i data-lucide="trash-2"></i> ลบ
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($slides) == 0): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลแบนเนอร์</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted mt-2 small"><i data-lucide="info" style="width:14px; height:14px;"></i> หากมีมากกว่า 1 รายการ ระบบจะแสดงผลแบบสไลด์อัตโนมัติที่หน้าเว็บ Yakpho.com</p>
    </div>
</div>

<script>
function openHeroModal() {
    window.location.href = 'hero_edit.php';
}

document.addEventListener("DOMContentLoaded", function() {
    const tbody = document.getElementById('sortable-hero');
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
