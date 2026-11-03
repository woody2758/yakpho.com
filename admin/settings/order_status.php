<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$page_title = "จัดการสถานะออเดอร์";

// ดึงภาษาที่มีทั้งหมดในระบบ
$stmt_lang = $db->query("SELECT * FROM languages ORDER BY lang_order ASC");
$languages = $stmt_lang->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<style>
/* บังคับ Dark Mode ให้เข้มแน่นอน */
[data-theme="dark"] .modal-content {
    background-color: #1a1a1a !important;
    color: #f0f0f0 !important;
    border: 1px solid #333 !important;
}
[data-theme="dark"] .card {
    background-color: #1a1a1a !important;
    color: #f0f0f0 !important;
}
[data-theme="dark"] .bg-light {
    background-color: #262626 !important;
    color: #f0f0f0 !important;
}
[data-theme="dark"] .form-control, [data-theme="dark"] .form-select {
    background-color: #262626 !important;
    border-color: #444 !important;
    color: #fff !important;
}
[data-theme="dark"] .form-control:focus {
    background-color: #2d2d2d !important;
    color: #fff !important;
}
[data-theme="dark"] .nav-tabs .nav-link {
    color: #888 !important;
    border: none !important;
}
[data-theme="dark"] .nav-tabs .nav-link.active {
    background-color: #3498db !important;
    color: #fff !important;
}
[data-theme="dark"] .modal-header, [data-theme="dark"] .modal-footer {
    border-color: #333 !important;
}
[data-theme="dark"] .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

/* Spinner กลางจอ Modal */
#modalLoader {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
    border-radius: 20px;
}
[data-theme="dark"] #modalLoader {
    background: rgba(26, 26, 26, 0.8);
}

/* Fullscreen Textarea */
.fullscreen-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    background: #ffffff;
    z-index: 99999;
    padding: 20px;
    display: flex;
    flex-direction: column;
}
[data-theme="dark"] .fullscreen-overlay {
    background: #1a1a1a;
}
.fullscreen-overlay textarea {
    flex-grow: 1;
    width: 100% !important;
    height: 100% !important;
    font-size: 1.1rem;
    line-height: 1.6;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i data-lucide="settings-2" class="me-2"></i> จัดการสถานะออเดอร์ (Order Status)
                    </h5>
                    <button class="btn btn-primary btn-sm rounded-pill" onclick="addStatus()">
                        <i data-lucide="plus-circle" class="me-1"></i> เพิ่มสถานะใหม่
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="statusTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="50"></th>
                                    <th width="80">ลำดับ</th>
                                    <th>รหัสสถานะ</th>
                                    <th>ชื่อสถานะ (ไทย)</th>
                                    <th>ชื่อสถานะ (EN)</th>
                                    <th>สถานะการใช้งาน</th>
                                    <th>สีที่แสดง</th>
                                    <th class="text-center">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="sortableTbody">
                                <!-- Data will be loaded via AJAX -->
                                <tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับแก้ไขสถานะ (หลายภาษา) -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold" id="modalTitle">แก้ไขสถานะ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusForm">
                <div id="modalLoader" class="d-none">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                </div>
                <input type="hidden" name="orsts_id" id="orsts_id">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">รหัสย่อ (Code)</label>
                            <input type="text" class="form-control" name="orsts_code" id="orsts_code" placeholder="เช่น A, B, C">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">สีแสดงผล (Color)</label>
                            <input type="color" class="form-control form-control-color w-100" name="orsts_color" id="orsts_color" value="#000000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">ลำดับการแสดงผล</label>
                            <input type="number" class="form-control" name="orsts_index" id="orsts_index">
                        </div>
                    </div>

                    <!-- Tabs สำหรับหลายภาษา -->
                    <ul class="nav nav-tabs mb-3 border-bottom-0" id="langTabs" role="tablist">
                        <?php foreach($languages as $index => $lang): ?>
                        <li class="nav-item">
                            <button class="nav-link <?= $index === 0 ? 'active' : '' ?> rounded-pill me-2 px-4" 
                                    id="tab-<?= $lang['lang_code'] ?>" data-bs-toggle="tab" 
                                    data-bs-target="#content-<?= $lang['lang_code'] ?>" type="button">
                                <img src="<?= ROOT_URL ?>/assets/images/flags/<?= $lang['lang_code'] ?>.png" width="16" class="me-1">
                                <?= $lang['lang_name'] ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="tab-content p-3 border rounded-4 bg-light">
                        <?php foreach($languages as $index => $lang): ?>
                        <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" id="content-<?= $lang['lang_code'] ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">ชื่อสถานะ (<?= $lang['lang_name'] ?>)</label>
                                <input type="text" class="form-control" name="name[<?= $lang['lang_code'] ?>]" 
                                       id="name_<?= $lang['lang_code'] ?>" required>
                            </div>
                            <div class="mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-bold mb-0">ข้อความแจ้งเตือน (<?= $lang['lang_name'] ?>)</label>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-info text-decoration-none" 
                                            onclick="toggleFullscreen('msg_<?= $lang['lang_code'] ?>')" title="ขยายเต็มหน้าจอ">
                                        <i data-lucide="maximize" style="width:14px; height:14px;"></i>
                                    </button>
                                </div>
                                <textarea class="form-control" name="msg[<?= $lang['lang_code'] ?>]" 
                                          id="msg_<?= $lang['lang_code'] ?>" rows="4" placeholder="ระบุข้อความแจ้งเตือนลูกค้า..."></textarea>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" onclick="saveStatus()">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
let statusModal;

document.addEventListener('DOMContentLoaded', function() {
    statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    loadStatuses();
});

function loadStatuses() {
    fetch('../api/get_all_statuses.php?all=1')
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                renderTable(data.statuses);
            }
        });
}

function renderTable(statuses) {
    const tbody = document.querySelector('#sortableTbody');
    let html = '';
    statuses.forEach(s => {
        const isChecked = s.orsts_status == 1 ? 'checked' : '';
        const enName = s.orsts_name_en ? s.orsts_name_en : '-';
        html += `
            <tr data-id="${s.orsts_id}">
                <td class="text-center align-middle"><i data-lucide="grip-vertical" class="text-muted" style="cursor: grab;"></i></td>
                <td class="fw-bold idx-label align-middle">${s.orsts_index}</td>
                <td class="align-middle"><span class="badge bg-light text-dark border">${s.orsts_code || '-'}</span></td>
                <td class="align-middle">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle me-2" style="width:12px; height:12px; background-color:${s.orsts_color}"></div>
                        <span class="fw-bold">${s.orsts_detail}</span>
                    </div>
                </td>
                <td class="align-middle"><span class="text-muted small">${enName}</span></td>
                <td class="align-middle">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="toggle_${s.orsts_id}" ${isChecked} onchange="toggleStatus(${s.orsts_id}, this.checked)">
                        <label class="form-check-label small" for="toggle_${s.orsts_id}">${s.orsts_status == 1 ? 'เปิดใช้งาน' : 'ปิด'}</label>
                    </div>
                </td>
                <td class="align-middle"><code>${s.orsts_color}</code></td>
                <td class="text-center align-middle">
                    <button class="btn btn-sm btn-outline-primary rounded-pill" onclick="editStatus(${s.orsts_id})">
                        <i data-lucide="edit-3" style="width:14px; height:14px;"></i> แก้ไข
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
    lucide.createIcons();
    
    // Init Sortable
    if (window.sortableInstance) window.sortableInstance.destroy();
    window.sortableInstance = new Sortable(tbody, {
        handle: '.lucide-grip-vertical',
        animation: 150,
        onEnd: function (evt) {
            updateOrder();
        }
    });
}

function addStatus() {
    document.getElementById('statusForm').reset();
    document.getElementById('orsts_id').value = '';
    document.getElementById('modalTitle').innerText = 'เพิ่มสถานะใหม่';
    document.getElementById('orsts_color').value = '#3498db';
    document.getElementById('orsts_index').value = '10';
    document.getElementById('modalLoader').classList.add('d-none');
    statusModal.show();
}

function editStatus(id) {
    document.getElementById('statusForm').reset();
    document.getElementById('orsts_id').value = id;
    document.getElementById('modalTitle').innerText = 'กำลังโหลดข้อมูล...';
    
    // แสดง Modal และ Loader ทันที
    document.getElementById('modalLoader').classList.remove('d-none');
    statusModal.show();
    
    const form = document.getElementById('statusForm');
    form.style.opacity = '0.5';

    fetch(`../api/order_status_actions.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('modalLoader').classList.add('d-none');
            form.style.opacity = '1';
            form.style.pointerEvents = 'auto';
            
            if(data.success) {
                document.getElementById('orsts_code').value = data.status.orsts_code;
                document.getElementById('orsts_color').value = data.status.orsts_color;
                document.getElementById('orsts_index').value = data.status.orsts_index;
                document.getElementById('modalTitle').innerText = 'แก้ไขสถานะ: ' + data.status.orsts_detail;

                // ใส่ข้อมูลแปลภาษา
                data.translations.forEach(t => {
                    const nameField = document.getElementById(`name_${t.lang_code}`);
                    const msgField = document.getElementById(`msg_${t.lang_code}`);
                    if(nameField) nameField.value = t.orsts_name || '';
                    if(msgField) msgField.value = t.orsts_msg || '';
                });
            }
        });
}

function toggleFullscreen(targetId) {
    const textarea = document.getElementById(targetId);
    const isFullscreen = textarea.parentElement.classList.contains('fullscreen-overlay');

    if (!isFullscreen) {
        // สร้าง Overlay
        const overlay = document.createElement('div');
        overlay.className = 'fullscreen-overlay';
        overlay.id = 'fs_overlay_' + targetId;

        // หัวข้อและปุ่มปิด
        const header = document.createElement('div');
        header.className = 'd-flex justify-content-between align-items-center mb-3';
        header.innerHTML = `
            <h5 class="mb-0 text-primary fw-bold"><i data-lucide="edit-3" class="me-2"></i> กำลังแก้ไขข้อความ (เต็มหน้าจอ)</h5>
            <button type="button" class="btn btn-danger btn-sm rounded-pill px-4" onclick="toggleFullscreen('${targetId}')">
                <i data-lucide="minimize" class="me-1"></i> ย่อกลับ
            </button>
        `;

        // ย้าย Textarea เข้าไปใน Overlay
        const parent = textarea.parentElement;
        textarea.setAttribute('data-parent-id', parent.id || 'parent_' + targetId);
        if(!parent.id) parent.id = 'parent_' + targetId;

        overlay.appendChild(header);
        overlay.appendChild(textarea);
        document.body.appendChild(overlay);
        
        lucide.createIcons();
        textarea.focus();
    } else {
        // ย้ายกลับที่เดิม
        const overlay = document.getElementById('fs_overlay_' + targetId);
        const parentId = textarea.getAttribute('data-parent-id');
        const parent = document.getElementById(parentId);
        
        parent.appendChild(textarea);
        overlay.remove();
        textarea.focus();
    }
}

function saveStatus() {
    const btn = document.querySelector('#statusForm .btn-primary');
    const originalHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';

    const formElement = document.getElementById('statusForm');
    const formData = new FormData(formElement);

    // ส่งแบบ URLSearchParams หรือ FormData โดยตรง
    fetch('../api/order_status_actions.php?action=save', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text()) // รับเป็น text ก่อนเพื่อเช็คว่ามี PHP Error ไหม
    .then(text => {
        try {
            const result = JSON.parse(text);
            btn.disabled = false;
            btn.innerHTML = originalHtml;

            if(result.success) {
                statusModal.hide();
                loadStatuses();
                
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                Toast.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ'
                });
            } else {
                Swal.fire('ผิดพลาด', result.message || 'บันทึกไม่สำเร็จ', 'error');
            }
        } catch (e) {
            console.error('JSON Parse Error:', e, 'Raw Response:', text);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            Swal.fire('Error', 'Server ตอบกลับมาผิดรูปแบบ (ดู Log ใน Console)', 'error');
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อกับ Server ได้: ' + err.message, 'error');
    });
}

function toggleStatus(id, isChecked) {
    const statusVal = isChecked ? 1 : 0;
    fetch(`../api/order_status_actions.php?action=toggle_status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `orsts_id=${id}&orsts_status=${statusVal}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: 'อัพเดตสถานะการใช้งานแล้ว', showConfirmButton: false, timer: 1500
            });
            const label = document.querySelector(`label[for="toggle_${id}"]`);
            if(label) label.innerText = isChecked ? 'เปิดใช้งาน' : 'ปิด';
        } else {
            Swal.fire('Error', data.message, 'error');
            document.getElementById(`toggle_${id}`).checked = !isChecked;
        }
    });
}

function updateOrder() {
    const rows = document.querySelectorAll('#sortableTbody tr');
    let orders = [];
    rows.forEach((row, index) => {
        const id = row.getAttribute('data-id');
        const newIndex = index + 1; // Start at 1
        orders.push({ id: id, index: newIndex });
        row.querySelector('.idx-label').innerText = newIndex;
    });

    fetch('../api/order_status_actions.php?action=update_order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orders: orders })
    })
    .then(res => res.json())
    .then(data => {
        if(!data.success) {
            Swal.fire('Error', 'เรียงลำดับไม่สำเร็จ', 'error');
            loadStatuses(); 
        } else {
             Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: 'อัพเดตลำดับเรียบร้อย', showConfirmButton: false, timer: 1000
            });
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';

