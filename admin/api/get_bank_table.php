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
    $sql = "SELECT b.*, t.bank_bankname, t.bank_accountname 
            FROM bank b 
            LEFT JOIN bank_translations t ON b.bank_id = t.bank_id AND t.lang_code = 'th'
            WHERE b.bank_del = 0";
    
    $params = [];

    if ($search) {
        $sql .= " AND (t.bank_bankname LIKE :search OR t.bank_accountname LIKE :search OR b.bank_accountnumber LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Count total for pagination
    $countSql = str_replace("SELECT b.*, t.bank_bankname, t.bank_accountname", "SELECT COUNT(*)", $sql);
    $stmt = $db->prepare($countSql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Get data
    $sql .= " ORDER BY b.bank_index ASC, b.bank_id DESC LIMIT $offset, $limit";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML Table
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">ลำดับ</th>
                    <th style="width: 80px;">รูปภาพ</th>
                    <th>ชื่อธนาคาร</th>
                    <th>ชื่อบัญชี</th>
                    <th>เลขที่บัญชี</th>
                    <th class="text-center">สถานะ</th>
                    <th class="text-end" style="width: 150px;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($banks) > 0): ?>
                    <?php foreach ($banks as $bank): ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border"><?= str_pad($bank['bank_index'], 2, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($bank['bank_picture']) && file_exists("../../uploads/banks/" . $bank['bank_picture'])): ?>
                                    <img src="<?= ROOT_URL ?>/uploads/banks/<?= $bank['bank_picture'] ?>" alt="Bank Logo" 
                                         class="rounded" style="width: 40px; height: 40px; object-fit: contain; background: #fff; border: 1px solid #eee;">
                                <?php else: ?>
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center border" style="width: 40px; height: 40px;">
                                        <i data-lucide="image" class="text-muted" style="width: 20px; height: 20px;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($bank['bank_bankname'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($bank['bank_accountname'] ?? '-') ?></div>
                            </td>
                            <td>
                                <span class="font-monospace"><?= htmlspecialchars($bank['bank_accountnumber']) ?></span>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           onchange="toggleStatus(<?= $bank['bank_id'] ?>, <?= $bank['bank_status'] ?>)"
                                           <?= $bank['bank_status'] == 1 ? 'checked' : '' ?>>
                                </div>
                                <small class="text-<?= $bank['bank_status'] == 1 ? 'success' : 'muted' ?>">
                                    <?= $bank['bank_status'] == 1 ? 'Active' : 'Inactive' ?>
                                </small>
                            </td>
                            <td class="text-end">
                                <button onclick="editBank(<?= $bank['bank_id'] ?>)" class="btn btn-sm btn-outline-primary me-1">
                                    <i data-lucide="edit-2" style="width:16px; height:16px;"></i>
                                </button>
                                <button onclick="deleteBank(<?= $bank['bank_id'] ?>, '<?= htmlspecialchars($bank['bank_bankname']) ?>')" class="btn btn-sm btn-outline-danger">
                                    <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i data-lucide="inbox" class="mx-auto mb-2" style="width: 32px; height: 32px; opacity: 0.5;"></i>
                            <p class="mb-0">ไม่พบข้อมูลบัญชีธนาคาร</p>
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
