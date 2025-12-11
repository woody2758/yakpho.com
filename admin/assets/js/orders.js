/**
 * Order Management - Full AJAX
 */

let orderModal;
let currentPage = 1;
let currentStatusFilter = 0;
let currentOrderId = 0;
let currentUserIdFilter = 0;

// Initialize modal on page load
document.addEventListener('DOMContentLoaded', function () {
    orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
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

    // Show loading
    tableContainer.style.opacity = '0.5';
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

    let url = `${ADMIN_URL}/api/get_orders_table.php?page=${page}&search=${search}&status=${currentStatusFilter}`;
    if (currentUserIdFilter) {
        url += `&user_id=${currentUserIdFilter}`;
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
                // Initialize Tooltips
                try {
                    const tooltipTriggerList = [].slice.call(tableContainer.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                } catch (e) {
                    console.warn('Bootstrap Tooltip initialization failed:', e);
                }
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
    Object.values(counts).forEach(c => total += parseInt(c));

    html += `
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card h-100 shadow-sm border-0" style="cursor:pointer; transition: transform 0.2s;" 
                 onclick="filterStatus(0)" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle p-2 me-2 bg-light text-primary">
                        <i data-lucide="list" style="width:20px; height:20px;"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-0 text-muted small">ทั้งหมด</h6>
                        <h5 class="card-title mb-0 fw-bold">${total}</h5>
                    </div>
                </div>
            </div>
        </div>
    `;

    statuses.forEach(status => {
        const count = counts[status.orsts_id] || 0;
        const isActive = currentStatusFilter === status.orsts_id;
        const borderStyle = isActive ? `border: 2px solid ${status.orsts_color} !important;` : '';

        html += `
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <div class="card h-100 shadow-sm border-0" style="cursor:pointer; transition: transform 0.2s; ${borderStyle}" 
                     onclick="filterStatus(${status.orsts_id})" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="rounded-circle p-2 me-2" style="background-color: ${status.orsts_color}20; color: ${status.orsts_color};">
                            <i data-lucide="package" style="width:20px; height:20px;"></i>
                        </div>
                        <div>
                            <h6 class="card-subtitle mb-0 text-muted small">${status.orsts_detail}</h6>
                            <h5 class="card-title mb-0 fw-bold" style="color: ${status.orsts_color};">${count}</h5>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    dashboard.innerHTML = html;
    lucide.createIcons();
}

function filterStatus(statusId) {
    currentStatusFilter = statusId;
    loadOrderTable(1, document.getElementById('searchInput').value);
}

/**
 * View Order Details
 */
async function viewOrder(id) {
    currentOrderId = id;

    // Show loading in modal
    document.getElementById('modalContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    orderModal.show();

    try {
        const response = await fetch(`${ADMIN_URL}/api/get_order_details.php?id=${id}`);
        const data = await response.json();

        if (data.success) {
            renderOrderDetails(data);
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
    let html = `
        <div class="row g-4">
            <!-- Order Info -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i data-lucide="shopping-cart" class="me-2"></i>รายการสินค้า</h6>
                            <button onclick="addOrderItem(${order.orders_id})" class="btn btn-sm btn-success">
                                <i data-lucide="plus" style="width:14px;height:14px;"></i> เพิ่มสินค้า
                            </button>
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
    items.forEach(item => {
        const itemTotal = parseFloat(item.subtotal);
        totalAmount += itemTotal;
        const img = item.product_img ? `${ROOT_URL}/uploads/products/${item.product_img}` : `${ADMIN_URL}/assets/images/placeholder.png`;

        html += `
            <tr>
                <td>
                    <img src="${img}" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">
                </td>
                <td>
                    <div class="fw-bold">${item.product_name}</div>
                    <small class="text-muted">${item.product_code}</small>
                </td>
                <td class="text-end">${parseFloat(item.unit_price).toLocaleString()}</td>
                <td class="text-center">${item.ordetail_qty}</td>
                <td class="text-end fw-bold">${itemTotal.toLocaleString()}</td>
                <td class="text-center">
                    <button onclick="removeOrderItem(${item.ordetail_id}, ${order.orders_id})" 
                            class="btn btn-sm btn-danger" 
                            title="ลบสินค้า"
                            data-bs-toggle="tooltip">
                        <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += `
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">ยอดรวมทั้งสิ้น</td>
                                        <td class="text-end fw-bold text-primary fs-5">${parseFloat(order.orders_grandtotal).toLocaleString()} บาท</td>
                                    </tr>
                                </tfoot>
                            </table>
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

                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i data-lucide="map-pin" class="me-2"></i>ที่อยู่จัดส่ง</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>ที่อยู่:</strong> ${address.addr_detail} ${address.addr_detail2 || ''}</p>
                        <p class="mb-1"><strong>จังหวัด:</strong> ${address.province_name} ${address.addr_postcode}</p>
                        <div class="mt-3">
                            <button onclick="changeAddress(${order.orders_id}, ${order.user_id})" class="btn btn-sm btn-outline-primary w-100 mb-2">
                                <i data-lucide="map-pin" class="me-1" style="width:14px; height:14px;"></i> เปลี่ยนที่อยู่
                            </button>
                            <button onclick="editAddress(${order.orders_id}, ${order.user_id})" class="btn btn-sm btn-outline-secondary w-100">
                                <i data-lucide="edit-2" class="me-1" style="width:14px; height:14px;"></i> แก้ไขรายละเอียด
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('modalContent').innerHTML = html;
    lucide.createIcons();
}

/**
 * Update Order Status
 */
async function updateStatus() {
    const statusId = document.getElementById('statusSelect').value;
    const notifyEmail = document.getElementById('notifyEmail').checked;

    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`${ADMIN_URL}/api/update_order_status.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                orders_id: currentOrderId,
                orsts_id: statusId,
                notify_email: notifyEmail
            })
        });

        const data = await response.json();
        Swal.close();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: 'อัปเดตสถานะเรียบร้อยแล้ว',
                timer: 1500,
                showConfirmButton: false
            });

            // Reload table to reflect changes
            loadOrderTable(currentPage, document.getElementById('searchInput').value);

            // Close modal
            orderModal.hide();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถอัปเดตสถานะได้'
            });
        }
    } catch (error) {
        Swal.close();
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

    fetch(`${ADMIN_URL}/api/get_user_mini.php?user_id=${userId}&orders_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.data;
                document.getElementById('customerUserId').value = user.user_id;
                document.getElementById('customerName').value = user.user_name;
                document.getElementById('customerLastname').value = user.user_lastname;
                document.getElementById('customerMobile').value = user.user_mobile;
                document.getElementById('customerEmail').value = user.user_email;
                document.getElementById('customerOrdersMsg').value = user.orders_msg || ''; // Use orders_msg
                modal.show();
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
    console.log('Reorder', orderId);
    Swal.fire('Info', 'ฟีเจอร์นี้กำลังพัฒนา', 'info');
}

function editAddress(orderId, userId) {
    // Show loading
    Swal.fire({
        title: 'กำลังโหลด...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch address details AND provinces
    Promise.all([
        fetch(`${ADMIN_URL}/api/get_order_address_details.php?order_id=${orderId}`).then(res => res.json()),
        fetch(`${ADMIN_URL}/api/get_provinces.php`).then(res => res.json())
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

                Swal.fire({
                    title: 'แก้ไขที่อยู่ผู้รับ',
                    html: `
        <form id = "editAddressForm" class="text-start" >
            <input type="hidden" name="addr_id" value="${addr.addr_id}">
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้รับ</label>
                    <input type="text" class="form-control" name="addr_name" value="${addr.addr_name}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ที่อยู่</label>
                    <textarea class="form-control" name="addr_detail" rows="3" required>${addr.addr_detail}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">จังหวัด</label>
                    <select class="form-select" name="provinces_id" required>
                        ${provinceOptions}
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">เพิ่มเติม (แขวง/ตำบล/เขต/อำเภอ)</label>
                    <input type="text" class="form-control" name="addr_detail2" value="${addr.addr_detail2 || ''}">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">รหัสไปรษณีย์</label>
                        <input type="text" class="form-control" name="addr_postcode" value="${addr.addr_postcode}" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" name="addr_mobile" value="${addr.addr_mobile}" required>
                    </div>
                </div>
            </form>
    `,
                    width: '600px',
                    showCancelButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    preConfirm: () => {
                        const form = document.getElementById('editAddressForm');
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return false;
                        }
                        return new FormData(form);
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveAddressDetails(result.value);
                    }
                });
            } else {
                Swal.fire('Error', addrData.message || provinceData.message || 'ไม่พบข้อมูล', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        });
}

function saveAddressDetails(formData) {
    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`${ADMIN_URL}/api/update_address_details.php`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    loadOrderTable(currentPage);
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
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
                // Add Shop Default Option
                html += `<button type="button" class="list-group-item list-group-item-action" onclick="saveOrderAddress(${orderId}, 'sender', 0)">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i data-lucide="store" style="width:14px; height:14px;"></i> Shop Default</h6>
                        </div>
                        <small class="text-muted">ใช้ที่อยู่ร้านค้า</small>
                        </button>`;

                // Add Customer Addresses
                data.data.forEach(addr => {
                    html += `<button type="button" class="list-group-item list-group-item-action" onclick="saveOrderAddress(${orderId}, 'sender', ${addr.addr_id})">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${addr.addr_name}</h6>
                                <small>${addr.addr_mobile}</small>
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

function changeAddress(orderId, userId) {
    const modalEl = document.getElementById('addressSelectModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);

    document.getElementById('addressSelectTitle').innerText = 'เปลี่ยนที่อยู่จัดส่ง (Receiver)';
    const list = document.getElementById('addressList');
    list.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    fetch(`${ADMIN_URL}/api/get_customer_addresses.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                if (data.data.length === 0) {
                    html = '<div class="text-center p-3 text-muted">ไม่พบที่อยู่ลูกค้า</div>';
                } else {
                    data.data.forEach(addr => {
                        html += `<button type="button" class="list-group-item list-group-item-action" onclick="saveOrderAddress(${orderId}, 'receiver', ${addr.addr_id})">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${addr.addr_name}</h6>
                                    <small>${addr.addr_mobile}</small>
                                </div>
                                <p class="mb-1 small text-muted">${addr.addr_detail} ${addr.addr_detail2 || ''} ${addr.addr_postcode}</p>
                                </button>`;
                    });
                }
                list.innerHTML = html;
            } else {
                list.innerHTML = `<div class="text-danger p-3 text-center">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            list.innerHTML = '<div class="text-danger p-3 text-center">ไม่สามารถโหลดข้อมูลที่อยู่ได้</div>';
        });
}

function saveOrderAddress(orderId, type, addrId) {
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
                modal.hide();

                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    showConfirmButton: false,
                    timer: 1500
                });

                // Reload table
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

// --- User Image Functions ---
function openUserImageModal(userId, currentImage) {
    document.getElementById('user_image_id').value = userId;
    const img = document.getElementById('current_user_image');
    if (currentImage) {
        img.src = `${ROOT_URL}/uploads/profile/${userId}/${currentImage}`;
    } else {
        img.src = `${ADMIN_ASSETS}/images/placeholder.png`;
    }
    new bootstrap.Modal(document.getElementById('userImageModal')).show();
}

function saveUserImage() {
    const form = document.getElementById('userImageForm');
    const formData = new FormData(form);

    fetch(`${ADMIN_URL}/api/update_user_picture.php`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'อัปเดตเรียบร้อย',
                    showConfirmButton: false,
                    timer: 1500
                });
                bootstrap.Modal.getInstance(document.getElementById('userImageModal')).hide();
                loadOrderTable(currentPage); // Reload to show new image
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => console.error('Error:', error));
}

// --- Shipping Functions ---
function openShippingModal(orderId, transcatId, tracking) {
    document.getElementById('shipping_order_id').value = orderId;
    document.getElementById('shipping_tracking').value = tracking || '';

    // Load shipping options
    fetch(`${ADMIN_URL}/api/get_transcat_table.php?lang=th`)
        .then(res => res.json())
        .then(data => {
            fetch(`${ADMIN_URL}/api/get_transcat_options.php`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const select = document.getElementById('shipping_transcat_id');
                        select.innerHTML = '<option value="">-- เลือกขนส่ง --</option>';
                        res.data.forEach(t => {
                            const selected = t.transcat_id == transcatId ? 'selected' : '';
                            select.innerHTML += `<option value="${t.transcat_id}" ${selected}>${t.transcat_name}</option>`;
                        });
                        new bootstrap.Modal(document.getElementById('shippingModal')).show();
                    }
                });
        });
}

function saveShipping() {
    const form = document.getElementById('shippingForm');
    const formData = new FormData(form);
    const data = {
        orders_id: formData.get('orders_id'),
        transcat_id: formData.get('transcat_id'),
        orders_tracking: formData.get('orders_tracking')
    };

    fetch(`${ADMIN_URL}/api/update_order_shipping.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
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
                    btnElement.className = 'btn btn-sm w-100 fw-bold btn-danger';
                    btnElement.innerHTML = '<i data-lucide="printer" style="width:12px; height:12px;"></i> พิมพ์แล้ว';
                } else {
                    btnElement.className = 'btn btn-sm w-100 fw-bold btn-success';
                    btnElement.innerHTML = '<i data-lucide="printer" style="width:12px; height:12px;"></i> ยังไม่พิมพ์';
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

function uploadSlip(orderId, input) {
    if (input.files && input.files[0]) {
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('slip_image', input.files[0]);

        Swal.fire({
            title: 'กำลังอัพโหลด...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(`${ADMIN_URL}/api/upload_slip.php`, {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: 'อัพโหลดสลิปเรียบร้อยแล้ว',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        // Refresh modal content
                        viewOrder(orderId);
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการอัพโหลด', 'error');
            });
    }
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
 * Add item to order
 */
async function addOrderItem(orderId) {
    // 1. โหลดรายการสินค้า
    Swal.fire({
        title: 'กำลังโหลดสินค้า...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const productsResponse = await fetch(`${ADMIN_URL}/api/get_products_simple.php`);
        const productsData = await productsResponse.json();

        if (!productsData.success) {
            Swal.fire('ผิดพลาด', 'ไม่สามารถโหลดสินค้าได้', 'error');
            return;
        }

        // 2. สร้าง options
        const productOptions = productsData.products.map(p =>
            `<option value="${p.product_id}" data-price="${p.product_price}" data-stock="${p.product_stock}">
                ${p.product_name} (${p.product_code}) - ${parseFloat(p.product_price).toLocaleString()} บาท [คงเหลือ: ${p.product_stock}]
            </option>`
        ).join('');

        // 3. แสดง SweetAlert Form
        const { value: formValues } = await Swal.fire({
            title: 'เพิ่มสินค้าเข้าออเดอร์',
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold">เลือกสินค้า</label>
                        <select id="product_select" class="form-select">
                            <option value="">-- เลือกสินค้า --</option>
                            ${productOptions}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">จำนวน</label>
                        <input type="number" id="qty_input" class="form-control" 
                               value="1" min="1">
                        <small class="text-muted" id="stock_info"></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ราคา/หน่วย (บาท)</label>
                        <input type="number" id="price_input" class="form-control" 
                               step="0.01" min="0">
                    </div>
                    <div class="alert alert-info small">
                        <i data-lucide="info" style="width:14px;height:14px;"></i>
                        สามารถแก้ไขราคาได้ตามต้องการ
                    </div>
                </div>
            `,
            width: '500px',
            showCancelButton: true,
            confirmButtonText: 'เพิ่มสินค้า',
            cancelButtonText: 'ยกเลิก',
            didOpen: () => {
                lucide.createIcons();
                // Auto-fill price and show stock when product selected
                document.getElementById('product_select').addEventListener('change', (e) => {
                    const selectedOption = e.target.selectedOptions[0];
                    const price = selectedOption.dataset.price || 0;
                    const stock = selectedOption.dataset.stock || 0;
                    document.getElementById('price_input').value = price;
                    document.getElementById('stock_info').textContent = `สต็อกคงเหลือ: ${stock} ชิ้น`;
                });
            },
            preConfirm: () => {
                const productId = document.getElementById('product_select').value;
                const qty = document.getElementById('qty_input').value;
                const price = document.getElementById('price_input').value;

                if (!productId) {
                    Swal.showValidationMessage('กรุณาเลือกสินค้า');
                    return false;
                }
                if (!qty || qty <= 0) {
                    Swal.showValidationMessage('กรุณาระบุจำนวนที่ถูกต้อง');
                    return false;
                }
                if (!price || price < 0) {
                    Swal.showValidationMessage('กรุณาระบุราคาที่ถูกต้อง');
                    return false;
                }

                return { productId, qty, price };
            }
        });

        if (!formValues) return;

        // 4. บันทึก
        Swal.fire({
            title: 'กำลังเพิ่มสินค้า...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const response = await fetch(`${ADMIN_URL}/api/add_order_item.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                product_id: formValues.productId,
                qty: formValues.qty,
                price: formValues.price
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: `เพิ่ม ${data.product_name} แล้ว`,
                timer: 1500,
                showConfirmButton: false
            });
            viewOrder(orderId); // Reload order details
        } else {
            Swal.fire('ผิดพลาด', data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเพิ่มสินค้า', 'error');
    }
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
    // Reset cart
    newOrderCart = {
        customer: null,
        address: null,
        items: [],
        shipping: 0,
        notes: ''
    };

    // Show customer selector first
    const customer = await showCustomerSelector();
    if (!customer) return;

    newOrderCart.customer = customer;

    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('createOrderModal'));

    // Update customer info display
    updateNewOrderCustomerDisplay();

    // Load customer addresses
    await loadCustomerAddresses(customer.user_id);

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

            if (!data.success) {
                throw new Error('Failed to load customers');
            }

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
                        <small class="text-muted">
                            C${u.user_id} | ${u.user_mobile || '-'}
                        </small>
                    </div>
                    <i data-lucide="chevron-right" style="width:16px;height:16px;"></i>
                </div>
            </button>
            `;
        }).join('');
    }

    // Initial load
    Swal.fire({
        title: 'กำลังโหลดข้อมูลลูกค้า...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const initialUsers = await loadCustomers();

    if (initialUsers.length === 0) {
        Swal.fire('ผิดพลาด', 'ไม่สามารถโหลดข้อมูลลูกค้าได้', 'error');
        return null;
    }

    const { value: selectedCustomer } = await Swal.fire({
        title: 'เลือกลูกค้า',
        html: `
            <div class="text-start">
                <input type="text" id="customer_search" 
                       class="form-control mb-3" 
                       placeholder="ค้นหาชื่อ, นามสกุล, ชื่อเล่น, อีเมล, เบอร์โทร, รหัสลูกค้า...">
                <div id="customer_list" class="list-group" 
                     style="max-height: 400px; overflow-y: auto;">
                    ${renderCustomerList(initialUsers)}
                </div>
                <a href="${ADMIN_URL}/users/" target="_blank"
                   class="btn btn-success btn-sm mt-3 w-100">
                    <i data-lucide="user-plus" style="width:14px;height:14px;"></i> 
                    เพิ่มลูกค้าใหม่ (เปิดหน้าใหม่)
                </a>
                <input type="hidden" id="selected_customer_data" value="">
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        showConfirmButton: true,
        confirmButtonText: 'เลือก',
        cancelButtonText: 'ยกเลิก',
        customClass: {
            confirmButton: 'd-none' // Hide confirm button visually
        },
        preConfirm: () => {
            const selectedData = document.getElementById('selected_customer_data').value;
            if (selectedData) {
                return JSON.parse(selectedData);
            }
            return null;
        },
        didOpen: () => {
            lucide.createIcons();

            const searchInput = document.getElementById('customer_search');
            const customerList = document.getElementById('customer_list');

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
 * Quick add customer
 */
async function quickAddCustomer() {
    const { value: formValues } = await Swal.fire({
        title: 'เพิ่มลูกค้าใหม่',
        html: `
            <div class="text-start">
                <div class="mb-2">
                    <label class="form-label">ชื่อ *</label>
                    <input type="text" id="quick_cust_name" class="form-control">
                </div>
                <div class="mb-2">
                    <label class="form-label">นามสกุล *</label>
                    <input type="text" id="quick_cust_lastname" class="form-control">
                </div>
                <div class="mb-2">
                    <label class="form-label">เบอร์โทร *</label>
                    <input type="text" id="quick_cust_mobile" class="form-control">
                </div>
                <div class="mb-2">
                    <label class="form-label">อีเมล</label>
                    <input type="email" id="quick_cust_email" class="form-control">
                </div>
            </div>
        `,
        confirmButtonText: 'เพิ่มลูกค้า',
        showCancelButton: true,
        preConfirm: () => {
            const name = document.getElementById('quick_cust_name').value;
            const lastname = document.getElementById('quick_cust_lastname').value;
            const mobile = document.getElementById('quick_cust_mobile').value;
            const email = document.getElementById('quick_cust_email').value;

            if (!name || !lastname || !mobile) {
                Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                return false;
            }

            return { name, lastname, mobile, email };
        }
    });

    if (!formValues) return null;

    // Save customer via API
    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const response = await fetch(`${ADMIN_URL}/api/add_customer.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_name: formValues.name,
                user_lastname: formValues.lastname,
                user_mobile: formValues.mobile,
                user_email: formValues.email || '',
                user_password: Math.random().toString(36).slice(-8) // Random password
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'เพิ่มลูกค้าสำเร็จ',
                timer: 1500,
                showConfirmButton: false
            });

            // Return new customer data
            return {
                user_id: data.user_id,
                user_name: formValues.name,
                user_lastname: formValues.lastname,
                user_mobile: formValues.mobile,
                user_email: formValues.email
            };
        } else {
            Swal.fire('ผิดพลาด', data.message, 'error');
            return null;
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'ไม่สามารถเพิ่มลูกค้าได้', 'error');
        return null;
    }
}

/**
 * Update customer display in modal
 */
function updateNewOrderCustomerDisplay() {
    const customer = newOrderCart.customer;
    if (!customer) return;

    document.getElementById('selectedCustomerInfo').innerHTML = `
        <div class="d-flex align-items-center">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3"
                 style="width: 50px; height: 50px; font-size: 1.2rem;">
                ${customer.user_name.charAt(0).toUpperCase()}
            </div>
            <div>
                <h6 class="mb-0">${customer.user_name} ${customer.user_lastname}</h6>
                <small class="text-muted">${customer.user_email || '-'} | ${customer.user_mobile || '-'}</small>
            </div>
        </div>
    `;

    lucide.createIcons();
}

/**
 * Load customer addresses
 */
async function loadCustomerAddresses(userId) {
    try {
        const response = await fetch(`${ADMIN_URL}/api/get_customer_addresses.php?user_id=${userId}`);
        const data = await response.json();

        if (data.success && data.data.length > 0) {
            // Show address section
            document.getElementById('addressSection').classList.remove('d-none');

            // Set first address as default
            newOrderCart.address = data.data[0];

            // Display address
            const addr = data.data[0];
            document.getElementById('selectedAddressInfo').innerHTML = `
                <div>
                    <strong>${addr.addr_name}</strong><br>
                    ${addr.addr_detail} ${addr.addr_detail2 || ''}<br>
                    ${addr.province_name || ''} ${addr.addr_postcode}<br>
                    <small class="text-muted">📞 ${addr.addr_mobile}</small>
                </div>
                ${data.data.length > 1 ? `<button onclick="selectDifferentAddress(${userId})" class="btn btn-sm btn-outline-primary mt-2">เปลี่ยนที่อยู่</button>` : ''}
            `;

            // Show cart and settings sections
            document.getElementById('cartSection').classList.remove('d-none');
            document.getElementById('settingsSection').classList.remove('d-none');
        } else {
            // No address - show message
            document.getElementById('addressSection').classList.remove('d-none');
            document.getElementById('selectedAddressInfo').innerHTML = `
                <div class="text-center text-warning">
                    <i data-lucide="alert-triangle" style="width:24px;height:24px;"></i>
                    <p class="mb-0 mt-2">ลูกค้ายังไม่มีที่อยู่ในระบบ</p>
                    <small class="text-muted">สามารถสร้างออเดอร์ได้ แต่ควรเพิ่มที่อยู่ภายหลัง</small>
                </div>
            `;

            // Still show cart
            document.getElementById('cartSection').classList.remove('d-none');
            document.getElementById('settingsSection').classList.remove('d-none');
        }

        lucide.createIcons();

    } catch (error) {
        console.error('Error loading addresses:', error);
    }
}

/**
 * Add product to new order cart
 */
async function addProductToNewOrder() {
    // Same as addOrderItem but for new order cart
    // Load products
    Swal.fire({
        title: 'กำลังโหลดสินค้า...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const productsResponse = await fetch(`${ADMIN_URL}/api/get_products_simple.php`);
        const productsData = await productsResponse.json();

        if (!productsData.success) {
            Swal.fire('ผิดพลาด', 'ไม่สามารถโหลดสินค้าได้', 'error');
            return;
        }

        // Create options
        const productOptions = productsData.products.map(p =>
            `<option value="${p.product_id}" data-price="${p.product_price}" data-name="${p.product_name}" data-code="${p.product_code}">
                ${p.product_name} (${p.product_code}) - ${parseFloat(p.product_price).toLocaleString()} บาท
            </option>`
        ).join('');

        const { value: formValues } = await Swal.fire({
            title: 'เพิ่มสินค้า',
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold">เลือกสินค้า</label>
                        <select id="product_select_new" class="form-select">
                            <option value="">-- เลือกสินค้า --</option>
                            ${productOptions}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">จำนวน</label>
                        <input type="number" id="qty_input_new" class="form-control" value="1" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ราคา/หน่วย</label>
                        <input type="number" id="price_input_new" class="form-control" step="0.01" min="0">
                    </div>
                </div>
            `,
            width: '500px',
            showCancelButton: true,
            confirmButtonText: 'เพิ่ม',
            didOpen: () => {
                document.getElementById('product_select_new').addEventListener('change', (e) => {
                    const option = e.target.selectedOptions[0];
                    document.getElementById('price_input_new').value = option.dataset.price || 0;
                });
            },
            preConfirm: () => {
                const select = document.getElementById('product_select_new');
                const productId = select.value;
                const option = select.selectedOptions[0];
                const qty = parseInt(document.getElementById('qty_input_new').value);
                const price = parseFloat(document.getElementById('price_input_new').value);

                if (!productId || qty <= 0 || price < 0) {
                    Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                    return false;
                }

                return {
                    product_id: productId,
                    product_name: option.dataset.name,
                    product_code: option.dataset.code,
                    qty,
                    price
                };
            }
        });

        if (!formValues) return;

        // Add to cart
        newOrderCart.items.push(formValues);

        // Update display
        updateNewOrderCartDisplay();
        updateNewOrderTotal();

    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาด', 'error');
    }
}

/**
 * Update cart display
 */
function updateNewOrderCartDisplay() {
    const container = document.getElementById('newOrderCartItems');

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
                        <div class="fw-bold">${item.product_name}</div>
                        <small class="text-muted">${item.product_code}</small>
                    </td>
                    <td class="text-end">${item.price.toLocaleString()}</td>
                    <td class="text-center">${item.qty}</td>
                    <td class="text-end fw-bold">${total.toLocaleString()}</td>
                    <td class="text-center">
                        <button onclick="removeFromNewOrderCart(${index})" class="btn btn-sm btn-danger">
                            <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
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
    newOrderCart.shipping = shipping;

    const subtotal = newOrderCart.items.reduce((sum, item) => sum + (item.qty * item.price), 0);
    const total = subtotal + shipping;

    document.getElementById('newOrderGrandTotal').textContent = `${total.toLocaleString()} บาท`;
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

    // Prepare data
    const orderData = {
        user_id: newOrderCart.customer.user_id,
        addr_id: newOrderCart.address ? newOrderCart.address.addr_id : null,
        items: newOrderCart.items.map(item => ({
            product_id: item.product_id,
            qty: item.qty,
            price: item.price
        })),
        shipping_cost: newOrderCart.shipping,
        order_msg: document.getElementById('newOrderNotes').value
    };

    // Submit
    Swal.fire({
        title: 'กำลังสร้างออเดอร์...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

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
            modal.hide();

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

        } else {
            Swal.fire('ผิดพลาด', data.message, 'error');
        }

    } catch (error) {
        console.error('Error:', error);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการสร้างออเดอร์', 'error');
    }
}
