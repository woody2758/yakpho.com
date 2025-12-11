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
                   ct_th.productcat_name as name_th,
                   ct_en.productcat_name as name_en,
                   COUNT(DISTINCT p.product_id) as product_count
            FROM productcat c
            LEFT JOIN productcat_translations ct_th ON c.productcat_id = ct_th.productcat_id AND ct_th.lang_code = 'th'
            LEFT JOIN productcat_translations ct_en ON c.productcat_id = ct_en.productcat_id AND ct_en.lang_code = 'en'
            LEFT JOIN product p ON c.productcat_id = p.productcat_id AND p.product_del = 0
            WHERE c.productcat_del = 0";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND ct.productcat_name LIKE ?";
        $params[] = "%$search%";
    }
    
    $sql .= " GROUP BY c.productcat_id ORDER BY c.productcat_index ASC, c.productcat_id ASC LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total
    $countSql = "SELECT COUNT(DISTINCT c.productcat_id) 
                 FROM productcat c
                 LEFT JOIN productcat_translations ct_th ON c.productcat_id = ct_th.productcat_id AND ct_th.lang_code = 'th'
                 WHERE c.productcat_del = 0";
    
    if (!empty($search)) {
        $countSql .= " AND ct_th.productcat_name LIKE ?";
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
                    <th>ชื่อหมวดสินค้า</th>
                    <th style="width: 120px;">จำนวนสินค้า</th>
                    <th style="width: 100px;">สถานะ</th>
                    <th style="width: 150px;" class="text-end pe-4">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr data-id="<?= $cat['productcat_id'] ?>" class="sortable-row">
                            <td class="drag-handle text-center" style="cursor: grab;">
                                <i data-lucide="grip-vertical" style="width:16px;height:16px;color:#6c757d;"></i>
                            </td>
                            <td class="ps-3"><code><?= $cat['productcat_id'] ?></code></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($cat['name_th'] ?? 'ไม่มีชื่อ') ?></div>
                                <?php if (!empty($cat['name_en'])): ?>
                                    <small class="text-muted d-block" style="font-size: 0.85rem;"><?= htmlspecialchars($cat['name_en']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $cat['product_count'] ?> สินค้า</span>
                            </td>
                            <td>
                                <?php if ($cat['productcat_status'] == 1): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill" 
                                          style="cursor: pointer;"
                                          onclick="toggleStatus(<?= $cat['productcat_id'] ?>, 1)"
                                          title="คลิกเพื่อเปลี่ยนสถานะ">
                                        Active <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" 
                                          style="cursor: pointer;"
                                          onclick="toggleStatus(<?= $cat['productcat_id'] ?>, 0)"
                                          title="คลิกเพื่อเปลี่ยนสถานะ">
                                        Inactive <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button onclick="editCategory(<?= $cat['productcat_id'] ?>)" class="btn btn-sm btn-outline-primary me-1" title="แก้ไข">
                                    <i data-lucide="edit-2" style="width:16px; height:16px;"></i>
                                </button>
                                <button onclick="deleteCategory(<?= $cat['productcat_id'] ?>, '<?= htmlspecialchars($cat['name_th'] ?? '') ?>')" class="btn btn-sm btn-outline-danger" title="ลบ">
                                    <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">ไม่พบข้อมูลหมวดสินค้า</td>
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
