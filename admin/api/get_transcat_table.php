<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $lang   = isset($_GET['lang']) ? $_GET['lang'] : 'th';

    // Join with translations; fall back to main table columns when translation not available
    $sql = "SELECT 
                t.*,
                COALESCE(tt.transcat_name,   t.transcat_name)   AS transcat_name_display,
                COALESCE(tt.transcat_nshort, t.transcat_nshort) AS transcat_nshort_display,
                COALESCE(tt.transcat_detail, t.transcat_detail) AS transcat_detail_display
            FROM transcat t 
            LEFT JOIN transcat_translations tt 
                ON t.transcat_id = tt.transcat_id AND tt.lang_code = :lang
            WHERE t.transcat_del = 0";
    
    $params = [':lang' => $lang];

    if ($search) {
        $sql .= " AND (
            COALESCE(tt.transcat_name, t.transcat_name) LIKE :search 
            OR COALESCE(tt.transcat_nshort, t.transcat_nshort) LIKE :search
        )";
        $params[':search'] = "%$search%";
    }

    $sql .= " ORDER BY CAST(t.transcat_index AS UNSIGNED) ASC, t.transcat_id ASC";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $transcats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($transcats);

    // Generate HTML Table
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;" class="text-center">#</th>
                    <th>ชื่อขนส่ง</th>
                    <th style="width: 110px;">ชื่อย่อ</th>
                    <th>Link ตรวจสอบพัสดุ</th>
                    <th class="text-center" style="width: 70px;">COD</th>
                    <th class="text-center" style="width: 90px;">สถานะ</th>
                    <th class="text-end" style="width: 100px;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transcats) > 0): ?>
                    <?php foreach ($transcats as $tc):
                        $rowOpacity = $tc['transcat_status'] == 0 ? 'opacity: 0.45;' : '';
                        $hasLink    = !empty($tc['transcat_link']);
                        $hasPlaceholder = $hasLink && strpos($tc['transcat_link'], 'xxx') !== false;
                        $sampleUrl  = '';
                        if ($hasLink) {
                            $sampleUrl = $hasPlaceholder
                                ? str_replace('xxx', 'TH123456789', $tc['transcat_link'])
                                : $tc['transcat_link'] . 'TH123456789';
                        }
                        $name   = htmlspecialchars($tc['transcat_name_display'] ?? '');
                        $nshort = htmlspecialchars($tc['transcat_nshort_display'] ?? '');
                        $detail = htmlspecialchars(mb_strimwidth($tc['transcat_detail_display'] ?? '', 0, 60, '...'));
                    ?>
                        <tr style="<?= $rowOpacity ?>">
                            <td class="text-center fw-bold text-muted" style="font-size:0.85rem;"><?= (int)$tc['transcat_index'] ?></td>
                            <td>
                                <div class="fw-bold"><?= $name ?></div>
                                <?php if ($detail): ?>
                                    <small class="text-muted" style="font-size:0.75rem;"><?= $detail ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($nshort): ?>
                                    <span class="badge bg-secondary bg-opacity-15 text-secondary border fw-normal px-2 py-1" style="font-size:0.78rem;">
                                        <?= $nshort ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($hasLink): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="flex-grow-1 min-width-0">
                                            <?php if ($hasPlaceholder): ?>
                                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 mb-1 d-inline-flex align-items-center gap-1" style="font-size:0.7rem;">
                                                    <i data-lucide="check-circle" style="width:10px;height:10px;"></i> รองรับ Tracking
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25 mb-1 d-inline-flex align-items-center gap-1" style="font-size:0.7rem;">
                                                    <i data-lucide="alert-triangle" style="width:10px;height:10px;"></i> ลิงค์เว็บเท่านั้น
                                                </span>
                                            <?php endif; ?>
                                            <div class="text-truncate" style="max-width:250px; font-size:0.75rem; color:#666;">
                                                <?= htmlspecialchars($tc['transcat_link']) ?>
                                            </div>
                                        </div>
                                        <a href="<?= htmlspecialchars($sampleUrl) ?>" target="_blank" rel="noopener noreferrer"
                                           class="btn btn-sm btn-outline-success flex-shrink-0" 
                                           title="ทดสอบลิงค์ (ตัวอย่าง)">
                                            <i data-lucide="external-link" style="width:13px;height:13px;"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">— ไม่มีลิงค์</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($tc['transcat_cod'] == 1): ?>
                                    <span class="badge bg-success fw-normal">COD</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center m-0">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           onchange="toggleStatus(<?= $tc['transcat_id'] ?>, this.checked)"
                                           <?= $tc['transcat_status'] == 1 ? 'checked' : '' ?>>
                                </div>
                            </td>
                            <td class="text-end">
                                <button onclick="editTranscat(<?= $tc['transcat_id'] ?>)" 
                                        class="btn btn-sm btn-outline-primary me-1"
                                        title="แก้ไข">
                                    <i data-lucide="edit" style="width:14px; height:14px;"></i>
                                </button>
                                <button onclick="deleteTranscat(<?= $tc['transcat_id'] ?>)" 
                                        class="btn btn-sm btn-outline-danger"
                                        title="ลบ">
                                    <i data-lucide="trash-2" style="width:14px; height:14px;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i data-lucide="package-x" class="d-block mx-auto mb-2" style="width:32px;height:32px;opacity:0.4;"></i>
                            ไม่พบข้อมูลขนส่ง<?= $search ? " สำหรับ \"$search\"" : '' ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $html = ob_get_clean();

    echo json_encode(['success' => true, 'html' => $html, 'total' => $total]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
