document.addEventListener('DOMContentLoaded', function() {
    loadAttributes();
    
    // Handle Form Submit
    document.getElementById('attributeForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const saveBtn = document.getElementById('saveBtn');
        const spinner = saveBtn.querySelector('.spinner-border');
        const btnText = saveBtn.querySelector('.btn-text');
        
        // Show Loading State
        saveBtn.disabled = true;
        spinner.classList.remove('d-none');
        btnText.innerText = 'กำลังบันทึก...';
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('../api/save_master_attribute.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: result.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                bootstrap.Modal.getInstance(document.getElementById('attributeModal')).hide();
                loadAttributes();
            } else {
                Swal.fire('ข้อผิดพลาด', result.message, 'error');
            }
        } catch (error) {
            console.error('Error saving attribute:', error);
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
        } finally {
            // Restore Button State
            saveBtn.disabled = false;
            spinner.classList.add('d-none');
            btnText.innerText = 'บันทึกข้อมูลทั้งหมด';
        }
    });
});

async function loadAttributes() {
    try {
        const response = await fetch('../api/get_master_attributes.php');
        const data = await response.json();
        
        if (data.success) {
            renderTable('scentTable', data.scents, 'scent');
            renderTable('formulaTable', data.formulas, 'formula');
        }
    } catch (error) {
        console.error('Error loading attributes:', error);
    }
}

function renderTable(tableId, items, type) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${type === 'scent' ? 5 : 2}" class="text-center py-4 text-muted">ไม่พบข้อมูล</td></tr>`;
        return;
    }
    
    let html = '';
    items.forEach(item => {
        if (type === 'scent') {
            html += `
                <tr data-id="${item.attribute_id}">
                    <td class="ps-4 cursor-move text-muted">
                        <i data-lucide="grip-vertical" style="width: 16px; height: 16px;"></i>
                    </td>
                    <td><code>${item.attribute_id}</code></td>
                    <td class="fw-bold">${item.name_th || '-'}</td>
                    <td class="text-muted">${item.name_en || '-'}</td>
                    <td><span class="badge bg-light text-dark fw-normal">${item.attribute_code || '-'}</span></td>
                    <td class="text-center pe-4">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editAttribute(${JSON.stringify(item).replace(/"/g, '&quot;')}, 'scent')">
                                <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteAttribute(${item.attribute_id})">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        } else {
            html += `
                <tr data-id="${item.attribute_id}">
                    <td class="ps-4 cursor-move text-muted">
                        <i data-lucide="grip-vertical" style="width: 16px; height: 16px;"></i>
                    </td>
                    <td>
                        <div class="fw-bold">${item.name_th || '-'}</div>
                        <small class="text-muted">${item.name_en || '-'}</small>
                    </td>
                    <td class="text-center pe-4">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editAttribute(${JSON.stringify(item).replace(/"/g, '&quot;')}, 'formula')">
                                <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteAttribute(${item.attribute_id})">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
    });
    
    tbody.innerHTML = html;
    lucide.createIcons();
    
    // Initialize Sortable
    new Sortable(tbody, {
        animation: 150,
        handle: '.cursor-move',
        onEnd: function() {
            saveOrder(tableId);
        }
    });
}

async function saveOrder(tableId) {
    const tbody = document.querySelector(`#${tableId} tbody`);
    const order = Array.from(tbody.querySelectorAll('tr')).map(tr => tr.dataset.id);
    
    const formData = new FormData();
    order.forEach((id, index) => {
        formData.append(`order[${index}]`, id);
    });
    
    try {
        const response = await fetch('../api/save_attribute_order.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            // Optional: Show a small toast instead of a full Swal
            console.log('Order updated');
        }
    } catch (error) {
        console.error('Error saving order:', error);
    }
}

function openModal(groupCode) {
    const modal = new bootstrap.Modal(document.getElementById('attributeModal'));
    document.getElementById('attributeForm').reset();
    document.getElementById('attrId').value = '';
    document.getElementById('groupCode').value = groupCode;
    document.getElementById('attrCode').value = '';
    
    // Reset all language fields
    document.querySelectorAll('[name^="names["]').forEach(input => input.value = '');
    
    // Switch to first tab
    const firstTab = document.querySelector('#langTabs .nav-link');
    if (firstTab) bootstrap.Tab.getInstance(firstTab)?.show() || new bootstrap.Tab(firstTab).show();

    document.getElementById('modalTitle').innerText = groupCode === 'scent' ? 'เพิ่มกลิ่นใหม่' : 'เพิ่มสูตรใหม่';
    modal.show();
}

function editAttribute(item, groupCode) {
    const modal = new bootstrap.Modal(document.getElementById('attributeModal'));
    document.getElementById('attrId').value = item.attribute_id;
    document.getElementById('groupCode').value = groupCode;
    document.getElementById('attrCode').value = item.attribute_code || '';
    
    // Populate translations
    if (item.translations) {
        Object.keys(item.translations).forEach(langCode => {
            const input = document.getElementById(`name-${langCode}`);
            if (input) input.value = item.translations[langCode] || '';
        });
    }
    
    // Switch to first tab
    const firstTab = document.querySelector('#langTabs .nav-link');
    if (firstTab) bootstrap.Tab.getInstance(firstTab)?.show() || new bootstrap.Tab(firstTab).show();

    document.getElementById('modalTitle').innerText = groupCode === 'scent' ? 'แก้ไขข้อมูลกลิ่น' : 'แก้ไขข้อมูลสูตร';
    modal.show();
}

async function deleteAttribute(id) {
    const result = await Swal.fire({
        title: 'คุณมั่นใจหรือไม่?',
        text: "การลบข้อมูลนี้อาจส่งผลกระทบต่อสินค้าที่ใช้งานอยู่",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ยืนยันการลบ',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
    });
    
    if (result.isConfirmed) {
        try {
            const response = await fetch(`../api/delete_master_attribute.php?id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'ลบข้อมูลสำเร็จ',
                    timer: 1500,
                    showConfirmButton: false
                });
                loadAttributes();
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        } catch (error) {
            console.error('Error deleting attribute:', error);
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถลบข้อมูลได้', 'error');
        }
    }
}
