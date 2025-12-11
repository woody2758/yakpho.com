<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Base query
    $sql = "SELECT p.*, t.paycat_name 
            FROM paycat p 
            LEFT JOIN paycat_translations t ON p.paycat_id = t.paycat_id AND t.lang_code = 'th'
            WHERE p.paycat_del = 0";
    
    $params = [];

    if ($search) {
        $sql .= " AND (t.paycat_name LIKE :search OR p.paycat_nshort LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Count total for pagination
    $countSql = str_replace("SELECT p.*, t.paycat_name", "SELECT COUNT(*)", $sql);
    $stmt = $db->prepare($countSql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Get data
    $sql .= " ORDER BY p.paycat_index ASC, p.paycat_id DESC LIMIT $offset, $limit";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $paycats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML Table
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">ลำดับ</th>
                    <th>ชื่อวิธีการชำระเงิน</th>
                    <th>รหัสย่อ</th>
                    <th class="text-center">สถานะ</th>
                    <th class="text-end" style="width: 150px;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($paycats) > 0): ?>
                    <?php foreach ($paycats as $cat): ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border"><?= str_pad($cat['paycat_index'], 2, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($cat['paycat_name'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($cat['paycat_nshort']) ?></span>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           onchange="toggleStatus(<?= $cat['paycat_id'] ?>, <?= $cat['paycat_status'] ?>)"
                                           <?= $cat['paycat_status'] == 1 ? 'checked' : '' ?>>
                                </div>
                                <small class="text-<?= $cat['paycat_status'] == 1 ? 'success' : 'muted' ?>">
                                    <?= $cat['paycat_status'] == 1 ? 'Active' : 'Inactive' ?>
                                </small>
                            </td>
                            <td class="text-end">
                                <button onclick="editPaycat(<?= $cat['paycat_id'] ?>)" class="btn btn-sm btn-outline-primary me-1">
                                    <i data-lucide="edit-2" style="width:16px; height:16px;"></i>
                                </button>
                                <button onclick="deletePaycat(<?= $cat['paycat_id'] ?>, '<?= htmlspecialchars($cat['paycat_name']) ?>')" class="btn btn-sm btn-outline-danger">
                                    <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i data-lucide="inbox" class="mx-auto mb-2" style="width: 32px; height: 32px; opacity: 0.5;"></i>
                            <p class="mb-0">ไม่พบข้อมูลวิธีการชำระเงิน</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $tableHtml = ob_get_clean();

    // Generate Pagination
    ob_start();
    if ($totalPages > 1):
    ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-end mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
    <?php
    endif;
    $paginationHtml = ob_get_clean();

    echo json_encode([
        'success' => true,
        'table' => $tableHtml,
        'pagination' => $paginationHtml
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
