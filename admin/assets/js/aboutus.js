/**
 * About Us Management JavaScript
 * Handles AJAX operations, form management, and Summernote
 */

let aboutusModal;
let sortable;

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', function () {
    aboutusModal = new bootstrap.Modal(document.getElementById('aboutusModal'));
});

/**
 * Load about us table
 */
async function loadAboutUsTable() {
    try {
        const response = await fetch('../api/get_aboutus_table.php');
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        const tbody = document.getElementById('aboutusTableBody');

        if (result.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i data-lucide="inbox" style="width:48px;height:48px;margin-bottom:10px;"></i>
                        <div>No about us pages found</div>
                        <button class="btn btn-sm btn-primary mt-3" onclick="addAboutUs()">
                            <i data-lucide="plus"></i> Add First Page
                        </button>
                    </td>
                </tr>
            `;
            lucide.createIcons();
            return;
        }

        tbody.innerHTML = result.data.map((item, index) => `
            <tr data-id="${item.aboutus_id}">
                <td class="text-muted">${index + 1}</td>
                <td>
                    <i data-lucide="grip-vertical" style="width:16px;height:16px;cursor:move;" class="text-muted me-2"></i>
                    <strong>${escapeHtml(item.aboutus_heading || '')}</strong>
                </td>
                <td>${escapeHtml(item.title_th || '-')}</td>
                <td>${escapeHtml(item.title_en || '-')}</td>
                <td class="text-center">
                    ${item.aboutus_status ?
                '<span class="badge bg-success">Active</span>' :
                '<span class="badge bg-secondary">Inactive</span>'}
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary" onclick="editAboutUs(${item.aboutus_id})" title="Edit">
                        <i data-lucide="edit-2" style="width:14px;height:14px;"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteAboutUs(${item.aboutus_id})" title="Delete">
                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        lucide.createIcons();
        initSortable();

    } catch (error) {
        console.error('Error loading table:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Initialize sortable drag-drop
 */
function initSortable() {
    const tbody = document.getElementById('aboutusTableBody');

    if (sortable) {
        sortable.destroy();
    }

    sortable = new Sortable(tbody, {
        animation: 150,
        handle: '[data-lucide="grip-vertical"]',
        onEnd: async function (evt) {
            const rows = tbody.querySelectorAll('tr[data-id]');
            const order = Array.from(rows).map(row => row.getAttribute('data-id'));

            try {
                const response = await fetch('../api/save_aboutus_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order })
                });

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message);
                }

                // Update row numbers
                rows.forEach((row, index) => {
                    row.querySelector('td:first-child').textContent = index + 1;
                });

            } catch (error) {
                console.error('Error saving order:', error);
                Swal.fire('Error', 'Failed to save order', 'error');
                loadAboutUsTable(); // Reload to restore order
            }
        }
    });
}

/**
 * Add new about us
 */
function addAboutUs() {
    document.getElementById('modalTitle').textContent = 'Add About Us';
    document.getElementById('aboutusForm').reset();
    document.getElementById('aboutusId').value = '';
    document.getElementById('aboutusHeading').value = '';
    document.getElementById('aboutusStatus').checked = true;

    // Clear all Summernote editors
    document.querySelectorAll('.summernote').forEach(textarea => {
        if ($(textarea).summernote) {
            $(textarea).summernote('code', '');
        }
    });

    // Initialize Summernote for all textareas
    initSummernote();

    // Show modal
    aboutusModal.show();
}

/**
 * Edit about us
 */
async function editAboutUs(id) {
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
        const response = await fetch(`../api/get_aboutus.php?id=${id}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        const data = result.data;

        // Close loading
        Swal.close();

        document.getElementById('modalTitle').textContent = 'Edit About Us';
        document.getElementById('aboutusId').value = data.aboutus_id;
        document.getElementById('aboutusHeading').value = data.aboutus_heading || '';
        document.getElementById('aboutusStatus').checked = data.aboutus_status === 1;

        // Initialize Summernote first
        initSummernote();

        // Load translations
        const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
        languages.forEach(lang => {
            const trans = data.translations[lang] || {};

            document.getElementById(`title_${lang}`).value = trans.title || '';
            document.getElementById(`subtitle_${lang}`).value = trans.subtitle || '';

            // Set Summernote content
            const textarea = document.getElementById(`content_${lang}`);
            if ($(textarea).summernote) {
                $(textarea).summernote('code', trans.content || '');
            }
        });

        aboutusModal.show();

    } catch (error) {
        console.error('Error loading about us:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Save about us
 */
async function saveAboutUs() {
    const form = document.getElementById('aboutusForm');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Collect data
    const aboutus_id = document.getElementById('aboutusId').value;
    const aboutus_heading = document.getElementById('aboutusHeading').value.trim();
    const aboutus_status = document.getElementById('aboutusStatus').checked ? 1 : 0;

    const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    const translations = {};

    languages.forEach(lang => {
        const textarea = document.getElementById(`content_${lang}`);
        const content = $(textarea).summernote ? $(textarea).summernote('code') : textarea.value;

        translations[lang] = {
            title: document.getElementById(`title_${lang}`).value.trim(),
            subtitle: document.getElementById(`subtitle_${lang}`).value.trim(),
            content: content
        };
    });

    // Show loading
    Swal.fire({
        title: 'Saving...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch('../api/save_aboutus.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                aboutus_id: aboutus_id || 0,
                aboutus_heading,
                aboutus_status,
                translations
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: result.message,
            timer: 1500,
            showConfirmButton: false
        });

        aboutusModal.hide();
        loadAboutUsTable();

    } catch (error) {
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Delete about us
 */
async function deleteAboutUs(id) {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Are you sure?',
        text: 'This about us page will be deleted',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) return;

    Swal.fire({
        title: 'Deleting...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch('../api/delete_aboutus.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ aboutus_id: id })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'Deleted!',
            text: result.message,
            timer: 1500,
            showConfirmButton: false
        });

        loadAboutUsTable();

    } catch (error) {
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Initialize Summernote editors
 */
function initSummernote() {
    document.querySelectorAll('.summernote').forEach(textarea => {
        // Destroy existing instance
        if ($(textarea).data('summernote')) {
            $(textarea).summernote('destroy');
        }

        // Initialize fresh
        $(textarea).summernote({
            height: 300,
            placeholder: 'Enter content...',
            dialogsInBody: true,
            dialogsFade: false,
            disableDragAndDrop: true,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['codeview', 'help']]
            ],
            callbacks: {
                onChange: function (contents, $editable) {
                    $(textarea).val(contents);
                }
            }
        });
    });
}

/**
 * Initialize language tabs
 */
function initLanguageTabs() {
    const tabs = document.querySelectorAll('#languageTabs button');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const lang = this.getAttribute('data-lang');

            // Update active state
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Show corresponding tab pane
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            document.getElementById(`lang-${lang}`).classList.add('show', 'active');
        });
    });
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
