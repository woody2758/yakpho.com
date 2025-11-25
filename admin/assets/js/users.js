// Edit User Function
function editUser(userId) {
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

    // Fetch user data
    fetch(`${ADMIN_URL}/api/get_user.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'ไม่สามารถโหลดข้อมูลได้',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                });
                return;
            }

            const user = data.user;
            const profilePicUrl = user.user_picture
                ? `${ROOT_URL}/assets/img/profile/${user.user_id}/${user.user_picture}`
                : `${ROOT_URL}/assets/img/profile/default.png`;

            // Show edit dialog
            Swal.fire({
                title: 'แก้ไขข้อมูลสมาชิก',
                html: `
                    <form id="editUserForm" enctype="multipart/form-data">
                        <div class="text-center mb-3">
                            <img id="preview-pic" src="${profilePicUrl}" class="rounded-circle" style="width:100px; height:100px; object-fit:cover;">
                            <div class="mt-2">
                                <label for="user_picture" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-camera"></i> เปลี่ยนรูป
                                </label>
                                <input type="file" id="user_picture" name="user_picture" accept="image/*" style="display:none;">
                            </div>
                        </div>
                        
                        <div class="row g-3 text-start">
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ *</label>
                                <input type="text" id="first_name" class="form-control" value="${user.user_name || ''}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">นามสกุล</label>
                                <input type="text" id="last_name" class="form-control" value="${user.user_lastname || ''}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ชื่อเล่น</label>
                                <input type="text" id="nickname" class="form-control" value="${user.user_nickname || ''}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เบอร์โทรศัพท์ *</label>
                                <input type="text" id="mobile" class="form-control" value="${user.user_mobile || ''}" required>
                                <small class="text-muted">ต้องกรอกอีเมลหรือเบอร์โทรอย่างน้อย 1 อย่าง</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">อีเมล</label>
                                <input type="email" id="email" class="form-control" value="${user.user_email || ''}">
                            </div>
                        </div>
                    </form>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'บันทึก',
                cancelButtonText: 'ยกเลิก',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454',
                didOpen: () => {
                    // Preview image on file select
                    document.getElementById('user_picture').addEventListener('change', function (e) {
                        if (e.target.files && e.target.files[0]) {
                            const reader = new FileReader();
                            reader.onload = function (e) {
                                document.getElementById('preview-pic').src = e.target.result;
                            };
                            reader.readAsDataURL(e.target.files[0]);
                        }
                    });
                },
                preConfirm: () => {
                    const firstName = document.getElementById('first_name').value.trim();
                    const email = document.getElementById('email').value.trim();
                    const mobile = document.getElementById('mobile').value.trim();

                    if (!firstName) {
                        Swal.showValidationMessage('กรุณากรอกชื่อ');
                        return false;
                    }

                    if (!email && !mobile) {
                        Swal.showValidationMessage('กรุณากรอกอีเมลหรือเบอร์โทรศัพท์อย่างน้อย 1 อย่าง');
                        return false;
                    }

                    return {
                        first_name: firstName,
                        last_name: document.getElementById('last_name').value.trim(),
                        nickname: document.getElementById('nickname').value.trim(),
                        email: email,
                        mobile: mobile,
                        picture_file: document.getElementById('user_picture').files[0]
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('user_id', userId);
                    formData.append('first_name', result.value.first_name);
                    formData.append('last_name', result.value.last_name);
                    formData.append('nickname', result.value.nickname);
                    formData.append('email', result.value.email);
                    formData.append('mobile', result.value.mobile);

                    if (result.value.picture_file) {
                        formData.append('user_picture', result.value.picture_file);
                    }

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

                    // Send request
                    fetch(`${ADMIN_URL}/api/update_user.php`, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'บันทึกสำเร็จ',
                                    text: 'แก้ไขข้อมูลเรียบร้อยแล้ว',
                                    timer: 1500,
                                    showConfirmButton: false,
                                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                                    color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                                }).then(() => {
                                    location.reload();
                                });
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
            });
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

// Delete User Function
function deleteUser(userId) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบสมาชิกคนนี้ใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้',
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

            // Call delete API
            fetch(`${ADMIN_URL}/api/delete_user.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: userId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const row = document.getElementById(`user-row-${userId}`);

                        if (row) {
                            // Fade out animation
                            row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(-20px)';

                            setTimeout(() => {
                                row.remove();

                                // Check if table is empty
                                const tbody = document.querySelector('tbody');
                                if (tbody && tbody.children.length === 0) {
                                    tbody.innerHTML = `
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i data-lucide="inbox" style="width:48px;height:48px;opacity:0.3;"></i>
                                            <p class="mt-2 text-muted">ไม่พบข้อมูลผู้ใช้</p>
                                        </td>
                                    </tr>
                                `;
                                    lucide.createIcons();
                                }
                            }, 300);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: 'ลบสมาชิกเรียบร้อยแล้ว',
                            timer: 1500,
                            showConfirmButton: false,
                            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: data.message || 'ไม่สามารถลบสมาชิกได้',
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
// Add Customer Function (Using Bootstrap Modal)
let provincesLoaded = false;

async function addCustomer() {
    // Show loading immediately
    Swal.fire({
        title: 'กำลังโหลด...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
    });

    // Load provinces if not already loaded
    if (!provincesLoaded) {
        try {
            const response = await fetch(`${ADMIN_URL}/api/get_provinces.php`);
            const data = await response.json();
            if (data.success) {
                const provinceSelect = document.getElementById('customerProvince');
                // Keep the first option
                provinceSelect.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';

                data.provinces.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.id;
                    option.textContent = p.name_th;
                    provinceSelect.appendChild(option);
                });

                provincesLoaded = true;
            }
        } catch (error) {
            console.error('Error loading provinces:', error);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถโหลดข้อมูลจังหวัดได้',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
            });
            return;
        }
    }

    // Close loading
    Swal.close();

    // Reset form
    document.getElementById('addCustomerForm').reset();
    document.getElementById('customerDataPaste').value = '';

    // Show Modal
    const modal = new bootstrap.Modal(document.getElementById('addCustomerModal'));
    modal.show();

    // Re-initialize icons inside modal if needed
    lucide.createIcons();
}

// Parse Customer Data
function parseCustomerData() {
    const textarea = document.getElementById('customerDataPaste');
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

    if (lines.length < 3) {
        Swal.fire({
            icon: 'warning',
            title: 'ข้อมูลไม่ครบ',
            text: 'ข้อมูลดูเหมือนจะไม่ครบถ้วน กรุณาตรวจสอบ',
            timer: 2000,
            showConfirmButton: false
        });
        // Still try to parse what we have
    }

    // Line 1: Name Lastname Nickname
    if (lines[0]) {
        const nameParts = lines[0].split(/\s+/);
        document.getElementById('customerName').value = nameParts[0] || '';
        document.getElementById('customerLastname').value = nameParts[1] || '';
        document.getElementById('customerNickname').value = nameParts[2] || '';
    }

    // Line 2: Address Detail 1
    if (lines[1]) document.getElementById('customerAddrDetail').value = lines[1] || '';

    // Line 3: Address Detail 2
    if (lines[2]) document.getElementById('customerAddrDetail2').value = lines[2] || '';

    // Line 4: Province and Postcode
    // Try to find line with postcode (5 digits)
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
            document.getElementById('customerPostcode').value = postcodeMatch[1];

            // Extract province name
            let provinceName = provincePostcode.replace(/(\d{5})/, '').replace(/^จ\./, '').trim();
            // Remove common prefixes if user pasted them
            provinceName = provinceName.replace('จังหวัด', '').trim();

            // Find province in dropdown
            const provinceSelect = document.getElementById('customerProvince');
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
            document.getElementById('customerMobile').value = mobile;
            break;
        }
    }

    // Find Email
    for (let line of lines) {
        if (line.includes('@') && line.includes('.')) {
            document.getElementById('customerEmail').value = line;
            break;
        }
    }

    // Last line might be note if it's not email/mobile/address
    // This is a heuristic, might need adjustment
    const lastLine = lines[lines.length - 1];
    if (!lastLine.match(/\d{5}/) && !lastLine.includes('@') && !lastLine.match(/^0\d{8,9}$/)) {
        document.getElementById('customerForword').value = lastLine;
    }

    // Show success toast
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true
    });
    Toast.fire({
        icon: 'success',
        title: 'แยกข้อมูลเรียบร้อย'
    });
}

// Save Customer
function saveCustomer() {
    const form = document.getElementById('addCustomerForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    // Determine source page
    const isUsersPage = window.location.href.includes('/users/');
    formData.append('source_page', isUsersPage ? 'users' : 'orders');

    const data = Object.fromEntries(formData.entries());

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

    fetch(`${ADMIN_URL}/api/add_customer.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modalEl = document.getElementById('addCustomerModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();

                if (data.duplicate) {
                    Swal.fire({
                        icon: 'info',
                        title: 'พบข้อมูลซ้ำ',
                        text: data.message,
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'ไปออกออเดอร์',
                        denyButtonText: 'ดูข้อมูลลูกค้า',
                        cancelButtonText: 'ยกเลิก',
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Go to Orders
                            window.location.href = `${ADMIN_URL}/orders/`;
                        } else if (result.isDenied) {
                            // Go to Users Search
                            window.location.href = `${ADMIN_URL}/users/index.php?search=${data.user.user_mobile}`;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: data.message,
                        timer: 1500, // Auto close after 1.5s
                        showConfirmButton: false,
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    }).then(() => {
                        // Redirect or Reload based on API response
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            location.reload();
                        }
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: data.message,
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

// Initialize Lucide icons on page load
document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();
});
