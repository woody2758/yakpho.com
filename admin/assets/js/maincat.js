// =========================================
// Main Category Management JavaScript
// File: admin/assets/js/maincat.js
// =========================================

let mainCategoryModal;

// Initialize Bootstrap Modal on load
document.addEventListener('DOMContentLoaded', function () {
    mainCategoryModal = new bootstrap.Modal(document.getElementById('maincatModal'));
});

// =========================================
// Load Main Categories Table
// =========================================
async function loadMainCategoriesTable(page = 1, search = '') {
    try {
        const params = new URLSearchParams({
            page: page,
            search: search
        });

        const response = await fetch(`${ADMIN_URL}/api/get_maincat_table.php?${params}`);
        const data = await response.json();

        if (data.success) {
            document.getElementById('tableContainer').innerHTML = data.table;
            document.getElementById('paginationContainer').innerHTML = data.pagination;

            // Reinitialize Lucide icons
            if (window.lucide) {
                lucide.createIcons();
            }

            // Initialize drag-and-drop sorting
            initializeSorting();
        } else {
            showAlert('error', data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
}

// =========================================
// Add New Main Category
// =========================================
function addMainCategory() {
    // Reset form
    document.getElementById('maincatForm').reset();
    document.getElementById('maincatId').value = '';
    document.getElementById('maincatStatus').checked = true;
    document.getElementById('statusBadge').textContent = 'เปิดใช้งาน';
    document.getElementById('statusBadge').className = 'badge bg-success';
    document.getElementById('modalTitle').textContent = 'เพิ่มหมวดหมู่หลัก';

    // Show modal
    mainCategoryModal.show();
}

// =========================================
// Edit Main Category
// =========================================
async function editMainCategory(id) {
    try {
        showLoading('กำลังโหลดข้อมูล...');

        const response = await fetch(`${ADMIN_URL}/api/get_maincat.php?id=${id}`);
        const data = await response.json();

        hideLoading();

        if (data.success) {
            const maincat = data.maincat;

            // Fill form
            document.getElementById('maincatId').value = maincat.maincat_id;
            document.getElementById('maincat_slug').value = maincat.maincat_slug || '';
            document.getElementById('maincat_icon').value = maincat.maincat_icon || '';
            document.getElementById('maincatStatus').checked = maincat.maincat_status == 1;

            // Update status badge
            const badge = document.getElementById('statusBadge');
            if (maincat.maincat_status == 1) {
                badge.textContent = 'เปิดใช้งาน';
                badge.className = 'badge bg-success';
            } else {
                badge.textContent = 'ปิดใช้งาน';
                badge.className = 'badge bg-secondary';
            }

            // Fill translations
            if (maincat.translations) {
                maincat.translations.forEach(trans => {
                    const nameInput = document.querySelector(`[name="maincat_name_${trans.lang_code}"]`);
                    const detailInput = document.querySelector(`[name="maincat_detail_${trans.lang_code}"]`);
                    if (nameInput) nameInput.value = trans.maincat_name || '';
                    if (detailInput) detailInput.value = trans.maincat_detail || '';
                });
            }

            document.getElementById('modalTitle').textContent = 'แก้ไขหมวดหมู่หลัก';
            mainCategoryModal.show();
        } else {
            showAlert('error', data.message || 'ไม่พบข้อมูล');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showAlert('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
    }
}

// =========================================
// Save Main Category
// =========================================
async function saveMainCategory() {
    const form = document.getElementById('maincatForm');

    // Validate
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    try {
        showLoading('กำลังบันทึก...');

        const formData = new FormData(form);

        const response = await fetch(`${ADMIN_URL}/api/save_maincat.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        hideLoading();

        if (data.success) {
            showAlert('success', data.message || 'บันทึกสำเร็จ');
            mainCategoryModal.hide();

            // Reload table
            const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
            const currentSearch = document.getElementById('searchInput').value;
            loadMainCategoriesTable(currentPage, currentSearch);
        } else {
            showAlert('error', data.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showAlert('error', 'เกิดข้อผิดพลาดในการบันทึก');
    }
}

// =========================================
// Toggle Status
// =========================================
async function toggleMainCategoryStatus(id, currentStatus) {
    try {
        const formData = new FormData();
        formData.append('maincat_id', id);
        formData.append('maincat_status', currentStatus == 1 ? 0 : 1);
        formData.append('quick_update', '1');

        const response = await fetch(`${ADMIN_URL}/api/save_maincat.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Reload table
            const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
            const currentSearch = document.getElementById('searchInput').value;
            loadMainCategoriesTable(currentPage, currentSearch);
        } else {
            showAlert('error', data.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'เกิดข้อผิดพลาด');
    }
}

// =========================================
// Delete Main Category
// =========================================
async function deleteMainCategory(id, name) {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        html: `คุณต้องการลบหมวดหมู่ <strong>${name}</strong> หรือไม่?<br><br>
               <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> 
               หมวดหมู่ย่อยภายใต้หมวดนี้จะยังคงอยู่</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก'
    });

    if (!result.isConfirmed) return;

    try {
        showLoading('กำลังลบ...');

        const response = await fetch(`${ADMIN_URL}/api/delete_maincat.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `maincat_id=${id}`
        });

        const data = await response.json();

        hideLoading();

        if (data.success) {
            showAlert('success', 'ลบสำเร็จ');

            // Reload table
            const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
            const currentSearch = document.getElementById('searchInput').value;
            loadMainCategoriesTable(currentPage, currentSearch);
        } else {
            showAlert('error', data.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showAlert('error', 'เกิดข้อผิดพลาด');
    }
}

// =========================================
// Initialize Drag-and-Drop Sorting
// =========================================
function initializeSorting() {
    const tbody = document.querySelector('tbody');
    if (!tbody) return;

    let draggedRow = null;

    tbody.querySelectorAll('.sortable-row').forEach(row => {
        row.setAttribute('draggable', 'true');

        row.addEventListener('dragstart', function (e) {
            draggedRow = this;
            this.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
        });

        row.addEventListener('dragend', function () {
            this.style.opacity = '1';
        });

        row.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            if (this !== draggedRow) {
                const rect = this.getBoundingClientRect();
                const midpoint = rect.top + rect.height / 2;
                if (e.clientY < midpoint) {
                    this.parentNode.insertBefore(draggedRow, this);
                } else {
                    this.parentNode.insertBefore(draggedRow, this.nextSibling);
                }
            }
        });
    });

    // Save order on drop
    tbody.addEventListener('drop', async function (e) {
        e.preventDefault();
        await saveSortOrder();
    });
}

// =========================================
// Save Sort Order
// =========================================
async function saveSortOrder() {
    const rows = document.querySelectorAll('.sortable-row');
    const order = Array.from(rows).map(row => row.dataset.id);

    try {
        const response = await fetch(`${ADMIN_URL}/api/save_maincat_order.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order: order })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', 'บันทึกลำดับเรียบร้อย', 1500);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// =========================================
// Helper Functions
// =========================================
function showLoading(message = 'กำลังโหลด...') {
    Swal.fire({
        title: message,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function hideLoading() {
    Swal.close();
}

function showAlert(icon, message, timer = 2000) {
    Swal.fire({
        icon: icon,
        title: message,
        timer: timer,
        showConfirmButton: timer > 2000,
        toast: timer <= 2000,
        position: timer <= 2000 ? 'top-end' : 'center'
    });
}
