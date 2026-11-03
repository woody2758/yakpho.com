/**
 * Product Category Management - Full AJAX
 * รองรับ 6 ภาษา: th, en, de, fr, zh, ko
 */

let categoryModal;
let currentPage = 1;

// Initialize modal on page load
document.addEventListener('DOMContentLoaded', function () {
    categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    // Load main categories for dropdown
    loadMainCategories();
});

/**
 * Load main categories for dropdown
 */
async function loadMainCategories() {
    try {
        const response = await fetch(`${ADMIN_URL}/api/get_maincat.php?all=1`);
        const data = await response.json();

        if (data.success && data.categories) {
            const select = document.getElementById('maincat_id');
            // Clear existing options except first
            while (select.options.length > 1) {
                select.remove(1);
            }

            // Add main categories
            data.categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.maincat_id;
                option.textContent = cat.name_th + (cat.name_en ? ` (${cat.name_en})` : '');
                select.appendChild(option);
            });

            // If using Tom Select, sync the changes
            if (select.tomselect) {
                select.tomselect.clearOptions();
                data.categories.forEach(cat => {
                    select.tomselect.addOption({
                        value: cat.maincat_id,
                        text: cat.name_th + (cat.name_en ? ` (${cat.name_en})` : '')
                    });
                });
                select.tomselect.sync();
            }
        }
    } catch (error) {
        console.error('Error loading main categories:', error);
    }
}

/**
 * Load categories table via AJAX
 */
function loadCategoriesTable(page = 1, search = '') {
    currentPage = page;
    const tableContainer = document.getElementById('tableContainer');

    // Show loading
    tableContainer.style.opacity = '0.5';
    tableContainer.style.pointerEvents = 'none';

    const params = new URLSearchParams({
        page: page,
        search: search
    });

    fetch(`${ADMIN_URL}/api/get_productcat_table.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tableContainer.innerHTML = data.table;
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';

                // Update pagination
                document.getElementById('paginationContainer').innerHTML = data.pagination;

                // Re-initialize icons
                lucide.createIcons();

                // Initialize drag & drop sorting
                initializeSortable();
            } else {
                throw new Error(data.message || 'Failed to load data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tableContainer.style.opacity = '1';
            tableContainer.style.pointerEvents = 'auto';

            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถโหลดข้อมูลได้'
            });
        });
}

/**
 * Add new category
 */
function addCategory() {
    // Reset form
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('modalTitle').textContent = 'เพิ่มหมวดสินค้า';
    document.getElementById('categoryStatus').checked = true;
    document.getElementById('statusBadge').textContent = 'เปิดใช้งาน';
    document.getElementById('statusBadge').className = 'badge bg-success';

    // Reset main category selection
    const maincatSelect = document.getElementById('maincat_id');
    if (maincatSelect) {
        maincatSelect.value = '';
        if (maincatSelect.tomselect) {
            maincatSelect.tomselect.clear();
        }
    }

    // Show first tab
    const firstTab = document.querySelector('[data-bs-target="#lang-th"]');
    if (firstTab) {
        const tab = new bootstrap.Tab(firstTab);
        tab.show();
    }

    // Reset production settings
    document.getElementById('is_production').checked = false;
    document.getElementById('productionSettings').style.display = 'none';
    document.getElementById('prod_group_name').value = '';
    document.getElementById('prod_size').value = '';
    document.getElementById('prod_formula').value = '';

    categoryModal.show();

    // Re-initialize icons
    setTimeout(() => {
        lucide.createIcons();
    }, 100);
}

/**
 * Edit category
 */
async function editCategory(categoryId) {
    // ✅ Reset form first to clear old data
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = categoryId;

    // ✅ OPEN MODAL IMMEDIATELY
    categoryModal.show();
    
    // ✅ SHOW INTERNAL LOADING OVERLAY
    const loadingOverlay = document.getElementById('modalLoadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.setProperty('display', 'flex', 'important');
    }

    try {
        const response = await fetch(`${ADMIN_URL}/api/get_productcat.php?id=${categoryId}`);
        const data = await response.json();

        // ✅ HIDE INTERNAL LOADING OVERLAY
        if (loadingOverlay) {
            loadingOverlay.style.setProperty('display', 'none', 'important');
        }

        if (data.success) {
            const category = data.category;

            // Fill form
            document.getElementById('categoryId').value = category.productcat_id;
            document.getElementById('categoryStatus').checked = category.productcat_status == 1;

            // Set main category
            const maincatSelect = document.getElementById('maincat_id');
            if (maincatSelect && category.maincat_id) {
                const val = category.maincat_id.toString();
                maincatSelect.value = val;
                
                // If using Tom Select, update the UI
                if (maincatSelect.tomselect) {
                    maincatSelect.tomselect.setValue(val);
                }
            }

            // Set production settings
            const isProd = category.is_production == 1;
            document.getElementById('is_production').checked = isProd;
            document.getElementById('productionSettings').style.display = isProd ? 'block' : 'none';
            document.getElementById('prod_group_name').value = category.prod_group_name || '';
            document.getElementById('prod_size').value = category.prod_size || '';
            document.getElementById('prod_formula').value = category.prod_formula || '';

            // Update status badge
            const statusBadge = document.getElementById('statusBadge');
            if (category.productcat_status == 1) {
                statusBadge.textContent = 'เปิดใช้งาน';
                statusBadge.className = 'badge bg-success';
            } else {
                statusBadge.textContent = 'ปิดใช้งาน';
                statusBadge.className = 'badge bg-secondary';
            }

            // Fill translations for all languages
            const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
            languages.forEach(lang => {
                const translation = data.translations.find(t => t.lang_code === lang);
                const nameInput = document.querySelector(`[name="productcat_name_${lang}"]`);
                const detailInput = document.querySelector(`[name="productcat_detail_${lang}"]`);

                // ✅ CRITICAL: Always set value (empty if no translation)
                // This clears old data from previous edits
                if (nameInput) {
                    nameInput.value = translation ? (translation.productcat_name || '') : '';
                }
                if (detailInput) {
                    detailInput.value = translation ? (translation.productcat_detail || '') : '';
                }
            });

            // Update modal title
            document.getElementById('modalTitle').textContent = 'แก้ไขหมวดสินค้า';

            // Show modal
            categoryModal.show();

            // Re-initialize icons
            setTimeout(() => {
                lucide.createIcons();
            }, 100);

        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถโหลดข้อมูลได้'
            });
        }
    } catch (error) {
        if (loadingOverlay) {
            loadingOverlay.style.setProperty('display', 'none', 'important');
        }
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถโหลดข้อมูลได้'
        });
    }
}

/**
 * Save category (Add/Edit)
 */
async function saveCategory() {
    const form = document.getElementById('categoryForm');
    const saveBtn = document.getElementById('saveCategoryBtn');
    const spinner = saveBtn.querySelector('.spinner-border');
    const btnText = saveBtn.querySelector('.btn-text');

    // Validate
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Show loading in button
    saveBtn.disabled = true;
    spinner.classList.remove('d-none');
    btnText.textContent = 'กำลังบันทึก...';

    try {
        const formData = new FormData(form);

        const response = await fetch(`${ADMIN_URL}/api/save_productcat.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        // Reset button
        saveBtn.disabled = false;
        spinner.classList.add('d-none');
        btnText.textContent = 'บันทึก';

        if (data.success) {
            // Close modal
            categoryModal.hide();

            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: document.getElementById('categoryId').value ? 'บันทึกการแก้ไขเรียบร้อย' : 'เพิ่มหมวดสินค้าเรียบร้อย',
                timer: 1500,
                showConfirmButton: false
            });

            // Reload table
            loadCategoriesTable(currentPage, document.getElementById('searchInput').value);

        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถบันทึกข้อมูลได้'
            });
        }
    } catch (error) {
        saveBtn.disabled = false;
        spinner.classList.add('d-none');
        btnText.textContent = 'บันทึก';
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถบันทึกข้อมูลได้'
        });
    }
}

/**
 * Delete category
 */
function deleteCategory(categoryId, categoryName) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบหมวดสินค้า "${categoryName}" ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังลบ...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`${ADMIN_URL}/api/delete_productcat.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ productcat_id: categoryId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบเรียบร้อย',
                            text: 'ลบหมวดสินค้าเรียบร้อยแล้ว',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Reload table
                        loadCategoriesTable(currentPage, document.getElementById('searchInput').value);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: data.message || 'ไม่สามารถลบได้'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถลบได้'
                    });
                });
        }
    });
}

/**
 * Toggle category status
 */
function toggleStatus(categoryId, currentStatus) {
    const newStatus = currentStatus === 1 ? 0 : 1;
    const statusText = newStatus === 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

    Swal.fire({
        title: 'ยืนยันการเปลี่ยนสถานะ?',
        text: `คุณต้องการเปลี่ยนสถานะเป็น "${statusText}" ใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, เปลี่ยนเลย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`${ADMIN_URL}/api/save_productcat.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `productcat_id=${categoryId}&productcat_status=${newStatus}&quick_update=1`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: 'เปลี่ยนสถานะเรียบร้อยแล้ว',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Reload table
                        loadCategoriesTable(currentPage, document.getElementById('searchInput').value);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: data.message || 'ไม่สามารถเปลี่ยนสถานะได้'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเปลี่ยนสถานะได้'
                    });
                });
        }
    });
}

/**
 * Initialize Sortable.js for drag & drop
 */
function initializeSortable() {
    const tbody = document.querySelector('#tableContainer tbody');
    if (!tbody) return;

    // Destroy existing sortable instance if any
    if (tbody.sortable) {
        tbody.sortable.destroy();
    }

    // Create new sortable instance
    tbody.sortable = new Sortable(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: function (evt) {
            saveNewOrder();
        }
    });
}

/**
 * Save new category order to backend
 */
function saveNewOrder() {
    const rows = document.querySelectorAll('.sortable-row');
    const order = Array.from(rows).map(row => row.dataset.id);

    console.log('Sorting Page:', currentPage, 'Order:', order);

    if (order.length === 0) {
        console.warn('No rows found for sorting');
        return;
    }

    // Show Loading Overlay
    const overlay = document.getElementById('tableLoadingOverlay');
    if (overlay) {
        overlay.style.setProperty('display', 'flex', 'important');
    }

    fetch(`${ADMIN_URL}/api/save_productcat_order.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            order: order,
            page: currentPage,
            limit: 15
        })
    })
        .then(res => res.json())
        .then(data => {
            // Hide Overlay
            if (overlay) {
                overlay.style.setProperty('display', 'none', 'important');
            }

            if (data.success) {
                // Subtle success feedback could be added here if needed, 
                // but usually the spinner disappearing is enough.
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'ไม่สามารถบันทึกลำดับได้',
                    text: data.message
                });
            }
        })
        .catch(error => {
            // Hide Overlay
            if (overlay) {
                overlay.style.setProperty('display', 'none', 'important');
            }
            console.error('Error saving order:', error);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'
            });
        });
}

/**
 * Handle pagination clicks
 */
document.addEventListener('click', function (e) {
    const pageLink = e.target.closest('.page-link');
    if (pageLink && !pageLink.parentElement.classList.contains('disabled')) {
        e.preventDefault();
        const url = new URL(pageLink.href);
        const page = url.searchParams.get('page') || 1;
        loadCategoriesTable(page, document.getElementById('searchInput').value);
    }
});
