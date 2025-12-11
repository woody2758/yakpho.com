<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $lang = isset($_GET['lang']) ? $_GET['lang'] : 'th';

    $sql = "SELECT t.*, tt.transcat_name, tt.transcat_nshort, tt.transcat_detail 
            FROM transcat t 
            LEFT JOIN transcat_translations tt ON t.transcat_id = tt.transcat_id AND tt.lang_code = :lang
            WHERE t.transcat_del = 0";
    
    $params = [':lang' => $lang];

    if ($search) {
        $sql .= " AND (tt.transcat_name LIKE :search OR tt.transcat_nshort LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $sql .= " ORDER BY CAST(t.transcat_index AS UNSIGNED) ASC";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $transcats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML Table
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>ชื่อขนส่ง</th>
                    <th>ชื่อย่อ</th>
                    <th>Link ตรวจสอบ</th>
                    <th class="text-center">COD</th>
                    <th class="text-center">สถานะ</th>
                    <th class="text-end">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transcats) > 0): ?>
                    <?php foreach ($transcats as $tc): 
                        $rowStyle = $tc['transcat_status'] == 0 ? 'style="opacity: 0.4;"' : '';
                    ?>
                        <tr <?= $rowStyle ?>>
                            <td><?= $tc['transcat_index'] ?></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($tc['transcat_name']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($tc['transcat_nshort']) ?></td>
                            <td>
                                <?php if ($tc['transcat_link']): ?>
                                    <a href="<?= $tc['transcat_link'] ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                        <?= htmlspecialchars($tc['transcat_link']) ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($tc['transcat_cod'] == 1): ?>
                                    <span class="badge bg-success">รองรับ</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">ไม่รองรับ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           onchange="toggleStatus(<?= $tc['transcat_id'] ?>, this.checked)"
                                           <?= $tc['transcat_status'] == 1 ? 'checked' : '' ?>>
                                </div>
                            </td>
                            <td class="text-end">
                                <button onclick="editTranscat(<?= $tc['transcat_id'] ?>)" class="btn btn-sm btn-outline-primary me-1">
                                    <i data-lucide="edit" style="width:16px; height:16px;"></i>
                                </button>
                                <button onclick="deleteTranscat(<?= $tc['transcat_id'] ?>)" class="btn btn-sm btn-outline-danger">
                                    <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">ไม่พบข้อมูล</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $html = ob_get_clean();

    echo json_encode(['success' => true, 'html' => $html]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
