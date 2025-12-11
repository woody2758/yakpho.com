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
    $sql = "SELECT s.*, 
                   st.orsts_name as name_th,
                   st.orsts_msg as msg_th
            FROM orsts s
            LEFT JOIN orsts_translations st ON s.orsts_id = st.orsts_id AND st.lang_code = 'th'
            WHERE s.orsts_del = 0";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (st.orsts_name LIKE ? OR s.orsts_code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Sort by index (custom order)
    $sql .= " ORDER BY s.orsts_index ASC, s.orsts_id ASC LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total
    $countSql = "SELECT COUNT(DISTINCT s.orsts_id) 
                 FROM orsts s
                 LEFT JOIN orsts_translations st ON s.orsts_id = st.orsts_id AND st.lang_code = 'th'
                 WHERE s.orsts_del = 0";
    
    if (!empty($search)) {
        $countSql .= " AND (st.orsts_name LIKE ? OR s.orsts_code LIKE ?)";
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
                    <th class="ps-4" style="width: 80px;">ลำดับ</th>
                    <th style="width: 100px;">สี</th>
                    <th>ชื่อสถานะ</th>
                    <th>ข้อความแจ้งเตือน (TH)</th>
                    <th style="width: 120px;">ลูกค้าเห็น</th>
                    <th style="width: 100px;">สถานะ</th>
                    <th style="width: 150px;" class="text-end pe-4">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($statuses) > 0): ?>
                    <?php foreach ($statuses as $status): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-light text-dark border"><?= $status['orsts_index'] ?></span>
                            </td>
                            <td>
                                <div style="width: 30px; height: 30px; border-radius: 50%; background-color: <?= $status['orsts_color'] ?>; border: 1px solid rgba(0,0,0,0.1);" title="<?= $status['orsts_color'] ?>"></div>
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($status['name_th'] ?? 'ไม่มีชื่อ') ?></div>
                                <small class="text-muted">Code: <?= htmlspecialchars($status['orsts_code']) ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars(mb_substr($status['msg_th'] ?? '', 0, 50)) ?>...</small>
                            </td>
                            <td>
                                <?php if ($status['orsts_user'] == 1): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill">
                                        <i data-lucide="eye" style="width:14px; height:14px;" class="me-1"></i> แสดง
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill">
                                        <i data-lucide="eye-off" style="width:14px; height:14px;" class="me-1"></i> ซ่อน
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($status['orsts_status'] == 1): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill" 
                                          style="cursor: pointer;"
                                          onclick="toggleStatus(<?= $status['orsts_id'] ?>, 1)"
                                          title="คลิกเพื่อเปลี่ยนสถานะ">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" 
                                          style="cursor: pointer;"
                                          onclick="toggleStatus(<?= $status['orsts_id'] ?>, 0)"
                                          title="คลิกเพื่อเปลี่ยนสถานะ">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button onclick="editStatus(<?= $status['orsts_id'] ?>)" class="btn btn-sm btn-outline-primary me-1" title="แก้ไข">
                                    <i data-lucide="edit-2" style="width:16px; height:16px;"></i>
                                </button>
                                <button onclick="deleteStatus(<?= $status['orsts_id'] ?>, '<?= htmlspecialchars($status['name_th'] ?? '') ?>')" class="btn btn-sm btn-outline-danger" title="ลบ">
                                    <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">ไม่พบข้อมูลสถานะ</td>
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
