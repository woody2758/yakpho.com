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
            // TODO: Implement delete API
            Swal.fire({
                icon: 'info',
                title: 'Coming Soon',
                text: 'ฟีเจอร์ลบสมาชิกยังไม่พร้อมใช้งาน',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
            });
        }
    });
}

// Add Customer Function with Auto-Parse (FIXED VERSION)
async function addCustomer() {
    // Show loading while fetching provinces
    Swal.fire({
        title: 'กำลังโหลด...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
    });

    // Load provinces first
    let provinces = [];
    try {
        const response = await fetch(`${ADMIN_URL}/api/get_provinces.php`);
        const data = await response.json();
        if (data.success) {
            provinces = data.provinces;
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

    const provinceOptions = provinces.map(p =>
        `<option value="${p.id}">${p.name_th}</option>`
    ).join('');

    Swal.fire({
        title: 'เพิ่มลูกค้าใหม่',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">วางข้อมูลลูกค้าทั้งหมดที่นี่:</label>
                    <textarea id="customerDataPaste" class="form-control" rows="7" placeholder="วางข้อมูลลูกค้าทั้งหมดที่นี่...

ตัวอย่าง:
ฟักแฟง แตงไทย แตง
94/59 หมู่บ้านนาราสิริ ซ.วัชธพล 1/3
แขวงท่าแร้ง เขตบางเขน
จ.กรุงเทพมหานคร 10220
0932541294
saravuth@gmail.com
ส่งต่อไปยังพี่พร"></textarea>
                    <button type="button" id="parseBtn" class="btn btn-sm btn-secondary mt-2">
                        <i data-lucide="zap" style="width:14px;height:14px;"></i> แยกข้อมูลอัตโนมัติ
                    </button>
                </div>
                <hr>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                        <input type="text" id="customerName" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" id="customerLastname" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ชื่อเล่น</label>
                        <input type="text" id="customerNickname" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                        <input type="text" id="customerMobile" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">อีเมล</label>
                        <input type="email" id="customerEmail" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">ที่อยู่บรรทัด 1 <span class="text-danger">*</span></label>
                        <input type="text" id="customerAddrDetail" class="form-control" placeholder="เลขที่ หมู่บ้าน/คอนโด ชั้น ห้อง ถนน ซอย" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ที่อยู่บรรทัด 2 <span class="text-danger">*</span></label>
                        <input type="text" id="customerAddrDetail2" class="form-control" placeholder="ตำบล/แขวง อำเภอ/เขต" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                        <select id="customerProvince" class="form-select" required>
                            <option value="">-- เลือกจังหวัด --</option>
                            ${provinceOptions}
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                        <input type="text" id="customerPostcode" class="form-control" maxlength="5" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ประเภทที่อยู่</label>
                        <select id="customerAddrType" class="form-select">
                            <option value="ที่บ้าน" selected>ที่บ้าน</option>
                            <option value="ที่ทำงาน">ที่ทำงาน</option>
                            <option value="ที่อยู่สำหรับออกใบกำกับภาษี">ที่อยู่สำหรับออกใบกำกับภาษี</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">หมายเหตุ</label>
                        <input type="text" id="customerForword" class="form-control">
                    </div>
                </div>
            </div>
        `,
        width: '800px',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454',
        didOpen: () => {
            lucide.createIcons();

            // ✅ SOLUTION: Override SweetAlert's click handler for parse button
            const swalContainer = Swal.getContainer();
            const parseBtn = document.getElementById('parseBtn');

            if (swalContainer && parseBtn) {
                // Block SweetAlert's click event on the parse button
                swalContainer.addEventListener('click', function (e) {
                    const target = e.target;
                    if (target.id === 'parseBtn' || target.closest('#parseBtn')) {
                        e.stopImmediatePropagation();
                        e.preventDefault();
                    }
                }, true); // Use capture phase

                // Add our own click handler
                parseBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    parseCustomerData();
                });
            }
        },
        preConfirm: () => {
            const name = document.getElementById('customerName').value.trim();
            const lastname = document.getElementById('customerLastname').value.trim();
            const mobile = document.getElementById('customerMobile').value.trim();
            const addr_detail = document.getElementById('customerAddrDetail').value.trim();
            const addr_detail2 = document.getElementById('customerAddrDetail2').value.trim();
            const province_id = document.getElementById('customerProvince').value;
            const postcode = document.getElementById('customerPostcode').value.trim();

            if (!name || !lastname || !mobile || !addr_detail || !addr_detail2 || !province_id || !postcode) {
                Swal.showValidationMessage('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
                return false;
            }

            return {
                name,
                lastname,
                nickname: document.getElementById('customerNickname').value.trim(),
                mobile,
                email: document.getElementById('customerEmail').value.trim(),
                addr_detail,
                addr_detail2,
                province_id,
                postcode,
                addr_type: document.getElementById('customerAddrType').value,
                addr_forword: document.getElementById('customerForword').value.trim()
            };
        }
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

            // Send to API
            fetch(`${ADMIN_URL}/api/add_customer.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(result.value)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: data.message,
                            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                        }).then(() => {
                            // Redirect to orders page
                            window.location.href = data.redirect;
                        });
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
    });
}

// Parse Customer Data from Textarea
function parseCustomerData() {
    const textarea = document.getElementById('customerDataPaste');
    const data = textarea.value.trim();

    if (!data) {
        alert('กรุณาวางข้อมูลลูกค้าก่อน');
        return;
    }

    const lines = data.split('\n').map(line => line.trim()).filter(line => line);

    if (lines.length < 4) {
        alert('ข้อมูลไม่ครบถ้วน กรุณาตรวจสอบ');
        return;
    }

    // Line 1: Name Lastname Nickname
    const nameParts = lines[0].split(/\s+/);
    document.getElementById('customerName').value = nameParts[0] || '';
    document.getElementById('customerLastname').value = nameParts[1] || '';
    document.getElementById('customerNickname').value = nameParts[2] || '';

    // Line 2: Address Detail 1
    document.getElementById('customerAddrDetail').value = lines[1] || '';

    // Line 3: Address Detail 2
    document.getElementById('customerAddrDetail2').value = lines[2] || '';

    // Line 4: Province and Postcode
    const provincePostcode = lines[3];
    const postcodeMatch = provincePostcode.match(/(\d{5})/);
    if (postcodeMatch) {
        document.getElementById('customerPostcode').value = postcodeMatch[1];

        // Extract province name
        const provinceName = provincePostcode.replace(/(\d{5})/, '').replace(/^จ\./, '').trim();

        // Find province in dropdown
        const provinceSelect = document.getElementById('customerProvince');
        for (let option of provinceSelect.options) {
            if (option.text.includes(provinceName)) {
                provinceSelect.value = option.value;
                break;
            }
        }
    }

    // Line 5: Mobile (if exists)
    if (lines[4]) {
        const mobile = lines[4].replace(/[^0-9]/g, '');
        if (mobile.length >= 9) {
            document.getElementById('customerMobile').value = mobile;
        }
    }

    // Line 6: Email (if exists)
    if (lines[5] && lines[5].includes('@')) {
        document.getElementById('customerEmail').value = lines[5];
    }

    // Line 7: Forward note (if exists)
    if (lines[6]) {
        document.getElementById('customerForword').value = lines[6];
    }

    // Show success message
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
    Toast.fire({
        icon: 'success',
        title: 'แยกข้อมูลสำเร็จ!'
    });
}

// Initialize Lucide icons on page load
document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();
});
