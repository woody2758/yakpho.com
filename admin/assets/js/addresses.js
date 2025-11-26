/**
 * Address Management JavaScript
 * Handles CRUD operations for user addresses via AJAX
 */

let currentUserId = null;
let currentUserName = '';
let provincesLoadedForAddress = false;

/**
 * Open Address Modal
 */
function openAddressModal(userId, userName) {
    currentUserId = userId;
    currentUserName = userName;

    // Update modal title
    document.getElementById('addressModalTitle').textContent = `ที่อยู่ของ ${userName}`;

    // Hide form, show list
    document.getElementById('addressFormContainer').style.display = 'none';

    // Load provinces if not loaded
    if (!provincesLoadedForAddress) {
        loadProvincesForAddress();
    }

    // Load addresses
    loadAddresses(userId);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('addressModal'));
    modal.show();
}

/**
 * Load provinces for address form
 */
async function loadProvincesForAddress() {
    try {
        const response = await fetch(`${ADMIN_URL}/api/get_provinces.php`);
        const data = await response.json();

        if (data.success) {
            const select = document.getElementById('provinces_id');
            select.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';

            data.provinces.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = p.name_th;
                select.appendChild(option);
            });

            provincesLoadedForAddress = true;
        }
    } catch (error) {
        console.error('Error loading provinces:', error);
    }
}

/**
 * Load addresses for a user
 */
function loadAddresses(userId) {
    const container = document.getElementById('addressList');
    container.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    fetch(`${ADMIN_URL}/api/get_addresses.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderAddressCards(data.addresses);
            } else {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i data-lucide="alert-circle"></i>
                        ${data.message || 'ไม่สามารถโหลดข้อมูลได้'}
                    </div>
                `;
                lucide.createIcons();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i data-lucide="alert-circle"></i>
                    เกิดข้อผิดพลาดในการโหลดข้อมูล
                </div>
            `;
            lucide.createIcons();
        });
}

/**
 * Render address cards
 */
function renderAddressCards(addresses) {
    const container = document.getElementById('addressList');

    if (addresses.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i data-lucide="inbox" style="width:48px;height:48px;opacity:0.3;"></i>
                <p class="mt-2">ยังไม่มีที่อยู่</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }

    const typeIcons = {
        1: 'home',
        2: 'building-2',
        3: 'file-text',
        4: 'package',
        5: 'user-check'
    };

    const typeLabels = {
        1: 'ที่บ้าน',
        2: 'ที่ทำงาน',
        3: 'ที่อยู่สำหรับออกบิล/ใบกำกับภาษี',
        4: 'ใช้เป็นผู้ส่งในนาม',
        5: 'ผู้รับแทน'
    };

    const typeBadges = {
        1: 'bg-success',
        2: 'bg-primary',
        3: 'bg-warning text-dark',
        4: 'bg-info text-dark',
        5: 'bg-secondary'
    };

    let html = '<div class="row g-3">';

    addresses.forEach(addr => {
        const icon = typeIcons[addr.addr_type] || 'map-pin';
        const label = typeLabels[addr.addr_type] || 'ไม่ระบุ';
        const badge = typeBadges[addr.addr_type] || 'bg-secondary';
        const isDefault = addr.addr_position == 1;
        const borderClass = isDefault ? 'border-success border-2' : '';

        html += `
            <div class="col-md-6">
                <div class="card h-100 ${borderClass}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge ${badge} rounded-pill">
                                    <i data-lucide="${icon}" style="width:12px;height:12px;"></i>
                                    ${label}
                                </span>
                                ${isDefault ? '<span class="badge bg-success bg-opacity-10 text-success rounded-pill ms-1">หลัก</span>' : ''}
                            </div>
                            <div class="btn-group btn-group-sm">
                                ${!isDefault ? `<button class="btn btn-outline-success" onclick="setDefaultAddress(${addr.addr_id})" title="ตั้งเป็นที่อยู่หลัก">
                                    <i data-lucide="star" style="width:14px;height:14px;"></i>
                                </button>` : ''}
                                <button class="btn btn-outline-primary" onclick="editAddress(${addr.addr_id})" title="แก้ไข">
                                    <i data-lucide="edit-2" style="width:14px;height:14px;"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteAddress(${addr.addr_id}, false)" title="ลบ">
                                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                </button>
                            </div>
                        </div>
                        
                        <h6 class="mb-1">${escapeHtml(addr.addr_name)}</h6>
                        <p class="text-muted small mb-2">
                            <i data-lucide="phone" style="width:12px;height:12px;"></i>
                            ${escapeHtml(addr.addr_mobile)}
                        </p>
                        
                        <p class="mb-1 small">${escapeHtml(addr.addr_detail)}</p>
                        ${addr.addr_detail2 ? `<p class="mb-1 small">${escapeHtml(addr.addr_detail2)}</p>` : ''}
                        <p class="mb-0 small">
                            ${addr.province_name ? escapeHtml(addr.province_name) : ''} 
                            ${addr.addr_postcode ? escapeHtml(addr.addr_postcode) : ''}
                        </p>
                        
                        ${addr.addr_forword ? `<p class="mt-2 mb-0 small text-muted"><i data-lucide="message-circle" style="width:12px;height:12px;"></i> ${escapeHtml(addr.addr_forword)}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;

    // Re-initialize Lucide icons
    lucide.createIcons();
}

/**
 * Show address form for adding
 */
function showAddressForm() {
    // Reset form
    document.getElementById('addressForm').reset();
    document.getElementById('addr_id').value = '';
    document.getElementById('user_id').value = currentUserId;
    document.getElementById('formTitle').textContent = 'เพิ่มที่อยู่ใหม่';

    // Show form
    document.getElementById('addressFormContainer').style.display = 'block';

    // Scroll to form
    document.getElementById('addressFormContainer').scrollIntoView({ behavior: 'smooth' });

    // Re-initialize icons
    lucide.createIcons();
}

/**
 * Edit address
 */
function editAddress(addrId) {
    // Show loading
    Swal.fire({
        title: 'กำลังโหลด...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
    });

    fetch(`${ADMIN_URL}/api/get_address.php?id=${addrId}`)
        .then(response => response.json())
        .then(data => {
            Swal.close();

            if (data.success) {
                const addr = data.address;

                // Fill form
                document.getElementById('addr_id').value = addr.addr_id;
                document.getElementById('user_id').value = addr.user_id;
                document.getElementById('addr_name').value = addr.addr_name || '';
                document.getElementById('addr_mobile').value = addr.addr_mobile || '';
                document.getElementById('addr_detail').value = addr.addr_detail || '';
                document.getElementById('addr_detail2').value = addr.addr_detail2 || '';
                document.getElementById('addr_postcode').value = addr.addr_postcode || '';
                document.getElementById('provinces_id').value = addr.provinces_id || '';
                document.getElementById('addr_type').value = addr.addr_type || 1;
                document.getElementById('addr_forword').value = addr.addr_forword || '';

                document.getElementById('formTitle').textContent = 'แก้ไขที่อยู่';

                // Show form
                document.getElementById('addressFormContainer').style.display = 'block';

                // Scroll to form
                document.getElementById('addressFormContainer').scrollIntoView({ behavior: 'smooth' });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: data.message || 'ไม่สามารถโหลดข้อมูลได้',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'เกิดข้อผิดพลาดในการโหลดข้อมูล',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
            });
        });
}

/**
 * Save address (add or update)
 */
function saveAddress() {
    const form = document.getElementById('addressForm');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    const addrId = data.addr_id;
    const isEdit = addrId && addrId !== '';
    const url = isEdit ? `${ADMIN_URL}/api/update_address.php` : `${ADMIN_URL}/api/add_address.php`;

    // Show loading
    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
    });

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false,
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                });

                // Hide form and reload addresses
                cancelAddressForm();
                loadAddresses(currentUserId);

                // Update address count in main table
                updateAddressCount(currentUserId);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: data.message || 'ไม่สามารถบันทึกข้อมูลได้',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
            });
        });
}

/**
 * Delete address
 */
function deleteAddress(addrId, permanent = false) {
    const title = permanent ? 'ยืนยันการลบถาวร?' : 'ยืนยันการลบ?';
    const text = permanent
        ? 'คุณต้องการลบที่อยู่นี้ถาวรใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้'
        : 'คุณต้องการลบที่อยู่นี้ใช่หรือไม่?';

    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#d33',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'กำลังลบ...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
            });

            fetch(`${ADMIN_URL}/api/delete_address.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ addr_id: addrId, permanent: permanent })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false,
                            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                        });

                        // Reload addresses
                        loadAddresses(currentUserId);

                        // Update address count in main table
                        updateAddressCount(currentUserId);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: data.message || 'ไม่สามารถลบที่อยู่ได้',
                            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    });
                });
        }
    });
}

/**
 * Set address as default
 */
function setDefaultAddress(addrId) {
    Swal.fire({
        title: 'ตั้งเป็นที่อยู่หลัก?',
        text: 'คุณต้องการตั้งที่อยู่นี้เป็นที่อยู่จัดส่งหลักใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ตั้งเป็นหลัก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#10b981',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
            });

            fetch(`${ADMIN_URL}/api/set_default_address.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ addr_id: addrId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false,
                            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                        });

                        // Reload addresses to show updated default
                        loadAddresses(currentUserId);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: data.message || 'ไม่สามารถตั้งเป็นที่อยู่หลักได้',
                            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    });
                });
        }
    });
}

/**
 * Cancel address form
 */
function cancelAddressForm() {
    document.getElementById('addressFormContainer').style.display = 'none';
    document.getElementById('addressForm').reset();
}

/**
 * Update address count in main users table
 */
function updateAddressCount(userId) {
    fetch(`${ADMIN_URL}/api/get_addresses.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Find and update the count in the table
                const userRow = document.getElementById(`user-row-${userId}`);
                if (userRow) {
                    const addressLink = userRow.querySelector('a[onclick*="openAddressModal"]');
                    if (addressLink) {
                        const countText = addressLink.textContent.match(/\((\d+)\)/);
                        if (countText) {
                            addressLink.innerHTML = addressLink.innerHTML.replace(/\(\d+\)/, `(${data.count})`);
                        }
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error updating address count:', error);
        });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
