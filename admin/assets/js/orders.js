/**
 * Order Management - Full AJAX
 */

let orderModal;
let currentPage = 1;
let currentStatusFilter = 1;
let currentOrderId = 0;
let currentUserIdFilter = 0;
let editingOrderId = null;
let currentPaymentFilter = null;
let currentSearchQuery = '';

// Initialize modal on page load
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('orderModal');
    if (modalEl) {
        orderModal = new bootstrap.Modal(modalEl, {
            focus: false
        });
    }

    // Global focus fix for Swal inside Bootstrap Modals
    $(document).on('focusin', function(e) {
        if ($(e.target).closest(".swal2-container").length) {
            e.stopImmediatePropagation();
        }
    });
});

/**
 * Load order table via AJAX
 */
function loadOrderTable(page = 1, search = '', userId = null) {
    currentPage = page;
    const tableContainer = document.getElementById('tableContainer');
    const pagination = document.getElementById('paginationContainer');

    // Update global filter if userId is provided (explicitly check for null)
    if (userId !== null) {
        currentUserIdFilter = userId;
    }

    // Show loading spinner
    tableContainer.innerHTML = `
        <div class="d-flex flex-column justify-content-center align-items-center py-5" style="min-height: 400px;">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">กำลังโหลด...</span>
            </div>
            <p class="text-muted mt-3 mb-0 fs-5">กำลังโหลดข้อมูล กรุณารอสักครู่...</p>
        </div>
    `;
    tableContainer.style.opacity = '1';
    tableContainer.style.pointerEvents = 'none';

    // Handle Search Input Display
    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearSearch');

    if (currentUserIdFilter) {
        if (searchInput) searchInput.value = `Customer ID: ${currentUserIdFilter}`;
        if (clearBtn) {
            clearBtn.classList.remove('d-none');
            clearBtn.classList.add('d-flex');
        }
        // If filtering by user, ignore the search text (which is just the label)
        search = '';
    } else {
        // If not filtering by user, ensure UI reflects that
        if (clearBtn && !search) {
            clearBtn.classList.remove('d-flex');
            clearBtn.classList.add('d-none');
        }
    }

    currentSearchQuery = search;
    let url = `${ADMIN_URL}/api/get_orders_table.php?page=${page}&search=${search}&status=${currentStatusFilter}`;
    if (currentUserIdFilter) {
        url += `&user_id=${currentUserIdFilter}`;
    }
    if (currentPaymentFilter) {
        url += `&payment=${currentPaymentFilter}`;
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tableContainer.innerHTML = data.table;
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';

                // Update pagination
                if (pagination) pagination.innerHTML = data.pagination;

                // Update Dashboard
                updateDashboard(data.statusCounts);

                // Re-initialize icons
                lucide.createIcons();

                // Initialize Tooltips
                try {
                    const tooltipTriggerList = [].slice.call(tableContainer.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                } catch (e) {
                    console.warn('Bootstrap Tooltip initialization failed:', e);
                }

                // Initialize Checkboxes for Bulk Actions
                initBulkActions();
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
 * Update Dashboard Status Cards
 */
function updateDashboard(counts) {
    fetch(`${ADMIN_URL}/api/get_all_statuses.php`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.statuses) {
                renderDashboard(data.statuses, counts);
            }
        })
        .catch(error => console.error('Error loading statuses:', error));
}

function renderDashboard(statuses, counts) {
    const dashboard = document.getElementById('statusDashboard');
    let html = '';

    // Add "All" card
    let total = 0;
    Object.keys(counts).forEach(key => {
        if (key !== 'cod') {
            total += parseInt(counts[key] || 0, 10);
        }
    });
    
    const isAllActive = currentStatusFilter === 0 && currentPaymentFilter === null;
    const allBorderStyle = isAllActive ? `border: 2px solid #0d6efd !important;` : '';

    html += `
        <div class="col-xl-auto col-lg-2 col-md-3 col-6 flex-grow-1" style="min-width: 120px;">
            <div class="card shadow-sm border-0 mb-2" style="cursor:pointer; transition: transform 0.2s; ${allBorderStyle}" 
                 onclick="filterStatus(0, this)" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="card-body d-flex align-items-center p-1 px-2">
                    <div class="rounded-circle p-1 me-2 bg-light text-primary d-flex align-items-center justify-content-center" style="width:28px; height:28px;">
                        <i data-lucide="list" style="width:14px; height:14px;"></i>
                    </div>
                    <div class="text-truncate" style="padding-top: 3px; padding-bottom: 2px;">
                        <h6 class="card-subtitle mb-0 text-dark fw-bold" style="font-size: 0.75rem; line-height: normal;">ทั้งหมด</h6>
                        <h5 class="card-title mb-0 fw-bold text-primary" style="font-size: 1.1rem; line-height: normal;">${total}</h5>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add COD card (RED as requested)
    const codCount = counts['cod'] || 0;
    const isCodActive = currentPaymentFilter === 'cod';
    const codBorderStyle = isCodActive ? `border: 2px solid #dc3545 !important;` : '';
    
    html += `
        <div class="col-xl-auto col-lg-2 col-md-3 col-6 flex-grow-1" style="min-width: 120px;">
            <div class="card shadow-sm border-0 mb-2" style="cursor:pointer; transition: transform 0.2s; ${codBorderStyle}" 
                 onclick="filterPayment('cod', this)" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="card-body d-flex align-items-center p-1 px-2">
                    <div class="rounded-circle p-1 me-2 bg-danger text-white d-flex align-items-center justify-content-center" style="width:28px; height:28px;">
                        <i data-lucide="banknote" style="width:14px; height:14px;"></i>
                    </div>
                    <div class="text-truncate" style="padding-top: 3px; padding-bottom: 2px;">
                        <h6 class="card-subtitle mb-0 text-dark fw-bold" style="font-size: 0.75rem; line-height: normal;">COD</h6>
                        <h5 class="card-title mb-0 fw-bold text-danger" style="font-size: 1.1rem; line-height: normal;">${codCount}</h5>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Render remaining statuses (directly from the database order)
    statuses.forEach(status => {
        const count = counts[status.orsts_id] || 0;
        const isActive = currentStatusFilter == status.orsts_id;
        const borderStyle = isActive ? `border: 2px solid ${status.orsts_color} !important;` : '';

        html += `
        <div class="col-xl-auto col-lg-2 col-md-3 col-6 flex-grow-1" style="min-width: 120px;">
                <div class="card shadow-sm border-0 mb-2" style="cursor:pointer; transition: transform 0.2s; ${borderStyle}" 
                     onclick="filterStatus(${status.orsts_id}, this)" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div class="card-body d-flex align-items-center p-1 px-2">
                        <div class="rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" 
                             style="background-color: ${status.orsts_color}10; color: ${status.orsts_color}; width:28px; height:28px; border: 1px solid ${status.orsts_color}20;">
                            <i data-lucide="package" style="width:14px; height:14px;"></i>
                        </div>
                        <div class="text-truncate" style="padding-top: 3px; padding-bottom: 2px;">
                            <h6 class="card-subtitle mb-0 text-dark fw-bold" style="font-size: 0.75rem; line-height: normal; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100px;">${status.orsts_detail}</h6>
                            <h5 class="card-title mb-0 fw-bold" style="color: ${status.orsts_color}; filter: brightness(0.7); font-size: 1.1rem; line-height: normal;">${count}</h5>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    dashboard.innerHTML = html;
    lucide.createIcons();
}

function filterStatus(statusId, element = null) {
    currentStatusFilter = statusId;
    currentPaymentFilter = null; // Clear payment filter when status is changed
    
    // Show spinner in the clicked card
    if (element) {
        const titleEl = element.querySelector('.card-title');
        if (titleEl) {
            titleEl.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        }
    }
    
    loadOrderTable(1, currentSearchQuery);
}

/**
 * Filter by Payment Type (e.g., COD)
 */
function filterPayment(type, element = null) {
    if (currentPaymentFilter === type) {
        currentPaymentFilter = null; // Toggle off
    } else {
        currentPaymentFilter = type;
        currentStatusFilter = 0; // Clear status filter when payment is selected
    }
    
    // Show spinner in the clicked card
    if (element) {
        const titleEl = element.querySelector('.card-title');
        if (titleEl) {
            titleEl.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        }
    }
    
    loadOrderTable(1, currentSearchQuery);
}

/**
 * View Order Details
 */
async function viewOrder(id) {
    currentOrderId = id;
    
    // Reset Save Button State to default
    const saveBtn = document.getElementById('modalSaveBtn');
    if (saveBtn) {
        saveBtn.innerHTML = '<i data-lucide="check" class="me-1" style="width:18px;height:18px;"></i> บันทึกข้อมูล';
        saveBtn.disabled = false;
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Show loading in modal
    document.getElementById('modalContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    // Set print link if button exists
    const printBtn = document.getElementById('modalPrintBtn');
    if (printBtn) {
        printBtn.setAttribute('onclick', `printOrder(${id})`);
        printBtn.removeAttribute('href');
        printBtn.removeAttribute('target');
    }
    orderModal.show();

    try {
        const response = await fetch(`${ADMIN_URL}/api/get_order_details.php?id=${id}`);
        const data = await response.json();

        if (data.success) {
            renderOrderDetails(data);
            // Initialize tracking link button after DOM is updated
            setTimeout(updateTrackingLinkBtn, 50);
        } else {
            document.getElementById('modalContent').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('modalContent').innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>`;
    }
}

function renderOrderDetails(data) {
    const order = data.order;
    const items = data.items;
    const address = data.address;
    const statuses = data.statuses;

    // Update Status Select
    const statusSelect = document.getElementById('statusSelect');
    statusSelect.innerHTML = statuses.map(s =>
        `<option value="${s.orsts_id}" ${s.orsts_id == order.orders_status ? 'selected' : ''}>${s.orsts_detail}</option>`
    ).join('');

    // Render Content
    const lockedStatuses = [11, 7, 8, 9];
    const isLocked = lockedStatuses.includes(parseInt(order.orders_status));

    let html = `
        <div class="row g-4">
            <!-- Order Info -->
            <div class="col-md-8">
                ${isLocked ? `
                <div class="alert alert-warning d-flex align-items-center mb-4 border-0 shadow-sm" style="border-radius: 12px; background-color: #fff9db;">
                    <i data-lucide="lock" class="me-3 text-warning" style="width:24px; height:24px;"></i>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1" style="color: #856404;">ออเดอร์นี้ถูกล็อกแล้ว</h6>
                        <p class="mb-0 small" style="color: #856404;">เนื่องจากสถานะปัจจุบัน คือ <strong>${order.orsts_detail}</strong> จึงไม่สามารถแก้ไขรายการสินค้าหรือที่อยู่ได้อีก</p>
                    </div>
                </div>
                ` : ''}
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i data-lucide="shopping-cart" class="me-2"></i>รายการสินค้า</h6>
                            ${!isLocked ? `
                            <button onclick="addOrderItem(${order.orders_id})" class="btn btn-sm btn-success">
                                <i data-lucide="plus" style="width:14px;height:14px;"></i> เพิ่มสินค้า
                            </button>
                            ` : ''}
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">รูป</th>
                                        <th>สินค้า</th>
                                        <th class="text-end">ราคา</th>
                                        <th class="text-center">จำนวน</th>
                                        <th class="text-end">รวม</th>
                                        <th style="width: 60px;" class="text-center">ลบ</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;

    let totalAmount = 0;
    let totalQty = 0;
    items.forEach(item => {
        const price = parseFloat(item.product_price) || 0;
        const qty = parseInt(item.ordetail_qty) || 0;
        const itemTotal = price * qty;
        totalAmount += itemTotal;
        totalQty += qty;
        const img = item.product_img ? `${ROOT_URL}/uploads/products/small-${item.product_img}` : `${ADMIN_ASSETS}/images/placeholder.png`;


        html += `
            <tr data-ordetail-id="${item.ordetail_id}" data-vat-type="${item.vat_type || 'included'}">
                <td>
                    <img src="${img}" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">
                </td>
                <td>
                    <div class="fw-bold text-truncate" style="max-width: 200px;" title="${(item.product_name || '').replace(/\"/g, '&quot;')}">${item.product_name}</div>
                    <div class="d-flex align-items-center gap-1 mt-1" id="memo-container-${item.ordetail_id}">
                        <small class="text-muted">${item.product_code}</small>
                        <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer" 
                             style="width: 20px; height: 20px; border-radius: 4px; cursor: pointer !important;" 
                             onclick="editItemMemo(${item.ordetail_id}, ${order.orders_id}, '${(item.ordetail_memo || '').replace(/'/g, "\\'")}')"
                             title="หมายเหตุสินค้า">
                            <i data-lucide="message-square" class="${item.ordetail_memo ? 'text-info' : 'text-muted'}" style="width:10px; height:10px;"></i>
                        </div>
                        ${item.ordetail_memo ? `<div class="mt-1"><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-normal" style="font-size: 0.7rem; white-space: normal; text-align: left;"><i data-lucide="info" class="me-1" style="width:10px; height:10px;"></i>${item.ordetail_memo}</span></div>` : ''}
                    </div>
                </td>
                <td class="text-end" style="width: 120px;">
                    ${!isLocked ? `
                        <input type="number" 
                               class="form-control form-control-sm text-end item-price-input" 
                               value="${price}" 
                               step="0.01"
                               onchange="updateItemPrice(${item.ordetail_id}, this.value, ${order.orders_id})">
                    ` : price.toLocaleString(undefined, {minimumFractionDigits: 2})}
                </td>
                <td class="text-center" style="width: 100px;">
                    ${!isLocked ? `
                        <input type="number" 
                               class="form-control form-control-sm text-center mx-auto item-qty-input" 
                               value="${item.ordetail_qty}" 
                               min="1" 
                               style="width: 70px;"
                               onchange="updateItemQty(${item.ordetail_id}, this.value, ${order.orders_id})">
                    ` : item.ordetail_qty}
                </td>
                <td class="text-end fw-bold item-row-subtotal">${itemTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                <td class="text-center">
                    ${!isLocked ? `
                    <button onclick="removeOrderItem(${item.ordetail_id}, ${order.orders_id})" 
                            class="btn btn-sm btn-danger" 
                            title="ลบสินค้า"
                            data-bs-toggle="tooltip">
                        <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                    </button>
                    ` : '<i data-lucide="lock" style="width:14px;height:14px;" class="text-muted"></i>'}
                </td>
            </tr>
        `;
    });

    // VAT Calculation
    let vatHtml = '';
    // Calculation logic
    const itemsTotal = totalAmount; // Use calculated amount from items loop instead of DB value
    const shipping = parseFloat(order.orders_delicost) || 0;
    
    // Calculate COD Fee (3%) if paycat is COD (ID 2)
    const paycatId = parseInt(order.paycat_id);
    let codFee = 0;
    if (paycatId === 2) {
        codFee = Math.round(itemsTotal * 0.03);
    }
    
    // Get discount
    const discount = parseFloat(order.orders_discount) || 0;
    
    // Calculate Grand Total initially
    let grandtotal = itemsTotal + shipping + codFee - discount;

    if (order.orders_is_vat == 1) {
        let sumExcluded = 0;
        let sumIncluded = 0;
        orderItems.forEach(item => {
            let itemTotal = parseFloat(item.product_price) * parseInt(item.ordetail_qty);
            if (item.vat_type === 'excluded') {
                sumExcluded += itemTotal;
            } else {
                sumIncluded += itemTotal;
            }
        });
        
        // Include shipping, codFee in sumIncluded, and subtract discount from it
        // Or apply discount proportionally? We'll apply it to sumIncluded for simplicity.
        sumIncluded += shipping + codFee - discount;
        
        const vatExcluded = sumExcluded * 0.07;
        const vatIncluded = sumIncluded * 7 / 107;
        
        const vatAmount = vatExcluded + vatIncluded;
        const basePrice = sumExcluded + (sumIncluded * 100 / 107);
        
        // Add VAT from excluded items to grandtotal
        grandtotal += vatExcluded;

        vatHtml = `
            <tr class="table-info border-0">
                <td colspan="4" class="text-end border-0">ยอดรวมก่อนภาษี (Tax Base)</td>
                <td class="text-end fw-bold border-0 modal-tax-base">${basePrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="border-0"></td>
            </tr>
            <tr class="table-info border-0">
                <td colspan="4" class="text-end border-0">ภาษีมูลค่าเพิ่ม (VAT 7%)</td>
                <td class="text-end fw-bold border-0 modal-vat-7">${vatAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="border-0"></td>
            </tr>
        `;
    }

    const paidAmount = parseFloat(order.orders_paid_amount) || 0;
    const balance = grandtotal - paidAmount;

    html += `
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end">รวมราคาสินค้า (<span class="modal-total-qty">${totalQty}</span>)</td>
                                        <td class="text-end fw-bold modal-total-sum">${itemsTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-light border-bottom">
                                        <td colspan="4" class="text-end">ค่าจัดส่ง</td>
                                        <td class="text-end fw-bold p-1">
                                            <input type="number" id="modalShippingInput" 
                                                   class="form-control form-control-sm text-end fw-bold ms-auto" 
                                                   style="width: 100px;" 
                                                   value="${shipping}" step="0.01" 
                                                   onchange="recalculateOrderTotalsLocally()">
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-light border-bottom">
                                        <td colspan="4" class="text-end">ส่วนลด (Discount)</td>
                                        <td class="text-end fw-bold p-1">
                                            <input type="number" id="modalDiscountInput" 
                                                   class="form-control form-control-sm text-end fw-bold ms-auto text-danger" 
                                                   style="width: 100px;" 
                                                   value="${parseFloat(order.orders_discount) || 0}" step="0.01" 
                                                   onchange="recalculateOrderTotalsLocally()">
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-light border-0">
                                         <td colspan="4" class="text-end border-0 align-middle">
                                             <label class="small fw-bold mb-0 me-2" style="color: #495057;">ช่องทางชำระเงิน:</label>
                                         </td>
                                         <td class="text-end border-0 p-1">
                                            <select id="paycatSelectSummary" class="form-select form-select-sm text-end ms-auto fw-bold" style="width: 110px; color: #0d6efd;" onchange="updatePaycatSync(this.value)">
                                                ${data.paycats.map(p => `
                                                    <option value="${p.paycat_id}" ${p.paycat_id == order.paycat_id ? 'selected' : ''}>
                                                        ${p.paycat_name}
                                                    </option>
                                                `).join('')}
                                            </select>
                                         </td>
                                         <td class="border-0"></td>
                                     </tr>
                                    <tr class="table-warning border-0 cod-fee-row ${codFee > 0 ? '' : 'd-none'}">
                                        <td colspan="4" class="text-end border-0">ค่าธรรมเนียม COD (3%)</td>
                                        <td class="text-end fw-bold text-danger border-0 modal-cod-fee">${codFee.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
                                        <td class="border-0"></td>
                                    </tr>
                                    ${vatHtml}
                                    <tr class="border-bottom">
                                        <td colspan="4" class="text-end align-middle">
                                            ${!isLocked ? `
                                            <div class="form-check form-switch d-inline-block border rounded p-1 px-2 bg-light">
                                                <input class="form-check-input" type="checkbox" id="orderVatToggle" 
                                                       ${order.orders_is_vat == 1 ? 'checked' : ''} 
                                                       onchange="toggleOrderVat(${order.orders_id}, this.checked)">
                                                <label class="form-check-label fw-bold small" for="orderVatToggle">
                                                     คิด VAT 7%
                                                </label>
                                            </div>
                                            ` : ''}
                                            <span class="ms-3 fw-bold fs-5">ยอดสุทธิรวมทั้งสิ้น</span>
                                        </td>
                                        <td class="text-end fw-bold text-primary fs-5" style="white-space: nowrap;">
                                            <span class="modal-grand-total">${grandtotal.toLocaleString(undefined, {minimumFractionDigits: 2})}</span> 
                                            <span class="fs-6 fw-normal text-muted">บาท</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="border-0">
                                        <td colspan="4" class="text-end border-0 py-1">มัดจำ/ชำระแล้ว:</td>
                                        <td class="text-end fw-bold border-0 py-1 text-success modal-paid-amount">${paidAmount.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                        <td class="text-center border-0 py-1">
                                            <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer mx-auto shadow-sm-hover" 
                                                 style="width: 24px; height: 24px; border-radius: 4px; cursor: pointer !important; border: 1px solid #dee2e6 !important;" 
                                                 onclick="editPaidAmountModal(${order.orders_id}, ${paidAmount})"
                                                 title="แก้ไขยอดมัดจำ">
                                                <i data-lucide="edit-2" class="text-primary" style="width:12px; height:12px;"></i>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-0">
                                        <td colspan="4" class="text-end border-0 py-1">ยอดคงเหลือ:</td>
                                        <td class="text-end fw-bold border-0 py-1 ${balance > 0 ? 'text-danger' : 'text-muted'} modal-balance">${balance.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                        <td class="border-0"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Shipping & Notes (Moved from right column) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i data-lucide="truck" class="me-2"></i>การจัดส่งและหมายเหตุ</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">บริษัทขนส่ง:</label>
                                <select id="transcatSelect" class="form-select form-select-sm" onchange="updateTrackingLinkBtn()" data-transcats='${JSON.stringify(data.transcats)}'>
                                    <option value="">-- ยังไม่ระบุ --</option>
                                    ${data.transcats.map(t => `
                                        <option value="${t.transcat_id}" data-link="${t.transcat_link || ''}" ${t.transcat_id == order.transcat_id ? 'selected' : ''}>
                                            ${t.transcat_name}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">เลขพัสดุ (Tracking):</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="trackingInput" class="form-control form-control-sm" 
                                           value="${order.orders_tracking || ''}" placeholder="ระบุเลขพัสดุ..."
                                           oninput="updateTrackingLinkBtn()">
                                    <a id="trackingLinkBtn" href="#" target="_blank" rel="noopener noreferrer"
                                       class="btn btn-sm btn-success d-none" title="คลิกตรวจสอบสถานะพัสดุ">
                                        <i data-lucide="external-link" style="width:14px; height:14px;"></i>
                                    </a>
                                </div>
                                <div id="trackingLinkHint" class="mt-1" style="font-size: 0.72rem;"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-danger"><i data-lucide="message-circle" class="me-1"></i>หมายเหตุถึงลูกค้า (พิมพ์ในบิล):</label>
                                <textarea id="orderMsgInput" class="form-control form-control-sm" rows="3" 
                                          placeholder="เช่น ขอบคุณที่อุดหนุน, ของแถมอยู่ในกล่อง...">${order.orders_msg || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer & Address -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i data-lucide="user" class="me-2"></i>ข้อมูลลูกค้า</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>ชื่อ:</strong> ${order.user_name} ${order.user_lastname}</p>
                        <p class="mb-1"><strong>อีเมล:</strong> ${order.user_email}</p>
                        <p class="mb-0"><strong>เบอร์โทร:</strong> ${order.user_tel}</p>
                    </div>
                </div>

                <div id="shippingAddressCardContainer" class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i data-lucide="map-pin" class="me-2"></i>ที่อยู่จัดส่ง</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>ชื่อผู้รับ:</strong> <span class="fw-bold text-primary">${address.addr_name}</span> ${address.addr_mobile ? `(${address.addr_mobile})` : ''}</p>
                        <p class="mb-1"><strong>ที่อยู่:</strong> ${address.addr_detail} ${address.addr_detail2 || ''}</p>
                        <p class="mb-1"><strong>จังหวัด:</strong> ${address.province_name || ''} ${address.addr_postcode || ''}</p>
                        ${address.addr_forword ? `<p class="mt-2 mb-0 small fw-bold" style="color: #157347;"><i data-lucide="info" class="me-1" style="width:12px; height:12px;"></i>${address.addr_forword}</p>` : ''}
                        ${!isLocked ? `
                        <div class="mt-3 d-flex gap-2">
                            <button onclick="changeAddress(${order.orders_id}, ${order.user_id}, ${order.addr_id}, 'receiver')" class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i data-lucide="map-pin" class="me-1" style="width:14px; height:14px;"></i> เปลี่ยนที่อยู่
                            </button>
                            <button onclick="editAddressDetails(${address.addr_id}, ${order.orders_id})" class="btn btn-sm btn-primary">
                                <i data-lucide="edit-3" class="me-1" style="width:14px; height:14px;"></i> แก้ไข
                            </button>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <div id="billingAddressCardContainer" class="card shadow-sm mb-4 border-warning border-opacity-50">
                    <div class="card-header bg-warning bg-opacity-10 py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i data-lucide="file-text" class="me-2"></i>ที่อยู่ใบเสร็จ / ใบกำกับภาษี</h6>
                    </div>
                    <div class="card-body">
                        ${data.billing_address ? `
                            <p class="mb-1"><strong>ชื่อในบิล:</strong> <span class="fw-bold text-warning-emphasis">${data.billing_address.addr_name}</span> ${data.billing_address.addr_mobile ? `(${data.billing_address.addr_mobile})` : ''}</p>
                            <p class="mb-1"><strong>ที่อยู่:</strong> ${data.billing_address.addr_detail} ${data.billing_address.addr_detail2 || ''}</p>
                            <p class="mb-1"><strong>จังหวัด:</strong> ${data.billing_address.province_name || ''} ${data.billing_address.addr_postcode || ''}</p>
                            ${data.billing_address.addr_tax_id ? `<p class="mb-1"><strong>Tax ID:</strong> ${data.billing_address.addr_tax_id}</p>` : ''}
                        ` : `
                            <div class="text-center py-2 text-muted small">
                                <i data-lucide="info" class="d-block mx-auto mb-1" style="width:16px; height:16px;"></i>
                                ยังไม่ได้ระบุที่อยู่ใบเสร็จแยกต่างหาก<br>(จะใช้ที่อยู่จัดส่งแทน)
                            </div>
                        `}
                        
                        ${!isLocked ? `
                        <div class="mt-3 d-flex gap-2">
                            <button onclick="changeAddress(${order.orders_id}, ${order.user_id}, ${order.billing_addr_id || 0}, 'billing')" class="btn btn-sm btn-outline-warning flex-grow-1">
                                <i data-lucide="map-pin" class="me-1" style="width:14px; height:14px;"></i> ${data.billing_address ? 'เปลี่ยนที่อยู่' : 'ระบุแยก'}
                            </button>
                            ${data.billing_address ? `
                            <button onclick="editAddressDetails(${data.billing_address.addr_id}, ${order.orders_id})" class="btn btn-sm btn-warning">
                                <i data-lucide="edit-3" class="me-1" style="width:14px; height:14px;"></i> แก้ไข
                            </button>
                            ` : ''}
                        </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Payment & Sales Channel Management -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i data-lucide="credit-card" class="me-2"></i>การชำระเงินและช่องทางขาย</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ช่องทางชำระเงิน:</label>
                            <select id="paycatSelect" class="form-select form-select-sm" onchange="updatePaycatSync(this.value)">
                                ${data.paycats.map(p => `
                                    <option value="${p.paycat_id}" ${p.paycat_id == order.paycat_id ? 'selected' : ''}>
                                        ${p.paycat_name}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ช่องทางขาย (Sales Channel):</label>
                            <select id="channelSelect" class="form-select form-select-sm">
                                <option value="">-- ยังไม่ระบุ --</option>
                                ${data.channels.map(c => `
                                    <option value="${c.channel_code}" ${c.channel_code === order.orders_channel ? 'selected' : ''}>
                                        ${c.channel_name}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ประเทศลูกค้า (Country):</label>
                            <select id="countrySelect" class="form-select form-select-sm">
                                ${data.countries.map(ct => `
                                    <option value="${ct.country_id}" ${ct.country_id == order.orders_country_id ? 'selected' : ''}>
                                        ${ct.country_name} (${ct.country_code})
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ผู้แนะนำ / ตัวแทน / Therapist:</label>
                            <div class="input-group input-group-sm">
                                <select id="agentSelect" class="form-select form-select-sm">
                                    <option value="">-- ไม่ระบุ --</option>
                                    ${(() => {
                                        let agentList = [...data.agents];
                                        if (data.current_agent) {
                                            const exists = agentList.some(a => a.user_id == data.current_agent.user_id);
                                            if (!exists) {
                                                agentList.push(data.current_agent);
                                            }
                                        }
                                        return agentList.map(a => `
                                            <option value="${a.user_id}" ${a.user_id == order.agent_user_id ? 'selected' : ''}>
                                                ${a.user_name} ${a.user_lastname} (${a.role})
                                            </option>
                                        `).join('');
                                    })()}
                                </select>
                                <button class="btn btn-outline-secondary" type="button" onclick="searchOtherAgentEdit()" title="ค้นหาตัวแทนอื่น...">
                                    <i data-lucide="search" style="width:12px;height:12px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing & Accounting -->
                <div class="card shadow-sm mb-4 border-info">
                    <div class="card-header bg-info bg-opacity-10 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-info"><i data-lucide="file-text" class="me-2"></i>ระบบบัญชี (Billing)</h6>
                        <span class="badge ${order.peak_status == 1 ? 'bg-success' : (order.peak_status == 2 ? 'bg-danger' : 'bg-secondary')}">
                            ${order.peak_status == 1 ? 'Sync แล้ว' : (order.peak_status == 2 ? 'Error' : 'ยังไม่ได้ส่ง')}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ประเภทบิลที่ต้องการ:</label>
                            <select id="billingTypeSelect" class="form-select form-select-sm">
                                <option value="none" ${order.billing_type === 'none' ? 'selected' : ''}>-- ไม่ระบุ (ยังไม่ออกบิล) --</option>
                                <option value="receipt" ${order.billing_type === 'receipt' ? 'selected' : ''}>ใบเสร็จรับเงิน (Regular Receipt)</option>
                                <option value="tax_invoice" ${order.billing_type === 'tax_invoice' ? 'selected' : ''}>ใบกำกับภาษี (Tax Invoice)</option>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-8 mb-3">
                                <label class="form-label small fw-bold">เลขผู้เสียภาษี (Tax ID):</label>
                                <input type="text" id="taxIdInput" class="form-control form-control-sm" 
                                       value="${order.tax_id || (data.billing_address ? data.billing_address.addr_tax_id : address.addr_tax_id) || ''}" placeholder="ระบุเลขผู้เสียภาษี">
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label small fw-bold">รหัสสาขา:</label>
                                <input type="text" id="branchCodeInput" class="form-control form-control-sm" 
                                       value="${order.branch_code || (data.billing_address ? data.billing_address.addr_branch : address.addr_branch) || '00000'}" placeholder="00000">
                            </div>
                        </div>
                        
                        ${order.peak_res_code ? `
                            <div class="alert alert-light border small py-2 mb-2">
                                <strong>เลขที่ใน PEAK:</strong> ${order.peak_res_code}
                            </div>
                        ` : ''}

                        <button class="btn btn-sm btn-outline-info w-100 mt-1" onclick="syncToPeak(${order.orders_id})" disabled>
                            <i data-lucide="refresh-cw" class="me-1" style="width:14px; height:14px;"></i> ส่งข้อมูลไป PEAK (Coming Soon)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('modalContent').innerHTML = html;
    lucide.createIcons();
}

/**
 * Add New Address (SweetAlert Form)
 */
async function addNewAddress(userId, orderId, type = 'receiver') {
    // Show modal immediately with loading state
    Swal.fire({
        title: 'เพิ่มที่อยู่ใหม่',
        html: `
            <div class="text-center p-5">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div class="text-muted small">กำลังโหลดข้อมูลจังหวัด...</div>
            </div>
        `,
        width: '600px',
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'ยกเลิก',
        allowOutsideClick: false
    });
    
    // Fix focus issue for Bootstrap Modal + SweetAlert2
    const parentModal = document.getElementById('addressSelectModal') || document.getElementById('orderModal');
    if (parentModal) {
        parentModal.removeAttribute('tabindex');
    }
    
    try {
        const response = await fetch(`${ADMIN_URL}/api/get_provinces.php`);
        const data = await response.json();
        
        if (!data.success) throw new Error(data.message);
        
    const provinces = data.provinces;
    let provinceOptions = '<option value="">เลือกจังหวัด</option>';
    provinces.forEach(p => {
        provinceOptions += `<option value="${p.id}">${p.name_th}</option>`;
    });

    // Global Focus Fix for Bootstrap 5 + Swal
    const fixFocus = (e) => {
        if (e.target.closest('.swal2-container')) {
            e.stopImmediatePropagation();
        }
    };
    document.addEventListener('focusin', fixFocus, true);

        // Update existing Swal with form
        const result = await Swal.fire({
            title: 'เพิ่มที่อยู่ใหม่',
            html: `
                <form id="addNewAddressForm" class="text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อผู้รับ / ชื่อที่อยู่</label>
                        <input type="text" id="swal-name" class="form-control" placeholder="เช่น นายสมชาย หรือ บ้านแม่" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ที่อยู่ (เลขที่, ถนน, หมู่บ้าน)</label>
                        <textarea id="swal-detail" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">เพิ่มเติม (แขวง/ตำบล/เขต/อำเภอ)</label>
                        <input type="text" id="swal-detail2" class="form-control" placeholder="ระบุเพิ่มเติม">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">จังหวัด</label>
                        <select id="swal-province" class="form-select" required>
                            ${provinceOptions}
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">รหัสไปรษณีย์</label>
                            <input type="text" id="swal-postcode" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">เบอร์โทรศัพท์</label>
                            <input type="text" id="swal-mobile" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8 mb-3">
                            <label class="form-label small fw-bold">เลขผู้เสียภาษี (Tax ID)</label>
                            <input type="text" id="swal-tax-id" class="form-control" placeholder="ระบุเลขผู้เสียภาษี">
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label small fw-bold">รหัสสาขา</label>
                            <input type="text" id="swal-branch" class="form-control" value="00000" placeholder="00000">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ประเภทที่อยู่</label>
                        <select id="swal-addr-type" class="form-select">
                            <option value="1" selected>🏠 ที่บ้าน</option>
                            <option value="2">🏢 ที่ทำงาน</option>
                            <option value="3">📄 ที่อยู่สำหรับออกบิล/ใบกำกับภาษี</option>
                            <option value="4">📦 ใช้เป็นผู้ส่งในนาม</option>
                            <option value="5">👤 ผู้รับแทน</option>
                            <option value="6">🏕️ ที่อยู่รับสินค้าชั่วคราว</option>
                        </select>
                    </div>
                </form>
            `,
            width: '600px',
            focusConfirm: false,
            showConfirmButton: true,
            showCancelButton: true,
            confirmButtonText: 'บันทึกและใช้ที่อยู่นี้',
            cancelButtonText: 'ยกเลิก',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                const addrData = {
                    user_id: userId,
                    addr_name: document.getElementById('swal-name').value,
                    addr_detail: document.getElementById('swal-detail').value,
                    addr_detail2: document.getElementById('swal-detail2').value,
                    provinces_id: document.getElementById('swal-province').value,
                    addr_postcode: document.getElementById('swal-postcode').value,
                    addr_mobile: document.getElementById('swal-mobile').value,
                    addr_tax_id: document.getElementById('swal-tax-id').value,
                    addr_branch: document.getElementById('swal-branch').value,
                    addr_type: document.getElementById('swal-addr-type').value
                };

                if (!addrData.addr_name || !addrData.addr_detail || !addrData.provinces_id || !addrData.addr_postcode || !addrData.addr_mobile) {
                    Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                    return false;
                }

                // Show spinner on the target address card immediately
                const loaderHtml = `
                    <div class="d-flex flex-column align-items-center justify-content-center py-5">
                        <div class="spinner-border text-primary mb-2" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
                        <div class="text-muted small">กำลังเพิ่มที่อยู่ใหม่...</div>
                    </div>
                `;
                if (type === 'receiver' || type === 'sender') {
                    const container = document.getElementById('shippingAddressCardContainer');
                    if (container) container.innerHTML = loaderHtml;
                } else if (type === 'billing') {
                    const container = document.getElementById('billingAddressCardContainer');
                    if (container) container.innerHTML = loaderHtml;
                }

                try {
                    // 1. Save new address
                    const saveRes = await fetch(`${ADMIN_URL}/api/save_new_address.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(addrData)
                    });
                    const saveData = await saveRes.json();
                    if (!saveData.success) throw new Error(saveData.message);

                    // 2. Link to order
                    const linkData = new FormData();
                    linkData.append('order_id', orderId);
                    linkData.append('type', type);
                    linkData.append('addr_id', saveData.addr_id);

                    const linkRes = await fetch(`${ADMIN_URL}/api/save_order_address.php`, {
                        method: 'POST',
                        body: linkData
                    });
                    const linkResult = await linkRes.json();
                    if (!linkResult.success) throw new Error(linkResult.message);

                    return linkResult;
                } catch (error) {
                    // Restore card content on error
                    if (orderId) refreshOrderAddressSectionOnly(orderId);
                    Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`);
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        });

        // Remove global focus fix
        document.removeEventListener('focusin', fixFocus, true);

        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ',
                showConfirmButton: false,
                timer: 800
            });

            // Close intermediate modals if any
            const addrSelectModalEl = document.getElementById('addressSelectModal');
            const addrSelectModal = bootstrap.Modal.getInstance(addrSelectModalEl);
            if (addrSelectModal) addrSelectModal.hide();

            // Refresh UI
            loadOrderTable(currentPage, currentSearchQuery);
            if (orderId) refreshOrderAddressSectionOnly(orderId);
        } else {
            // Restore tabindex if user cancelled
            if (parentModal) parentModal.setAttribute('tabindex', '-1');
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการประมวลผล', 'error');
    }
}

// saveNewAddressAndSelect is removed as it's merged into addNewAddress preConfirm

/**
 * Quick Edit Order Msg
 */
async function editOrderMsg(orderId, currentMsg) {
    const { value: newMsg } = await Swal.fire({
        title: 'แก้ไขหมายเหตุถึงลูกค้า (แสดงในบิล)',
        input: 'textarea',
        inputLabel: 'ข้อความนี้จะปรากฏที่ส่วนท้ายของใบเสร็จ/บิล',
        inputValue: currentMsg,
        inputPlaceholder: 'เช่น ขอบคุณที่อุดหนุนครับ...',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        inputAttributes: {
            'rows': 4
        }
    });

    if (newMsg !== undefined) {
        Swal.fire({ title: 'กำลังบันทึก...', didOpen: () => Swal.showLoading() });
        
        try {
            const response = await fetch(`${ADMIN_URL}/api/update_order_status.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    orders_id: orderId,
                    orders_msg: newMsg
                })
            });
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'อัปเดตหมายเหตุสำเร็จ', timer: 800, showConfirmButton: false });
                loadOrderTable(currentPage, document.getElementById('searchInput').value);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'ไม่สามารถบันทึกหมายเหตุได้', 'error');
        }
    }
}

/**
 * Recalculate Modal Totals (Client Side)
 */
function recalculateModalTotals() {
    recalculateOrderTotalsLocally();
}

/**
 * Sync Paycat selects
 */
function updatePaycatSync(val) {
    const mainSelect = document.getElementById('paycatSelect');
    if (mainSelect) {
        mainSelect.value = val;
    }
    const summarySelect = document.getElementById('paycatSelectSummary');
    if (summarySelect) {
        summarySelect.value = val;
    }
    recalculateModalTotals();
}

/**
 * Update Order Status
 */
async function updateStatus(btn) {
    const statusId = document.getElementById('statusSelect').value;
    const notifyEmail = document.getElementById('notifyEmail').checked;

    // Button loading state
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>กำลังบันทึก...';
    btn.disabled = true;

    try {
        const response = await fetch(`${ADMIN_URL}/api/update_order_status.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                orders_id: currentOrderId,
                orsts_id: statusId,
                notify_email: notifyEmail,
                paycat_id: document.getElementById('paycatSelect').value,
                orders_discount: document.getElementById('modalDiscountInput').value,
                orders_delicost: document.getElementById('modalShippingInput').value,
                transcat_id: document.getElementById('transcatSelect').value,
                orders_tracking: document.getElementById('trackingInput').value,
                orders_msg: document.getElementById('orderMsgInput').value,
                billing_type: document.getElementById('billingTypeSelect').value,
                tax_id: document.getElementById('taxIdInput').value,
                branch_code: document.getElementById('branchCodeInput').value,
                orders_channel: document.getElementById('channelSelect').value,
                orders_country_id: document.getElementById('countrySelect').value,
                agent_user_id: document.getElementById('agentSelect').value || null
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: 'อัปเดตสถานะเรียบร้อยแล้ว',
                timer: 1500,
                showConfirmButton: false
            });
            const searchInput = document.getElementById('searchInput');
            const searchVal = searchInput ? searchInput.value : '';
            if (typeof loadOrderTable === 'function' && typeof currentPage !== 'undefined') {
                loadOrderTable(currentPage, searchVal);
            }
            const modalEl = document.getElementById('orderDetailModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        } else {
            Swal.fire('Error', data.message || 'เกิดข้อผิดพลาด', 'error');
        }
        btn.innerHTML = originalContent;
        btn.disabled = false;
    } catch (error) {
        btn.innerHTML = originalContent;
        btn.disabled = false;
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถอัปเดตสถานะได้'
        });
    }
}

// --- Utility Functions ---
function copyToClipboard(text, element) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCopySuccess(element);
        }).catch(err => {
            console.error('Async: Could not copy text: ', err);
            fallbackCopyTextToClipboard(text, element);
        });
    } else {
        fallbackCopyTextToClipboard(text, element);
    }
}

function fallbackCopyTextToClipboard(text, element) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess(element);
        } else {
            console.error('Fallback: Oops, unable to copy');
            Swal.fire('Error', 'ไม่สามารถคัดลอกได้', 'error');
        }
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
        Swal.fire('Error', 'ไม่สามารถคัดลอกได้', 'error');
    }

    document.body.removeChild(textArea);
}

function showCopySuccess(element) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000
    });
    Toast.fire({
        icon: 'success',
        title: 'บันทึกในคลิปบอร์ดแล้ว'
    });

    if (element) {
        element.classList.add('text-success');
        setTimeout(() => {
            element.classList.remove('text-success');
        }, 2000);
    }
}

// --- Customer Functions ---
function openCustomerModal(userId, orderId = 0) {
    const modalEl = document.getElementById('customerModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) {
        modal = new bootstrap.Modal(modalEl);
    }

    // Show loading state or clear form
    document.getElementById('customerForm').reset();
    document.getElementById('customerOrderId').value = orderId; // Set Order ID

    // Show modal immediately with loading overlay
    modal.show();
    const loadingOverlay = document.getElementById('customerModalLoadingOverlay');
    if (loadingOverlay) loadingOverlay.classList.remove('d-none');

    fetch(`${ADMIN_URL}/api/get_user_mini.php?user_id=${userId}&orders_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (loadingOverlay) loadingOverlay.classList.add('d-none');
            
            if (data.success) {
                const user = data.data;
                document.getElementById('customerUserId').value = user.user_id;
                document.getElementById('customerName').value = user.user_name || '';
                document.getElementById('customerLastname').value = user.user_lastname || '';
                
                const elNickname = document.getElementById('customerNickname');
                if(elNickname) elNickname.value = user.user_nickname || '';
                
                document.getElementById('customerMobile').value = user.user_mobile || '';
                document.getElementById('customerEmail').value = user.user_email || '';
                
                const elNote = document.getElementById('customerNote');
                if(elNote) elNote.value = user.user_note || '';
                
                const elMap = document.getElementById('customerGoogleMap');
                if(elMap) elMap.value = user.user_google_map || '';

                const btnOpenMap = document.getElementById('btnOpenMap');
                if (btnOpenMap) {
                    if (user.user_google_map && user.user_google_map.trim() !== '') {
                        btnOpenMap.href = user.user_google_map;
                        btnOpenMap.style.display = 'inline-flex';
                        btnOpenMap.style.alignItems = 'center';
                    } else {
                        btnOpenMap.style.display = 'none';
                        btnOpenMap.href = '#';
                    }
                }

                const elWebsite = document.getElementById('customerWebsite');
                if(elWebsite) elWebsite.value = user.user_website || '';

                const btnOpenWebsite = document.getElementById('btnOpenWebsite');
                if (btnOpenWebsite) {
                    if (user.user_website && user.user_website.trim() !== '') {
                        let websiteUrl = user.user_website.trim();
                        if (!/^https?:\/\//i.test(websiteUrl)) {
                            websiteUrl = 'http://' + websiteUrl;
                        }
                        btnOpenWebsite.href = websiteUrl;
                        btnOpenWebsite.style.display = 'inline-flex';
                        btnOpenWebsite.style.alignItems = 'center';
                    } else {
                        btnOpenWebsite.style.display = 'none';
                        btnOpenWebsite.href = '#';
                    }
                }
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }

            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'ไม่สามารถโหลดข้อมูลลูกค้าได้', 'error');
        });
}

function saveCustomerInfo() {
    const form = document.getElementById('customerForm');
    const formData = new FormData(form);

    fetch(`${ADMIN_URL}/api/save_user_mini.php`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById('customerModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();

                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    showConfirmButton: false,
                    timer: 1500
                });

                // Reload table to show updated info
                const searchVal = document.getElementById('searchInput').value;
                loadOrderTable(currentPage, searchVal);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
        });
}

function reorder(orderId) {
    Swal.fire({
        title: 'ยืนยันการคัดลอกออเดอร์?',
        text: 'คุณต้องการสร้างออเดอร์ใหม่โดยใช้ข้อมูลจากออเดอร์นี้ใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, สร้างเลย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังสร้างออเดอร์...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(ADMIN_URL + '/api/reorder.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: 'สร้างออเดอร์ใหม่เรียบร้อยแล้ว',
                        timer: 1500
                    }).then(() => {
                        loadOrderTable(1);
                        viewOrder(data.new_order_id);
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message || 'เกิดข้อผิดพลาดในการสร้างออเดอร์', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            });
        }
    });
}

function editAddress(orderId, userId) {
    // Show modal immediately with loading state
    Swal.fire({
        title: 'แก้ไขที่อยู่ผู้รับ',
        html: `
            <div class="text-center p-5">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div class="text-muted small">กำลังดึงข้อมูลที่อยู่...</div>
            </div>
        `,
        width: '600px',
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'ยกเลิก',
        allowOutsideClick: false
    });

    // Fetch address details AND provinces
    Promise.all([
        fetch(`../api/get_order_address_details.php?order_id=${orderId}`).then(res => {
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            return res.json();
        }),
        fetch(`../api/get_provinces.php`).then(res => {
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            return res.json();
        })
    ])
        .then(([addrData, provinceData]) => {
            if (addrData.success && provinceData.success) {
                const addr = addrData.data;
                const provinces = provinceData.provinces;

                // Generate province options
                let provinceOptions = '<option value="">เลือกจังหวัด</option>';
                provinces.forEach(p => {
                    const selected = (addr.provinces_id == p.id) ? 'selected' : '';
                    provinceOptions += `<option value="${p.id}" ${selected}>${p.name_th}</option>`;
                });

                // Update Swal with form
                Swal.fire({
                    html: `
            <form id="editAddressForm" class="text-start">
                <input type="hidden" name="addr_id" value="${addr.addr_id}">
                <input type="hidden" name="order_id" value="${orderId}">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold mb-1">ชื่อผู้รับ</label>
                    <input type="text" class="form-control form-control-sm" name="addr_name" value="${addr.addr_name}" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold mb-1">ที่อยู่ (เลขที่, หมู่บ้าน, ถนน)</label>
                    <textarea class="form-control form-control-sm" name="addr_detail" rows="3" required>${addr.addr_detail}</textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold mb-1">จังหวัด</label>
                        <select class="form-select form-select-sm" name="provinces_id" required>
                            ${provinceOptions}
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold mb-1">เพิ่มเติม (แขวง/ตำบล/เขต/อำเภอ)</label>
                        <input type="text" class="form-control form-control-sm" name="addr_detail2" value="${addr.addr_detail2 || ''}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold mb-1">รหัสไปรษณีย์</label>
                        <input type="text" class="form-control form-control-sm" name="addr_postcode" value="${addr.addr_postcode}" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold mb-1">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control form-control-sm" name="addr_mobile" value="${addr.addr_mobile}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-8 mb-3">
                        <label class="form-label small fw-bold mb-1">เลขผู้เสียภาษี (Tax ID)</label>
                        <input type="text" class="form-control form-control-sm" name="addr_tax_id" value="${addr.addr_tax_id || ''}" placeholder="ระบุเลขผู้เสียภาษี">
                    </div>
                    <div class="col-4 mb-3">
                        <label class="form-label small fw-bold mb-1">รหัสสาขา</label>
                        <input type="text" class="form-control form-control-sm" name="addr_branch" value="${addr.addr_branch || '00000'}" placeholder="00000">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold mb-1">ประเภทที่อยู่</label>
                    <select class="form-select form-select-sm" name="addr_type">
                        <option value="1" ${addr.addr_type == 1 ? 'selected' : ''}>🏠 ที่บ้าน</option>
                        <option value="2" ${addr.addr_type == 2 ? 'selected' : ''}>🏢 ที่ทำงาน</option>
                        <option value="3" ${addr.addr_type == 3 ? 'selected' : ''}>📄 ที่อยู่สำหรับออกบิล/ใบกำกับภาษี</option>
                        <option value="4" ${addr.addr_type == 4 ? 'selected' : ''}>📦 ใช้เป็นผู้ส่งในนาม</option>
                        <option value="5" ${addr.addr_type == 5 ? 'selected' : ''}>👤 ผู้รับแทน</option>
                        <option value="6" ${addr.addr_type == 6 ? 'selected' : ''}>🏕️ ที่อยู่รับสินค้าชั่วคราว</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold mb-1 text-danger">หมายเหตุที่อยู่ (สำหรับพนักงาน)</label>
                    <input type="text" class="form-control form-control-sm border-danger" name="addr_forword" value="${addr.addr_forword || ''}" placeholder="ระบุหมายเหตุเพิ่มเติมสำหรับที่อยู่นี้">
                </div>
            </form>
        `,
                    showConfirmButton: true,
                    confirmButtonText: 'บันทึก',
                    showLoaderOnConfirm: true,
                    preConfirm: async () => {
                        const form = document.getElementById('editAddressForm');
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return false;
                        }
                        const formData = new FormData(form);
                        try {
                            const response = await fetch(`${ADMIN_URL}/api/update_address_details.php`, {
                                method: 'POST',
                                body: formData
                            });
                            const data = await response.json();
                            if (!data.success) {
                                throw new Error(data.message || 'บันทึกไม่สำเร็จ');
                            }
                            return data;
                        } catch (error) {
                            Swal.showValidationMessage(`Error: ${error.message}`);
                        }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: result.value.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            loadOrderTable(currentPage);
                        });
                    }
                });
                
                // Refresh Icons
                lucide.createIcons();
            } else {
                Swal.fire('Error', 'ไม่สามารถโหลดข้อมูลได้', 'error');
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                text: err.message || 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง',
                footer: '<small>โปรดแจ้งผู้ดูแลระบบหากปัญหายังคงอยู่</small>'
            });
        });
}

function editItemMemo(ordetailId, orderId, currentMemo) {
    Swal.fire({
        title: 'หมายเหตุสินค้า',
        input: 'text',
        inputLabel: 'ระบุหมายเหตุสำหรับรายการสินค้านี้',
        inputValue: currentMemo,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        inputPlaceholder: 'เช่น สูตรเย็น, เพิ่มของแถม...',
        didOpen: () => {
            setTimeout(() => {
                const input = Swal.getInput();
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 150);
        },
        preConfirm: (memo) => {
            Swal.showLoading();
            return fetch(`${ADMIN_URL}/api/update_ordetail_memo.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ordetail_id=${ordetailId}&memo=${encodeURIComponent(memo)}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return memo;
            })
            .catch(error => {
                Swal.hideLoading();
                Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'บันทึกเรียบร้อย',
                timer: 1000,
                showConfirmButton: false
            }).then(() => {
                const memo = result.value;
                const container = document.getElementById(`memo-container-${ordetailId}`);
                if (container) {
                    const encodedMemo = memo ? memo.replace(/'/g, "\\'") : '';
                    const safeMemo = memo ? memo.replace(/</g, "&lt;").replace(/>/g, "&gt;") : '';
                    const productCode = container.querySelector('small.text-muted').innerText;
                    
                    container.innerHTML = `
                        <small class="text-muted">${productCode}</small>
                        <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer" 
                             style="width: 20px; height: 20px; border-radius: 4px; cursor: pointer !important;" 
                             onclick="editItemMemo(${ordetailId}, ${orderId}, '${encodedMemo}')"
                             title="หมายเหตุสินค้า">
                            <i data-lucide="message-square" class="${safeMemo ? 'text-info' : 'text-muted'}" style="width:10px; height:10px;"></i>
                        </div>
                        ${safeMemo ? '<div class="mt-1"><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 fw-normal" style="font-size: 0.7rem; white-space: normal; text-align: left;"><i data-lucide="info" class="me-1" style="width:10px; height:10px;"></i>' + safeMemo + '</span></div>' : ''}
                    `;
                    lucide.createIcons();
                }
                loadOrderTable(currentPage);
            });
        }
    });
}

function changeSender(orderId, userId) {
    const modalEl = document.getElementById('addressSelectModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);

    document.getElementById('addressSelectTitle').innerText = 'เลือกผู้ส่ง (Sender)';
    const list = document.getElementById('addressList');
    list.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    fetch(`${ADMIN_URL}/api/get_customer_addresses.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                // Add New Address Button
                html += `<button type="button" class="list-group-item list-group-item-action list-group-item-info text-center fw-bold py-3" onclick="addNewAddress(${userId}, ${orderId}, 'sender')">
                        <i data-lucide="plus-circle" class="me-2" style="width:18px; height:18px;"></i> เพิ่มที่อยู่ผู้ส่งใหม่
                        </button>`;

                // Add Shop Default Option
                html += `<button type="button" class="list-group-item list-group-item-action" onclick="saveOrderAddress(${orderId}, 'sender', 0, this)">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i data-lucide="store" style="width:14px; height:14px;"></i> Shop Default</h6>
                        </div>
                        <small class="text-muted">ใช้ที่อยู่ร้านค้า</small>
                        </button>`;

                // Add Customer Addresses
                data.data.forEach(addr => {
                    const typeInfo = getAddressTypeInfo(addr.addr_type);
                    html += `<button type="button" class="list-group-item list-group-item-action" onclick="saveOrderAddress(${orderId}, 'sender', ${addr.addr_id}, this)">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="mb-0 fw-bold">${addr.addr_name}</h6>
                                    <span class="badge ${typeInfo.badge}" style="font-size: 10px;">
                                        <i data-lucide="${typeInfo.icon}" style="width:10px;height:10px;vertical-align:middle;"></i> ${typeInfo.label}
                                    </span>
                                </div>
                                <small class="fw-bold">${addr.addr_mobile}</small>
                            </div>
                            <p class="mb-1 small text-muted">${addr.addr_detail} ${addr.addr_detail2 || ''} ${addr.addr_postcode}</p>
                            </button>`;
                });
                list.innerHTML = html;
                lucide.createIcons();
            } else {
                list.innerHTML = `<div class="text-danger p-3 text-center">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            list.innerHTML = '<div class="text-danger p-3 text-center">ไม่สามารถโหลดข้อมูลที่อยู่ได้</div>';
        });
}

function changeAddress(orderId, userId, currentAddrId = null, target = 'receiver') {
    const modalEl = document.getElementById('addressSelectModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);

    const titleMap = {
        'receiver': 'เปลี่ยนที่อยู่จัดส่ง (Receiver)',
        'billing': 'เปลี่ยนที่อยู่ใบเสร็จ / ใบกำกับภาษี (Billing)'
    };

    document.getElementById('addressSelectTitle').innerText = titleMap[target] || 'เปลี่ยนที่อยู่';
    const list = document.getElementById('addressList');
    list.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    fetch(`${ADMIN_URL}/api/get_customer_addresses.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                // Add New Address Button
                html += `<button type="button" class="list-group-item list-group-item-action list-group-item-success text-center fw-bold py-3" onclick="addNewAddress(${userId}, ${orderId}, '${target}')">
                        <i data-lucide="plus-circle" class="me-2" style="width:18px; height:18px;"></i> เพิ่มที่อยู่ใหม่สำหรับลูกค้าท่านนี้
                        </button>`;
                
                // Add "Use same as Shipping" option if target is billing
                if (target === 'billing') {
                    html += `<button type="button" class="list-group-item list-group-item-action list-group-item-light text-center py-3" onclick="saveOrderAddress(${orderId}, 'billing', 0, this)">
                            <i data-lucide="copy" class="me-2" style="width:16px; height:16px;"></i> ใช้ที่อยู่เดียวกับที่อยู่จัดส่ง (เคลียร์ค่าที่แยกไว้)
                            </button>`;
                }

                if (data.data.length === 0) {
                    html += '<div class="text-center p-3 text-muted">ไม่พบที่อยู่ลูกค้า</div>';
                } else {
                    data.data.forEach(addr => {
                        const isCurrent = currentAddrId && addr.addr_id == currentAddrId;
                        const activeClass = isCurrent ? 'border-primary bg-primary bg-opacity-10' : '';
                        const checkIcon = isCurrent ? '<i data-lucide="check-circle" class="text-primary" style="width:16px; height:16px;"></i>' : '';
                        const typeInfo = getAddressTypeInfo(addr.addr_type);

                        // If target is billing, highlight billing addresses
                        const isBillingType = addr.addr_type == 3;
                        const highlightClass = (target === 'billing' && isBillingType) ? 'border-warning border-2' : '';

                        html += `
                            <button type="button" class="list-group-item list-group-item-action ${activeClass} ${highlightClass} d-flex align-items-center" 
                                    onclick="saveOrderAddress(${orderId}, '${target}', ${addr.addr_id}, this)">
                                <div class="flex-grow-1">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="mb-0 fw-bold">${addr.addr_name}</h6>
                                            <span class="badge ${typeInfo.badge}" style="font-size: 10px;">
                                                <i data-lucide="${typeInfo.icon}" style="width:10px;height:10px;vertical-align:middle;"></i> ${typeInfo.label}
                                            </span>
                                        </div>
                                        <small class="fw-bold">${addr.addr_mobile}</small>
                                    </div>
                                    <p class="mb-1 small text-dark">${addr.addr_detail} ${addr.addr_detail2 || ''} ${addr.addr_postcode}</p>
                                    ${isCurrent ? '<span class="badge bg-primary">ที่อยู่ปัจจุบัน</span>' : ''}
                                </div>
                                <div class="ms-2">
                                    ${checkIcon}
                                </div>
                            </button>`;
                    });
                }
                list.innerHTML = html;
                lucide.createIcons();
            } else {
                list.innerHTML = `<div class="text-danger p-3 text-center">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            list.innerHTML = '<div class="text-danger p-3 text-center">ไม่สามารถโหลดข้อมูลที่อยู่ได้</div>';
        });
}

function saveOrderAddress(orderId, type, addrId, btnElement = null) {
    if (btnElement) {
        // Disable all buttons in the list to prevent multiple clicks
        const listGroup = btnElement.closest('.list-group');
        if (listGroup) {
            const listButtons = listGroup.querySelectorAll('.list-group-item');
            listButtons.forEach(btn => {
                btn.classList.add('disabled');
                btn.style.pointerEvents = 'none';
            });
        }
        
        // Add a spinner to the clicked button
        const spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm text-primary ms-2';
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-hidden', 'true');
        btnElement.appendChild(spinner);
    }

    // Show spinner on the target address card immediately during save operation
    const loaderHtml = `
        <div class="d-flex flex-column align-items-center justify-content-center py-5">
            <div class="spinner-border text-primary mb-2" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
            <div class="text-muted small">กำลังอัปเดตที่อยู่...</div>
        </div>
    `;
    if (type === 'receiver' || type === 'sender') {
        const container = document.getElementById('shippingAddressCardContainer');
        if (container) container.innerHTML = loaderHtml;
    } else if (type === 'billing') {
        const container = document.getElementById('billingAddressCardContainer');
        if (container) container.innerHTML = loaderHtml;
    }

    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('type', type);
    formData.append('addr_id', addrId);

    fetch(`${ADMIN_URL}/api/save_order_address.php`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById('addressSelectModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    showConfirmButton: false,
                    timer: 800
                });

                // Reload table and modal
                const searchVal = document.getElementById('searchInput').value;
                loadOrderTable(currentPage, searchVal);
                refreshOrderAddressSectionOnly(orderId);
            } else {
                Swal.fire('Error', data.message, 'error');
                // Restore card content on error
                refreshOrderAddressSectionOnly(orderId);
                // Re-enable buttons on error
                if (btnElement) {
                    const listGroup = btnElement.closest('.list-group');
                    if (listGroup) {
                        const listButtons = listGroup.querySelectorAll('.list-group-item');
                        listButtons.forEach(btn => {
                            btn.classList.remove('disabled');
                            btn.style.pointerEvents = '';
                        });
                    }
                    const spinner = btnElement.querySelector('.spinner-border');
                    if (spinner) spinner.remove();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
            // Restore card content on error
            refreshOrderAddressSectionOnly(orderId);
            // Re-enable buttons on error
            if (btnElement) {
                const listGroup = btnElement.closest('.list-group');
                if (listGroup) {
                    const listButtons = listGroup.querySelectorAll('.list-group-item');
                    listButtons.forEach(btn => {
                        btn.classList.remove('disabled');
                        btn.style.pointerEvents = '';
                    });
                }
                const spinner = btnElement.querySelector('.spinner-border');
                if (spinner) spinner.remove();
            }
        });
}

// --- User Image Functions ---
function openUserImageModal(userId, currentImage) {
    document.getElementById('userImageForm').reset();
    document.getElementById('user_image_id').value = userId;
    const img = document.getElementById('current_user_image');
    if (currentImage) {
        img.src = `${ROOT_URL}/uploads/profile/${userId}/${currentImage}`;
    } else {
        img.src = `${ADMIN_ASSETS}/images/placeholder.png`;
    }

    // Load History
    const historyContainer = document.getElementById('user_image_history');
    historyContainer.innerHTML = '<div class="text-muted small">กำลังโหลดประวัติ...</div>';
    
    fetch(`${ADMIN_URL}/api/get_user_image_history.php?user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.images.length > 0) {
                let html = '';
                data.images.forEach(img => {
                    const isActive = img.filename === currentImage ? 'border-primary' : 'opacity-75';
                    html += `
                        <div class="position-relative cursor-pointer flex-shrink-0" 
                             onclick="selectHistoryImage(${userId}, '${img.filename}')" 
                             title="เลือกรูปนี้">
                            <img src="${img.url}" 
                                 class="rounded border ${isActive}" 
                                 style="width: 60px; height: 60px; object-fit: cover; transition: all 0.2s;"
                                 onmouseover="this.style.opacity='1'; this.style.transform='scale(1.05)'"
                                 onmouseout="if(!this.classList.contains('border-primary')) this.style.opacity='0.75'; this.style.transform='scale(1)'">
                        </div>`;
                });
                historyContainer.innerHTML = html;
            } else {
                historyContainer.innerHTML = '<div class="text-muted small italic">ไม่มีประวัติรูปภาพ</div>';
            }
        })
        .catch(err => {
            console.error(err);
            historyContainer.innerHTML = '<div class="text-danger small">โหลดล้มเหลว</div>';
        });

    new bootstrap.Modal(document.getElementById('userImageModal')).show();
}

function selectHistoryImage(userId, filename) {
    Swal.fire({
        title: 'ยืนยันการเปลี่ยนรูป',
        text: 'คุณต้องการใช้รูปที่เคยอัปโหลดไว้แล้วใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ใช้รูปนี้',
        cancelButtonText: 'ยกเลิก',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`${ADMIN_URL}/api/set_user_picture_from_history.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, filename: filename })
            })
            .then(response => {
                if (!response.ok) throw new Error(response.statusText);
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'เปลี่ยนรูปสำเร็จ',
                    showConfirmButton: false,
                    timer: 800
                });
                const modal = bootstrap.Modal.getInstance(document.getElementById('userImageModal'));
                if (modal) modal.hide();
                loadOrderTable(currentPage, document.getElementById('searchInput').value);
            } else {
                Swal.fire('Error', result.value.message, 'error');
            }
        }
    });
}

function saveUserImage() {
    const form = document.getElementById('userImageForm');
    const formData = new FormData(form);
    const btn = document.getElementById('btnSaveUserImage');
    const originalHtml = btn.innerHTML;

    // Show Loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> กำลังบันทึก...';

    fetch(`${ADMIN_URL}/api/update_user_picture.php`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    showConfirmButton: false,
                    timer: 800
                });
                const modal = bootstrap.Modal.getInstance(document.getElementById('userImageModal'));
                if (modal) modal.hide();
                loadOrderTable(currentPage, document.getElementById('searchInput').value);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            Swal.fire('Error', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
        });
}

/**
 * Update the tracking link button in order detail modal based on selected carrier + tracking number
 */
function updateTrackingLinkBtn() {
    const select = document.getElementById('transcatSelect');
    const trackingInput = document.getElementById('trackingInput');
    const linkBtn = document.getElementById('trackingLinkBtn');
    const hintDiv = document.getElementById('trackingLinkHint');

    if (!select || !trackingInput || !linkBtn) return;

    const selectedOption = select.options[select.selectedIndex];
    const carrierLink = selectedOption ? (selectedOption.dataset.link || '') : '';
    const tracking = trackingInput.value.trim();

    if (carrierLink && tracking) {
        let url = '';
        if (carrierLink.includes('xxx')) {
            url = carrierLink.replace('xxx', encodeURIComponent(tracking));
        } else {
            url = carrierLink + encodeURIComponent(tracking);
        }
        linkBtn.href = url;
        linkBtn.classList.remove('d-none');
        if (hintDiv) {
            hintDiv.innerHTML = `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-success text-decoration-none" style="word-break:break-all;"><i data-lucide="external-link" style="width:10px;height:10px;"></i> ${url}</a>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } else {
        linkBtn.href = '#';
        linkBtn.classList.add('d-none');
        if (hintDiv) {
            if (carrierLink && !tracking) {
                hintDiv.innerHTML = `<span class="text-muted">ระบุเลขพัสดุเพื่อดูลิงค์</span>`;
            } else {
                hintDiv.innerHTML = '';
            }
        }
    }
}

// --- Shipping Functions ---
function openShippingModal(orderId, transcatId, tracking, delicosttrue = '') {
    document.getElementById('shipping_order_id').value = orderId;
    document.getElementById('shipping_tracking').value = tracking || '';
    
    // ตั้งค่าต้นทุนค่าส่งจริง (ถ้ามี)
    const costInput = document.getElementById('shipping_delicosttrue');
    if(costInput) {
        costInput.value = delicosttrue !== 'null' && delicosttrue !== '' ? parseFloat(delicosttrue).toFixed(2) : '';
    }

    const select = document.getElementById('shipping_transcat_id');
    select.innerHTML = '<option value="">กำลังโหลด...</option>';
    
    // Show modal immediately for better UX
    const modalEl = document.getElementById('shippingModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);
    modal.show();

    // Fetch only the necessary options
    fetch(`${ADMIN_URL}/api/get_transcat_options.php`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                select.innerHTML = '<option value="">-- เลือกขนส่ง --</option>';
                res.data.forEach(t => {
                    const selected = t.transcat_id == transcatId ? 'selected' : '';
                    select.innerHTML += `<option value="${t.transcat_id}" ${selected}>${t.transcat_name}</option>`;
                });
            } else {
                select.innerHTML = '<option value="">โหลดข้อมูลล้มเหลว</option>';
            }
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">เกิดข้อผิดพลาด</option>';
        });
}

function saveShipping() {
    const form = document.getElementById('shippingForm');
    const formData = new FormData(form);
    const data = {
        orders_id: formData.get('orders_id'),
        transcat_id: formData.get('transcat_id'),
        orders_tracking: formData.get('orders_tracking'),
        orders_delicosttrue: formData.get('orders_delicosttrue')
    };

    const btn = document.getElementById('btnSaveShipping');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> กำลังบันทึก...';
    btn.disabled = true;

    fetch(`${ADMIN_URL}/api/update_order_shipping.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกเรียบร้อย',
                    showConfirmButton: false,
                    timer: 1500
                });
                bootstrap.Modal.getInstance(document.getElementById('shippingModal')).hide();
                loadOrderTable(currentPage);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        });
}

// --- Copy Info ---
function copyOrderInfo(orderId) {
    fetch(`${ADMIN_URL}/api/get_order_customer_info.php?orders_id=${orderId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                copyToClipboard(data.text, null);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
}

// --- Print Status ---
function togglePrintStatus(orderId, currentStatus, btnElement) {
    // Optimistic Update
    const newStatus = currentStatus == 1 ? 0 : 1;
    const originalHtml = btnElement.innerHTML;
    const originalClass = btnElement.className;
    const originalOnclick = btnElement.getAttribute('onclick');

    // Show loading state
    btnElement.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    btnElement.disabled = true;

    fetch(`${ADMIN_URL}/api/toggle_print_status.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orders_id: orderId, status: newStatus })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update Button State to Success
                btnElement.disabled = false;
                if (newStatus == 1) {
                    btnElement.className = 'btn btn-xs w-100 fw-bold btn-danger';
                    btnElement.innerHTML = '<i data-lucide="printer" style="width:10px; height:10px;"></i> พิมพ์แล้ว';
                } else {
                    btnElement.className = 'btn btn-xs w-100 fw-bold btn-success';
                    btnElement.innerHTML = '<i data-lucide="printer" style="width:10px; height:10px;"></i> ยังไม่พิมพ์';
                }
                // Update onclick to reflect new status
                btnElement.setAttribute('onclick', `togglePrintStatus(${orderId}, ${newStatus}, this)`);
                lucide.createIcons();
            } else {
                // Revert on failure
                btnElement.innerHTML = originalHtml;
                btnElement.className = originalClass;
                btnElement.disabled = false;
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            // Revert on error
            btnElement.innerHTML = originalHtml;
            btnElement.className = originalClass;
            btnElement.disabled = false;
            console.error('Error:', error);
            Swal.fire('Error', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
        });
}

// --- Filter by Customer ---
function filterByCustomer(userId) {
    loadOrderTable(1, '', userId);
}

// --- Slip Upload ---
function triggerSlipUpload(orderId) {
    document.getElementById('slipUploadInput').click();
}

// --- Order Item Management ---

/**
 * Remove item from order
 */
async function removeOrderItem(ordetailId, orderId) {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบสินค้าชิ้นนี้ออกจากออเดอร์?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#d33'
    });

    if (!result.isConfirmed) return;

    Swal.fire({
        title: 'กำลังลบ...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const response = await fetch(`${ADMIN_URL}/api/delete_order_item.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ordetail_id: ordetailId,
                order_id: orderId
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: 'ลบสินค้าแล้ว',
                timer: 1500,
                showConfirmButton: false
            });
            viewOrder(orderId); // Reload order details
        } else {
            Swal.fire('ผิดพลาด', data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการลบสินค้า', 'error');
    }
}

/**
 * Update item quantity in order
 */
async function updateItemQty(ordetailId, newQty, orderId) {
    if (newQty < 1) {
        Swal.fire('คำเตือน', 'จำนวนต้องอย่างน้อย 1 ชิ้น', 'warning');
        return;
    }

    // --- Fast UI Update (Local Calculation) ---
    const row = document.querySelector(`tr[data-ordetail-id="${ordetailId}"]`);
    if (row) {
        const price = parseFloat(row.querySelector('.item-price-input').value) || 0;
        const subtotal = price * newQty;
        row.querySelector('.item-row-subtotal').innerText = subtotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        recalculateOrderTotalsLocally();
    }

    try {
        const response = await fetch(`${ADMIN_URL}/api/update_item_qty.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ordetail_id: ordetailId,
                qty: newQty,
                order_id: orderId
            })
        });

        const data = await response.json();
        if (!data.success) {
            Swal.fire('ผิดพลาด', data.message, 'error');
            viewOrder(orderId); // Revert on failure
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการอัปเดตจำนวน', 'error');
        viewOrder(orderId);
    }
}

/**
 * Update item price in order
 */
async function updateItemPrice(ordetailId, newPrice, orderId) {
    if (newPrice < 0) {
        Swal.fire('คำเตือน', 'ราคาต้องไม่ติดลบ', 'warning');
        return;
    }

    // --- Fast UI Update (Local Calculation) ---
    const row = document.querySelector(`tr[data-ordetail-id="${ordetailId}"]`);
    if (row) {
        const qty = parseInt(row.querySelector('.item-qty-input').value) || 0;
        const subtotal = newPrice * qty;
        row.querySelector('.item-row-subtotal').innerText = subtotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        recalculateOrderTotalsLocally();
    }

    try {
        const response = await fetch(`${ADMIN_URL}/api/update_item_price.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ordetail_id: ordetailId,
                price: newPrice,
                order_id: orderId
            })
        });

        const data = await response.json();
        if (!data.success) {
            Swal.fire('ผิดพลาด', data.message, 'error');
            viewOrder(orderId);
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการอัปเดตราคา', 'error');
        viewOrder(orderId);
    }
}

/**
 * Toggle Order VAT status
 */
async function toggleOrderVat(orderId, isVat) {
    // --- Fast UI Update ---
    recalculateOrderTotalsLocally();
    
    try {
        const response = await fetch(`${ADMIN_URL}/api/update_order_vat.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                is_vat: isVat ? 1 : 0
            })
        });

        const data = await response.json();
        if (!data.success) {
            Swal.fire('ผิดพลาด', data.message, 'error');
            viewOrder(orderId);
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการอัปเดตสถานะ VAT', 'error');
        viewOrder(orderId);
    }
}

/**
 * View All Payment Slips for an Order
 */
async function viewOrderSlips(orderId) {
    // Show modal with loading
    Swal.fire({
        title: 'สลิปการชำระเงินทั้งหมด',
        html: `
            <div class="text-center p-5">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div class="text-muted small">กำลังโหลดข้อมูลสลิป...</div>
            </div>
        `,
        width: 600,
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: false
    });

    try {
        const response = await fetch(`${ADMIN_URL}/api/get_order_details.php?id=${orderId}`);
        const data = await response.json();
        
        let slipsHtml = '';
        if (data.success && data.slips && data.slips.length > 0) {
            slipsHtml = data.slips.map((slip, index) => `
                <div class="mb-3 border rounded p-2 bg-light position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary">ใบที่ ${index + 1}</span>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">${slip.slip_uploaded_at || 'ไม่ระบุวันที่'}</small>
                            <button onclick="deleteOrderSlip(${slip.slip_id}, ${orderId})" class="btn btn-sm btn-outline-danger" style="padding: 0px 5px;" title="ลบสลิปนี้">
                                <i data-lucide="trash-2" style="width:14px; height:14px;"></i>
                            </button>
                        </div>
                    </div>
                    <a href="${ROOT_URL}/uploads/slips/${slip.slip_filename}" target="_blank">
                        <img src="${ROOT_URL}/uploads/slips/${slip.slip_filename}" class="img-fluid rounded shadow-sm border" style="max-height: 400px; display: block; margin: 0 auto;">
                    </a>
                </div>
            `).join('');
        } else {
            slipsHtml = '<div class="text-center text-muted py-4 border rounded mb-3 bg-light">ยังไม่มีสลิปการชำระเงิน</div>';
        }

        // Update modal with content
        Swal.fire({
            title: 'สลิปการชำระเงินทั้งหมด',
            html: `
                <div class="mb-3 p-2 border rounded bg-white shadow-sm">
                    <label class="form-label fw-bold small d-block text-start mb-1">อัปโหลดสลิปใหม่:</label>
                    <div class="input-group input-group-sm">
                        <input type="file" id="quickSlipInput" class="form-control" accept="image/*">
                        <button class="btn btn-success" type="button" onclick="handleQuickSlipUpload(${orderId})">
                            <i data-lucide="upload" style="width:14px; height:14px;"></i> อัปโหลด
                        </button>
                    </div>
                </div>
                <div style="max-height: 500px; overflow-y: auto;">${slipsHtml}</div>`,
            width: 600,
            showConfirmButton: false,
            showCancelButton: false,
            showCloseButton: true,
            didOpen: () => {
                lucide.createIcons();
            }
        });
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการดึงข้อมูลสลิป', 'error');
    }
}

async function handleQuickSlipUpload(orderId) {
    const input = document.getElementById('quickSlipInput');
    const file = input.files[0];
    
    if (!file) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกไฟล์สลิปก่อนกดอัปโหลด', 'warning');
        return;
    }

    // Show Loading
    Swal.fire({
        title: 'กำลังอัปโหลด...',
        text: 'กรุณารอซักครู่ ระบบกำลังประมวลผลรูปภาพ',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('slip_image', file);

    try {
        const response = await fetch(`${ADMIN_URL}/api/upload_slip.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            // Refresh main table and modal immediately
            loadOrderTable(currentPage, currentSearchQuery);
            viewOrderSlips(orderId);

            // Show non-blocking success toast
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
            Toast.fire({
                icon: 'success',
                title: 'อัปโหลดสลิปสำเร็จ'
            });
        } else {
            Swal.fire('ผิดพลาด', data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล', 'error');
    }
}

/**
 * Delete a Specific Payment Slip
 */
async function deleteOrderSlip(slipId, orderId) {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบสลิปใบนี้ใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    });

    if (result.isConfirmed) {
        // Show Loading during delete
        Swal.fire({
            title: 'กำลังลบข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const response = await fetch(`${ADMIN_URL}/api/delete_slip.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    slip_id: slipId,
                    orders_id: orderId
                })
            });

            const data = await response.json();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'ลบแล้ว!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                // Re-open slips gallery to show updated list
                viewOrderSlips(orderId);
                // Also update main table to reflect count change
                loadOrderTable(currentPage, currentSearchQuery);
            } else {
                Swal.fire('ผิดพลาด', data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        }
    }
}

/**
 * Edit Paid Amount (Deposit)
 */
async function editPaidAmount(orderId, currentAmount) {
    const { value: result } = await Swal.fire({
        title: 'ระบุยอดที่ชำระแล้ว (มัดจำ)',
        input: 'number',
        inputLabel: 'จำนวนเงิน (บาท)',
        inputValue: currentAmount,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        showLoaderOnConfirm: true,
        inputValidator: (value) => {
            if (!value && value !== '0') {
                return 'กรุณาระบุจำนวนเงิน';
            }
            if (parseFloat(value) < 0) {
                return 'จำนวนเงินต้องไม่ติดลบ';
            }
        },
        preConfirm: async (amount) => {
            try {
                const response = await fetch(`${ADMIN_URL}/api/update_paid_amount.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        amount: parseFloat(amount)
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'ไม่สามารถบันทึกข้อมูลได้');
                }
                return data;
            } catch (error) {
                Swal.showValidationMessage(`Request failed: ${error}`);
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (result && result.success) {
        Swal.fire({
            icon: 'success',
            title: 'บันทึกแล้ว',
            text: result.message,
            timer: 1500,
            showConfirmButton: false
        });
        loadOrderTable(currentPage, currentSearchQuery);
    }
}

/**
 * Edit Paid Amount from inside the viewOrder modal
 * Same as editPaidAmount but reloads the modal after saving
 */
async function editPaidAmountModal(orderId, currentAmount) {
    const { value: result } = await Swal.fire({
        title: 'ระบุยอดมัดจำ / ชำระแล้ว',
        input: 'number',
        inputLabel: 'จำนวนเงิน (บาท)',
        inputValue: currentAmount,
        inputAttributes: { min: 0, step: 0.01 },
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#27ae60',
        showLoaderOnConfirm: true,
        inputValidator: (value) => {
            if (!value && value !== '0') return 'กรุณาระบุจำนวนเงิน';
            if (parseFloat(value) < 0) return 'จำนวนเงินต้องไม่ติดลบ';
        },
        preConfirm: async (amount) => {
            try {
                const response = await fetch(`${ADMIN_URL}/api/update_paid_amount.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, amount: parseFloat(amount) })
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'ไม่สามารถบันทึกข้อมูลได้');
                return data;
            } catch (error) {
                Swal.showValidationMessage(`Request failed: ${error}`);
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (result && result.success) {
        Swal.fire({ icon: 'success', title: 'บันทึกแล้ว', timer: 1200, showConfirmButton: false });
        loadOrderTable(currentPage, currentSearchQuery); // refresh table in background
        viewOrder(orderId); // reload modal to show updated amount
    }
}

/**
 * Add / View Internal Notes
 */
async function openOrderNotes(orderId) {
    // Show modal with loading
    Swal.fire({
        title: 'บันทึกภายใน / Timeline',
        html: `
            <div class="text-center p-5">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div class="text-muted small">กำลังโหลดบันทึก...</div>
            </div>
        `,
        width: 600,
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: false
    });

    try {
        const response = await fetch(`${ADMIN_URL}/api/get_order_notes.php?order_id=${orderId}`);
        const data = await response.json();
        
        let notesHtml = '';
        if (data.notes && data.notes.length > 0) {
            notesHtml = data.notes.map(note => {
                const picUrl = note.user_picture 
                    ? `${ROOT_URL}/uploads/profile/${note.user_id}/${note.user_picture}` 
                    : null;
                
                return `
                <div class="d-flex mb-3">
                    <div class="me-2 text-center" style="width: 40px;">
                        ${picUrl ? `
                            <img src="${picUrl}" class="rounded-circle border border-2 border-white shadow-sm" style="width: 35px; height: 35px; object-fit: cover;">
                        ` : `
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 35px; height: 35px;">
                                <i data-lucide="user" style="width: 18px; height: 18px;" class="text-muted"></i>
                            </div>
                        `}
                    </div>
                    <div class="flex-grow-1 border-start border-3 border-info ps-2 py-1 position-relative bg-light bg-opacity-50 rounded-end">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="fw-bold text-primary">${note.admin_name || 'พนักงาน'}</small>
                            <small class="text-muted" style="font-size: 0.7rem;">${note.created_at}</small>
                        </div>
                        <div class="text-dark py-1" style="font-size: 0.9rem; white-space: pre-wrap;">${note.note_text}</div>
                        ${note.note_image ? `
                            <div class="mt-2 text-start">
                                <a href="${ROOT_URL}/uploads/notes/${note.note_image}" target="_blank">
                                    <img src="${ROOT_URL}/uploads/notes/${note.note_image}" class="img-thumbnail" style="max-height: 180px; cursor: zoom-in;">
                                </a>
                            </div>
                        ` : ''}
                    </div>
                </div>
                `;
            }).join('');
        } else {
            notesHtml = '<div class="text-center text-muted p-3">ยังไม่มีบันทึกภายใน</div>';
        }

        Swal.fire({
            title: 'บันทึกภายใน / Timeline',
            html: `
                <div id="notes-list" class="text-start mb-3" style="max-height: 400px; overflow-y: auto;">
                    ${notesHtml}
                </div>
                <hr>
                <div class="text-start">
                    <label class="form-label fw-bold small">เพิ่มบันทึกใหม่:</label>
                    <textarea id="new-note-text" class="form-control mb-2" rows="3" placeholder="ระบุรายละเอียด..."></textarea>
                    <input type="file" id="new-note-image" class="form-control form-control-sm" accept="image/*">
                    <small class="text-danger d-block mt-2">* บันทึกแล้วไม่สามารถแก้ไขหรือลบได้ กรุณาตรวจสอบให้ถูกต้อง</small>
                </div>
            `,
            width: 600,
            showConfirmButton: true,
            showCancelButton: true,
            confirmButtonText: 'บันทึกข้อมูล',
            cancelButtonText: 'ปิด',
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                const text = document.getElementById('new-note-text').value;
                const file = document.getElementById('new-note-image').files[0];
                
                if (!text) {
                    Swal.showValidationMessage('กรุณากรอกข้อความบันทึก');
                    return false;
                }

                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('note_text', text);
                if (file) formData.append('note_image', file);

                try {
                    const saveRes = await fetch(`${ADMIN_URL}/api/save_order_note.php`, {
                        method: 'POST',
                        body: formData
                    });
                    const saveData = await saveRes.json();
                    if (!saveData.success) {
                        throw new Error(saveData.message || 'ไม่สามารถบันทึกข้อมูลได้');
                    }
                    return saveData;
                } catch (error) {
                    Swal.showValidationMessage(`Request failed: ${error}`);
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value.success) {
                Swal.fire({ 
                    icon: 'success', 
                    title: 'บันทึกสำเร็จ', 
                    timer: 800, 
                    showConfirmButton: false 
                });
                openOrderNotes(orderId); // Reload notes modal
                loadOrderTable(currentPage, currentSearchQuery); // Refresh main table to update note count
            }
        });
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
    }
}

/**
 * Upload Payment Slip
 */
async function uploadSlip(orderId) {
    const { value: file } = await Swal.fire({
        title: 'อัปโหลดสลิปชำระเงิน',
        input: 'file',
        inputAttributes: {
            'accept': 'image/*',
            'aria-label': 'เลือกรูปภาพสลิป'
        },
        showCancelButton: true,
        confirmButtonText: 'อัปโหลด',
        cancelButtonText: 'ยกเลิก',
        showLoaderOnConfirm: true,
        preConfirm: (file) => {
            if (!file) {
                Swal.showValidationMessage('กรุณาเลือกไฟล์สลิป');
                return false;
            }
            return file;
        }
    });

    if (file) {
        // Show Loading during upload
        Swal.fire({
            title: 'กำลังอัปโหลดสลิป...',
            text: 'กรุณารอซักครู่ ระบบกำลังประมวลผลรูปภาพ',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('slip_image', file);

        try {
            const response = await fetch(`${ADMIN_URL}/api/upload_slip.php`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    // Refresh both the main table and the slip gallery modal
                    loadOrderTable(currentPage, currentSearchQuery);
                    viewOrderSlips(orderId);
                });
            } else {
                Swal.fire('ผิดพลาด', data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        }
    }
}

/**
 * Helper to recalculate totals in the UI without server reload
 */
function recalculateOrderTotalsLocally() {
    let subtotalSum = 0;
    let totalQtySum = 0;
    document.querySelectorAll('.item-row-subtotal').forEach(el => {
        subtotalSum += parseFloat(el.innerText.replace(/,/g, '')) || 0;
    });
    
    document.querySelectorAll('.item-qty-input').forEach(el => {
        totalQtySum += parseInt(el.value) || 0;
    });

    const shipping = parseFloat(document.getElementById('modalShippingInput')?.value || 0);
    const discount = parseFloat(document.getElementById('modalDiscountInput')?.value || 0);
    const isVat = document.querySelector('#orderVatToggle')?.checked || false;
    
    // Update Subtotal Display
    const subtotalEl = document.querySelector('.modal-total-sum');
    if (subtotalEl) subtotalEl.innerText = subtotalSum.toLocaleString(undefined, {minimumFractionDigits: 2});
 
    // Update Total Qty Display
    const totalQtyEl = document.querySelector('.modal-total-qty');
    if (totalQtyEl) totalQtyEl.innerText = totalQtySum;
 
    // COD Fee Calculation
    const paycatSelect = document.querySelector('#paycatSelect');
    const paycatId = paycatSelect ? parseInt(paycatSelect.value) : 0;
    let codFee = 0;
    if (paycatId === 2) {
        codFee = Math.round(subtotalSum * 0.03);
    }
 
    // Update COD Fee Display
    const codFeeEl = document.querySelector('.modal-cod-fee');
    const codRow = document.querySelector('.cod-fee-row');
    if (codFeeEl) {
        codFeeEl.innerText = codFee.toLocaleString(undefined, {minimumFractionDigits: 2});
        if (codRow) {
            if (codFee > 0) codRow.classList.remove('d-none');
            else codRow.classList.add('d-none');
        }
    }
 
    // Grandtotal in this system includes VAT if enabled
    let grandTotal = subtotalSum + shipping + codFee - discount;
    
    // VAT Calculation if enabled
    if (isVat) {
        let sumExcluded = 0;
        let sumIncluded = 0;
        
        document.querySelectorAll('tr[data-ordetail-id]').forEach(row => {
            const vatType = row.getAttribute('data-vat-type') || 'included';
            const subtotalEl = row.querySelector('.item-row-subtotal');
            if (subtotalEl) {
                const itemTotal = parseFloat(subtotalEl.innerText.replace(/,/g, '')) || 0;
                if (vatType === 'excluded') {
                    sumExcluded += itemTotal;
                } else {
                    sumIncluded += itemTotal;
                }
            }
        });
        
        // Include shipping, codFee in sumIncluded, and subtract discount from it
        sumIncluded += shipping + codFee - discount;
        
        const vatExcluded = sumExcluded * 0.07;
        const vatIncluded = sumIncluded * 7 / 107;
        
        const vatAmount = vatExcluded + vatIncluded;
        const basePrice = sumExcluded + (sumIncluded * 100 / 107);
        
        // Add VAT from excluded items to grandtotal
        grandTotal += vatExcluded;
        
        // Update VAT Display
        const vatBaseEl = document.querySelector('.modal-tax-base');
        const vat7El = document.querySelector('.modal-vat-7');
        if (vatBaseEl) vatBaseEl.innerText = basePrice.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (vat7El) vat7El.innerText = vatAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
 
    // Update Grandtotal Display
    const grandTotalEl = document.querySelector('.modal-grand-total');
    if (grandTotalEl) grandTotalEl.innerText = grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2});

    // Update Paid and Balance
    const paidAmount = parseFloat(document.querySelector('.modal-paid-amount')?.innerText.replace(/,/g, '')) || 0;
    const balance = grandTotal - paidAmount;
    
    const balanceEl = document.querySelector('.modal-balance');
    if (balanceEl) {
        balanceEl.innerText = balance.toLocaleString(undefined, {minimumFractionDigits: 2});
        if (balance > 0) {
            balanceEl.classList.add('text-danger');
            balanceEl.classList.remove('text-muted');
        } else {
            balanceEl.classList.remove('text-danger');
            balanceEl.classList.add('text-muted');
        }
    }

    // Show/hide VAT rows based on toggle
    const vatRow1 = document.querySelector('.modal-tax-base')?.closest('tr');
    const vatRow2 = document.querySelector('.modal-vat-7')?.closest('tr');

    if (isVat) {
        if (vatRow1) vatRow1.classList.remove('d-none');
        if (vatRow2) vatRow2.classList.remove('d-none');
    } else {
        if (vatRow1) vatRow1.classList.add('d-none');
        if (vatRow2) vatRow2.classList.add('d-none');
    }
}

/**
 * Add item to order
 */
async function addOrderItem(orderId) {
    // Set active editing order and open the visual selector
    editingOrderId = orderId;
    addProductToNewOrder();
}

/**
 * Create new order - Main flow
 */
// Global cart state for new order
let newOrderCart = {
    customer: null,
    address: null,
    items: [],
    shipping: 0,
    notes: ''
};

async function createNewOrder() {
    // Reset flags
    editingOrderId = null;
    
    // Reset cart
    newOrderCart = {
        customer: null,
        address: null,
        items: [],
        shipping: 0,
        notes: ''
    };

    // Update UI to clear displays
    updateNewOrderCustomerDisplay();
    updateNewOrderAddressDisplay();
    updateNewOrderCartDisplay();
    updateNewOrderTotal();

    // Show customer selector first
    const customer = await showCustomerSelector();
    if (!customer) return;

    newOrderCart.customer = customer;

    // Update customer info display
    updateNewOrderCustomerDisplay();

    // Load customer addresses in the background
    loadCustomerAddresses(customer.user_id);

    // Open the modal immediately
    const modal = new bootstrap.Modal(document.getElementById('createOrderModal'));
    modal.show();

    // Re-initialize icons
    lucide.createIcons();
}

/**
 * Show customer selector
 */
async function showCustomerSelector() {
    let currentUsers = [];
    let searchTimeout = null;

    // Function to load customers from API
    async function loadCustomers(searchTerm = '') {
        try {
            const url = searchTerm
                ? `${ADMIN_URL}/api/get_users_list.php?search=${encodeURIComponent(searchTerm)}`
                : `${ADMIN_URL}/api/get_users_list.php`;

            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) throw new Error('Failed to load customers');

            currentUsers = data.users;
            return data.users;
        } catch (error) {
            console.error('Error loading customers:', error);
            return [];
        }
    }

    // Function to render customer list
    function renderCustomerList(users) {
        if (!users || users.length === 0) {
            return `
                <div class="text-center text-muted py-4">
                    <i data-lucide="users" style="width:32px;height:32px;"></i>
                    <p class="mb-0 mt-2">ไม่พบข้อมูลลูกค้า</p>
                </div>
            `;
        }

        return users.map(u => {
            const profilePic = u.user_picture
                ? `${ROOT_URL}/uploads/profile/${u.user_id}/${u.user_picture}`
                : null;

            return `
            <button type="button" class="list-group-item list-group-item-action customer-item" 
                    data-customer='${JSON.stringify(u)}'>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        ${profilePic
                    ? `<img src="${profilePic}" class="rounded-circle" 
                                    style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #e9ecef;">`
                    : `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                    style="width: 50px; height: 50px; font-size: 1.2rem; font-weight: bold;">
                                ${u.user_name.charAt(0).toUpperCase()}
                               </div>`
                }
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">
                            ${u.user_name} ${u.user_lastname}${u.user_nickname ? ` (${u.user_nickname})` : ''}
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                C${u.user_id} | ${u.user_mobile || '-'}
                            </small>
                            ${u.user_last_active ? `
                                <small class="text-info" style="font-size: 0.75rem;">
                                    <i data-lucide="clock" style="width:12px;height:12px;margin-bottom:2px;"></i> 
                                    ${u.user_last_active}
                                </small>
                            ` : ''}
                        </div>
                    </div>
                    <i data-lucide="chevron-right" class="ms-2" style="width:16px;height:16px; opacity: 0.5;"></i>
                </div>
            </button>
            `;
        }).join('');
    }

    // Open Modal Immediately with Loading State inside the list
    const { value: selectedCustomer } = await Swal.fire({
        title: 'เลือกลูกค้า',
        html: `
            <div class="text-start">
                <input type="text" id="customer_search" 
                       class="form-control mb-3" 
                       placeholder="ค้นหาชื่อ, นามสกุล, ชื่อเล่น, อีเมล, เบอร์โทร, รหัสลูกค้า...">
                <div id="customer_list" class="list-group" 
                     style="max-height: 400px; min-height: 200px; overflow-y: auto;">
                    <div class="text-center text-muted py-5 mt-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mb-0 mt-3">กำลังโหลดข้อมูลลูกค้า...</p>
                    </div>
                </div>
                <button type="button" onclick="addNewCustomerQuickly()"
                   class="btn btn-success btn-sm mt-3 w-100">
                    <i data-lucide="user-plus" style="width:14px;height:14px;"></i> 
                    เพิ่มลูกค้าใหม่ (ด่วน)
                </button>
                <input type="hidden" id="selected_customer_data" value="">
            </div>
        `,
        width: '600px',
        showCancelButton: false,
        showConfirmButton: false,
        showCloseButton: true,
        preConfirm: () => {
            const selectedData = document.getElementById('selected_customer_data').value;
            if (selectedData) {
                return JSON.parse(selectedData);
            }
            return null;
        },
        didOpen: async () => {
            lucide.createIcons();

            const searchInput = document.getElementById('customer_search');
            const customerList = document.getElementById('customer_list');

            // --- Load initial data ---
            const initialUsers = await loadCustomers();
            customerList.innerHTML = renderCustomerList(initialUsers);
            lucide.createIcons();
            attachCustomerClickHandlers();

            // Debounced search - calls API after user stops typing
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.trim();

                // Clear existing timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }

                // Show loading
                customerList.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <p class="mb-0 mt-2">กำลังค้นหา...</p>
                    </div>
                `;

                // Set new timeout
                searchTimeout = setTimeout(async () => {
                    const users = await loadCustomers(searchTerm);
                    customerList.innerHTML = renderCustomerList(users);
                    lucide.createIcons();

                    // Re-attach click handlers
                    attachCustomerClickHandlers();
                }, 500); // Wait 500ms after user stops typing
            });

            // Attach click handlers
            attachCustomerClickHandlers();

            function attachCustomerClickHandlers() {
                const customerItems = document.querySelectorAll('.customer-item');
                customerItems.forEach(item => {
                    item.addEventListener('click', function () {
                        const customerData = JSON.parse(this.dataset.customer);
                        // Store selected customer in hidden field
                        document.getElementById('selected_customer_data').value = this.dataset.customer;
                        // Close modal and trigger preConfirm
                        Swal.clickConfirm();
                    });
                });
            }
        }
    });

    return selectedCustomer || null;
}

/**
 * Quick Add Customer Module - Instant Flow
 */
async function addNewCustomerQuickly() {
    const { value: result } = await Swal.fire({
        title: 'เพิ่มลูกค้าใหม่ (ด่วน)',
        html: `
            <div class="text-start" style="font-size: 0.9rem;">
                <div class="card bg-light border-dashed mb-3">
                    <div class="card-body p-2">
                        <label class="form-label small fw-bold mb-1">วางข้อมูลลูกค้าดิบที่นี่ (เพื่อแยกข้อมูลอัตโนมัติ):</label>
                        <textarea id="q_raw_data" class="form-control form-control-sm mb-2" rows="3" placeholder="ตัวอย่าง:\nสมชาย ใจดี ชาย\n123/45 หมู่ 1 ต.บางเขน อ.เมือง\nจ.นนทบุรี 11000\n0812345678"></textarea>
                        <button type="button" class="btn btn-dark btn-sm w-100" onclick="parseQuickCustomerData()">
                            <i data-lucide="zap" style="width:14px;height:14px;"></i> แยกข้อมูลอัตโนมัติ
                        </button>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">ชื่อ <span class="text-danger">*</span></label>
                        <input type="text" id="q_name" class="form-control form-control-sm" placeholder="ระบุชื่อ">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" id="q_lastname" class="form-control form-control-sm" placeholder="ระบุนามสกุล">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold mb-1">ชื่อเล่น</label>
                        <input type="text" id="q_nickname" class="form-control form-control-sm" placeholder="ระบุชื่อเล่น">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                        <input type="tel" id="q_mobile" class="form-control form-control-sm" placeholder="08xxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">อีเมล (ถ้ามี)</label>
                        <input type="email" id="q_email" class="form-control form-control-sm" placeholder="customer@example.com">
                    </div>
                </div>
                <hr class="my-3">
                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">ที่อยู่จัดส่ง 1 (เลขที่, หมู่บ้าน, ถนน) <span class="text-danger">*</span></label>
                    <input type="text" id="q_addr1" class="form-control form-control-sm" placeholder="99/99 หมู่ 9 ถ.สุขุมวิท...">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">ที่อยู่จัดส่ง 2 (ตำบล, อำเภอ) <span class="text-danger">*</span></label>
                    <input type="text" id="q_addr2" class="form-control form-control-sm" placeholder="แขวงบางนา เขตบางนา">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">จังหวัด <span class="text-danger">*</span></label>
                        <select id="q_province" class="form-select form-select-sm">
                            <option value="">-- กำลังโหลดจังหวัด... --</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                        <input type="text" id="q_postcode" class="form-control form-control-sm" placeholder="10xxx">
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">ประเภทที่อยู่</label>
                        <select id="q_type" class="form-select form-select-sm">
                            <option value="1">🏠 ที่บ้าน</option>
                            <option value="2">🏢 ที่ทำงาน</option>
                            <option value="3">📄 ที่อยู่สำหรับออกบิล/ใบกำกับภาษี</option>
                            <option value="4">📦 ใช้เป็นผู้ส่งในนาม</option>
                            <option value="5">👤 ผู้รับแทน</option>
                            <option value="6">🏕️ ที่อยู่รับสินค้าชั่วคราว</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">หมายเหตุจัดส่ง (ถ้ามี)</label>
                        <input type="text" id="q_memo" class="form-control form-control-sm" placeholder="เช่น ส่งชั้น 2, ฝากนิติ...">
                    </div>
                </div>
            </div>
        `,
        width: '650px',
        showCancelButton: true,
        confirmButtonText: 'บันทึกและเริ่มสั่งซื้อ',
        cancelButtonText: 'ย้อนกลับ',
        focusConfirm: false,
        didOpen: async () => {
            lucide.createIcons();
            
            // Load provinces asynchronously in background
            try {
                const res = await fetch(`${ADMIN_URL}/api/get_provinces.php`);
                const data = await res.json();
                if (data.success) {
                    const provinceSelect = document.getElementById('q_province');
                    if (provinceSelect) {
                        let html = '<option value="">-- เลือกจังหวัด --</option>';
                        data.provinces.forEach(p => {
                            html += `<option value="${p.id}">${p.name_th}</option>`;
                        });
                        provinceSelect.innerHTML = html;
                    }
                }
            } catch (e) { 
                console.error(e); 
                const ps = document.getElementById('q_province');
                if (ps) ps.innerHTML = '<option value="">-- โหลดจังหวัดล้มเหลว --</option>';
            }
        },
        preConfirm: async () => {
            const name = document.getElementById('q_name').value.trim();
            const lastname = document.getElementById('q_lastname').value.trim();
            const mobile = document.getElementById('q_mobile').value.trim();
            const addr_detail = document.getElementById('q_addr1').value.trim();
            const addr_detail2 = document.getElementById('q_addr2').value.trim();
            const province_id = document.getElementById('q_province').value;
            const postcode = document.getElementById('q_postcode').value.trim();

            if (!name || !lastname || !mobile || !addr_detail || !addr_detail2 || !province_id || !postcode) {
                Swal.showValidationMessage('กรุณากรอกข้อมูลที่มีเครื่องหมาย (*) ให้ครบถ้วน');
                return false;
            }

            // Show loading inside the current modal while saving
            Swal.showLoading();

            try {
                const response = await fetch(`${ADMIN_URL}/api/add_customer.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name, lastname, mobile, 
                        nickname: document.getElementById('q_nickname').value.trim(),
                        email: document.getElementById('q_email').value.trim(),
                        addr_detail, addr_detail2, province_id, postcode,
                        addr_type: document.getElementById('q_type').value,
                        addr_forword: document.getElementById('q_memo').value.trim(),
                        source_page: 'orders'
                    })
                });
                const resData = await response.json();
                if (resData.success) {
                    return { ...resData, name, lastname, mobile };
                } else {
                    Swal.showValidationMessage(resData.message || 'บันทึกข้อมูลไม่สำเร็จ');
                    return false;
                }
            } catch (error) {
                Swal.showValidationMessage('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
                return false;
            }
        }
    });

    if (result) {
        // 1. Clear ALL old state and set the new customer
        newOrderCart.customer = {
            user_id: result.user_id,
            user_name: result.name,
            user_lastname: result.lastname,
            user_mobile: result.mobile,
            user_email: document.getElementById('q_email')?.value.trim() || '',
            user_nickname: document.getElementById('q_nickname')?.value.trim() || ''
        };
        newOrderCart.address = null; 
        newOrderCart.items = []; // Clear the cart items!
        newOrderCart.shipping = 0;
        newOrderCart.notes = '';
        
        // 2. Update ALL UI displays to clear old data
        updateNewOrderCustomerDisplay();
        updateNewOrderAddressDisplay();
        updateNewOrderCartDisplay(); // Refresh the table to be empty
        updateNewOrderTotal(); // Reset total to 0 ฿
        
        // 3. Load fresh addresses for this new user and WAIT for it
        await loadCustomerAddresses(result.user_id);
        
        // 4. Open the product selection modal
        const modal = new bootstrap.Modal(document.getElementById('createOrderModal'));
        modal.show();
        lucide.createIcons();

        if (result.duplicate) {
            Swal.fire({
                title: 'พบข้อมูลลูกค้าในระบบแล้ว',
                text: 'ลูกค้าท่านนี้มีข้อมูลในระบบแล้ว คุณต้องการใช้ที่อยู่เดิม หรือเปลี่ยนที่อยู่ใหม่?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'ใช้ที่อยู่นี้เลย',
                cancelButtonText: 'เปลี่ยนที่อยู่ใหม่',
                reverseButtons: true,
                focusConfirm: true
            }).then((confirmResult) => {
                if (confirmResult.isConfirmed) {
                    // ใช้ที่อยู่เดิม
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'info',
                        title: 'พบข้อมูลลูกค้าเดิม',
                        text: 'ระบบดึงข้อมูลมาให้โดยอัตโนมัติ'
                    });
                    // ไปหน้าเพิ่มสินค้าเลย
                    addProductToNewOrder();
                } else if (confirmResult.dismiss === Swal.DismissReason.cancel) {
                    // เปลี่ยนที่อยู่ใหม่ เปิด Modal ให้เลือกที่อยู่ใหม่
                    changeOrderAddress();
                }
            });
        }
    }
}

/**
 * Parse Quick Customer Data logic
 */
function parseQuickCustomerData() {
    const textarea = document.getElementById('q_raw_data');
    const data = textarea.value.trim();

    if (!data) {
        Swal.fire({
            icon: 'warning',
            title: 'แจ้งเตือน',
            text: 'กรุณาวางข้อมูลลูกค้าก่อน',
            timer: 1500,
            showConfirmButton: false
        });
        return;
    }

    const lines = data.split('\n').map(line => line.trim()).filter(line => line);

    // Line 1: Name Lastname Nickname
    if (lines[0]) {
        const nameParts = lines[0].split(/\s+/);
        document.getElementById('q_name').value = nameParts[0] || '';
        document.getElementById('q_lastname').value = nameParts[1] || '';
        document.getElementById('q_nickname').value = nameParts[2] || '';
    }

    // Line 2: Address Detail 1
    if (lines[1]) document.getElementById('q_addr1').value = lines[1] || '';

    // Line 3: Address Detail 2
    if (lines[2]) document.getElementById('q_addr2').value = lines[2] || '';

    // Find Province and Postcode
    let provinceLineIndex = -1;
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].match(/\d{5}/)) {
            provinceLineIndex = i;
            break;
        }
    }

    if (provinceLineIndex !== -1) {
        const provincePostcode = lines[provinceLineIndex];
        const postcodeMatch = provincePostcode.match(/(\d{5})/);

        if (postcodeMatch) {
            document.getElementById('q_postcode').value = postcodeMatch[1];

            // Extract province name
            let provinceName = provincePostcode.replace(/(\d{5})/, '').replace(/^จ\./, '').trim();
            provinceName = provinceName.replace('จังหวัด', '').trim();

            // Find province in dropdown
            const provinceSelect = document.getElementById('q_province');
            for (let option of provinceSelect.options) {
                if (option.text.includes(provinceName)) {
                    provinceSelect.value = option.value;
                    break;
                }
            }
        }
    }

    // Find Mobile (starts with 0 and has 9-10 digits)
    for (let line of lines) {
        const mobile = line.replace(/[^0-9]/g, '');
        if (mobile.startsWith('0') && (mobile.length === 9 || mobile.length === 10)) {
            document.getElementById('q_mobile').value = mobile;
            break;
        }
    }

    // Find Email
    for (let line of lines) {
        if (line.includes('@') && line.includes('.')) {
            document.getElementById('q_email').value = line;
            break;
        }
    }

    // Heuristic for note
    const lastLine = lines[lines.length - 1];
    if (lines.length > 4 && !lastLine.match(/\d{5}/) && !lastLine.includes('@') && !lastLine.match(/^0\d{8,9}$/)) {
        document.getElementById('q_memo').value = lastLine;
    }

    // Success feedback (Change button color temporarily instead of using Swal Toast)
    const btn = document.querySelector('button[onclick="parseQuickCustomerData()"]');
    if (btn) {
        const originalHtml = btn.innerHTML;
        btn.classList.replace('btn-dark', 'btn-success');
        btn.innerHTML = '<i data-lucide="check" style="width:14px;height:14px;"></i> แยกข้อมูลสำเร็จ!';
        lucide.createIcons();
        
        setTimeout(() => {
            btn.classList.replace('btn-success', 'btn-dark');
            btn.innerHTML = originalHtml;
            lucide.createIcons();
        }, 2000);
    }
}

/**
 * Update customer display in modal
 */
function updateNewOrderCustomerDisplay() {
    const container = document.getElementById('selectedCustomerInfo');
    if (!container) return;

    const customer = newOrderCart.customer;
    if (!customer) {
        container.innerHTML = `
            <div class="text-center p-3 border rounded border-dashed text-muted" style="font-size: 0.85rem;">
                <i data-lucide="user" class="mb-1" style="width:20px;height:20px;"></i>
                <div>ยังไม่ได้เลือกข้อมูลลูกค้า</div>
            </div>
        `;
    } else {
        container.innerHTML = `
            <div class="d-flex align-items-center p-2 border rounded mb-2" style="background: #f8f9fa; font-size: 0.85rem;">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2"
                     style="width: 32px; height: 32px; font-size: 0.9rem; font-weight: bold;">
                    ${customer.user_name.charAt(0).toUpperCase()}
                </div>
                <div class="flex-grow-1">
                    <strong>${customer.user_name} ${customer.user_lastname}</strong>
                    <span class="text-muted ms-2">| ${customer.user_email || '-'} | ${customer.user_mobile || '-'}</span>
                </div>
            </div>
        `;
    }

    lucide.createIcons();
}

/**
 * Update address display in modal
 */
function updateNewOrderAddressDisplay() {
    const container = document.getElementById('selectedAddressInfo');
    if (!container) return;

    const addr = newOrderCart.address;
    if (!addr) {
        container.innerHTML = `
            <div class="text-center p-3 border rounded border-dashed text-muted" style="font-size: 0.85rem;">
                <i data-lucide="map-pin" class="mb-1" style="width:20px;height:20px;"></i>
                <div class="mb-2">ยังไม่ได้ระบุที่อยู่จัดส่ง หรือลูกค้ายังไม่มีที่อยู่</div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="addNewAddressForOrder()">
                    <i data-lucide="plus" style="width:14px;height:14px;"></i> เพิ่มที่อยู่
                </button>
            </div>
        `;
    } else {
        const typeInfo = getAddressTypeInfo(addr.addr_type);
        container.innerHTML = `
            <div class="d-flex align-items-center justify-content-between p-2 border rounded" style="background: #fdfdfd; font-size: 0.85rem;">
                <div class="text-truncate flex-grow-1">
                    <span class="badge ${typeInfo.badge} me-2" style="font-size: 10px;">
                        <i data-lucide="${typeInfo.icon}" style="width:10px;height:10px; vertical-align: middle;"></i> ${typeInfo.label}
                    </span>
                    <i data-lucide="map-pin" class="text-danger me-1" style="width:14px;height:14px; vertical-align: text-bottom;"></i>
                    <strong>${addr.addr_name}</strong>: 
                    ${addr.addr_detail} ${addr.addr_detail2 || ''} 
                    ${addr.province_name || ''} ${addr.addr_postcode || ''}
                    <span class="text-muted ms-2">📞 ${addr.addr_mobile}</span>
                </div>
                <button type="button" class="btn btn-outline-dark btn-xs ms-2" id="btnChangeAddr" onclick="changeOrderAddress(this)">เปลี่ยน</button>
            </div>
        `;
    }
    lucide.createIcons();
}

/**
 * Helper to get address type metadata
 */
function getAddressTypeInfo(type) {
    const types = {
        1: { label: 'ที่บ้าน', icon: 'home', badge: 'bg-success' },
        2: { label: 'ที่ทำงาน', icon: 'building-2', badge: 'bg-primary' },
        3: { label: 'ที่อยู่ออกบิล', icon: 'file-text', badge: 'bg-warning text-dark' },
        4: { label: 'ส่งในนาม', icon: 'package', badge: 'bg-info text-dark' },
        5: { label: 'ผู้รับแทน', icon: 'user-check', badge: 'bg-secondary' },
        6: { label: 'ที่อยู่รับสินค้าชั่วคราว', icon: 'clock', badge: 'bg-dark' }
    };
    return types[type] || { label: 'ทั่วไป', icon: 'map-pin', badge: 'bg-secondary' };
}

/**
 * Change order address - Open selector
 */
async function changeOrderAddress(btn) {
    const customer = newOrderCart.customer;
    if (!customer) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกลูกค้าก่อน', 'warning');
        return;
    }

    // Show loading on button if possible
    let originalHtml = '';
    if (btn) {
        originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    }

    try {
        const response = await fetch(`${ADMIN_URL}/api/get_customer_addresses.php?user_id=${customer.user_id}`);
        const data = await response.json();

        // Restore button state
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }

        if (!data.success || data.data.length === 0) {
            Swal.fire('แจ้งเตือน', 'ไม่พบที่อยู่อื่นของลูกค้ารายนี้', 'info');
            return;
        }

        const { value: selectedAddr } = await Swal.fire({
            title: 'เลือกที่อยู่จัดส่ง',
            html: `
                <div class="text-start mb-3">
                    <div class="alert alert-success d-flex align-items-center justify-content-center py-2 mb-3" 
                         style="cursor: pointer; border-style: dashed; font-size: 0.85rem;" 
                         onclick="addNewAddressForOrder()">
                        <i data-lucide="plus-circle" class="me-2" style="width:16px;height:16px;"></i> 
                        <strong>เพิ่มที่อยู่ใหม่สำหรับลูกค้ารายนี้</strong>
                    </div>
                    <div class="list-group">
                        ${data.data.map((addr, index) => {
                            const typeInfo = getAddressTypeInfo(addr.addr_type);
                            return `
                            <button type="button" class="list-group-item list-group-item-action p-2" 
                                    onclick="Swal.clickConfirm(); window.lastSelectedAddr = ${index}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <div class="fw-bold text-primary">${addr.addr_name}</div>
                                            <span class="badge ${typeInfo.badge}" style="font-size: 10px;">
                                                <i data-lucide="${typeInfo.icon}" style="width:10px;height:10px;vertical-align:middle;"></i> ${typeInfo.label}
                                            </span>
                                        </div>
                                        <div class="small text-dark">${addr.addr_detail} ${addr.addr_detail2 || ''}</div>
                                        <div class="small text-muted">${addr.province_name || ''} ${addr.addr_postcode || ''}</div>
                                        <div class="small text-muted mt-1">📞 ${addr.addr_mobile}</div>
                                    </div>
                                    <i data-lucide="chevron-right" class="text-muted" style="width:16px;height:16px;"></i>
                                </div>
                            </button>
                        `}).join('')}
                    </div>
                </div>
            `,
            showCancelButton: true,
            cancelButtonText: 'ยกเลิก',
            showConfirmButton: false,
            didOpen: () => {
                lucide.createIcons();
            },
            preConfirm: () => {
                return data.data[window.lastSelectedAddr];
            }
        });

        if (selectedAddr) {
            newOrderCart.address = selectedAddr;
            updateNewOrderAddressDisplay();
        }
    } catch (e) {
        console.error(e);
        Swal.fire('ผิดพลาด', 'ไม่สามารถโหลดข้อมูลที่อยู่ได้', 'error');
    }
}

/**
 * Add New Address for existing customer in Order flow
 */
async function addNewAddressForOrder() {
    // Get the CURRENTLY active customer from the cart state
    const customer = newOrderCart.customer;
    if (!customer) {
        Swal.fire('ผิดพลาด', 'ไม่พบข้อมูลลูกค้าที่กำลังดำเนินการ', 'error');
        return;
    }

    // Use a clean form with NO autocomplete
    const { value: result } = await Swal.fire({
        title: 'เพิ่มที่อยู่ใหม่',
        target: document.getElementById('createOrderModal'), // INJECT INTO THE MODAL
        html: `
            <div class="text-start" style="font-size: 0.85rem;">
                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">ชื่อที่อยู่ (เช่น บ้าน, ที่ทำงาน, ชื่อผู้รับ) <span class="text-danger">*</span></label>
                    <input type="text" id="n_addr_name" class="form-control form-control-sm" autocomplete="off" 
                           placeholder="เช่น บ้านสีแดง / คุณสมศักดิ์" value="">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">เบอร์โทรศัพท์ผู้รับ <span class="text-danger">*</span></label>
                    <input type="tel" id="n_addr_mobile" class="form-control form-control-sm" autocomplete="off" 
                           placeholder="08xxxxxxxx" value="">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">ที่อยู่เลขที่, หมู่บ้าน, ซอย, ถนน <span class="text-danger">*</span></label>
                    <input type="text" id="n_addr_detail" class="form-control form-control-sm" autocomplete="off" 
                           placeholder="99/99 หมู่ 9 ถ.สุขุมวิท...">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">แขวง/ตำบล, เขต/อำเภอ <span class="text-danger">*</span></label>
                    <input type="text" id="n_addr_detail2" class="form-control form-control-sm" autocomplete="off" 
                           placeholder="แขวงบางนา เขตบางนา">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">จังหวัด <span class="text-danger">*</span></label>
                        <select id="n_province" class="form-select form-select-sm">
                            <option value="">-- กำลังโหลดจังหวัด... --</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                        <input type="text" id="n_postcode" class="form-control form-control-sm" autocomplete="off" placeholder="10xxx">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">ประเภทที่อยู่</label>
                    <select id="n_addr_type" class="form-select form-select-sm">
                        <option value="1" selected>🏠 ที่บ้าน</option>
                        <option value="2">🏢 ที่ทำงาน</option>
                        <option value="3">📄 ที่อยู่สำหรับออกบิล/ใบกำกับภาษี</option>
                        <option value="4">📦 ใช้เป็นผู้ส่งในนาม</option>
                        <option value="5">👤 ผู้รับแทน</option>
                        <option value="6">🏕️ ที่อยู่รับสินค้าชั่วคราว</option>
                    </select>
                </div>
            </div>
        `,
        width: '500px',
        showCancelButton: true,
        confirmButtonText: 'บันทึกที่อยู่',
        cancelButtonText: 'ยกเลิก',
        allowOutsideClick: false,
        didOpen: async () => {
            const input = document.getElementById('n_addr_name');
            if (input) {
                setTimeout(() => {
                    input.focus();
                    input.select();
                }, 300);
            }

            // Load provinces
            try {
                const res = await fetch(`${ADMIN_URL}/api/get_provinces.php`);
                const data = await res.json();
                if (data.success) {
                    const select = document.getElementById('n_province');
                    if (select) {
                        let html = '<option value="">-- เลือกจังหวัด --</option>';
                        data.provinces.forEach(p => {
                            html += `<option value="${p.id}">${p.name_th}</option>`;
                        });
                        select.innerHTML = html;
                    }
                }
            } catch (e) { console.error(e); }
        },
        willClose: () => {
            // Restore modal behavior
            const mainModalEl = document.getElementById('createOrderModal');
            if (mainModalEl) {
                mainModalEl.setAttribute('tabindex', '-1');
            }
        },
        preConfirm: async () => {
            const name = document.getElementById('n_addr_name').value.trim();
            const mobile = document.getElementById('n_addr_mobile').value.trim();
            const detail = document.getElementById('n_addr_detail').value.trim();
            const detail2 = document.getElementById('n_addr_detail2').value.trim();
            const province = document.getElementById('n_province').value;
            const postcode = document.getElementById('n_postcode').value.trim();

            if (!name || !mobile || !detail || !detail2 || !province || !postcode) {
                Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                return false;
            }

            Swal.showLoading();
            try {
                const response = await fetch(`${ADMIN_URL}/api/add_address.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: customer.user_id,
                        addr_name: name,
                        addr_mobile: mobile,
                        addr_detail: detail,
                        addr_detail2: detail2,
                        provinces_id: province, // Fix: Added 's' to match API
                        addr_postcode: postcode,
                        addr_type: document.getElementById('n_addr_type').value
                    })
                });
                const res = await response.json();
                if (res.success) {
                    return { ...res, addr_id: res.id || res.addr_id, addr_name: name, addr_detail: detail, addr_detail2: detail2, addr_mobile: mobile, addr_postcode: postcode };
                } else {
                    Swal.showValidationMessage(res.message || 'บันทึกไม่สำเร็จ');
                    return false;
                }
            } catch (e) {
                Swal.showValidationMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                return false;
            }
        }
    });

    if (result) {
        // Automatically select the new address
        // We need province name for display
        const provinceSelect = document.getElementById('n_province');
        const provinceName = provinceSelect ? provinceSelect.options[provinceSelect.selectedIndex].text : '';

        newOrderCart.address = {
            addr_id: result.addr_id,
            addr_name: result.addr_name,
            addr_detail: result.addr_detail,
            addr_detail2: result.addr_detail2,
            addr_postcode: result.addr_postcode,
            addr_mobile: result.addr_mobile,
            addr_type: document.getElementById('n_addr_type').value,
            province_name: provinceName
        };
        
        updateNewOrderAddressDisplay();
        
        Swal.fire({
            icon: 'success',
            title: 'เพิ่มที่อยู่ใหม่สำเร็จ',
            timer: 1500,
            showConfirmButton: false
        });
    }
}

/**
 * Load customer addresses
 */
async function loadCustomerAddresses(userId) {
    if (!userId) return;
    try {
        const response = await fetch(`${ADMIN_URL}/api/get_customer_addresses.php?user_id=${userId}`);
        const data = await response.json();

        if (data.success && data.data.length > 0) {
            newOrderCart.address = data.data[0];
        } else {
            newOrderCart.address = null;
        }
        
        // Always make sure address section is visible
        document.getElementById('addressSection')?.classList.remove('d-none');
        updateNewOrderAddressDisplay();

        // Ensure other sections are visible
        document.getElementById('cartSection')?.classList.remove('d-none');
        document.getElementById('settingsSection')?.classList.remove('d-none');
        lucide.createIcons();
    } catch (e) {
        console.error('Error loading addresses:', e);
        newOrderCart.address = null;
        document.getElementById('addressSection')?.classList.remove('d-none');
        updateNewOrderAddressDisplay();
        // Ensure other sections are visible even on error
        document.getElementById('cartSection')?.classList.remove('d-none');
        document.getElementById('settingsSection')?.classList.remove('d-none');
    }
}

/**
 * Open Product Grid Modal
 */
async function addProductToNewOrder() {
    // Show product grid modal
    const productGridModal = new bootstrap.Modal(document.getElementById('productGridModal'));
    productGridModal.show();

    // Update cart summary count
    updateProductGridCartSummary();

    // Load product grid
    loadProductGrid();
}

/**
 * Update the cart summary counter in the product grid modal footer
 */
function updateProductGridCartSummary() {
    const summaryEl = document.getElementById('productGridCartSummaryCount');
    if (!summaryEl) return;
    
    let count = 0;
    if (typeof editingOrderId !== 'undefined' && editingOrderId) {
        // Calculate from the view order modal DOM
        document.querySelectorAll('.item-qty-input').forEach(el => {
            count += parseInt(el.value) || 0;
        });
    } else if (typeof newOrderCart !== 'undefined' && newOrderCart.items) {
        // Calculate from newOrderCart
        count = newOrderCart.items.reduce((sum, item) => sum + item.qty, 0);
    }
    summaryEl.innerText = count;
}

/**
 * Load and render product grid
 */
async function loadProductGrid() {
    const tabsContainer = document.getElementById('categoryTabs');
    const contentContainer = document.getElementById('productGridContent');

    // Show loading
    contentContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    try {
        const response = await fetch(`${ADMIN_URL}/api/get_products_grid.php`);
        const data = await response.json();

        if (!data.success || !data.categories || data.categories.length === 0) {
            contentContainer.innerHTML = `
                <div class="alert alert-warning">
                    <i data-lucide="package-x"></i> ไม่พบสินค้า
                </div>
            `;
            return;
        }

        // Render category tabs
        let tabsHTML = '';
        let contentHTML = '';

        data.categories.forEach((cat, index) => {
            const isActive = index === 0 ? 'active' : '';
            const catId = `cat-${cat.productcat_id}`;

            // Category tab
            tabsHTML += `
                <li class="nav-item" role="presentation">
                    <button class="nav-link ${isActive}" id="${catId}-tab" 
                            data-bs-toggle="tab" data-bs-target="#${catId}" 
                            type="button" role="tab">
                        ${cat.productcat_name} (${cat.products.length})
                    </button>
                </li>
            `;

            // Product grid for this category
            let productsHTML = '';
            if (cat.products.length === 0) {
                productsHTML = `
                    <div class="text-center text-muted py-4">
                        <i data-lucide="inbox" style="width:48px;height:48px;opacity:0.3;"></i>
                        <p class="mt-2">ไม่มีสินค้าในหมวดนี้</p>
                    </div>
                `;
            } else {
                productsHTML = '<div class="row g-3">';
                cat.products.forEach(product => {
                    productsHTML += `
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="card h-100 product-card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                                 onclick="selectProductForCart(${product.product_id}, '${(product.product_name || 'ไม่มีชื่อ').replace(/'/g, "\\'")}', ${product.product_price})"
                                 onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)';"
                                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                                <div class="position-relative" style="height: 200px; overflow: hidden; background: #f8f9fa;">
                                    <img src="${product.product_image_url}" 
                                         class="card-img-top" 
                                         style="width: 100%; height: 100%; object-fit: cover;"
                                         alt="${product.product_name}"
                                         onerror="this.src='${ROOT_URL}/uploads/products/default.png';">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-success">${parseFloat(product.product_price).toLocaleString()} ฿</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title mb-1" style="min-height: 40px;">${product.product_name}</h6>
                                    <small class="text-muted">${product.product_code}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                productsHTML += '</div>';
            }

            contentHTML += `
                <div class="tab-pane fade ${isActive ? 'show active' : ''}" 
                     id="${catId}" role="tabpanel">
                    ${productsHTML}
                </div>
            `;
        });

        tabsContainer.innerHTML = tabsHTML;
        contentContainer.innerHTML = contentHTML;

        // Reinitialize icons
        if (window.lucide) lucide.createIcons();

    } catch (error) {
        console.error('Error loading products:', error);
        contentContainer.innerHTML = `
            <div class="alert alert-danger">
                <i data-lucide="alert-circle"></i> เกิดข้อผิดพลาดในการโหลดสินค้า
            </div>
        `;
    }
}

/**
 * Select product and show quantity input
 */
async function selectProductForCart(productId, productName, productPrice, vatType = 'included') {
    const swalResult = await Swal.fire({
        title: productName,
        target: document.getElementById('productGridModal'), // Fix Focus Trap: Target the modal
        html: `
            <div class="text-center">
                <div class="mb-2 fw-bold text-muted" style="font-size: 0.9rem;">ระบุจำนวนที่ต้องการ</div>
                <input type="number" id="product_qty" class="form-control form-control-lg text-center mx-auto" 
                       value="1" min="1" step="1" 
                       style="font-size: 3rem; font-weight: bold; border-radius: 15px; width: 200px; height: 100px; border: 2px solid #0d6efd;">
                <div class="mt-3 fs-5 text-primary">ราคา: ${parseFloat(productPrice).toLocaleString()} บาท</div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'เพิ่มในตะกร้า',
        cancelButtonText: 'ยกเลิก',
        showLoaderOnConfirm: true,
        didOpen: () => {
            // Disable Bootstrap's focus enforcement for the active modal
            const modalEl = document.getElementById('productGridModal');
            if (modalEl) {
                modalEl.removeAttribute('tabindex'); 
            }

            const input = document.getElementById('product_qty');
            if (input) {
                setTimeout(() => {
                    input.focus();
                    input.select();
                }, 100);

                // Allow pressing Enter to submit
                input.addEventListener('keyup', (e) => {
                    if (e.key === 'Enter') {
                        Swal.clickConfirm();
                    }
                });
            }
        },
        willClose: () => {
            // Restore tabindex when Swal closes
            const modalEl = document.getElementById('productGridModal');
            if (modalEl) {
                modalEl.setAttribute('tabindex', '-1');
            }
        },
        preConfirm: async () => {
            const qtyInput = document.getElementById('product_qty');
            const qty = parseInt(qtyInput.value);
            if (!qty || qty <= 0) {
                Swal.showValidationMessage('กรุณาระบุจำนวนที่ถูกต้อง');
                return false;
            }

            if (editingOrderId) {
                try {
                    const response = await fetch(`${ADMIN_URL}/api/add_order_item.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            order_id: editingOrderId,
                            product_id: productId,
                            qty: qty,
                            price: productPrice
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Update table behind the modal
                        if (typeof viewOrder === 'function') {
                            await viewOrder(editingOrderId);
                            updateProductGridCartSummary();
                        }
                        return { qty: qty, success: true };
                    } else {
                        Swal.showValidationMessage(data.message || 'เกิดข้อผิดพลาดในการเพิ่มสินค้า');
                        return false;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.showValidationMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                    return false;
                }
            } else {
                // Case B: Adding to NEW order cart (local state)
                const existingItemIndex = newOrderCart.items.findIndex(item => item.product_id === productId);

                if (existingItemIndex > -1) {
                    newOrderCart.items[existingItemIndex].qty += qty;
                } else {
                    newOrderCart.items.push({
                        product_id: productId,
                        product_name: productName,
                        product_code: '', 
                        qty: qty,
                        price: productPrice,
                        memo: '',
                        vat_type: vatType
                    });
                }

                // Update cart display
                updateNewOrderCartDisplay();
                updateNewOrderTotal();
                
                // Update summary in grid modal
                updateProductGridCartSummary();
                
                return { qty: qty, success: true };
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });

    if (swalResult.isConfirmed && swalResult.value && swalResult.value.success) {
        // Show a non-blocking toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
        Toast.fire({
            icon: 'success',
            title: 'เพิ่มสินค้าแล้ว',
            text: `${productName} x ${swalResult.value.qty}`
        });
    }
}

/**
 * Update cart display
 */
function updateNewOrderCartDisplay() {
    const container = document.getElementById('newOrderCartItems');
    
    // Ensure cart section is visible whenever we update it
    document.getElementById('cartSection')?.classList.remove('d-none');

    if (newOrderCart.items.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i data-lucide="shopping-cart" style="width:32px;height:32px;"></i>
                <p class="mb-0 mt-2">ยังไม่มีสินค้าในตะกร้า</p>
            </div>
        `;
    } else {
        let html = `
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>สินค้า</th>
                        <th class="text-end">ราคา</th>
                        <th class="text-center">จำนวน</th>
                        <th class="text-end">รวม</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
        `;

        newOrderCart.items.forEach((item, index) => {
            const total = item.qty * item.price;
            html += `
                <tr>
                    <td>
                        <div class="fw-bold d-flex align-items-center">
                            ${item.product_name}
                            <button class="btn btn-link btn-sm p-0 ms-2 text-info" onclick="editNewOrderItemMemo(${index})" title="ใส่หมายเหตุสินค้า">
                                <i data-lucide="edit-3" style="width:12px;height:12px;"></i>
                            </button>
                        </div>
                        <small class="text-muted">${item.product_code}</small>
                        ${item.memo ? `<div class="small text-danger mt-1">📝 ${item.memo}</div>` : ''}
                    </td>
                    <td class="text-end" style="width: 120px;">
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control text-end fw-bold" 
                                   value="${item.price}" onchange="updateNewOrderItemPrice(${index}, this.value)">
                            <span class="input-group-text p-1" style="font-size: 0.7rem;">฿</span>
                        </div>
                    </td>
                    <td class="text-center" style="width: 90px;">
                        <input type="number" class="form-control form-control-sm text-center fw-bold" 
                               value="${item.qty}" min="1" onchange="updateNewOrderItemQty(${index}, this.value)">
                    </td>
                    <td class="text-end fw-bold" style="width: 110px;">${total.toLocaleString()}</td>
                    <td class="text-center" style="width: 50px;">
                        <button onclick="removeFromNewOrderCart(${index})" class="btn btn-sm btn-outline-danger border-0">
                            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
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
    }

    lucide.createIcons();
}

/**
 * Update price for an item in the NEW order cart
 */
function updateNewOrderItemPrice(index, newPrice) {
    newOrderCart.items[index].price = parseFloat(newPrice) || 0;
    updateNewOrderCartDisplay();
    updateNewOrderTotal();
}

/**
 * Update quantity for an item in the NEW order cart
 */
function updateNewOrderItemQty(index, newQty) {
    const qty = parseInt(newQty) || 1;
    newOrderCart.items[index].qty = qty < 1 ? 1 : qty;
    updateNewOrderCartDisplay();
    updateNewOrderTotal();
}

/**
 * Edit memo for an item in the NEW order cart
 */
async function editNewOrderItemMemo(index) {
    const item = newOrderCart.items[index];
    const { value: memo } = await Swal.fire({
        title: 'หมายเหตุสินค้า',
        html: `
            <div class="text-start">
                <div class="small fw-bold text-muted mb-1">บันทึกเพิ่มเติมสำหรับ:</div>
                <div class="p-2 bg-light rounded small border mb-3 text-dark" style="font-size: 0.85rem; line-height: 1.4;">
                    ${item.product_name}
                </div>
                <textarea id="swal_memo_input" class="form-control" rows="4" 
                          placeholder="เช่น สูตรพิเศษ, เปลี่ยนขนาด..." 
                          style="font-size: 0.9rem; border-radius: 8px;">${item.memo || ''}</textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        width: '500px',
        preConfirm: () => {
            return document.getElementById('swal_memo_input').value;
        },
        didOpen: () => {
            document.getElementById('swal_memo_input').focus();
        }
    });

    if (memo !== undefined) {
        newOrderCart.items[index].memo = memo;
        updateNewOrderCartDisplay();
    }
}

/**
 * Remove item from cart
 */
function removeFromNewOrderCart(index) {
    newOrderCart.items.splice(index, 1);
    updateNewOrderCartDisplay();
    updateNewOrderTotal();
}

/**
 * Update total
 */
function updateNewOrderTotal() {
    const shipping = parseFloat(document.getElementById('newOrderShipping').value) || 0;
    const discount = parseFloat(document.getElementById('newOrderDiscount').value) || 0;
    const paycatId = parseInt(document.getElementById('newOrderPaycat').value) || 1;
    const isVat = document.getElementById('newOrderVat').checked;
    
    newOrderCart.shipping = shipping;
    newOrderCart.discount = discount;
    newOrderCart.paycat_id = paycatId;

    const subtotal = newOrderCart.items.reduce((sum, item) => sum + (item.qty * item.price), 0);
    
    // Calculate COD Fee (3%)
    let codFee = 0;
    if (paycatId === 2) {
        codFee = Math.round(subtotal * 0.03);
    }

    let total = (subtotal + shipping + codFee) - discount;

    let vatInfo = '';
    if (isVat) {
        let sumExcluded = 0;
        let sumIncluded = 0;
        newOrderCart.items.forEach(item => {
            let itemTotal = item.qty * item.price;
            if (item.vat_type === 'excluded') {
                sumExcluded += itemTotal;
            } else {
                sumIncluded += itemTotal;
            }
        });

        sumIncluded += shipping + codFee - discount;

        const vatExcluded = sumExcluded * 0.07;
        const vatIncluded = sumIncluded * 7 / 107;

        const vatAmount = vatExcluded + vatIncluded;
        const basePrice = sumExcluded + (sumIncluded * 100 / 107);

        total += vatExcluded;

        vatInfo = `<div class="text-muted" style="font-size:0.8rem; font-weight: normal;">(Tax Base: ${basePrice.toLocaleString(undefined, {minimumFractionDigits: 2})} | VAT 7%: ${vatAmount.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿)</div>`;
    }

    let codInfo = '';
    if (codFee > 0) {
        codInfo = `<div class="text-danger small" style="font-weight: normal;">+ ค่าธรรมเนียม COD 3%: ${codFee.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0})} ฿</div>`;
    }

    document.getElementById('newOrderGrandTotal').innerHTML = `
        ${codInfo}
        <span class="fs-4">${total.toLocaleString()}</span> บาท 
        ${vatInfo}
    `;
}

/**
 * Submit new order
 */
async function submitNewOrder() {
    // Validate
    if (!newOrderCart.customer) {
        Swal.fire('ผิดพลาด', 'กรุณาเลือกลูกค้า', 'error');
        return;
    }

    if (newOrderCart.items.length === 0) {
        Swal.fire('ผิดพลาด', 'กรุณาเพิ่มสินค้าอย่างน้อย 1 รายการ', 'error');
        return;
    }

    if (!newOrderCart.address) {
        Swal.fire('ผิดพลาด', 'กรุณาระบุที่อยู่จัดส่งสำหรับลูกค้ารายนี้ก่อนสร้างออเดอร์', 'error');
        return;
    }

    // Prepare data
    const orderData = {
        user_id: newOrderCart.customer.user_id,
        addr_id: newOrderCart.address ? newOrderCart.address.addr_id : null,
        items: newOrderCart.items.map(item => ({
            product_id: item.product_id,
            qty: item.qty,
            price: item.price,
            memo: item.memo || ''
        })),
        paycat_id: document.getElementById('newOrderPaycat').value,
        shipping_cost: newOrderCart.shipping,
        orders_discount: document.getElementById('newOrderDiscount').value,
        order_msg: document.getElementById('newOrderNotes').value,
        orders_is_vat: document.getElementById('newOrderVat').checked ? 1 : 0,
        orders_channel: document.getElementById('newOrderChannel').value,
        orders_country_id: document.getElementById('newOrderCountry').value,
        agent_user_id: document.getElementById('newOrderAgent').value || null
    };

    // Submit
    const submitBtn = document.getElementById('submitOrderBtn');
    const originalBtnHTML = submitBtn ? submitBtn.innerHTML : '<i data-lucide="check" class="me-2"></i>สร้างออเดอร์';
    const cancelBtns = document.querySelectorAll('#createOrderModal [data-bs-dismiss="modal"]');
    
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>กำลังสร้างออเดอร์...';
    }
    cancelBtns.forEach(btn => btn.disabled = true);

    try {
        const response = await fetch(`${ADMIN_URL}/api/create_order.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });

        const data = await response.json();

        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createOrderModal'));
            if (modal) modal.hide();

            // Show success
            Swal.fire({
                icon: 'success',
                title: 'สร้างออเดอร์สำเร็จ',
                text: `เลขที่ออเดอร์: #${data.order_id}`,
                timer: 2000,
                showConfirmButton: false
            });

            // Reload table
            loadOrderTable(1);

            // Reset cart
            newOrderCart = {
                customer: null,
                address: null,
                items: [],
                shipping: 0,
                notes: ''
            };

            // Restore button state
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHTML;
            }
            cancelBtns.forEach(btn => btn.disabled = false);

        } else {
            Swal.fire('ผิดพลาด', data.message, 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHTML;
            }
            cancelBtns.forEach(btn => btn.disabled = false);
        }

    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการสร้างออเดอร์', 'error');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHTML;
        }
        cancelBtns.forEach(btn => btn.disabled = false);
    }
}

/**
 * Search Other Agent / Therapist / User
 */
async function searchOtherAgent() {
    let currentUsers = [];
    let searchTimeout = null;

    // Function to load customers from API
    async function loadCustomers(searchTerm = '') {
        try {
            const url = searchTerm
                ? `${ADMIN_URL}/api/get_users_list.php?search=${encodeURIComponent(searchTerm)}`
                : `${ADMIN_URL}/api/get_users_list.php`;

            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) throw new Error('Failed to load users');

            return data.users;
        } catch (error) {
            console.error('Error loading users:', error);
            return [];
        }
    }

    // Function to render customer list
    function renderCustomerList(users) {
        if (!users || users.length === 0) {
            return `
                <div class="text-center text-muted py-4">
                    <i data-lucide="users" style="width:32px;height:32px;"></i>
                    <p class="mb-0 mt-2">ไม่พบข้อมูลผู้แนะนำ</p>
                </div>
            `;
        }

        return users.map(u => {
            return `
            <button type="button" class="list-group-item list-group-item-action agent-search-item" 
                    data-agent='${JSON.stringify(u)}'>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold">
                            ${u.user_name} ${u.user_lastname}${u.user_nickname ? ` (${u.user_nickname})` : ''}
                        </div>
                        <small class="text-muted">
                            C${u.user_id} | ${u.user_mobile || '-'}
                        </small>
                    </div>
                    <i data-lucide="check" style="width:16px;height:16px; opacity: 0.5;"></i>
                </div>
            </button>
            `;
        }).join('');
    }

    const { value: selectedAgent } = await Swal.fire({
        title: 'ค้นหาผู้แนะนำ / ตัวแทน / Therapist',
        target: document.getElementById('createOrderModal'), // keep focus within order modal
        html: `
            <div class="text-start" style="font-size: 0.85rem;">
                <input type="text" id="agent_search_input" 
                       class="form-control form-control-sm mb-3" 
                       placeholder="ค้นหาชื่อ, เบอร์โทร, รหัสลูกค้า...">
                <div id="agent_search_list" class="list-group" 
                     style="max-height: 250px; overflow-y: auto;">
                    <div class="text-center text-muted py-4">
                        <p class="mb-0">พิมพ์คำค้นหาเพื่อเริ่มค้นหา...</p>
                    </div>
                </div>
                <input type="hidden" id="selected_agent_data" value="">
            </div>
        `,
        width: '450px',
        showCancelButton: true,
        cancelButtonText: 'ยกเลิก',
        showConfirmButton: false,
        didOpen: () => {
            const searchInput = document.getElementById('agent_search_input');
            const agentList = document.getElementById('agent_search_list');

            searchInput.focus();

            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.trim();

                if (searchTimeout) clearTimeout(searchTimeout);

                if (!searchTerm) {
                    agentList.innerHTML = '<div class="text-center text-muted py-4"><p class="mb-0">พิมพ์คำค้นหาเพื่อเริ่มค้นหา...</p></div>';
                    return;
                }

                agentList.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <p class="mb-0 mt-2">กำลังค้นหา...</p>
                    </div>
                `;

                searchTimeout = setTimeout(async () => {
                    const users = await loadCustomers(searchTerm);
                    agentList.innerHTML = renderCustomerList(users);
                    if (window.lucide) lucide.createIcons();
                    attachAgentClickHandlers();
                }, 300);
            });

            function attachAgentClickHandlers() {
                const items = document.querySelectorAll('.agent-search-item');
                items.forEach(item => {
                    item.addEventListener('click', function () {
                        document.getElementById('selected_agent_data').value = this.dataset.agent;
                        Swal.clickConfirm();
                    });
                });
            }
        },
        preConfirm: () => {
            const dataVal = document.getElementById('selected_agent_data').value;
            return dataVal ? JSON.parse(dataVal) : null;
        }
    });

    if (selectedAgent) {
        // Parse and add to select dropdown if not exists
        const agent = selectedAgent;
        const select = document.getElementById('newOrderAgent');
        let exists = false;
        for (let option of select.options) {
            if (option.value == agent.user_id) {
                exists = true;
                select.value = agent.user_id;
                break;
            }
        }

        if (!exists) {
            const opt = document.createElement('option');
            opt.value = agent.user_id;
            opt.text = `${agent.user_name} ${agent.user_lastname} (ค้นหาพิเศษ)`;
            opt.selected = true;
            select.appendChild(opt);
        }
    }
}

/**
 * Search Other Agent / Therapist / User (Edit Order Mode)
 */
async function searchOtherAgentEdit() {
    let currentUsers = [];
    let searchTimeout = null;

    // Function to load customers from API
    async function loadCustomers(searchTerm = '') {
        try {
            const url = searchTerm
                ? `${ADMIN_URL}/api/get_users_list.php?search=${encodeURIComponent(searchTerm)}`
                : `${ADMIN_URL}/api/get_users_list.php`;

            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) throw new Error('Failed to load users');

            return data.users;
        } catch (error) {
            console.error('Error loading users:', error);
            return [];
        }
    }

    // Function to render customer list
    function renderCustomerList(users) {
        if (!users || users.length === 0) {
            return `
                <div class="text-center text-muted py-4">
                    <i data-lucide="users" style="width:32px;height:32px;"></i>
                    <p class="mb-0 mt-2">ไม่พบข้อมูลผู้แนะนำ</p>
                </div>
            `;
        }

        return users.map(u => {
            return `
            <button type="button" class="list-group-item list-group-item-action agent-search-item" 
                    data-agent='${JSON.stringify(u)}'>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold">
                            ${u.user_name} ${u.user_lastname}${u.user_nickname ? ` (${u.user_nickname})` : ''}
                        </div>
                        <small class="text-muted">
                            C${u.user_id} | ${u.user_mobile || '-'}
                        </small>
                    </div>
                    <i data-lucide="check" style="width:16px;height:16px; opacity: 0.5;"></i>
                </div>
            </button>
            `;
        }).join('');
    }

    const { value: selectedAgent } = await Swal.fire({
        title: 'ค้นหาผู้แนะนำ / ตัวแทน / Therapist',
        target: document.getElementById('orderModal'), // keep focus within order edit modal
        html: `
            <div class="text-start" style="font-size: 0.85rem;">
                <input type="text" id="agent_search_input_edit" 
                       class="form-control form-control-sm mb-3" 
                       placeholder="ค้นหาชื่อ, เบอร์โทร, รหัสลูกค้า...">
                <div id="agent_search_list_edit" class="list-group" 
                     style="max-height: 250px; overflow-y: auto;">
                    <div class="text-center text-muted py-4">
                        <p class="mb-0">พิมพ์คำค้นหาเพื่อเริ่มค้นหา...</p>
                    </div>
                </div>
                <input type="hidden" id="selected_agent_data_edit" value="">
            </div>
        `,
        width: '450px',
        showCancelButton: true,
        cancelButtonText: 'ยกเลิก',
        showConfirmButton: false,
        didOpen: () => {
            const searchInput = document.getElementById('agent_search_input_edit');
            const agentList = document.getElementById('agent_search_list_edit');

            searchInput.focus();

            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.trim();

                if (searchTimeout) clearTimeout(searchTimeout);

                if (!searchTerm) {
                    agentList.innerHTML = '<div class="text-center text-muted py-4"><p class="mb-0">พิมพ์คำค้นหาเพื่อเริ่มค้นหา...</p></div>';
                    return;
                }

                agentList.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <p class="mb-0 mt-2">กำลังค้นหา...</p>
                    </div>
                `;

                searchTimeout = setTimeout(async () => {
                    const users = await loadCustomers(searchTerm);
                    agentList.innerHTML = renderCustomerList(users);
                    if (window.lucide) lucide.createIcons();
                    attachAgentClickHandlers();
                }, 300);
            });

            function attachAgentClickHandlers() {
                const items = document.querySelectorAll('.agent-search-item');
                items.forEach(item => {
                    item.addEventListener('click', function () {
                        document.getElementById('selected_agent_data_edit').value = this.dataset.agent;
                        Swal.clickConfirm();
                    });
                });
            }
        },
        preConfirm: () => {
            const dataVal = document.getElementById('selected_agent_data_edit').value;
            return dataVal ? JSON.parse(dataVal) : null;
        }
    });

    if (selectedAgent) {
        // Parse and add to select dropdown if not exists
        const agent = selectedAgent;
        const select = document.getElementById('agentSelect');
        let exists = false;
        for (let option of select.options) {
            if (option.value == agent.user_id) {
                exists = true;
                select.value = agent.user_id;
                break;
            }
        }

        if (!exists) {
            const opt = document.createElement('option');
            opt.value = agent.user_id;
            opt.text = `${agent.user_name} ${agent.user_lastname} (ค้นหาพิเศษ)`;
            opt.selected = true;
            select.appendChild(opt);
        }
    }
}

// --- Bulk Actions ---
function initBulkActions() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    const bulkBar = document.getElementById('bulkActions');
    const countSpan = document.getElementById('selectedCount');

    checkboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
            if (checkedCount > 0) {
                bulkBar.classList.remove('d-none');
                countSpan.textContent = checkedCount;
            } else {
                bulkBar.classList.add('d-none');
            }
        });
    });
}

function printSelectedOrders() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    if (checked.length === 0) return;

    const ids = Array.from(checked).map(cb => cb.value).join(',');
    
    Swal.fire({
        title: 'พิมพ์เอกสารเป็นชุด',
        text: `คุณเลือกทั้งหมด ${checked.length} รายการ กรุณาเลือกรูปแบบเอกสาร`,
        icon: 'question',
        input: 'select',
        inputOptions: {
            'full': 'A4 - ใบเสร็จ/ใบแจ้งหนี้ (Invoice)',
            'quotation': 'A4 - ใบเสนอราคา (Quotation)',
            'receipt': 'A4 - ใบเสร็จรับเงิน (Receipt)',
            'tax_invoice': 'A4 - ใบกำกับภาษี (Tax Invoice)'
        },
        inputPlaceholder: '-- เลือกรูปแบบ --',
        showCancelButton: true,
        confirmButtonText: 'ตกลง',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
            if (!value) {
                return 'กรุณาเลือกรูปแบบการพิมพ์';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const mode = result.value;
            window.open(`${ADMIN_URL}/orders/print_batch.php?ids=${ids}&mode=${mode}`, '_blank');
        }
    });
}

/**
 * Print Order with Mode Selection
 */
function printOrder(id, isVat) {
    const options = {
        'full': 'A4 - ใบเสร็จ/ใบแจ้งหนี้ (Invoice)',
        'quotation': 'A4 - ใบเสนอราคา (Quotation)',
        'receipt': 'A4 - ใบเสร็จรับเงิน (Receipt)',
        'label': 'A5 - ฉลากแปะหน้าพัสดุ (Label)'
    };

    // Only show Tax Invoice if order has VAT
    if (isVat == 1) {
        options['tax_invoice'] = 'A4 - ใบกำกับภาษี (Tax Invoice)';
    }

    Swal.fire({
        title: 'เลือกรูปแบบการพิมพ์',
        text: 'กรุณาเลือกรูปแบบเอกสารที่ต้องการ',
        icon: 'question',
        input: 'select',
        inputOptions: options,
        inputPlaceholder: '-- เลือกรูปแบบ --',
        showCancelButton: true,
        confirmButtonText: 'ตกลง',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
            if (!value) {
                return 'กรุณาเลือกรูปแบบการพิมพ์';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const mode = result.value;
            window.open(`${ADMIN_URL}/orders/print.php?id=${id}&mode=${mode}`, '_blank');
        }
    });
}

/**
 * Open Status Change Modal
 */
let statusModalInstance = null;
function openStatusModal(orderId, orderNo, currentStatusId) {
    if (!statusModalInstance) {
        statusModalInstance = new bootstrap.Modal(document.getElementById('statusChangeModal'));
    }
    
    document.getElementById('statusModalTitle').textContent = 'เปลี่ยนสถานะ #' + orderNo;
    document.getElementById('statusOrderId').value = orderId;
    document.getElementById('statusRadioContainer').innerHTML = '<div class="text-center text-muted py-2"><div class="spinner-border spinner-border-sm"></div> กำลังโหลด...</div>';
    document.getElementById('customerSmsSection').innerHTML = '';
    
    statusModalInstance.show();
    
    // Fetch Statuses and Customer info
    Promise.all([
        fetch(`${ADMIN_URL}/api/get_all_statuses.php`).then(res => res.json()),
        fetch(`${ADMIN_URL}/api/get_order_customer_info.php?id=${orderId}`).then(res => res.json())
    ]).then(([statusData, customerData]) => {
        
        // Render Statuses
        let html = '';
        if (statusData.success && statusData.statuses) {
            statusData.statuses.forEach(st => {
                const checked = (st.orsts_id == currentStatusId) ? 'checked' : '';
                html += `
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="radio" name="orsts_id" id="status_${st.orsts_id}" value="${st.orsts_id}" ${checked}>
                        <label class="form-check-label cursor-pointer" for="status_${st.orsts_id}" style="color: ${st.orsts_color};">
                            ${st.orsts_detail}
                        </label>
                    </div>
                `;
            });
        }
        document.getElementById('statusRadioContainer').innerHTML = html;
        
        // Render SMS Checkbox
        let smsHtml = '';
        if (customerData.success && customerData.data) {
            const phone = customerData.data.user_phone;
            const name = customerData.data.user_name;
            if (phone && phone.trim() !== '') {
                smsHtml = `
                    <div class="form-check d-inline-block text-start">
                        <input class="form-check-input" type="checkbox" id="sendSmsCheck" name="send_sms" value="1">
                        <label class="form-check-label fw-bold text-success" for="sendSmsCheck">
                            ส่ง SMS ถึงลูกค้าหมายเลข <span class="text-dark">${phone}</span>
                        </label>
                    </div>
                `;
            } else {
                smsHtml = `
                    <div class="text-danger">
                        ลูกค้าชื่อ ${name} ไม่มีเบอร์โทรศัพท์
                    </div>
                `;
            }
        }
        document.getElementById('customerSmsSection').innerHTML = smsHtml;
        
    }).catch(err => {
        console.error(err);
        document.getElementById('statusRadioContainer').innerHTML = '<div class="text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
    });
}

function saveStatusChange(btnElement) {
    const form = document.getElementById('statusChangeForm');
    const orderId = document.getElementById('statusOrderId').value;
    
    // Find selected status
    const selectedRadio = form.querySelector('input[name="orsts_id"]:checked');
    if (!selectedRadio) {
        Swal.fire('ข้อผิดพลาด', 'กรุณาเลือกสถานะ', 'warning');
        return;
    }
    
    const statusId = selectedRadio.value;
    const sendSms = form.querySelector('input[name="send_sms"]')?.checked ? 1 : 0;
    
    // Disable save button
    const btn = btnElement || document.querySelector('#statusChangeModal .btn-primary');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm"></div> กำลังบันทึก...';
    btn.disabled = true;
    
    fetch(`${ADMIN_URL}/api/update_order_status.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            orders_id: orderId,
            orsts_id: statusId,
            send_sms: sendSms
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'เปลี่ยนสถานะเรียบร้อย',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                statusModalInstance.hide();
                loadOrderTable(currentPage, currentSearchQuery);
            });
        } else {
            Swal.fire('ข้อผิดพลาด', data.message || 'ไม่สามารถเปลี่ยนสถานะได้', 'error');
        }
    })
    .catch(error => {
        console.error(error);
        btn.innerHTML = originalText;
        btn.disabled = false;
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    });
}

/**
 * Refresh a single order card in place without reloading table or page
 */
function refreshSingleOrderCard(orderId) {
    if (!orderId) return Promise.resolve();
    const cardEl = document.getElementById(`order-card-${orderId}`);
    if (cardEl) {
        cardEl.style.opacity = '0.6';
        cardEl.style.pointerEvents = 'none';
    }

    return fetch(`${ADMIN_URL}/api/get_orders_table.php?single_order_id=${orderId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.card_html) {
                const targetCard = document.getElementById(`order-card-${orderId}`);
                if (targetCard) {
                    targetCard.outerHTML = data.card_html;
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                    try {
                        const newCard = document.getElementById(`order-card-${orderId}`);
                        if (newCard) {
                            const tooltips = [].slice.call(newCard.querySelectorAll('[data-bs-toggle="tooltip"]'));
                            tooltips.forEach(t => new bootstrap.Tooltip(t));
                        }
                    } catch (e) {}
                }
            }
            if (data.statusCounts && typeof updateDashboard === 'function') {
                updateDashboard(data.statusCounts);
            }
            return data;
        })
        .catch(err => {
            console.error('Error refreshing single order card:', err);
            const targetCard = document.getElementById(`order-card-${orderId}`);
            if (targetCard) {
                targetCard.style.opacity = '1';
                targetCard.style.pointerEvents = 'auto';
            }
        });
}

/**
 * Quick Change Status (Directly from Dropdown)
 */
function quickChangeStatus(orderId, statusId) {
    const cardEl = document.getElementById(`order-card-${orderId}`);
    if (cardEl) {
        cardEl.style.opacity = '0.5';
        cardEl.style.pointerEvents = 'none';
        cardEl.style.transition = 'opacity 0.2s ease';
    }

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true
    });

    fetch(`${ADMIN_URL}/api/update_order_status.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            orders_id: orderId,
            orsts_id: statusId,
            send_sms: 0
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Toast.fire({
                icon: 'success',
                title: 'เปลี่ยนสถานะเรียบร้อย'
            });
            if (currentStatusFilter != 0 && currentStatusFilter != statusId) {
                if (cardEl) {
                    cardEl.style.transition = 'all 0.35s ease';
                    cardEl.style.opacity = '0';
                    cardEl.style.transform = 'translateY(-12px) scale(0.98)';
                    setTimeout(() => {
                        cardEl.remove();
                        const remaining = document.querySelectorAll('[id^="order-card-"]');
                        if (remaining.length === 0) {
                            loadOrderTable(currentPage, currentSearchQuery);
                        }
                    }, 350);
                }
                fetch(`${ADMIN_URL}/api/get_orders_table.php?single_order_id=${orderId}`)
                    .then(r => r.json())
                    .then(d => {
                        if (d.statusCounts && typeof updateDashboard === 'function') {
                            updateDashboard(d.statusCounts);
                        }
                    })
                    .catch(() => {});
            } else {
                refreshSingleOrderCard(orderId);
            }
        } else {
            if (cardEl) {
                cardEl.style.opacity = '1';
                cardEl.style.pointerEvents = 'auto';
            }
            Swal.fire('ข้อผิดพลาด', data.message || 'ไม่สามารถเปลี่ยนสถานะได้', 'error');
        }
    })
    .catch(error => {
        console.error(error);
        if (cardEl) {
            cardEl.style.opacity = '1';
            cardEl.style.pointerEvents = 'auto';
        }
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    });
}

async function editAddressDetails(addrId, orderId) {
    try {
        Swal.fire({
            title: 'กำลังโหลดข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // 1. Get current address info
        const respAddr = await fetch(`${ADMIN_URL}/api/get_address.php?id=${addrId}`);
        const addrData = await respAddr.json();
        
        // 2. Get provinces
        const respProv = await fetch(`${ADMIN_URL}/api/get_provinces.php`);
        const provData = await respProv.json();

        if (!addrData.success) throw new Exception(addrData.message);

        const addr = addrData.address;
        const provinces = provData.provinces || [];

        const { value: formValues } = await Swal.fire({
            title: '<h5 class="fw-bold mb-0">แก้ไขรายละเอียดที่อยู่</h5>',
            html: `
                <div class="text-start p-2" style="font-family: 'Sarabun', sans-serif;">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อผู้รับ / ชื่อบริษัท</label>
                        <input id="swal-addr-name" class="form-control" value="${addr.addr_name || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">เบอร์โทรศัพท์</label>
                        <input id="swal-addr-mobile" class="form-control" value="${addr.addr_mobile || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">รายละเอียดที่อยู่ (บ้านเลขที่, ถนน)</label>
                        <textarea id="swal-addr-detail" class="form-control" rows="2">${addr.addr_detail || ''}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ตำบล / แขวง / อำเภอ</label>
                        <input id="swal-addr-detail2" class="form-control" value="${addr.addr_detail2 || ''}">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">จังหวัด</label>
                            <select id="swal-addr-province" class="form-select">
                                <option value="">-- เลือกจังหวัด --</option>
                                ${provinces.map(p => `<option value="${p.id}" ${p.id == addr.provinces_id ? 'selected' : ''}>${p.name_th}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">รหัสไปรษณีย์</label>
                            <input id="swal-addr-postcode" class="form-control" value="${addr.addr_postcode || ''}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">เลขผู้เสียภาษี (Tax ID)</label>
                        <input id="swal-addr-tax" class="form-control" value="${addr.addr_tax_id || ''}">
                    </div>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'บันทึกข้อมูล',
            cancelButtonText: 'ยกเลิก',
            width: '500px',
            preConfirm: () => {
                return {
                    addr_id: addrId,
                    order_id: orderId,
                    addr_name: document.getElementById('swal-addr-name').value,
                    addr_mobile: document.getElementById('swal-addr-mobile').value,
                    addr_detail: document.getElementById('swal-addr-detail').value,
                    addr_detail2: document.getElementById('swal-addr-detail2').value,
                    provinces_id: document.getElementById('swal-addr-province').value,
                    addr_postcode: document.getElementById('swal-addr-postcode').value,
                    addr_tax_id: document.getElementById('swal-addr-tax').value,
                    addr_type: addr.addr_type
                }
            }
        });

        if (formValues) {
            Swal.fire({
                title: 'กำลังบันทึก...',
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            for (const key in formValues) {
                formData.append(key, formValues[key]);
            }

            const response = await fetch(`${ADMIN_URL}/api/update_address_details.php`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                Swal.fire('สำเร็จ', result.message, 'success');
                // Refresh order details modal
                refreshOrderAddressSectionOnly(orderId);
            } else {
                Swal.fire('ข้อผิดพลาด', result.message, 'error');
            }
        }
    } catch (error) {
        Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
    }
}

async function refreshOrderAddressSectionOnly(orderId) {
    try {
        const response = await fetch(`${ADMIN_URL}/api/get_order_details.php?id=${orderId}`);
        const data = await response.json();

        if (data.success) {
            const order = data.order;
            const address = data.address;
            const lockedStatuses = [11, 7, 8, 9];
            const isLocked = lockedStatuses.includes(parseInt(order.orders_status));

            const shippingContainer = document.getElementById('shippingAddressCardContainer');
            if (shippingContainer && address) {
                shippingContainer.innerHTML = `
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i data-lucide="map-pin" class="me-2"></i>ที่อยู่จัดส่ง</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>ชื่อผู้รับ:</strong> <span class="fw-bold text-primary">${address.addr_name}</span> ${address.addr_mobile ? `(${address.addr_mobile})` : ''}</p>
                        <p class="mb-1"><strong>ที่อยู่:</strong> ${address.addr_detail} ${address.addr_detail2 || ''}</p>
                        <p class="mb-1"><strong>จังหวัด:</strong> ${address.province_name} ${address.addr_postcode}</p>
                        ${address.addr_forword ? `<p class="mt-2 mb-0 small fw-bold" style="color: #157347;"><i data-lucide="info" class="me-1" style="width:12px; height:12px;"></i>${address.addr_forword}</p>` : ''}
                        ${!isLocked ? `
                        <div class="mt-3 d-flex gap-2">
                            <button onclick="changeAddress(${order.orders_id}, ${order.user_id}, ${order.addr_id}, 'receiver')" class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i data-lucide="map-pin" class="me-1" style="width:14px; height:14px;"></i> เปลี่ยนที่อยู่
                            </button>
                            <button onclick="editAddressDetails(${address.addr_id}, ${order.orders_id})" class="btn btn-sm btn-primary">
                                <i data-lucide="edit-3" class="me-1" style="width:14px; height:14px;"></i> แก้ไข
                            </button>
                        </div>
                        ` : ''}
                    </div>
                `;
            }

            const billingContainer = document.getElementById('billingAddressCardContainer');
            if (billingContainer) {
                billingContainer.innerHTML = `
                    <div class="card-header bg-warning bg-opacity-10 py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i data-lucide="file-text" class="me-2"></i>ที่อยู่ใบเสร็จ / ใบกำกับภาษี</h6>
                    </div>
                    <div class="card-body">
                        ${data.billing_address ? `
                            <p class="mb-1"><strong>ชื่อในบิล:</strong> <span class="fw-bold text-warning-emphasis">${data.billing_address.addr_name}</span> ${data.billing_address.addr_mobile ? `(${data.billing_address.addr_mobile})` : ''}</p>
                            <p class="mb-1"><strong>ที่อยู่:</strong> ${data.billing_address.addr_detail} ${data.billing_address.addr_detail2 || ''}</p>
                            <p class="mb-1"><strong>จังหวัด:</strong> ${data.billing_address.province_name} ${data.billing_address.addr_postcode}</p>
                            ${data.billing_address.addr_tax_id ? `<p class="mb-1"><strong>Tax ID:</strong> ${data.billing_address.addr_tax_id}</p>` : ''}
                        ` : `
                            <div class="text-center py-2 text-muted small">
                                <i data-lucide="info" class="d-block mx-auto mb-1" style="width:16px; height:16px;"></i>
                                ยังไม่ได้ระบุที่อยู่ใบเสร็จแยกต่างหาก<br>(จะใช้ที่อยู่จัดส่งแทน)
                            </div>
                        `}
                        
                        ${!isLocked ? `
                        <div class="mt-3 d-flex gap-2">
                            <button onclick="changeAddress(${order.orders_id}, ${order.user_id}, ${order.billing_addr_id || 0}, 'billing')" class="btn btn-sm btn-outline-warning flex-grow-1">
                                <i data-lucide="map-pin" class="me-1" style="width:14px; height:14px;"></i> ${data.billing_address ? 'เปลี่ยนที่อยู่' : 'ระบุแยก'}
                            </button>
                            ${data.billing_address ? `
                            <button onclick="editAddressDetails(${data.billing_address.addr_id}, ${order.orders_id})" class="btn btn-sm btn-warning">
                                <i data-lucide="edit-3" class="me-1" style="width:14px; height:14px;"></i> แก้ไข
                            </button>
                            ` : ''}
                        </div>
                        ` : ''}
                    </div>
                `;
            }

            // Sync tax id / branch if empty
            const taxIdInput = document.getElementById('taxIdInput');
            if (taxIdInput && !taxIdInput.value) {
                const bTaxId = data.billing_address ? data.billing_address.addr_tax_id : (address ? address.addr_tax_id : '');
                taxIdInput.value = order.tax_id || bTaxId || '';
            }
            const branchCodeInput = document.getElementById('branchCodeInput');
            if (branchCodeInput && (!branchCodeInput.value || branchCodeInput.value === '00000')) {
                const bBranch = data.billing_address ? data.billing_address.addr_branch : (address ? address.addr_branch : '00000');
                branchCodeInput.value = order.branch_code || bBranch || '00000';
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    } catch (err) {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'Error: ' + err.message });
    }
}

async function openMoveOrderModal(orderId) {
    let currentUsers = [];
    let searchTimeout = null;

    // Function to load customers from API
    async function loadCustomers(searchTerm = '') {
        try {
            const url = searchTerm
                ? `${ADMIN_URL}/api/get_users_list.php?search=${encodeURIComponent(searchTerm)}`
                : `${ADMIN_URL}/api/get_users_list.php`;

            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) throw new Error('Failed to load customers');

            currentUsers = data.users;
            return data.users;
        } catch (error) {
            console.error('Error loading customers:', error);
            return [];
        }
    }

    // Function to render customer list
    function renderCustomerList(users) {
        if (!users || users.length === 0) {
            return `
                <div class="text-center text-muted py-4">
                    <i data-lucide="users" style="width:32px;height:32px;"></i>
                    <p class="mb-0 mt-2">ไม่พบข้อมูลลูกค้า</p>
                </div>
            `;
        }

        return users.map(u => {
            const profilePic = u.user_picture
                ? `${ROOT_URL}/uploads/profile/${u.user_id}/${u.user_picture}`
                : null;

            return `
            <button type="button" class="list-group-item list-group-item-action customer-item-move" 
                    data-customer='${JSON.stringify(u).replace(/'/g, "&#39;")}'>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        ${profilePic
                    ? `<img src="${profilePic}" class="rounded-circle" 
                                    style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #e9ecef;">`
                    : `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                    style="width: 50px; height: 50px; font-size: 1.2rem; font-weight: bold;">
                                ${u.user_name.charAt(0).toUpperCase()}
                               </div>`
                }
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">
                            ${u.user_name} ${u.user_lastname}${u.user_nickname ? ` (${u.user_nickname})` : ''}
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                C${u.user_id} | ${u.user_mobile || '-'}
                            </small>
                        </div>
                    </div>
                    <i data-lucide="chevron-right" class="ms-2" style="width:16px;height:16px; opacity: 0.5;"></i>
                </div>
            </button>
            `;
        }).join('');
    }

    const { value: selectedCustomer } = await Swal.fire({
        title: 'เลือกลูกค้าปลายทางที่ต้องการย้ายออเดอร์',
        html: `
            <div class="text-start">
                <input type="text" id="customer_search_move" 
                       class="form-control mb-3" 
                       placeholder="ค้นหาชื่อ, นามสกุล, เบอร์โทร, รหัสลูกค้า...">
                <div id="customer_list_move" class="list-group" 
                     style="max-height: 400px; min-height: 200px; overflow-y: auto;">
                    <div class="text-center text-muted py-5 mt-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mb-0 mt-3">กำลังโหลดข้อมูลลูกค้า...</p>
                    </div>
                </div>
                <input type="hidden" id="selected_customer_data_move" value="">
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        showConfirmButton: true,
        confirmButtonText: 'ยืนยันการย้าย',
        cancelButtonText: 'ยกเลิก',
        showCloseButton: true,
        preConfirm: () => {
            const selectedData = document.getElementById('selected_customer_data_move').value;
            if (selectedData) {
                return JSON.parse(selectedData);
            }
            Swal.showValidationMessage('กรุณาเลือกลูกค้าปลายทาง');
            return false;
        },
        didOpen: async () => {
            const searchInput = document.getElementById('customer_search_move');
            const listContainer = document.getElementById('customer_list_move');
            const hiddenInput = document.getElementById('selected_customer_data_move');

            // Load initial data
            const initialUsers = await loadCustomers('');
            listContainer.innerHTML = renderCustomerList(initialUsers);
            if(typeof lucide !== 'undefined') lucide.createIcons();

            // Handle Search
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                listContainer.innerHTML = `
                    <div class="text-center text-muted py-5 mt-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mb-0 mt-3">กำลังค้นหา...</p>
                    </div>
                `;
                
                searchTimeout = setTimeout(async () => {
                    const users = await loadCustomers(e.target.value);
                    listContainer.innerHTML = renderCustomerList(users);
                    if(typeof lucide !== 'undefined') lucide.createIcons();
                }, 500);
            });

            // Handle Selection
            listContainer.addEventListener('click', (e) => {
                const item = e.target.closest('.customer-item-move');
                if (!item) return;

                // Remove active class from all
                listContainer.querySelectorAll('.customer-item-move').forEach(el => {
                    el.classList.remove('active', 'bg-primary', 'text-white');
                    el.querySelector('.text-muted')?.classList.replace('text-muted', 'text-white-50');
                });

                // Add active class to selected
                item.classList.add('active', 'bg-primary', 'text-white');
                const mutedText = item.querySelectorAll('.text-muted');
                mutedText.forEach(el => {
                    el.classList.replace('text-muted', 'text-white-50');
                    el.dataset.wasMuted = 'true';
                });

                // Restore muted to others
                listContainer.querySelectorAll('.customer-item-move:not(.active)').forEach(el => {
                    const wasMuted = el.querySelectorAll('[data-was-muted="true"]');
                    wasMuted.forEach(mutedEl => {
                        mutedEl.classList.replace('text-white-50', 'text-muted');
                        mutedEl.removeAttribute('data-was-muted');
                    });
                });

                // Save to hidden input
                hiddenInput.value = item.dataset.customer;
            });
        }
    });

    if (selectedCustomer) {
        // Confirmation before move
        const confirmResult = await Swal.fire({
            title: 'ยืนยันการย้ายออเดอร์?',
            html: `คุณต้องการย้ายออเดอร์ไปให้ลูกค้า <b>${selectedCustomer.user_name} ${selectedCustomer.user_lastname}</b> ใช่หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, ย้ายเลย!',
            cancelButtonText: 'ยกเลิก'
        });

        if (confirmResult.isConfirmed) {
            try {
                Swal.fire({
                    title: 'กำลังย้ายออเดอร์...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const formData = new FormData();
                formData.append('orders_id', orderId);
                formData.append('new_user_id', selectedCustomer.user_id);

                const response = await fetch(`${ADMIN_URL}/api/move_order.php`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire('สำเร็จ!', 'ย้ายออเดอร์เรียบร้อยแล้ว', 'success');
                    loadOrderTable(currentPage, currentSearchQuery);
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาดในการย้ายออเดอร์');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('ข้อผิดพลาด', error.message, 'error');
            }
        }
    }
}

async function changeOrderCountry(orderId, currentCountryId) {
    try {
        Swal.fire({
            title: 'กำลังโหลดข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const response = await fetch(`${ADMIN_URL}/api/get_countries.php`);
        const data = await response.json();

        if (!data.success) throw new Error(data.message || 'ไม่สามารถโหลดข้อมูลประเทศได้');

        const countries = data.countries;
        
        let optionsHtml = '<select id="swal-input-country" class="form-select" style="width: 100%;">';
        const isUnspecified = (!currentCountryId || currentCountryId == 0) ? 'selected' : '';
        optionsHtml += `<option value="0" ${isUnspecified}>ไม่ระบุประเทศ</option>`;
        countries.forEach(c => {
            const isSelected = c.country_id == currentCountryId ? 'selected' : '';
            optionsHtml += `<option value="${c.country_id}" ${isSelected}>${c.country_name} (${c.country_code})</option>`;
        });
        optionsHtml += '</select>';

        Swal.close();

        const { value: selectedCountryId, isConfirmed } = await Swal.fire({
            title: 'เปลี่ยนประเทศ',
            html: optionsHtml,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            didOpen: () => {
                if (typeof $ !== 'undefined' && $.fn.select2) {
                    $('#swal-input-country').select2({
                        dropdownParent: $('.swal2-container'),
                        placeholder: '-- ไม่ระบุประเทศ --',
                        allowClear: true,
                        width: '100%'
                    });
                }
            },
            preConfirm: () => {
                const val = document.getElementById('swal-input-country').value;
                if (val === '' || val === null || val === undefined) {
                    return '0';
                }
                return val;
            }
        });

        if (isConfirmed && selectedCountryId !== undefined && selectedCountryId !== '' && selectedCountryId != currentCountryId) {
            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            formData.append('orders_id', orderId);
            formData.append('country_id', selectedCountryId);

            const updateRes = await fetch(`${ADMIN_URL}/api/update_order_country.php`, {
                method: 'POST',
                body: formData
            });

            const updateData = await updateRes.json();

            if (updateData.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: 'อัปเดตประเทศเรียบร้อยแล้ว',
                    timer: 1500,
                    showConfirmButton: false
                });
                if (typeof refreshSingleOrderCard === 'function') {
                    refreshSingleOrderCard(orderId);
                } else if (typeof loadOrderTable === 'function') {
                    loadOrderTable(currentPage, currentSearchQuery);
                } else {
                    location.reload();
                }
            } else {
                throw new Error(updateData.message || 'เกิดข้อผิดพลาดในการอัปเดต');
            }
        }
    } catch (error) {
        console.error(error);
        Swal.fire('ข้อผิดพลาด', error.message, 'error');
    }
}

/**
 * Edit Box Count for Order (จำนวนใบปะหน้า)
 */
function editBoxCount(orderId, currentCount) {
    Swal.fire({
        title: 'ระบุจำนวนกล่อง',
        text: 'จำนวนใบปะหน้าที่จะพิมพ์สำหรับออเดอร์นี้',
        input: 'number',
        inputValue: currentCount || 1,
        inputAttributes: {
            min: 1,
            max: 50,
            step: 1
        },
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            let count = parseInt(result.value);
            if (count > 0) {
                fetch(`${ADMIN_URL}/api/update_box_count.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ order_id: orderId, box_count: count })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', showConfirmButton: false, timer: 1000 });
                        loadOrderTable(currentPage, currentSearchQuery);
                    } else {
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
                    }
                })
                .catch(err => {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้' });
                });
            }
        }
    });
}

/**
 * Edit Inline Shipping Cost (ต้นทุนค่าส่งจริง)
 */
function editInlineShippingCost(orderId, currentCost) {
    Swal.fire({
        title: 'ต้นทุนค่าส่งที่จ่ายจริง',
        input: 'number',
        inputLabel: 'ระบุยอดเงิน (บาท)',
        inputValue: currentCost !== null ? currentCost : '',
        inputAttributes: {
            step: 0.01,
            min: 0
        },
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        preConfirm: (value) => {
            if (value === '') return null;
            return value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            formData.append('orders_id', orderId);
            if (result.value !== null) {
                formData.append('delicosttrue', result.value);
            } else {
                formData.append('delicosttrue', '');
            }

            fetch(`${ADMIN_URL}/api/update_shipping_cost_inline.php`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', showConfirmButton: false, timer: 1000 });
                    loadOrderTable(currentPage, currentSearchQuery);
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้' });
            });
        }
    });
}


/**
 * Change Order Sales Channel Inline
 */
async function changeOrderChannel(orderId, currentChannelCode) {
    try {
        Swal.fire({
            title: 'กำลังโหลดข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const response = await fetch(`${ADMIN_URL}/api/get_active_sales_channels.php`);
        const data = await response.json();

        if (!data.success) throw new Error(data.message || 'ไม่สามารถโหลดข้อมูลช่องทางขายได้');

        const channels = data.channels;
        
        let optionsHtml = '<select id="swal-input-channel" class="form-select" style="width: 100%;">';
        optionsHtml += '<option value="">-- ไม่ระบุ --</option>';
        channels.forEach(c => {
            const isSelected = c.channel_code === currentChannelCode ? 'selected' : '';
            optionsHtml += `<option value="${c.channel_code}" ${isSelected}>${c.channel_name}</option>`;
        });
        optionsHtml += '</select>';

        Swal.close();

        const { value: selectedChannel, isConfirmed } = await Swal.fire({
            title: 'เปลี่ยนช่องทางการขาย',
            html: optionsHtml,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            didOpen: () => {
                if (typeof $ !== 'undefined' && $.fn.select2) {
                    $('#swal-input-channel').select2({
                        dropdownParent: $('.swal2-container'),
                        placeholder: '-- ไม่ระบุ --',
                        allowClear: true,
                        width: '100%'
                    });
                }
            },
            preConfirm: () => {
                const val = document.getElementById('swal-input-channel').value;
                return val;
            }
        });

        if (isConfirmed && selectedChannel !== currentChannelCode) {
            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            formData.append('orders_id', orderId);
            formData.append('channel_code', selectedChannel);

            const updateRes = await fetch(`${ADMIN_URL}/api/update_order_channel.php`, {
                method: 'POST',
                body: formData
            });

            const updateData = await updateRes.json();

            if (updateData.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: 'อัปเดตช่องทางการขายเรียบร้อยแล้ว',
                    timer: 1500,
                    showConfirmButton: false
                });
                if (typeof refreshSingleOrderCard === 'function') {
                    refreshSingleOrderCard(orderId);
                } else if (typeof loadOrderTable === 'function') {
                    loadOrderTable(currentPage, currentSearchQuery);
                } else {
                    location.reload();
                }
            } else {
                throw new Error(updateData.message || 'เกิดข้อผิดพลาดในการอัปเดต');
            }
        }
    } catch (error) {
        console.error(error);
        Swal.fire('ข้อผิดพลาด', error.message, 'error');
    }
}

// Manage / Upload Parcel Images (Support Multiple Photos)
async function uploadParcelImage(orderId) {
    if (!orderId) return;

    Swal.fire({
        title: 'แนบรูปพัสดุ / ภาพสินค้าจัดเตรียม',
        html: `
            <div class="text-center p-4">
                <div class="spinner-border text-info mb-2" role="status"></div>
                <div class="text-muted small">กำลังโหลดรายการรูปภาพ...</div>
            </div>
        `,
        width: 650,
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: false
    });

    try {
        const formData = new FormData();
        formData.append('orders_id', orderId);
        formData.append('action', 'get');

        const res = await fetch(`${ADMIN_URL}/api/upload_parcel_image.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.status !== 'success') {
            throw new Error(data.message || 'ไม่สามารถโหลดรูปภาพได้');
        }

        renderParcelModalContent(orderId, data.images || []);

    } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาด', err.message || 'เกิดข้อผิดพลาดในการโหลดรูปภาพ', 'error');
    }
}

function renderParcelModalContent(orderId, images) {
    let imagesHtml = '';
    if (images && images.length > 0) {
        imagesHtml = `
            <div class="row g-2 mb-3 text-start">
                ${images.map((img, idx) => `
                    <div class="col-6 col-sm-4 position-relative">
                        <div class="card h-100 border shadow-sm p-1 text-center bg-white">
                            <a href="${ROOT_URL}/uploads/packing/${img}" target="_blank" title="คลิกเพื่อดูภาพขนาดเต็ม">
                                <img src="${ROOT_URL}/uploads/packing/${img}" class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;">
                            </a>
                            <div class="d-flex justify-content-between align-items-center mt-1 px-1">
                                <span class="badge bg-secondary" style="font-size: 0.65rem;">รูปที่ ${idx + 1}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger p-0 px-1" onclick="deleteParcelImage(${orderId}, '${img}')" title="ลบรูปนี้">
                                    <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    } else {
        imagesHtml = `
            <div class="alert alert-light border text-muted small py-3 mb-3 text-center">
                <i data-lucide="image" class="d-block mx-auto mb-1 text-secondary" style="width: 24px; height: 24px;"></i>
                ยังไม่มีรูปภาพพัสดุหรือภาพสินค้าจัดเตรียม
            </div>
        `;
    }

    const modalBodyHtml = `
        <div class="parcel-modal-body">
            ${imagesHtml}
            <div class="border rounded p-3 bg-light text-start">
                <label class="form-label fw-bold small text-dark mb-1">
                    เลือกรูปภาพเพื่อเพิ่ม (เลือกได้มากกว่า 1 รูปพร้อมกัน)
                </label>
                <input type="file" id="parcelImagesInput" class="form-control form-control-sm mb-2" accept="image/*" multiple>
                
                <div class="form-check small text-muted mb-2">
                    <input class="form-check-input" type="checkbox" id="parcelReplaceCheck">
                    <label class="form-check-label" for="parcelReplaceCheck">
                        แทนที่รูปเดิมทั้งหมด (หากไม่ติ๊ก จะเป็นการเพิ่มต่อท้าย)
                    </label>
                </div>

                <button type="button" class="btn btn-info text-white w-100 fw-bold btn-sm py-2" id="btnUploadParcelPhotos" onclick="doUploadParcelPhotos(${orderId})">
                    <i data-lucide="upload" style="width: 14px; height: 14px;" class="me-1"></i> อัปโหลดรูปภาพ
                </button>
            </div>
        </div>
    `;

    Swal.update({
        title: 'แนบรูปพัสดุ / ภาพสินค้าจัดเตรียม',
        html: modalBodyHtml,
        width: 650,
        showConfirmButton: false,
        showCloseButton: true
    });

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

async function doUploadParcelPhotos(orderId) {
    const fileInput = document.getElementById('parcelImagesInput');
    const replaceCheck = document.getElementById('parcelReplaceCheck');
    const btn = document.getElementById('btnUploadParcelPhotos');

    if (!fileInput || fileInput.files.length === 0) {
        Swal.showValidationMessage('กรุณาเลือกไฟล์รูปภาพอย่างน้อย 1 รูป');
        return;
    }

    const originalBtnText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> กำลังอัปโหลด...';
    }

    const formData = new FormData();
    formData.append('orders_id', orderId);
    formData.append('mode', replaceCheck && replaceCheck.checked ? 'replace' : 'append');

    for (let i = 0; i < fileInput.files.length; i++) {
        formData.append('packing_images[]', fileInput.files[i]);
    }

    try {
        const res = await fetch(`${ADMIN_URL}/api/upload_parcel_image.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
        }

        if (data.status === 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'success',
                title: data.message || 'อัปโหลดสำเร็จ'
            });

            // Re-render modal with updated images
            renderParcelModalContent(orderId, data.images || []);

            // Refresh only this order card in place
            if (typeof refreshSingleOrderCard === 'function') {
                refreshSingleOrderCard(orderId);
            }
        } else {
            Swal.fire('ข้อผิดพลาด', data.message || 'ไม่สามารถอัปโหลดได้', 'error');
        }
    } catch (err) {
        console.error(err);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
        }
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    }
}

async function deleteParcelImage(orderId, filename) {
    const confirm = await Swal.fire({
        title: 'ยืนยันการลบรูปภาพ?',
        text: 'คุณต้องการลบรูปภาพพัสดุนี้หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบรูปภาพ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#dc3545'
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'กำลังลบรูปภาพ...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const formData = new FormData();
        formData.append('orders_id', orderId);
        formData.append('action', 'delete');
        formData.append('filename', filename);

        const res = await fetch(`${ADMIN_URL}/api/upload_parcel_image.php`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.status === 'success') {
            renderParcelModalContent(orderId, data.images || []);
            if (typeof refreshSingleOrderCard === 'function') {
                refreshSingleOrderCard(orderId);
            }
        } else {
            Swal.fire('ข้อผิดพลาด', data.message || 'ไม่สามารถลบรูปภาพได้', 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    }
}

/**
 * Copy VIP Invite Link (30 Days Token)
 */
function copyVipInvite(userId, btnEl) {
    if (!userId) return;
    const originalText = btnEl ? btnEl.innerHTML : '';
    if (btnEl) {
        btnEl.disabled = true;
        btnEl.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังสร้างลิงก์...';
    }

    const formData = new FormData();
    formData.append('user_id', userId);

    fetch(`${ADMIN_URL}/api/generate_vip_invite.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (btnEl) {
            btnEl.disabled = false;
            btnEl.innerHTML = originalText;
        }

        if (data.status === 'success') {
            // Copy template to clipboard
            navigator.clipboard.writeText(data.message_template).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'คัดลอกลิงก์เชิญ VIP เรียบร้อยแล้ว!',
                    html: `
                        <div class="text-start p-2 bg-light rounded mt-2 border" style="font-size: 0.85rem; max-height: 200px; overflow-y: auto;">
                            <p class="mb-1 text-success fw-bold">✓ คัดลอกข้อความและลิงก์สำหรับส่งแชต LINE / Facebook แล้ว:</p>
                            <pre class="mb-0 text-muted" style="white-space: pre-wrap; font-family: inherit;">${data.message_template}</pre>
                        </div>
                        <div class="mt-2 text-muted small">
                            รหัสสมาชิก: <strong>${data.c_code}</strong> | อายุลิงก์: 30 วัน
                        </div>
                    `,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#0B3B35'
                });
            }).catch(err => {
                // Fallback prompt
                prompt('คัดลอกข้อความนี้ส่งให้ลูกค้า:', data.message_template);
            });
        } else {
            Swal.fire('ข้อผิดพลาด', data.message || 'ไม่สามารถสร้างลิงก์ VIP ได้', 'error');
        }
    })
    .catch(err => {
        if (btnEl) {
            btnEl.disabled = false;
            btnEl.innerHTML = originalText;
        }
        Swal.fire('ข้อผิดพลาด', err.message, 'error');
    });
}
