/**
 * Blog Categories Management JavaScript
 * Handles CRUD operations for blog categories with 8 languages
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
        const url = `../api/get_blogcat_table.php?page=${page}&search=${encodeURIComponent(currentSearch)}`;
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
                <p class="text-muted">ไม่พบข้อมูลหมวดบล็อก</p>
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
        const statusBadge = cat.blogcat_status == 1
            ? '<span class="badge bg-success">เปิดใช้งาน</span>'
            : '<span class="badge bg-secondary">ปิดใช้งาน</span>';

        html += `
            <tr data-id="${cat.blogcat_id}">
                <td>
                    <i data-lucide="grip-vertical" class="text-muted sortable-handle" style="cursor:move;width:20px;height:20px;"></i>
                </td>
                <td>${cat.name_th || '-'}</td>
                <td>${cat.name_en || '-'}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">
                    <button onclick="editCategory(${cat.blogcat_id})" class="btn btn-sm btn-primary me-1">
                        <i data-lucide="edit" style="width:14px;height:14px;"></i>
                    </button>
                    <button onclick="deleteCategory(${cat.blogcat_id})" class="btn btn-sm btn-danger">
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

    // Previous button
    html += `
        <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadCategoriesTable(${pagination.current_page - 1}); return false;">
                Previous
            </a>
        </li>
    `;

    // Page numbers
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

    // Next button
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
            // Get new order
            const rows = tbody.querySelectorAll('tr');
            const order = Array.from(rows).map(row => row.dataset.id);

            try {
                const response = await fetch('../api/save_blogcat_order.php', {
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
                loadCategoriesTable(currentPage); // Reload
            }
        }
    });
}

/**
 * Add new category
 */
function addCategory() {
    document.getElementById('modalTitle').textContent = 'เพิ่มหมวดบล็อก';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryStatus').checked = true;

    // Clear all language fields
    const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    languages.forEach(lang => {
        document.querySelector(`[name="blogcat_name_${lang}"]`).value = '';
        document.querySelector(`[name="blogcat_detail_${lang}"]`).value = '';
    });

    // Switch to Thai tab
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
    // Show loading
    Swal.fire({
        title: 'Loading...',
        html: 'กำลังโหลดข้อมูล',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`../api/get_blogcat.php?id=${id}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        const data = result.data;

        // Close loading
        Swal.close();

        document.getElementById('modalTitle').textContent = 'แก้ไขหมวดบล็อก';
        document.getElementById('categoryId').value = data.blogcat_id;
        document.getElementById('categoryStatus').checked = data.blogcat_status == 1;

        // Fill translations
        const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
        languages.forEach(lang => {
            const trans = data.translations[lang] || {};
            document.querySelector(`[name="blogcat_name_${lang}"]`).value = trans.blogcat_name || '';
            document.querySelector(`[name="blogcat_detail_${lang}"]`).value = trans.blogcat_detail || '';
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
    const blogcat_id = document.getElementById('categoryId').value;
    const blogcat_status = document.getElementById('categoryStatus').checked ? 1 : 0;

    // Collect translations
    const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    const translations = {};

    languages.forEach(lang => {
        translations[lang] = {
            blogcat_name: document.querySelector(`[name="blogcat_name_${lang}"]`).value.trim(),
            blogcat_detail: document.querySelector(`[name="blogcat_detail_${lang}"]`).value.trim()
        };
    });

    // Validate: Thai name is required
    if (!translations.th.blogcat_name) {
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
        const response = await fetch('../api/save_blogcat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                blogcat_id: blogcat_id ? parseInt(blogcat_id) : 0,
                blogcat_status,
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
        text: 'คุณต้องการลบหมวดบล็อกนี้หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ย้อนกลับ'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('../api/delete_blogcat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ blogcat_id: id })
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
