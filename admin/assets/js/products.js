/**
 * Products Management JavaScript
 * Handles all product-related AJAX operations
 */

let productModal;
let currentProductId = null;
let cropper = null; // Cropper.js instance

// Sorting variables
let currentSortCol = 'default';
let currentSortDir = 'ASC';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    // Initialize modal
    const modalElement = document.getElementById('productModal');
    if (modalElement) {
        productModal = new bootstrap.Modal(modalElement);

        // Initialize Summernote when modal is shown
        modalElement.addEventListener('shown.bs.modal', function () {
            initSummernote();
        });

        // Reset Summernote when modal is hidden (optional, but good for cleanup if needed)
        // modalElement.addEventListener('hidden.bs.modal', function () { ... });
    }

    // Load products table
    loadProductsTable(1);

    // Load attribute groups for modal
    loadAttributeGroups();

    // Event listeners
    setupEventListeners();

    // Initialize Summernote on tab change
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        initSummernote();
    });

    // Image upload event listeners
    const mainImageInput = document.getElementById('mainImageInput');
    if (mainImageInput) {
        mainImageInput.addEventListener('change', handleMainImageSelect);
    }

    const galleryInput = document.getElementById('galleryImagesInput');
    if (galleryInput) {
        galleryInput.addEventListener('change', handleGalleryImagesSelect);
    }
});

/**
 * Initialize Summernote Editors
 */
function initSummernote() {
    const textareas = document.querySelectorAll('.tinymce-editor');

    textareas.forEach((textarea, index) => {
        // Skip if not visible (in hidden tab)
        if (!$(textarea).is(':visible')) {
            return;
        }

        // Skip if already initialized
        if ($(textarea).data('summernote-initialized')) {
            return;
        }

        try {
            $(textarea).summernote({
                height: 200,
                placeholder: 'กรอกรายละเอียดสินค้า...',
                dialogsInBody: true, // ✅ Prevent dialog issues
                dialogsFade: false,  // ✅ Disable fade animation
                disableDragAndDrop: true, // ✅ Prevent drag issues  
                toolbar: [
                    ['style', ['style']], // ✅ คืนมาแล้ว
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']], // ✅ คืนมาแล้ว
                    ['view', ['codeview', 'help']]
                ],
                callbacks: {
                    onChange: function (contents, $editable) {
                        $(textarea).val(contents);
                    },
                    // ✅ Prevent toolbar buttons and dropdown items from submitting form
                    onInit: function () {
                        const editor = $(textarea).next('.note-editor');

                        // Set all buttons to type="button"
                        editor.find('button').each(function () {
                            if (!$(this).attr('type')) {
                                $(this).attr('type', 'button');
                            }
                        });

                        // ✅ CRITICAL FIX: Prevent ALL link navigation in dropdown menus
                        // Summernote dropdown items are <a> tags that can cause page reload
                        editor.find('.dropdown-menu a').each(function () {
                            $(this).on('click', function (e) {
                                e.preventDefault();
                                return false;
                            });
                        });

                        // Also handle dynamically added dropdown items
                        editor.on('click', '.dropdown-menu a', function (e) {
                            e.preventDefault();
                            return false;
                        });

                        // Stop all click events from bubbling
                        editor.on('click', function (e) {
                            e.stopPropagation();
                        });
                    }
                }
            });

            // Mark as initialized
            $(textarea).data('summernote-initialized', true);

        } catch (error) {
            console.error('Error initializing Summernote:', error);
        }
    });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Price tier toggle
    const priceTierSelect = document.getElementById('priceTier');
    if (priceTierSelect) {
        priceTierSelect.addEventListener('change', function () {
            const fixedSection = document.getElementById('fixedPriceSection');
            fixedSection.style.display = this.value ? 'none' : 'block';
        });
    }

    // Stock alert toggle
    const stockAlertCheckbox = document.getElementById('stockAlertEnabled');
    if (stockAlertCheckbox) {
        stockAlertCheckbox.addEventListener('change', function () {
            const section = document.getElementById('stockAlertSection');
            section.style.display = this.checked ? 'block' : 'none';
        });
    }
}

/**
 * Load products table via AJAX
 */
async function loadProductsTable(page = 1) {
    const search = new URLSearchParams(window.location.search).get('search') || '';
    const category = new URLSearchParams(window.location.search).get('category') || 0;

    const container = document.getElementById('productsTableContainer');

    // Show loading state with opacity
    container.style.opacity = '0.5';
    container.style.pointerEvents = 'none';

    try {
        const response = await fetch(`../api/get_products_table.php?page=${page}&search=${encodeURIComponent(search)}&category=${category}&sort_col=${currentSortCol}&sort_dir=${currentSortDir}`);
        const html = await response.text();

        container.innerHTML = html;

        // Restore opacity
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';

        // Reinitialize icons
        if (window.lucide) {
            lucide.createIcons();
        }

        // Initialize sortable
        initializeSortable();
    } catch (error) {
        console.error('Error loading products:', error);
        container.innerHTML =
            '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
    }
}

/**
 * Handle Sorting Click
 */
function toggleSort(col) {
    if (currentSortCol === col) {
        currentSortDir = currentSortDir === 'ASC' ? 'DESC' : 'ASC';
    } else {
        currentSortCol = col;
        currentSortDir = 'DESC'; // usually first click sorts desc (e.g. highest price first), user preference can vary
    }
    
    // Get current page if possible, otherwise page 1
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || 1;
    
    loadProductsTable(currentPage);
}

/**
 * Load attribute groups for modal
 */
async function loadAttributeGroups() {
    try {
        const response = await fetch('../api/get_attribute_groups.php');
        const data = await response.json();

        if (data.success) {
            const container = document.getElementById('attributeGroupsContainer');
            container.innerHTML = '';

            data.groups.forEach(group => {
                const div = document.createElement('div');
                div.className = 'form-check';
                div.innerHTML = `
                    <input class="form-check-input" 
                           type="checkbox" 
                           name="attribute_groups[]" 
                           value="${group.group_id}" 
                           id="group_${group.group_id}">
                    <label class="form-check-label" for="group_${group.group_id}">
                        <strong>${group.group_name}</strong>
                        ${group.group_description ? `<br><small class="text-muted">${group.group_description}</small>` : ''}
                    </label>
                `;
                container.appendChild(div);
            });
        }
    } catch (error) {
        console.error('Error loading attribute groups:', error);
    }
}

/**
 * Add new product
 */
function addProduct() {
    currentProductId = null;

    // Reset form
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('modalTitleText').textContent = 'เพิ่มสินค้า';

    // Reset Summernote editors
    $('.tinymce-editor').each(function () {
        if ($(this).data('summernote-initialized')) {
            $(this).summernote('code', '');
        } else {
            $(this).val('');
        }
    });

    // Reset image previews
    document.getElementById('productImageBase64').value = '';
    document.getElementById('oldProductPicture').value = '';
    document.getElementById('croppedPreview').style.display = 'none';
    document.getElementById('imageCropperContainer').style.display = 'none';
    document.getElementById('galleryPreview').innerHTML = '';
    document.getElementById('mainImageInput').value = '';
    document.getElementById('galleryImagesInput').value = '';
    document.getElementById('productScent').value = '';
    document.getElementById('productFormula').value = '';
    if (document.getElementById('productVatType')) {
        document.getElementById('productVatType').value = 'included';
    }



    // Generate product code (YP + next ID)
    generateProductCode();

    // Show modal
    productModal.show();

    // Reinitialize icons
    setTimeout(() => {
        if (window.lucide) lucide.createIcons();
    }, 100);
}

/**
 * Edit product
 */
async function editProduct(productId) {
    currentProductId = productId;

    // ✅ Reset form first to clear old data
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = productId;

    // Reset Summernote
    $('.tinymce-editor').each(function () {
        if ($(this).data('summernote-initialized')) {
            $(this).summernote('code', '');
        }
    });

    // Reset images & previews
    document.getElementById('productImageBase64').value = '';
    document.getElementById('oldProductPicture').value = '';
    document.getElementById('croppedPreview').style.display = 'none';
    document.getElementById('imageCropperContainer').style.display = 'none';
    document.getElementById('galleryPreview').innerHTML = '';
    document.getElementById('mainImageInput').value = '';
    document.getElementById('galleryImagesInput').value = '';
    document.getElementById('productScent').value = '';
    document.getElementById('productFormula').value = '';
    document.querySelectorAll('[name="attribute_groups[]"]').forEach(cb => cb.checked = false);



    // ✅ OPEN MODAL IMMEDIATELY
    productModal.show();
    
    // ✅ SHOW INTERNAL LOADING OVERLAY
    const loadingOverlay = document.getElementById('modalLoadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.classList.remove('d-none');
        loadingOverlay.style.setProperty('display', 'flex', 'important');
    }

    try {
        const response = await fetch(`../api/get_product.php?id=${productId}`);
        const data = await response.json();

        // ✅ HIDE INTERNAL LOADING OVERLAY
        if (loadingOverlay) {
            loadingOverlay.style.setProperty('display', 'none', 'important');
        }

        if (data.success) {
            const product = data.product;

            // Fill form
            document.getElementById('productId').value = product.product_id;

            // Handle product code - convert TH to YP if needed
            let productCode = product.product_code || '';
            if (productCode.startsWith('TH')) {
                productCode = 'YP' + product.product_id;
            } else if (!productCode || productCode === '') {
                productCode = 'YP' + product.product_id;
            }
            document.getElementById('productCode').value = productCode;

            document.getElementById('productSlug').value = product.product_slug || '';
            document.getElementById('productCategory').value = product.productcat_id || '';
            document.getElementById('productStatus').checked = product.product_status == 1;
            let tierId = product.price_tier_id;
            if (tierId === '0' || tierId === 0) tierId = '';
            document.getElementById('priceTier').value = tierId;
            document.getElementById('productPrice').value = product.product_price || 0;
            document.getElementById('productNPrice').value = product.product_nprice || 0;
            document.getElementById('productCPrice').value = product.product_cprice || 0;
            document.getElementById('productWeight').value = product.product_weight || 0;
            document.getElementById('productStock').value = product.product_stock || 0;
            document.getElementById('stockAlertEnabled').checked = product.stock_alert_enabled == 1;
            document.getElementById('stockAlertLevel').value = product.stock_alert_level || 10;
            document.getElementById('productScent').value = product.scent_id || '';
            document.getElementById('productFormula').value = product.formula_id || '';
            if (document.getElementById('productVatType')) {
                document.getElementById('productVatType').value = product.vat_type || 'included';
            }



            // Toggle sections
            const hasTier = product.price_tier_id && product.price_tier_id !== '0' && product.price_tier_id !== 0;
            document.getElementById('fixedPriceSection').style.display = hasTier ? 'none' : 'block';
            document.getElementById('stockAlertSection').style.display = product.stock_alert_enabled ? 'block' : 'none';

            // Fill translations
            if (data.translations) {
                data.translations.forEach(trans => {
                    const lang = trans.lang_code;
                    const nameField = document.querySelector(`[name="product_name_${lang}"]`);
                    const excerptField = document.querySelector(`[name="product_excerpt_${lang}"]`);
                    const detailField = document.querySelector(`[name="product_detail_${lang}"]`);
                    const unitField = document.querySelector(`[name="product_unit_${lang}"]`);
                    const tagField = document.querySelector(`[name="product_tag_${lang}"]`);

                    if (nameField) nameField.value = trans.product_name || '';
                    if (excerptField) excerptField.value = trans.product_excerpt || '';

                    // Handle Summernote for detail field
                    if (detailField) {
                        if ($(detailField).data('summernote-initialized')) {
                            $(detailField).summernote('code', trans.product_detail || '');
                        } else {
                            detailField.value = trans.product_detail || '';
                        }
                    }

                    if (unitField) unitField.value = trans.product_unit || '';
                    if (tagField) tagField.value = trans.product_tag || '';
                });
            }

            // Check attribute groups
            if (data.attribute_sets) {
                data.attribute_sets.forEach(set => {
                    const checkbox = document.getElementById(`group_${set.group_id}`);
                    if (checkbox) checkbox.checked = true;
                });
            }

            // Display existing main image
            if (product.product_picture) {
                const croppedImage = document.getElementById('croppedImage');
                const croppedPreview = document.getElementById('croppedPreview');
                const oldProductPicture = document.getElementById('oldProductPicture');

                croppedImage.src = `../../uploads/products/small-${product.product_picture}`;
                croppedPreview.style.display = 'block';
                oldProductPicture.value = product.product_picture;
            }

            // Display existing gallery images
            if (data.gallery_images && data.gallery_images.length > 0) {
                displayGalleryImages(data.gallery_images);
            }

            // Update modal title
            document.getElementById('modalTitleText').textContent = 'แก้ไขสินค้า';

            // Show modal
            productModal.show();

            // Reinitialize icons
            setTimeout(() => {
                if (window.lucide) lucide.createIcons();
            }, 100);

        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถโหลดข้อมูลสินค้าได้'
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
            text: 'ไม่สามารถโหลดข้อมูลสินค้าได้'
        });
    }
}

/**
 * Refresh all record counts in sidebar and UI
 */
async function refreshCounts() {
    try {
        // 1. Update Sidebar Badges
        const responseCounts = await fetch('../api/get_counts.php');
        const dataCounts = await responseCounts.json();
        if (dataCounts.success) {
            for (const [key, value] of Object.entries(dataCounts.counts)) {
                const badge = document.getElementById(`badge-${key}`);
                if (badge) badge.textContent = value;
            }
            
            // Update total product count text at the bottom if exists
            const totalProductCount = document.getElementById('totalProductCount');
            if (totalProductCount && dataCounts.counts.product) {
                totalProductCount.textContent = dataCounts.counts.product;
            }
        }

        // 2. Update Category Filter Dropdown (The one with counts in brackets)
        const responseCats = await fetch('../api/get_product_categories.php');
        const dataCats = await responseCats.json();
        if (dataCats.success) {
            const categorySelect = document.getElementById('categoryFilter');
            if (categorySelect) {
                const currentVal = categorySelect.value;
                
                // Keep the "All Categories" option
                let html = '<option value="0">ทุกหมวดหมู่</option>';
                
                dataCats.categories.forEach(cat => {
                    const selected = (currentVal == cat.id) ? 'selected' : '';
                    html += `<option value="${cat.id}" ${selected}>${cat.name} (${cat.count})</option>`;
                });
                
                categorySelect.innerHTML = html;
                
                // If using Tom Select, sync it
                if (categorySelect.tomselect) {
                    categorySelect.tomselect.clearOptions();
                    categorySelect.tomselect.addOptions(dataCats.categories.map(c => ({
                        value: c.id,
                        text: `${c.name} (${c.count})`
                    })));
                    categorySelect.tomselect.setValue(currentVal);
                }
                
                // ✅ Update summary statistics (the count below the table)
                if (typeof updateProductSummary === 'function') {
                    updateProductSummary(currentVal);
                }
            }
        }
    } catch (error) {
        console.error('Error refreshing counts:', error);
    }
}

/**
 * Save product (Add/Edit)
 */
async function saveProduct() {
    const form = document.getElementById('productForm');
    const saveBtn = document.getElementById('saveProductBtn');
    const spinner = saveBtn.querySelector('.spinner-border');
    const btnText = saveBtn.querySelector('.btn-text');

    // ✅ CRITICAL: Auto-save image if user uploaded but didn't crop/use
    if (cropper && document.getElementById('imageCropperContainer').style.display !== 'none') {
        console.log('⚠️ Auto-saving uploaded image before form submit');
        useOriginalImage();
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    // Enhanced validation
    if (!form.checkValidity()) {
        const invalidField = form.querySelector(':invalid');
        if (invalidField) {
            let fieldName = 'ฟิลด์นี้';
            const label = form.querySelector(`label[for="${invalidField.id}"]`);
            if (label) {
                fieldName = label.textContent.replace('*', '').trim();
            } else if (invalidField.name) {
                fieldName = invalidField.name;
            }

            Swal.fire({
                icon: 'warning',
                title: 'กรุณากรอกข้อมูลให้ครบถ้วน',
                text: `${fieldName} จำเป็นต้องกรอก`,
                confirmButtonText: 'ตกลง'
            });
            invalidField.focus();
            invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            form.reportValidity();
        }
        return;
    }

    // Show loading in button
    saveBtn.disabled = true;
    spinner.classList.remove('d-none');
    btnText.textContent = 'กำลังบันทึก...';

    try {
        const formData = new FormData(form);

        const response = await fetch('../api/save_product.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        // Reset button state
        saveBtn.disabled = false;
        spinner.classList.add('d-none');
        btnText.textContent = 'บันทึก';

        if (data.success) {
            // Close modal
            productModal.hide();

            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: currentProductId ? 'บันทึกการแก้ไขเรียบร้อย' : 'เพิ่มสินค้าเรียบร้อย',
                timer: 1500,
                showConfirmButton: false
            });

            // Reload table
            loadProductsTable(1);
            
            // Refresh sidebar counts
            refreshCounts();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถบันทึกข้อมูลได้'
            });
        }
    } catch (error) {
        // Reset button state
        saveBtn.disabled = false;
        spinner.classList.add('d-none');
        btnText.textContent = 'บันทึก';
        
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถบันทึกข้อมูลได้: ' + error.message
        });
    }
}


/**
 * Delete product
 */
async function deleteProduct(productId, productName) {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'ยืนยันการลบ',
        text: `ต้องการลบสินค้า "${productName}" ใช่หรือไม่?`,
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#dc3545',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            try {
                const response = await fetch('../api/delete_product.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });

                if (!response.ok) {
                    throw new Error('ไม่สามารถลบสินค้าได้');
                }

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'ไม่สามารถลบสินค้าได้');
                }
                return data;
            } catch (error) {
                Swal.showValidationMessage(`ข้อผิดพลาด: ${error.message}`);
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (result.isConfirmed) {
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ',
            text: 'ลบสินค้าเรียบร้อย',
            timer: 1500,
            showConfirmButton: false
        });

        // Reload table
        loadProductsTable(1);

        // Refresh sidebar counts
        refreshCounts();
    }
}

/**
 * Initialize Sortable for drag & drop product ordering
 */
function initializeSortable() {
    const tbody = document.querySelector('#productsTableContainer tbody');
    if (!tbody) {
        console.log('⚠️ Sortable: tbody not found');
        return;
    }

    // Destroy previous instance if exists
    if (tbody.sortable) {
        try {
            tbody.sortable.destroy();
            console.log('🔄 Sortable: Destroyed previous instance');
        } catch (e) {
            console.warn('⚠️ Sortable destroy error:', e);
        }
        tbody.sortable = null;
    }

    // Use timeout to ensure DOM is ready
    setTimeout(() => {
        try {
            tbody.sortable = new Sortable(tbody, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',

                // Only allow sorting within same category
                onMove: function (evt) {
                    const draggedCategory = evt.dragged.dataset.category;
                    const relatedCategory = evt.related.dataset.category;

                    // Prevent dragging to different category
                    return draggedCategory === relatedCategory;
                },

                onEnd: function (evt) {
                    console.log('📦 Drag ended, saving...');
                    saveProductOrder();
                }
            });
            console.log('✅ Sortable initialized');
        } catch (e) {
            console.error('❌ Sortable init failed:', e);
        }
    }, 50);
}

/**
 * Save new product order to database
 */
function saveProductOrder() {
    const rows = document.querySelectorAll('.sortable-row');
    const order = Array.from(rows).map(row => row.dataset.id);

    fetch('../api/save_product_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order: order })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✓ Product order saved');
            setTimeout(() => { initializeSortable(); }, 100);
        } else {
            console.error('Error saving product order:', data.message);
            initializeSortable();
        }
    })
    .catch(error => {
        console.error('Error saving product order:', error);
        initializeSortable();
    });
}

/**
 * Quick update product fields (Price, Stock, etc.) via AJAX
 */
async function quickUpdate(productId, field, value) {
    const input = event.target;
    const originalBackground = input.style.backgroundColor;
    input.style.backgroundColor = '#fff3cd';

    try {
        const response = await fetch('../api/quick_update_product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, field: field, value: value })
        });
        const data = await response.json();
        if (data.success) {
            input.style.backgroundColor = '#d4edda';
            setTimeout(() => { input.style.backgroundColor = originalBackground; }, 1000);
        } else {
            input.style.backgroundColor = '#f8d7da';
            alert('บันทึกไม่สำเร็จ: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        input.style.backgroundColor = '#f8d7da';
    }
}

/**
 * Toggle product status via AJAX
 */
async function toggleStatus(productId, newStatus) {
    const container = document.getElementById(`status-container-${productId}`);
    const originalHtml = container.innerHTML;
    container.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status"></span>';

    try {
        const response = await fetch('../api/quick_update_product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, field: 'product_status', value: newStatus })
        });
        const data = await response.json();
        if (data.success) {
            if (newStatus == 1) {
                container.innerHTML = `<button type="button" class="badge bg-success border-0" onclick="toggleStatus(${productId}, 0)" style="cursor: pointer;" title="คลิกเพื่อปิดการใช้งาน">เปิดใช้งาน</button>`;
            } else {
                container.innerHTML = `<button type="button" class="badge bg-secondary border-0" onclick="toggleStatus(${productId}, 1)" style="cursor: pointer;" title="คลิกเพื่อเปิดการใช้งาน">ปิดใช้งาน</button>`;
            }
        } else {
            alert('เปลี่ยนสถานะไม่สำเร็จ: ' + data.message);
            container.innerHTML = originalHtml;
        }
    } catch (error) {
        console.error('Error:', error);
        container.innerHTML = originalHtml;
    }
}

