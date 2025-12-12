/**
 * FAQs Categories Management JavaScript
 * Handles CRUD operations for FAQ categories with 8 languages
 */

let categoryModal;
let currentPage = 1;
let currentSearch = '';

document.addEventListener('DOMContentLoaded', function () {
    categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    loadCategoriesTable(1);

    // Search with debounce
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function () {
        const value = this.value.trim();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = value;
            loadCategoriesTable(1);
        }, 300);
    });
});

/**
 * Load categories table
 */
async function loadCategoriesTable(page = 1) {
    currentPage = page;

    try {
        const url = `../api/get_faqscat_table.php?page=${page}&search=${encodeURIComponent(currentSearch)}`;
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        renderTable(result.data);
        renderPagination(result.pagination);

    } catch (error) {
        console.error('Error loading categories:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Render categories table
 */
function renderTable(categories) {
    const container = document.getElementById('tableContainer');

    if (categories.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i data-lucide="inbox" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                <p class="text-muted">ไม่พบข้อมูลหมวด FAQ</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }

    let html = `
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width: 50px">#</th>
                    <th>ชื่อหมวดหมู่ (TH)</th>
                    <th>Name (EN)</th>
                    <th style="width: 100px" class="text-center">สถานะ</th>
                    <th style="width: 150px" class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody id="sortableCategories">
    `;

    categories.forEach((cat, index) => {
        const statusBadge = cat.faqscat_status == 1
            ? '<span class="badge bg-success">เปิดใช้งาน</span>'
            : '<span class="badge bg-secondary">ปิดใช้งาน</span>';

        html += `
            <tr data-id="${cat.faqscat_id}">
                <td>
                    <i data-lucide="grip-vertical" class="text-muted sortable-handle" style="cursor:move;width:20px;height:20px;"></i>
                </td>
                <td>${cat.name_th || '-'}</td>
                <td>${cat.name_en || '-'}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">
                    <button onclick="editCategory(${cat.faqscat_id})" class="btn btn-sm btn-primary me-1">
                        <i data-lucide="edit" style="width:14px;height:14px;"></i>
                    </button>
                    <button onclick="deleteCategory(${cat.faqscat_id})" class="btn btn-sm btn-danger">
                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
    `;

    container.innerHTML = html;
    lucide.createIcons();

    // Initialize sortable
    initSortable();
}

/**
 * Render pagination
 */
function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');

    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '<nav><ul class="pagination pagination-sm mb-0">';

    html += `
        <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadCategoriesTable(${pagination.current_page - 1}); return false;">
                Previous
            </a>
        </li>
    `;

    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || Math.abs(i - pagination.current_page) <= 2) {
            html += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadCategoriesTable(${i}); return false;">${i}</a>
                </li>
            `;
        } else if (Math.abs(i - pagination.current_page) === 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    html += `
        <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadCategoriesTable(${pagination.current_page + 1}); return false;">
                Next
            </a>
        </li>
    `;

    html += '</ul></nav>';
    container.innerHTML = html;
}

/**
 * Initialize drag-and-drop sorting
 */
function initSortable() {
    const tbody = document.getElementById('sortableCategories');
    if (!tbody) return;

    Sortable.create(tbody, {
        handle: '.sortable-handle',
        animation: 150,
        onEnd: async function (evt) {
            const rows = tbody.querySelectorAll('tr');
            const order = Array.from(rows).map(row => row.dataset.id);

            try {
                const response = await fetch('../api/save_faqscat_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order })
                });

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message);
                }

                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกลำดับเรียบร้อย',
                    timer: 1500,
                    showConfirmButton: false
                });

            } catch (error) {
                console.error('Error saving order:', error);
                Swal.fire('Error', error.message, 'error');
                loadCategoriesTable(currentPage);
            }
        }
    });
}

/**
 * Add new category
 */
function addCategory() {
    document.getElementById('modalTitle').textContent = 'เพิ่มหมวด FAQ';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryStatus').checked = true;

    const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    languages.forEach(lang => {
        document.querySelector(`[name="faqscat_name_${lang}"]`).value = '';
        document.querySelector(`[name="faqscat_detail_${lang}"]`).value = '';
    });

    const firstTab = document.querySelector('[data-bs-target="#lang-th"]');
    if (firstTab) {
        new bootstrap.Tab(firstTab).show();
    }

    categoryModal.show();
}

/**
 * Edit category
 */
async function editCategory(id) {
    Swal.fire({
        title: 'Loading...',
        html: 'กำลังโหลดข้อมูล',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`../api/get_faqscat.php?id=${id}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        const data = result.data;

        Swal.close();

        document.getElementById('modalTitle').textContent = 'แก้ไขหมวด FAQ';
        document.getElementById('categoryId').value = data.faqscat_id;
        document.getElementById('categoryStatus').checked = data.faqscat_status == 1;

        const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
        languages.forEach(lang => {
            const trans = data.translations[lang] || {};
            document.querySelector(`[name="faqscat_name_${lang}"]`).value = trans.faqscat_name || '';
            document.querySelector(`[name="faqscat_detail_${lang}"]`).value = trans.faqscat_detail || '';
        });

        categoryModal.show();

    } catch (error) {
        console.error('Error loading category:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Save category
 */
async function saveCategory() {
    const faqscat_id = document.getElementById('categoryId').value;
    const faqscat_status = document.getElementById('categoryStatus').checked ? 1 : 0;

    const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    const translations = {};

    languages.forEach(lang => {
        translations[lang] = {
            faqscat_name: document.querySelector(`[name="faqscat_name_${lang}"]`).value.trim(),
            faqscat_detail: document.querySelector(`[name="faqscat_detail_${lang}"]`).value.trim()
        };
    });

    if (!translations.th.faqscat_name) {
        Swal.fire('แจ้งเตือน', 'กรุณากรอกชื่อหมวดหมู่ภาษาไทย', 'warning');
        return;
    }

    // Show loading
    Swal.fire({
        title: 'กำลังบันทึก...',
        html: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('../api/save_faqscat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                faqscat_id: faqscat_id ? parseInt(faqscat_id) : 0,
                faqscat_status,
                translations
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'บันทึกเรียบร้อย',
            timer: 1500,
            showConfirmButton: false
        });

        // Wait for modal to close before reloading
        const modalElement = document.getElementById('categoryModal');
        modalElement.addEventListener('hidden.bs.modal', function () {
            loadCategoriesTable(currentPage);
        }, { once: true });

        categoryModal.hide();

    } catch (error) {
        console.error('Error saving category:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Delete category
 */
async function deleteCategory(id) {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบหมวด FAQ นี้หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('../api/delete_faqscat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ faqscat_id: id })
        });

        const apiResult = await response.json();

        if (!apiResult.success) {
            throw new Error(apiResult.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'ลบเรียบร้อย',
            timer: 1500,
            showConfirmButton: false
        });

        loadCategoriesTable(currentPage);

    } catch (error) {
        console.error('Error deleting category:', error);
        Swal.fire('Error', error.message, 'error');
    }
}
