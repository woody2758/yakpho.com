/**
 * Products Management JavaScript
 * Handles all product-related AJAX operations
 */

let productModal;
let currentProductId = null;
let cropper = null; // Cropper.js instance

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
        const response = await fetch(`../api/get_products_table.php?page=${page}&search=${encodeURIComponent(search)}&category=${category}`);
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

    // ✅ CRITICAL: Reset form first to clear old data
    // This prevents translation fields from showing previous product's data
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = productId;

    // Reset all Summernote editors to blank
    $('.tinymce-editor').each(function () {
        if ($(this).data('summernote-initialized')) {
            $(this).summernote('code', '');
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

    // Reset attribute checkboxes
    document.querySelectorAll('[name="attribute_groups[]"]').forEach(cb => cb.checked = false);

    // Show loading
    Swal.fire({
        title: 'กำลังโหลด...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`../api/get_product.php?id=${productId}`);
        const data = await response.json();

        Swal.close();

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
            document.getElementById('priceTier').value = product.price_tier_id || '';
            document.getElementById('productPrice').value = product.product_price || 0;
            document.getElementById('productNPrice').value = product.product_nprice || 0;
            document.getElementById('productCPrice').value = product.product_cprice || 0;
            document.getElementById('productWeight').value = product.product_weight || 0;
            document.getElementById('productStock').value = product.product_stock || 0;
            document.getElementById('stockAlertEnabled').checked = product.stock_alert_enabled == 1;
            document.getElementById('stockAlertLevel').value = product.stock_alert_level || 10;

            // Toggle sections
            document.getElementById('fixedPriceSection').style.display = product.price_tier_id ? 'none' : 'block';
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
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถโหลดข้อมูลสินค้าได้'
        });
    }
}

/**
 * Save product (Add/Edit)
 */
async function saveProduct() {
    const form = document.getElementById('productForm');

    // ✅ CRITICAL: Auto-save image if user uploaded but didn't crop/use
    // This handles the case where user forgets to click buttons
    if (cropper && document.getElementById('imageCropperContainer').style.display !== 'none') {
        // User uploaded image but didn't process it
        // Auto-use original to prevent data loss
        console.log('⚠️ Auto-saving uploaded image before form submit');
        useOriginalImage();

        // Wait a moment for image processing
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    // Enhanced validation with specific messages
    if (!form.checkValidity()) {
        // Find first invalid field
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

            // Focus on invalid field
            invalidField.focus();
            invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            form.reportValidity();
        }
        return;
    }

    // Show progress bar with smooth multi-phase animation
    let timerInterval;
    let progress = 0;

    Swal.fire({
        title: 'กำลังบันทึกข้อมูล...',
        html: `
            <div class="mb-3" id="progressStatus" style="font-weight: 500; color: #666;">กำลังเตรียมข้อมูล...</div>
            <div class="progress" style="height: 30px; border-radius: 15px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
                <div id="saveProgressBar" 
                     class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" 
                     style="width: 0%; background: linear-gradient(45deg, #28a745 25%, #20c997 25%, #20c997 50%, #28a745 50%, #28a745 75%, #20c997 75%, #20c997); background-size: 40px 40px; transition: width 0.3s ease;">
                    <span style="font-weight: bold; font-size: 14px; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">0%</span>
                </div>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            const progressBar = document.getElementById('saveProgressBar');
            const statusText = document.getElementById('progressStatus');

            timerInterval = setInterval(() => {
                // Phase 1: Quick to 30% (preparing)
                if (progress < 30) {
                    progress += 3;
                    statusText.textContent = 'กำลังเตรียมข้อมูล...';
                }
                // Phase 2: Medium to 60% (uploading)
                else if (progress < 60) {
                    progress += 2;
                    statusText.textContent = 'กำลังอัพโหลดข้อมูล...';
                }
                // Phase 3: Slower to 85% (processing images)
                else if (progress < 85) {
                    progress += 1;
                    statusText.textContent = 'กำลังประมวลผลรูปภาพ...';
                }
                // Phase 4: Very slow to 95% (saving)
                else if (progress < 95) {
                    progress += 0.5;
                    statusText.textContent = 'กำลังบันทึกลงฐานข้อมูล...';
                }

                progressBar.style.width = Math.floor(progress) + '%';
                progressBar.querySelector('span').textContent = Math.floor(progress) + '%';
            }, 150);
        },
        willClose: () => {
            clearInterval(timerInterval);
        }
    });

    try {
        const formData = new FormData(form);

        const response = await fetch('../api/save_product.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        // Complete progress bar smoothly
        clearInterval(timerInterval);
        const progressBar = document.getElementById('saveProgressBar');
        const statusText = document.getElementById('progressStatus');

        if (progressBar && statusText) {
            statusText.textContent = 'เสร็จสิ้น! ✓';
            statusText.style.color = '#28a745';

            // Smooth animation to 100%
            const completeInterval = setInterval(() => {
                progress += 3;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(completeInterval);
                }
                progressBar.style.width = progress + '%';
                progressBar.querySelector('span').textContent = progress + '%';
            }, 30);

            // Wait to show 100%
            await new Promise(resolve => setTimeout(resolve, 600));
        }

        Swal.close();

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

        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถบันทึกข้อมูลได้'
            });
        }
    } catch (error) {
        Swal.close();
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
        confirmButtonColor: '#dc3545'
    });

    if (!result.isConfirmed) return;

    // Show loading
    Swal.fire({
        title: 'กำลังลบ...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('../api/delete_product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        });

        const data = await response.json();

        Swal.close();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: 'ลบสินค้าเรียบร้อย',
                timer: 1500,
                showConfirmButton: false
            });

            // Reload table
            loadProductsTable(1);

        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถลบสินค้าได้'
            });
        }
    } catch (error) {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถลบสินค้าได้'
        });
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
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ order: order })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✓ Product order saved');

                // Reinitialize Sortable to keep it working
                // This ensures drag & drop works after first sort
                setTimeout(() => {
                    initializeSortable();
                }, 100);
            } else {
                console.error('Error saving product order:', data.message);
                // Reinitialize even on error to keep functionality
                initializeSortable();
            }
        })
        .catch(error => {
            console.error('Error saving product order:', error);
            // Reinitialize on error
            initializeSortable();
        });
}

