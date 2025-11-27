/**
 * Products Management JavaScript
 * Handles all product-related AJAX operations
 */

let productModal;
let currentProductId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    // Initialize modal
    const modalElement = document.getElementById('productModal');
    if (modalElement) {
        productModal = new bootstrap.Modal(modalElement);
    }

    // Load products table
    loadProductsTable(1);

    // Load attribute groups for modal
    loadAttributeGroups();

    // Event listeners
    setupEventListeners();
});

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

    try {
        const response = await fetch(`../api/get_products_table.php?page=${page}&search=${encodeURIComponent(search)}&category=${category}`);
        const html = await response.text();

        document.getElementById('productsTableContainer').innerHTML = html;

        // Reinitialize icons
        if (window.lucide) {
            lucide.createIcons();
        }
    } catch (error) {
        console.error('Error loading products:', error);
        document.getElementById('productsTableContainer').innerHTML =
            '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
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
            document.getElementById('productCode').value = product.product_code || '';
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
                    if (detailField) detailField.value = trans.product_detail || '';
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

    // Validate
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Show loading
    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const formData = new FormData(form);

        const response = await fetch('../api/save_product.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

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
            text: 'ไม่สามารถบันทึกข้อมูลได้'
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
