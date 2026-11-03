<?php
$page_title = "ประวัติใบสั่งผลิต";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print flex-wrap gap-3">
        <div>
            <h2 class="mb-0"><i data-lucide="history" class="me-2 text-primary"></i>ประวัติใบสั่งผลิต</h2>
            <p class="text-muted small mb-0">รายการใบสั่งผลิตที่เคยบันทึกไว้ในระบบ</p>
        </div>
        
        <form method="GET" class="d-flex gap-2 align-items-center">
            <?php
            $filter_date = isset($_GET['date']) ? trim($_GET['date']) : '';
            ?>
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>" onchange="this.form.submit()" style="max-width: 180px;">
            <?php if (!empty($filter_date)): ?>
                <a href="history.php" class="btn btn-outline-secondary px-3">ล้าง</a>
            <?php endif; ?>
            <a href="<?= ADMIN_URL ?>/production/" class="btn btn-outline-primary ms-2 text-nowrap">
                <i data-lucide="arrow-left" class="me-1" style="width:16px;"></i> กลับหน้าใบสั่งผลิต
            </a>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">เลขที่ใบสั่งผลิต</th>
                            <th>วันที่บันทึก</th>
                            <th>ผู้ทำรายการ</th>
                            <th class="text-end pe-4">เครื่องมือ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                        $limit = 10;
                        $offset = ($page - 1) * $limit;
                        
                        $where_clause = "";
                        $params = [];
                        if (!empty($filter_date)) {
                            $where_clause = "WHERE DATE(pb.created_at) = :filter_date";
                            $params[':filter_date'] = $filter_date;
                        }
                        
                        // Count total for pagination
                        $count_sql = "SELECT COUNT(*) FROM production_batches pb $where_clause";
                        $stmt_count = $db->prepare($count_sql);
                        $stmt_count->execute($params);
                        $total_records = $stmt_count->fetchColumn();
                        $total_pages = ceil($total_records / $limit);

                        $sql = "SELECT pb.*, u.user_name, u.user_lastname 
                                FROM production_batches pb 
                                LEFT JOIN user u ON pb.created_by = u.user_id 
                                $where_clause
                                ORDER BY pb.created_at DESC 
                                LIMIT $limit OFFSET $offset";
                        $stmt = $db->prepare($sql);
                        $stmt->execute($params);
                        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($batches)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i data-lucide="info" class="mb-2" style="width:32px;height:32px;"></i>
                                    <p class="mb-0">ยังไม่มีประวัติการบันทึก</p>
                                </td>
                            </tr>
                        <?php else: 
                            foreach ($batches as $row): 
                                $operatorName = trim(($row['user_name'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
                                if (empty($operatorName)) $operatorName = "ผู้ดูแลระบบ";
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary"><?= $row['batch_no'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($operatorName) ?></td>
                                <td class="text-end pe-4">
                                    <button onclick="viewBatch(<?= $row['batch_id'] ?>)" class="btn btn-sm btn-outline-primary me-1">
                                        <i data-lucide="eye" style="width:14px;height:14px;"></i> ดูข้อมูล
                                    </button>
                                    <button onclick="printBatch(<?= $row['batch_id'] ?>)" class="btn btn-sm btn-primary">
                                        <i data-lucide="printer" style="width:14px;height:14px;"></i> พิมพ์อีกครั้ง
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white border-0 py-3">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($filter_date) ? '&date='.$filter_date : '' ?>" tabindex="-1">ก่อนหน้า</a>
                        </li>
                        
                        <?php 
                        // Show up to 5 page numbers around the current page
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1'.(!empty($filter_date) ? '&date='.$filter_date : '').'">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= !empty($filter_date) ? '&date='.$filter_date : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; 
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.(!empty($filter_date) ? '&date='.$filter_date : '').'">'.$total_pages.'</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($filter_date) ? '&date='.$filter_date : '' ?>">ถัดไป</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal for Viewing Batch Details -->
<div class="modal fade" id="batchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i data-lucide="file-text" class="me-2"></i>รายละเอียดใบสั่งผลิต <span id="modalBatchNo"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="batchMatrixContent">
                    <!-- Matrix will be loaded here -->
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                <button type="button" id="modalPrintBtn" class="btn btn-primary px-4">
                    <i data-lucide="printer" class="me-2"></i>พิมพ์ใบนี้
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Reuse production matrix styles */
    .production-matrix { border-collapse: collapse; width: 100%; background: #fff; }
    .production-matrix th, .production-matrix td { border: 1px solid #dee2e6; padding: 10px 6px; text-align: center; vertical-align: middle; }
    .production-matrix th { background-color: #f8f9fa; font-weight: 600; color: #334155; font-size: 0.9rem; }
    .header-group { background-color: #4f46e5 !important; color: #fff !important; }
    .header-sub { background-color: #e2e8f0 !important; font-size: 0.75rem; }
    .row-title { text-align: left !important; font-weight: 500; font-size: 0.9rem; }
    .qty-cell { font-weight: bold; color: #4f46e5; }
    .qty-zero { color: #cbd5e1; }
    .total-row { background-color: #f1f5f9 !important; font-weight: bold; }
    
    /* Native Datepicker Dark Mode */
    [data-theme="dark"] input[type="date"] {
        color-scheme: dark;
    }
</style>

<script>
let allBatches = <?= json_encode($batches) ?>;
let currentBatchId = null;

function viewBatch(id) {
    const batch = allBatches.find(b => b.batch_id == id);
    if (!batch) return;

    currentBatchId = id;
    document.getElementById('modalBatchNo').textContent = batch.batch_no;
    
    const batchData = JSON.parse(batch.batch_data);
    renderBatchMatrix(batchData, 'batchMatrixContent');
    
    const modal = new bootstrap.Modal(document.getElementById('batchModal'));
    
    // Setup print button inside modal
    document.getElementById('modalPrintBtn').onclick = () => printBatch(id);
    
    modal.show();
    if (window.lucide) lucide.createIcons();
}

function printBatch(id) {
    const batch = allBatches.find(b => b.batch_id == id);
    if (!batch) return;

    const batchData = JSON.parse(batch.batch_data);
    
    // Create an invisible iframe for printing to avoid pop-up blockers
    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';
    document.body.appendChild(iframe);

    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(`
        <html>
        <head>
            <title>ใบสั่งผลิต ${batch.batch_no}</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                @page { size: A4; margin: 0.5cm; }
                body { padding: 0; margin: 0; font-family: sans-serif; font-size: 11pt; background: #fff; color: #000; }
                .text-center { text-align: center; margin-bottom: 20px; }
                .production-matrix { border-collapse: collapse !important; width: 100%; border: 2px solid #000; }
                .production-matrix th, .production-matrix td { border: 1px solid #000 !important; padding: 6px 4px; text-align: center; }
                .header-group { background-color: #eee !important; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .row-title { text-align: left !important; font-weight: bold; }
                .qty-cell { font-weight: bold; font-size: 1.2rem; }
                .total-row { background-color: #f0f0f0 !important; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .memo-text { color: #000 !important; font-weight: bold; font-size: 0.7rem; margin-top: 2px; }
                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body>
            <div class="text-center" style="margin-top: 30px;">
                <h2 style="margin:0;">ใบสั่งผลิตยาหม่องยักษ์โพธิ์</h2>
                <p style="margin: 5px 0;">เลขที่: <b>${batch.batch_no}</b> | วันที่บันทึก: ${new Date(batch.created_at).toLocaleString('th-TH')}</p>
                <p style="margin: 0; font-size: 0.9rem;">จัดพิมพ์โดย: <b>${(batch.user_name || '') + ' ' + (batch.user_lastname || '') || 'ผู้ดูแลระบบ'}</b></p>
            </div>
            <div id="printArea"></div>
        </body>
        </html>
    `);

    // Use a slightly modified version of the render function
    renderBatchMatrix(batchData, doc.getElementById('printArea'));
    doc.close();

    // Print the iframe once loaded
    iframe.onload = function() {
        setTimeout(() => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            setTimeout(() => document.body.removeChild(iframe), 1000);
        }, 200);
    };
}

// Simplified render function that works for both modal and print window
function renderBatchMatrix(data, containerOrId) {
    const { matrix, all_sizes, all_formulas } = data;
    const container = typeof containerOrId === 'string' ? document.getElementById(containerOrId) : containerOrId;

    // Sorting sizes
    all_sizes.sort((a, b) => {
        const valA = parseInt(a.match(/\d+/) || 0);
        const valB = parseInt(b.match(/\d+/) || 0);
        return valB - valA;
    });

    let html = `
        <table class="production-matrix">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 40px;">#</th>
                    <th rowspan="2" class="row-title">รายการ/กลิ่น</th>
    `;

    all_sizes.forEach(size => {
        html += `<th colspan="${all_formulas.length}" class="header-group">${size}</th>`;
    });

    html += `
                    <th rowspan="2" style="width: 100px;">หมายเหตุ</th>
                </tr>
                <tr>
    `;

    all_sizes.forEach(() => {
        all_formulas.forEach(formula => {
            html += `<th class="header-sub">${formula}</th>`;
        });
    });

    html += `</tr></thead><tbody>`;

    let rowIndex = 1;
    let colTotals = {}; 

    for (const [groupName, scents] of Object.entries(matrix)) {
        html += `
            <tr style="background-color: #f8fafc;">
                <td colspan="${2 + (all_sizes.length * all_formulas.length) + 1}" class="text-start ps-3 fw-bold">
                    [ ${groupName} ]
                </td>
            </tr>
        `;

        const sortedScents = Object.keys(scents).sort((a, b) => 
            a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' })
        );
        sortedScents.forEach(scentName => {
            const sizes = scents[scentName];
            html += `<tr><td>${rowIndex++}</td><td class="row-title">${scentName}</td>`;

            all_sizes.forEach(size => {
                all_formulas.forEach(formula => {
                    const cellData = sizes[size] ? (sizes[size][formula] || {qty: 0, memos: []}) : {qty: 0, memos: []};
                    const qty = typeof cellData === 'object' ? cellData.qty : cellData;
                    const memos = typeof cellData === 'object' ? (cellData.memos || []) : [];

                    const cellKey = `${size}_${formula}`;
                    colTotals[cellKey] = (colTotals[cellKey] || 0) + qty;
                    
                    let memoHtml = '';
                    if (memos.length > 0) {
                        memoHtml = `<div class="memo-text">${memos.join(', ')}</div>`;
                    }

                    html += `<td class="${qty > 0 ? 'qty-cell' : 'qty-zero'}">
                                ${qty > 0 ? qty : '-'}
                                ${memoHtml}
                             </td>`;
                });
            });
            html += `<td></td></tr>`;
        });
    }

    html += `<tr class="total-row"><td colspan="2" class="text-end pe-3">รวมทั้งหมด</td>`;
    all_sizes.forEach(size => {
        all_formulas.forEach(formula => {
            const total = colTotals[`${size}_${formula}`] || 0;
            html += `<td>${total > 0 ? total : '-'}</td>`;
        });
    });
    html += `<td></td></tr></tbody></table>`;

    container.innerHTML = html;
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php";
?>
