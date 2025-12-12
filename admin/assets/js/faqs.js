/**
 * FAQs Management JavaScript
 * Handles CRUD operations for FAQs with 8 languages and category selection
 */

let faqModal;
let currentPage = 1;
let currentSearch = '';
let currentCategory = 0;
let categories = [];

document.addEventListener('DOMContentLoaded', function () {
    faqModal = new bootstrap.Modal(document.getElementById('faqModal'));
    initSummernote();
    loadCategories();
    loadFAQsTable(1);

    // Search with debounce
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function () {
        const value = this.value.trim();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = value;
            loadFAQsTable(1);
        }, 300);
    });

    // Category filter
    document.getElementById('categoryFilter').addEventListener('change', function () {
        currentCategory = parseInt(this.value) || 0;
        loadFAQsTable(1);
    });
});

/**
 * Initialize Summernote for all language answer fields
 */
function initSummernote() {
    const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    languages.forEach(lang => {
        $(`[name="faqs_detail_${lang}"]`).summernote({
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['codeview', 'help']]
            ]
        });
    });
}

/**
 * Load categories for filter and dropdown
 */
async function loadCategories() {
    try {
        const response = await fetch('../api/get_faqs_categories.php');
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        categories = result.data;

        // Calculate total count
        const totalCount = categories.reduce((sum, cat) => sum + parseInt(cat.faqs_count || 0), 0);

        // Populate filter dropdown
        const filterSelect = document.getElementById('categoryFilter');
        filterSelect.innerHTML = `<option value="0">ทั้งหมด (${totalCount})</option>`;
        categories.forEach(cat => {
            const count = cat.faqs_count || 0;
            filterSelect.innerHTML += `<option value="${cat.faqscat_id}">${cat.faqscat_name || 'ไม่มีชื่อ'} (${count})</option>`;
        });

    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

/**
 * Load FAQs table
 */
async function loadFAQsTable(page = 1) {
    currentPage = page;

    try {
        const url = `../api/get_faqs_table.php?page=${page}&search=${encodeURIComponent(currentSearch)}&category=${currentCategory}`;
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        renderTable(result.data);
        renderPagination(result.pagination);

    } catch (error) {
        console.error('Error loading FAQs:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Render FAQs table
 */
function renderTable(faqs) {
    const container = document.getElementById('tableContainer');

    if (faqs.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i data-lucide="inbox" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                <p class="text-muted">ไม่พบข้อมูล FAQ</p>
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
                    <th>คำถาม (TH)</th>
                    <th style="width: 150px">หมวดหมู่</th>
                    <th style="width: 80px" class="text-center">Views</th>
                    <th style="width: 100px" class="text-center">สถานะ</th>
                    <th style="width: 150px" class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody id="sortableFAQs">
    `;

    faqs.forEach((faq) => {
        const statusBadge = faq.faqs_status == 1
            ? '<span class="badge bg-success">เปิดใช้งาน</span>'
            : '<span class="badge bg-secondary">ปิดใช้งาน</span>';

        html += `
            <tr data-id="${faq.faqs_id}">
                <td>
                    <i data-lucide="grip-vertical" class="text-muted sortable-handle" style="cursor:move;width:20px;height:20px;"></i>
                </td>
                <td>${faq.name_th || '-'}</td>
                <td><span class="badge bg-info">${faq.category_name || '-'}</span></td>
                <td class="text-center">${faq.faqs_view || 0}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">
                    <button onclick="editFAQ(${faq.faqs_id})" class="btn btn-sm btn-primary me-1">
                        <i data-lucide="edit" style="width:14px;height:14px;"></i>
                    </button>
                    <button onclick="deleteFAQ(${faq.faqs_id})" class="btn btn-sm btn-danger">
                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += `</tbody></table>`;

    container.innerHTML = html;
    lucide.createIcons();
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
            <a class="page-link" href="#" onclick="loadFAQsTable(${pagination.current_page - 1}); return false;">Previous</a>
        </li>
    `;

    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || Math.abs(i - pagination.current_page) <= 2) {
            html += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadFAQsTable(${i}); return false;">${i}</a>
                </li>
            `;
        } else if (Math.abs(i - pagination.current_page) === 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    html += `
        <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadFAQsTable(${pagination.current_page + 1}); return false;">Next</a>
        </li>
    `;

    html += '</ul></nav>';
    container.innerHTML = html;
}

/**
 * Initialize drag-and-drop sorting
 */
function initSortable() {
    const tbody = document.getElementById('sortableFAQs');
    if (!tbody) return;

    Sortable.create(tbody, {
        handle: '.sortable-handle',
        animation: 150,
        onEnd: async function (evt) {
            const rows = tbody.querySelectorAll('tr');
            const order = Array.from(rows).map(row => row.dataset.id);

            try {
                const response = await fetch('../api/save_faqs_order.php', {
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
                loadFAQsTable(currentPage);
            }
        }
    });
}

/**
 * Add new FAQ
 */
function addFAQ() {
    document.getElementById('modalTitle').textContent = 'เพิ่ม FAQ';
    document.getElementById('faqForm').reset();
    document.getElementById('faqId').value = '';
    document.getElementById('faqStatus').checked = true;

    // Populate category dropdown
    const categorySelect = document.getElementById('faqCategory');
    categorySelect.innerHTML = '<option value="">เลือกหมวดหมู่...</option>';
    categories.forEach(cat => {
        categorySelect.innerHTML += `<option value="${cat.faqscat_id}">${cat.faqscat_name || 'ไม่มีชื่อ'}</option>`;
    });

    // Clear all language fields
    const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    languages.forEach(lang => {
        document.querySelector(`[name="faqs_name_${lang}"]`).value = '';
        $(`[name="faqs_detail_${lang}"]`).summernote('code', '');
    });

    // Switch to Thai tab
    const firstTab = document.querySelector('[data-bs-target="#lang-th"]');
    if (firstTab) {
        new bootstrap.Tab(firstTab).show();
    }

    faqModal.show();
}

/**
 * Edit FAQ
 */
async function editFAQ(id) {
    Swal.fire({
        title: 'Loading...',
        html: 'กำลังโหลดข้อมูล',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`../api/get_faqs.php?id=${id}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        const data = result.data;

        Swal.close();

        document.getElementById('modalTitle').textContent = 'แก้ไข FAQ';
        document.getElementById('faqId').value = data.faqs_id;
        document.getElementById('faqStatus').checked = data.faqs_status == 1;

        // Populate category dropdown
        const categorySelect = document.getElementById('faqCategory');
        categorySelect.innerHTML = '<option value="">เลือกหมวดหมู่...</option>';
        categories.forEach(cat => {
            const selected = cat.faqscat_id == data.faqscat_id ? 'selected' : '';
            categorySelect.innerHTML += `<option value="${cat.faqscat_id}" ${selected}>${cat.faqscat_name || 'ไม่มีชื่อ'}</option>`;
        });

        // Fill translations
        const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
        languages.forEach(lang => {
            const trans = data.translations[lang] || {};
            document.querySelector(`[name="faqs_name_${lang}"]`).value = trans.faqs_name || '';
            $(`[name="faqs_detail_${lang}"]`).summernote('code', trans.faqs_detail || '');
        });

        faqModal.show();

    } catch (error) {
        console.error('Error loading FAQ:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Save FAQ
 */
async function saveFAQ() {
    const faqs_id = document.getElementById('faqId').value;
    const faqscat_id = document.getElementById('faqCategory').value;
    const faqs_status = document.getElementById('faqStatus').checked ? 1 : 0;

    // Collect translations
    const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    const translations = {};

    languages.forEach(lang => {
        translations[lang] = {
            faqs_name: document.querySelector(`[name="faqs_name_${lang}"]`).value.trim(),
            faqs_detail: $(`[name="faqs_detail_${lang}"]`).summernote('code')
        };
    });

    // Validate
    if (!faqscat_id) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกหมวดหมู่', 'warning');
        return;
    }

    if (!translations.th.faqs_name) {
        Swal.fire('แจ้งเตือน', 'กรุณากรอกคำถามภาษาไทย', 'warning');
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
        const response = await fetch('../api/save_faqs.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                faqs_id: faqs_id ? parseInt(faqs_id) : 0,
                faqscat_id: parseInt(faqscat_id),
                faqs_status,
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
        const modalElement = document.getElementById('faqModal');
        modalElement.addEventListener('hidden.bs.modal', function () {
            loadFAQsTable(currentPage);
        }, { once: true });

        faqModal.hide();

    } catch (error) {
        console.error('Error saving FAQ:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Delete FAQ
 */
async function deleteFAQ(id) {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบ FAQ นี้หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('../api/delete_faqs.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ faqs_id: id })
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

        loadFAQsTable(currentPage);

    } catch (error) {
        console.error('Error deleting FAQ:', error);
        Swal.fire('Error', error.message, 'error');
    }
}
