<?php
$page_title = "ใบสั่งผลิตสินค้า";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<style>
    /* ===== Light Mode ===== */
    .production-matrix {
        border-collapse: collapse;
        width: 100%;
        background: #fff;
    }
    .production-matrix th, .production-matrix td {
        border: 1px solid #dee2e6;
        padding: 12px 8px;
        text-align: center;
        vertical-align: middle;
    }
    .production-matrix th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #334155;
    }
    .header-group {
        background-color: #4f46e5 !important;
        color: #fff !important;
    }
    .header-sub {
        background-color: #e2e8f0 !important;
        font-size: 0.8rem;
        color: #475569;
    }
    .row-title {
        text-align: left !important;
        font-weight: 500;
        background-color: #fff;
        color: #1e293b;
    }
    .qty-cell {
        font-weight: bold;
        font-size: 1.1rem;
        color: #4f46e5;
    }
    .qty-zero {
        color: #cbd5e1;
        font-weight: normal;
    }
    .total-row {
        background-color: #f1f5f9 !important;
        font-weight: bold;
    }
    .group-header-row {
        background-color: #f8fafc;
    }

    /* ===== Dark Mode ===== */
    [data-theme="dark"] .card.shadow-sm {
        background-color: #1a1f2e !important;
        border: 1px solid rgba(255,255,255,0.08) !important;
        box-shadow: 0 4px 24px rgba(0,0,0,0.4) !important;
    }
    [data-theme="dark"] .card-body {
        background-color: transparent !important;
    }
    [data-theme="dark"] .card h3 {
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] .production-matrix {
        background: transparent;
    }
    [data-theme="dark"] .production-matrix th,
    [data-theme="dark"] .production-matrix td {
        border-color: rgba(255,255,255,0.1) !important;
        color: #e2e8f0;
    }
    [data-theme="dark"] .production-matrix th {
        background-color: rgba(255,255,255,0.05) !important;
        color: #cbd5e1 !important;
    }
    [data-theme="dark"] .header-group {
        background-color: #4f46e5 !important;
        color: #fff !important;
    }
    [data-theme="dark"] .header-sub {
        background-color: rgba(79,70,229,0.2) !important;
        color: #a5b4fc !important;
    }
    [data-theme="dark"] .row-title {
        background-color: rgba(255,255,255,0.02) !important;
        color: #e2e8f0 !important;
    }
    [data-theme="dark"] .qty-cell {
        color: #818cf8 !important;
    }
    [data-theme="dark"] .qty-zero {
        color: rgba(255,255,255,0.2) !important;
    }
    [data-theme="dark"] .total-row {
        background-color: rgba(79,70,229,0.15) !important;
        color: #e2e8f0 !important;
    }
    [data-theme="dark"] .total-row .text-primary {
        color: #818cf8 !important;
    }
    [data-theme="dark"] .total-row .text-muted {
        color: rgba(255,255,255,0.25) !important;
    }
    [data-theme="dark"] .group-header-row {
        background-color: rgba(255,255,255,0.03) !important;
    }
    [data-theme="dark"] .group-header-row td {
        color: #818cf8 !important;
    }
    [data-theme="dark"] .table-responsive {
        border-radius: 8px;
        overflow: hidden;
    }

    @media print {
        @page { size: A4; margin: 0.5cm; }
        .no-print, .topbar, .navbar { display: none !important; }
        .sidebar { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .card { border: none !important; box-shadow: none !important; }
        .container-fluid { padding: 0 !important; width: 100% !important; }
        body { background: white !important; font-size: 11pt !important; margin: 0; padding: 0; }
        
        .production-matrix { 
            width: 100% !important; 
            border: 2px solid #000 !important; 
            border-collapse: collapse !important;
        }
        .production-matrix th, .production-matrix td { 
            border: 1px solid #000 !important; 
            padding: 8px 4px !important;
        }
        .header-group { 
            background-color: #eee !important; 
            color: #000 !important; 
            -webkit-print-color-adjust: exact;
        }
        .qty-cell { 
            font-size: 1.3rem !important;
            color: #000 !important;
        }
        .text-danger { 
            color: #000 !important; 
            font-weight: bold !important;
        }
    }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2 class="mb-0"><i data-lucide="factory" class="me-2"></i>ใบสั่งผลิตสินค้า</h2>
            <p class="text-muted small mb-0">รวบรวมยอดที่ต้องผลิตจากออเดอร์สถานะ "เตรียมสินค้า"</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= ADMIN_URL ?>/production/history.php" class="btn btn-outline-secondary">
                <i data-lucide="history" class="me-1" style="width:16px;"></i> ดูประวัติย้อนหลัง
            </a>
            <button onclick="loadProductionData()" class="btn btn-outline-primary">
                <i data-lucide="refresh-cw" class="me-1" style="width:16px;"></i> อัปเดตข้อมูล
            </button>
            <button onclick="saveAndPrint()" class="btn btn-success" id="savePrintBtn">
                <i data-lucide="check-circle" class="me-1" style="width:16px;"></i> ยืนยันสั่งผลิต & พิมพ์
            </button>
            <button onclick="window.print()" class="btn btn-primary d-none">
                <i data-lucide="printer" class="me-1" style="width:16px;"></i> พิมพ์
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h3 class="mb-1">ใบสั่งผลิตยาหม่องยักษ์โพธิ์</h3>
                <p class="mb-0 fw-bold d-none d-print-block" style="font-size: 1.1rem;">เลขที่ใบสั่งผลิต: <span id="printBatchNo"></span></p>
                <p class="text-muted mb-0">ประจำวันที่ <span id="displayDate">...</span></p>
                <p class="small text-muted mb-0">ผู้สั่งผลิตและพิมพ์: <b>(ID: <?= $_SESSION['admin_id'] ?? '' ?>) <?= $ADMIN_NAME ?></b></p>
            </div>

            <div id="matrixContainer" class="table-responsive">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">กำลังคำนวณยอดผลิต...</p>
                </div>
            </div>

            <div class="mt-4 no-print text-muted small">
                <i data-lucide="info" style="width:14px; height:14px;" class="me-1"></i> 
                หมายเหตุ: ยอดจะคำนวณเฉพาะสินค้าในหมวดหมู่ที่ตั้งค่าเป็น "สินค้าสั่งผลิต" เท่านั้น
            </div>
        </div>
    </div>
</div>

<script>
let currentProductionData = null;

async function loadProductionData() {
    const container = document.getElementById('matrixContainer');
    try {
        const response = await fetch('<?= ADMIN_URL ?>/api/get_production_data.php');
        const data = await response.json();

        if (data.success) {
            currentProductionData = data;
            document.getElementById('displayDate').textContent = data.date;
            renderMatrix(data);
        } else {
            container.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    } catch (error) {
        container.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>`;
    }
}

async function saveAndPrint() {
    if (!currentProductionData) {
        Swal.fire('ข้อผิดพลาด', 'ไม่มีข้อมูลสำหรับบันทึก กรุณารีเฟรชข้อมูล', 'error');
        return;
    }

    const btn = document.getElementById('savePrintBtn');
    const originalHtml = btn.innerHTML;
    
    try {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';

        const response = await fetch('<?= ADMIN_URL ?>/api/save_production_batch.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(currentProductionData)
        });
        
        const res = await response.json();
        
        if (res.success) {
            // Success! Now print
            document.getElementById('printBatchNo').textContent = res.batch_no;
            
            const now = new Date();
            const timeStr = now.toLocaleTimeString('th-TH');
            const displayDateElem = document.getElementById('displayDate');
            if(!displayDateElem.textContent.includes('เวลา')) {
                displayDateElem.textContent += ' เวลา ' + timeStr;
            }

            setTimeout(() => {
                // Create an invisible iframe for printing to avoid pop-up blockers and CSS conflicts
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
                        <title>ใบสั่งผลิต ${res.batch_no}</title>
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
                            <p style="margin: 5px 0; font-size: 1.1rem;">เลขที่ใบสั่งผลิต: <b>${res.batch_no}</b></p>
                            <p style="margin: 5px 0;">วันที่บันทึก: ${new Date().toLocaleString('th-TH')}</p>
                            <p style="margin: 0; font-size: 0.9rem;">สร้างและพิมพ์โดย: <b>(ID: <?= $_SESSION['admin_id'] ?? '' ?>) <?= $ADMIN_NAME ?></b></p>
                        </div>
                        <div id="printArea"></div>
                    </body>
                    </html>
                `);

                renderPrintWindowMatrix(currentProductionData, doc.getElementById('printArea'));
                doc.close();

                // Print the iframe once loaded
                iframe.onload = function() {
                    setTimeout(() => {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                        setTimeout(() => document.body.removeChild(iframe), 1000);
                    }, 200);
                };

                // Restore button
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกประวัติแล้ว',
                    text: 'เลขที่ใบสั่งผลิต: ' + res.batch_no,
                    timer: 2000,
                    showConfirmButton: false
                });
            }, 100);
        } else {
            throw new Error(res.message);
        }
    } catch (error) {
        Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

async function editCellNote(group, scent, size, formula) {
    if (!currentProductionData) return;

    const cell = currentProductionData.matrix[group][scent][size][formula];
    const currentMemos = cell.memos.join(', ');

    const { value: newMemo } = await Swal.fire({
        title: `บันทึกเพิ่มเติม: ${scent}`,
        text: `ขนาด ${size} | สูตร ${formula}`,
        input: 'text',
        inputValue: currentMemos,
        inputPlaceholder: 'พิมพ์บันทึกเพิ่มเติมที่นี่...',
        showCancelButton: true,
        confirmButtonText: 'ตกลง',
        cancelButtonText: 'ยกเลิก'
    });

    if (newMemo !== undefined) {
        // Update data object
        currentProductionData.matrix[group][scent][size][formula].memos = newMemo ? [newMemo] : [];
        
        // Re-render to show update
        renderMatrix(currentProductionData);
    }
}

function renderMatrix(data) {
    const container = document.getElementById('matrixContainer');
    const { matrix, all_sizes, all_formulas } = data;

    if (Object.keys(matrix).length === 0) {
        container.innerHTML = '<div class="text-center py-5 text-muted">ไม่มีรายการสั่งผลิตในขณะนี้</div>';
        return;
    }

    // Sort sizes for consistency
    all_sizes.sort((a, b) => {
        const valA = parseInt(a.match(/\d+/) || 0);
        const valB = parseInt(b.match(/\d+/) || 0);
        return valB - valA;
    });

    let html = `
        <table class="production-matrix">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 50px;">ลำดับ</th>
                    <th rowspan="2" class="row-title">รายการ/กลิ่น</th>
    `;

    // Header Level 1: Sizes
    all_sizes.forEach(size => {
        html += `<th colspan="${all_formulas.length}" class="header-group">${size}</th>`;
    });

    html += `
                    <th rowspan="2" style="width: 150px;">หมายเหตุ</th>
                </tr>
                <tr>
    `;

    // Header Level 2: Formulas (repeated for each size)
    all_sizes.forEach(() => {
        all_formulas.forEach(formula => {
            html += `<th class="header-sub" style="min-width: 80px;">${formula}</th>`;
        });
    });

    html += `
                </tr>
            </thead>
            <tbody>
    `;

    let rowIndex = 1;
    let colTotals = {}; 

    for (const [groupName, scents] of Object.entries(matrix)) {
        // Group Header Row
        html += `
            <tr class="group-header-row">
                <td colspan="${2 + (all_sizes.length * all_formulas.length) + 1}" class="text-start ps-3 fw-bold text-primary">
                    <i data-lucide="package" style="width:14px;height:14px;" class="me-1"></i> ${groupName}
                </td>
            </tr>
        `;

        // Sort scents naturally (1, 2, 3... 10, 11)
        const sortedScents = Object.keys(scents).sort((a, b) => 
            a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' })
        );

        sortedScents.forEach(scentName => {
            const sizes = scents[scentName];
            html += `
                <tr>
                    <td>${rowIndex++}</td>
                    <td class="row-title">${scentName}</td>
            `;

            all_sizes.forEach(size => {
                all_formulas.forEach(formula => {
                    const cellData = sizes[size] ? (sizes[size][formula] || {qty: 0, memos: []}) : {qty: 0, memos: []};
                    const qty = cellData.qty;
                    const memos = cellData.memos || [];
                    
                    const cellKey = `${size}_${formula}`;
                    colTotals[cellKey] = (colTotals[cellKey] || 0) + qty;

                    let memoHtml = '';
                    if (memos.length > 0) {
                        memoHtml = `<div class="text-danger small fw-normal mt-1" style="font-size: 0.7rem; line-height: 1;">${memos.join(', ')}</div>`;
                    }

                    html += `
                        <td class="${qty > 0 ? 'qty-cell' : 'qty-zero'}" 
                            style="cursor: pointer;" 
                            onclick="editCellNote('${groupName}', '${scentName}', '${size}', '${formula}')">
                            ${qty > 0 ? qty : '-'}
                            ${memoHtml}
                        </td>`;
                });
            });

            html += `<td></td></tr>`;
        });
    }

    // Bottom Row: Totals
    html += `
        <tr class="total-row">
            <td colspan="2" class="text-end pe-4">ยอดรวมที่ต้องผลิตทั้งหมด</td>
    `;

    all_sizes.forEach(size => {
        all_formulas.forEach(formula => {
            const total = colTotals[`${size}_${formula}`] || 0;
            html += `<td class="${total > 0 ? 'text-primary' : 'text-muted'}">${total > 0 ? total : '-'}</td>`;
        });
    });

    html += `<td></td></tr></tbody></table>`;

    container.innerHTML = html;
    if (window.lucide) lucide.createIcons();
}

function renderPrintWindowMatrix(data, container) {
    const { matrix, all_sizes, all_formulas } = data;

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

document.addEventListener('DOMContentLoaded', loadProductionData);
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php";
?>
