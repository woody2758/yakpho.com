<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions/pagination.php';

header('Content-Type: application/json');

$page = $_GET['page'] ?? 1;
$search = trim($_GET['search'] ?? '');
$limit = 15;
$offset = ((int)$page - 1) * $limit;

try {
    // Build query
    $sql = "SELECT c.*, 
                   ct_th.maincat_name as name_th,
                   ct_en.maincat_name as name_en,
                   COUNT(DISTINCT pc.productcat_id) as subcat_count
            FROM maincat c
            LEFT JOIN maincat_translations ct_th ON c.maincat_id = ct_th.maincat_id AND ct_th.lang_code = 'th'
            LEFT JOIN maincat_translations ct_en ON c.maincat_id = ct_en.maincat_id AND ct_en.lang_code = 'en'
            LEFT JOIN productcat pc ON c.maincat_id = pc.maincat_id AND pc.productcat_del = 0
            WHERE c.maincat_del = 0";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (ct_th.maincat_name LIKE ? OR ct_en.maincat_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " GROUP BY c.maincat_id ORDER BY c.maincat_index ASC, c.maincat_id ASC LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total
    $countSql = "SELECT COUNT(DISTINCT c.maincat_id) 
                 FROM maincat c
                 LEFT JOIN maincat_translations ct_th ON c.maincat_id = ct_th.maincat_id AND ct_th.lang_code = 'th'
                 LEFT JOIN maincat_translations ct_en ON c.maincat_id = ct_en.maincat_id AND ct_en.lang_code = 'en'
                 WHERE c.maincat_del = 0";
    
    if (!empty($search)) {
        $countSql .= " AND (ct_th.maincat_name LIKE ? OR ct_en.maincat_name LIKE ?)";
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $limit);
    
    // Generate table HTML
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;" class="text-center">
                        <i data-lucide="grip-vertical" style="width:16px;height:16px;color:#6c757d;"></i>
                    </th>
                    <th class="ps-3" style="width: 80px;">ID</th>
                    <th>ชื่อหมวดหมู่หลัก</th>
                    <th style="width: 120px;">Slug</th>
                    <th style="width: 80px;">Icon</th>
                    <th style="width: 120px;">จำนวนหมวดย่อย</th>
                    <th style="width: 100px;">สถานะ</th>
                    <th style="width: 150px;" class="text-end pe-4">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr data-id="<?= $cat['maincat_id'] ?>" class="sortable-row">
                            <td class="drag-handle text-center" style="cursor: grab;">
                                <i data-lucide="grip-vertical" style="width:16px;height:16px;color:#6c757d;"></i>
                            </td>
                            <td class="ps-3"><code><?= $cat['maincat_id'] ?></code></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($cat['maincat_icon'])): ?>
                                        <i data-lucide="<?= htmlspecialchars($cat['maincat_icon']) ?>" style="width:18px;height:18px;color:#D1A55A;"></i>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($cat['name_th'] ?? 'ไม่มีชื่อ') ?></div>
                                        <?php if (!empty($cat['name_en'])): ?>
                                            <small class="text-muted d-block" style="font-size: 0.85rem;"><?= htmlspecialchars($cat['name_en']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($cat['maincat_slug'])): ?>
                                    <code class="text-primary"><?= htmlspecialchars($cat['maincat_slug']) ?></code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($cat['maincat_icon'])): ?>
                                    <i data-lucide="<?= htmlspecialchars($cat['maincat_icon']) ?>" style="width:20px;height:20px;"></i>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $cat['subcat_count'] ?> หมวด</span>
                            </td>
                            <td>
                                <?php if ($cat['maincat_status'] == 1): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill" 
                                          style="cursor: pointer;"
                                          onclick="toggleMainCategoryStatus(<?= $cat['maincat_id'] ?>, 1)"
                                          title="คลิกเพื่อเปลี่ยนสถานะ">
                                        Active <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" 
                                          style="cursor: pointer;"
                                          onclick="toggleMainCategoryStatus(<?= $cat['maincat_id'] ?>, 0)"
                                          title="คลิกเพื่อเปลี่ยนสถานะ">
                                        Inactive <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button onclick="editMainCategory(<?= $cat['maincat_id'] ?>)" class="btn btn-sm btn-outline-primary me-1" title="แก้ไข">
                                    <i data-lucide="edit-2" style="width:16px; height:16px;"></i>
                                </button>
                                <button onclick="deleteMainCategory(<?= $cat['maincat_id'] ?>, '<?= htmlspecialchars($cat['name_th'] ?? '') ?>')" class="btn btn-sm btn-outline-danger" title="ลบ">
                                    <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">ไม่พบข้อมูลหมวดหมู่หลัก</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $tableHtml = ob_get_clean();
    
    // Generate pagination
    $paginationHtml = render_pagination($page, $totalPages, "?page=%d" . (!empty($search) ? "&search=" . urlencode($search) : ""));
    
    echo json_encode([
        'success' => true,
        'table' => $tableHtml,
        'pagination' => $paginationHtml,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
