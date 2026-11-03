<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$page_title = "จัดการภาษา";
ob_start();
?>

<style>
/* ปรับแต่งปุ่มสวิตช์ iOS Style Switch ให้หรูหรา */
.form-switch .form-check-input {
    width: 2.8em;
    height: 1.5em;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
}
.form-switch .form-check-input:checked {
    background-color: #1abc9c !important; /* Premium Mint color */
    border-color: #1abc9c !important;
}
.form-switch .form-check-input:focus {
    box-shadow: none;
}

/* คลาสสำหรับลากวาง */
.drag-handle {
    cursor: grab;
    transition: color 0.2s ease;
}
.drag-handle:active {
    cursor: grabbing;
}
.drag-handle:hover {
    color: #3498db !important;
}
.sortable-ghost {
    opacity: 0.4;
    background-color: rgba(52, 152, 219, 0.08) !important;
}
.sortable-drag {
    background-color: rgba(255, 255, 255, 0.05) !important;
    box-shadow: 0 5px 15px rgba(0,0,0,0.5) !important;
}

.flag-img {
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}
</style>

<div class="container-fluid px-1 px-md-3 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0"><i data-lucide="languages" class="me-2 text-primary" style="width:24px; height:24px;"></i>ตั้งค่าภาษาของระบบ (Language Settings)</h3>
            <p class="text-muted small mb-0 mt-1 ms-4">เปิด/ปิด ภาษาเพื่อควบคุมหน้าบ้านและแบบฟอร์มบันทึกข้อมูลหลังบ้านทั้งหมด</p>
        </div>
    </div>

    <div class="row g-2">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded">
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="languageTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="120">อันดับ</th>
                                    <th width="100">ธงชาติ</th>
                                    <th>ชื่อภาษา (Language)</th>
                                    <th width="120">รหัสย่อ (Code)</th>
                                    <th width="150" class="text-center">สถานะใช้งาน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <div class="text-muted small mt-2">กำลังโหลดข้อมูลภาษา...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 p-3 rounded bg-light border">
                        <h6 class="fw-bold text-primary mb-2 small"><i data-lucide="info" class="me-1" style="width: 14px; height: 14px;"></i> แนะนำการใช้งาน:</h6>
                        <ul class="text-muted small mb-0 ps-3">
                            <li>เมื่อเปิดใช้งานสถานะ (MINT) ภาษานั้นจะพร้อมให้ลูกค้าเลือกใช้งานที่หน้าหลัก และปลดล็อกช่องกรอกรายละเอียดสินค้าเพิ่มขึ้นในระบบหลังบ้าน</li>
                            <li>หากปิดใช้งานสถานะ ภาษานั้นจะถูกซ่อนไว้ในทุกๆ เมนู ทำให้การทำงานง่ายและรวดเร็วขึ้น</li>
                            <li><strong>การจัดอันดับ:</strong> คลิกค้างที่ปุ่มไอคอนลากแฮนเดิลด้านหน้าสุด แล้วลากเพื่อเปลี่ยนอันดับสลับตำแหน่งได้ทันที ระบบจะจัดเก็บอันดับใหม่โดยอัตโนมัติ</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadLanguages();
});

// ดึงข้อมูลภาษาทั้งหมดผ่าน API
function loadLanguages() {
    fetch('../api/language_actions.php?action=list')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderLanguageTable(data.languages);
            } else {
                Swal.fire('ผิดพลาด', 'โหลดข้อมูลไม่สำเร็จ: ' + data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('ผิดพลาด', 'เชื่อมต่อเซิร์ฟเวอร์ล้มเหลว', 'error');
        });
}

// วาดตารางข้อมูลภาษา
function renderLanguageTable(languages) {
    const tbody = document.querySelector('#languageTable tbody');
    let html = '';
    
    if (languages.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">ไม่พบข้อมูลภาษาในระบบ</td></tr>`;
        return;
    }
    
    languages.forEach((lang, index) => {
        const isChecked = parseInt(lang.lang_status) === 1 ? 'checked' : '';
        html += `
            <tr data-code="${lang.lang_code}">
                <td>
                    <div class="d-flex align-items-center">
                        <i data-lucide="grip-vertical" class="drag-handle text-muted me-3" style="width: 18px; height: 18px;"></i>
                        <span class="badge bg-secondary bg-opacity-25 text-white fw-bold px-3 py-2 rounded-3 rank-number">${index + 1}</span>
                    </div>
                </td>
                <td>
                    <img src="<?= ROOT_URL ?>/assets/images/flags/${lang.lang_code}.png" width="30" class="flag-img" alt="${lang.lang_name}">
                </td>
                <td>
                    <strong class="font-size-15">${lang.lang_name}</strong>
                </td>
                <td>
                    <span class="badge bg-secondary bg-opacity-20 text-info fw-bold px-3 py-2 rounded-pill">${lang.lang_code.toUpperCase()}</span>
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" role="switch" ${isChecked} onchange="toggleLanguageStatus('${lang.lang_code}', this)">
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    if (window.lucide) {
        lucide.createIcons();
    }
    
    // ตั้งค่าความสามารถลากวาง (Drag & Drop)
    initializeDragAndDrop();
}

// ตั้งค่าลากวาง SortableJS
function initializeDragAndDrop() {
    const tbody = document.querySelector('#languageTable tbody');
    if (tbody.sortable) {
        tbody.sortable.destroy();
    }
    
    tbody.sortable = new Sortable(tbody, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: function() {
            saveOrderSequenceAuto();
        }
    });
}

// เปิด/ปิดการใช้งานภาษา
function toggleLanguageStatus(code, checkbox) {
    const status = checkbox.checked ? 1 : 0;
    
    const formData = new FormData();
    formData.append('lang_code', code);
    formData.append('lang_status', status);
    
    fetch('../api/language_actions.php?action=toggle_status', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
            Toast.fire({
                icon: 'success',
                title: status === 1 ? `เปิดใช้งานภาษา ${code.toUpperCase()} แล้ว` : `ปิดใช้งานภาษา ${code.toUpperCase()} แล้ว`
            });
        } else {
            checkbox.checked = !checkbox.checked; // คืนค่าหากมีข้อผิดพลาด
            Swal.fire('ผิดพลาด', 'อัปเดตสถานะไม่สำเร็จ: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        checkbox.checked = !checkbox.checked;
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการส่งข้อมูล', 'error');
    });
}

// อัปเดตและบันทึกลำดับจัดเรียงโดยอัตโนมัติหลังวางแถว
function saveOrderSequenceAuto() {
    const rows = document.querySelectorAll('#languageTable tbody tr');
    const orders = [];
    
    // วนรอบเพื่อเปลี่ยนเลขลำดับการแสดงผลในหน้าจอและเตรียมข้อมูลจัดส่ง
    rows.forEach((row, index) => {
        const code = row.getAttribute('data-code');
        if (code) {
            // อัปเดตตัวเลขอันดับบนหน้าจอทันที
            const rankBadge = row.querySelector('.rank-number');
            if (rankBadge) {
                rankBadge.textContent = index + 1;
            }
            orders.push({ code: code, order: index + 1 });
        }
    });
    
    const formData = new FormData();
    orders.forEach((item, index) => {
        formData.append(`orders[${index}][code]`, item.code);
        formData.append(`orders[${index}][order]`, item.order);
    });
    
    fetch('../api/language_actions.php?action=update_order', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
            Toast.fire({
                icon: 'success',
                title: 'จัดเรียงอันดับภาษาสำเร็จ'
            });
        } else {
            Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกลำดับได้: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('ผิดพลาด', 'เชื่อมต่อเซิร์ฟเวอร์ล้มเหลว', 'error');
    });
}
</script>

<?php 
$content = ob_get_clean();
include '../includes/layout.php'; 
?>
