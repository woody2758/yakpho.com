function checkAdminDark() {
    if (typeof getAdminIsDark === 'function') return getAdminIsDark();
    return document.documentElement.getAttribute('data-theme') === 'dark' ||
           (document.body && checkAdminDark()) ||
           (localStorage.getItem('yakpho-theme') || 'dark') === 'dark';
}
/**
 * Admin Users Management JavaScript
 * Yak Pho Aroma & Esther Synergy
 */

// 1. Customer Modal Functions (Matches Orders customerModal)
function openCustomerModal(userId, orderId = 0) {
    const modalEl = document.getElementById('customerModal');
    if (!modalEl) {
        Swal.fire('Error', 'ไม่พบ Customer Modal ในหน้านี้', 'error');
        return;
    }
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) {
        modal = new bootstrap.Modal(modalEl);
    }

    // Reset form
    const form = document.getElementById('customerForm');
    if (form) form.reset();
    
    document.getElementById('customerUserId').value = userId;
    const orderIdInput = document.getElementById('customerOrderId');
    if (orderIdInput) orderIdInput.value = orderId;

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
                if (elNickname) elNickname.value = user.user_nickname || '';
                
                document.getElementById('customerMobile').value = user.user_mobile || '';
                document.getElementById('customerEmail').value = user.user_email || '';

                const elTier = document.getElementById('customerTier');
                if (elTier) elTier.value = user.member_tier || 'general';

                const elPoints = document.getElementById('customerPoints');
                if (elPoints) elPoints.value = user.m_stamp_points || 0;
                
                // Address fields
                const addr = user.address || {};
                const elAddrId = document.getElementById('customerAddrId');
                if (elAddrId) elAddrId.value = addr.addr_id || 0;

                const elAddrName = document.getElementById('customerAddrName');
                if (elAddrName) elAddrName.value = addr.addr_name || '';

                const elAddrMobile = document.getElementById('customerAddrMobile');
                if (elAddrMobile) elAddrMobile.value = addr.addr_mobile || '';

                const elAddrDetail = document.getElementById('customerAddrDetail');
                if (elAddrDetail) elAddrDetail.value = addr.addr_detail || '';

                const elAddrDetail2 = document.getElementById('customerAddrDetail2');
                if (elAddrDetail2) elAddrDetail2.value = addr.addr_detail2 || '';

                const elAddrPostcode = document.getElementById('customerAddrPostcode');
                if (elAddrPostcode) elAddrPostcode.value = addr.addr_postcode || '';

                const elAddrTax = document.getElementById('customerAddrTax');
                if (elAddrTax) elAddrTax.value = addr.addr_tax_id || '';

                // Populate provinces
                const elProv = document.getElementById('customerProvince');
                if (elProv && data.provinces) {
                    let provHtml = '<option value="">-- เลือกจังหวัด --</option>';
                    data.provinces.forEach(p => {
                        const sel = (addr.provinces_id == p.provinces_id) ? 'selected' : '';
                        provHtml += `<option value="${p.provinces_id}" ${sel}>${p.name_th}</option>`;
                    });
                    elProv.innerHTML = provHtml;
                }

                const elNote = document.getElementById('customerNote');
                if (elNote) elNote.value = user.user_note || '';
                
                const elMap = document.getElementById('customerGoogleMap');
                if (elMap) elMap.value = user.user_google_map || '';

                const btnOpenMap = document.getElementById('btnOpenMap');
                if (btnOpenMap) {
                    if (user.user_google_map && user.user_google_map.trim() !== '') {
                        btnOpenMap.href = user.user_google_map.trim();
                        btnOpenMap.style.display = 'inline-flex';
                        btnOpenMap.style.alignItems = 'center';
                    } else {
                        btnOpenMap.style.display = 'none';
                        btnOpenMap.href = '#';
                    }
                }

                const elWebsite = document.getElementById('customerWebsite');
                if (elWebsite) elWebsite.value = user.user_website || '';

                const btnOpenWebsite = document.getElementById('btnOpenWebsite');
                if (btnOpenWebsite) {
                    if (user.user_website && user.user_website.trim() !== '') {
                        let websiteUrl = user.user_website.trim();
                        if (!/^https?:\/\//i.test(websiteUrl)) {
                            websiteUrl = 'https://' + websiteUrl;
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
                Swal.fire('Error', data.message || 'ไม่สามารถโหลดข้อมูลลูกค้าได้', 'error');
            }
        })
        .catch(error => {
            if (loadingOverlay) loadingOverlay.classList.add('d-none');
            console.error('Error:', error);
            Swal.fire('Error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        });
}

// Alias for customer name clicks
function openEditCustomerModal(userId) {
    openCustomerModal(userId, 0);
}

function saveCustomerInfo() {
    const form = document.getElementById('customerForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    fetch(`${ADMIN_URL}/api/save_user_mini.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modalEl = document.getElementById('customerModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }

            const isDark = checkAdminDark();
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ',
                text: data.message || 'อัปเดตข้อมูลลูกค้าเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 1500,
                background: isDark ? '#1a221f' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#1e293b'
            });

            // Reload user table
            if (typeof performSearch === 'function') {
                performSearch();
            } else {
                location.reload();
            }
        } else {
            Swal.fire('Error', data.message || 'เกิดข้อผิดพลาดในการบันทึก', 'error');
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        Swal.fire('Error', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
    });
}

// Listen to website and map inputs dynamically
document.addEventListener('DOMContentLoaded', function () {
    const mapInput = document.getElementById('customerGoogleMap');
    const btnMap = document.getElementById('btnOpenMap');
    if (mapInput && btnMap) {
        mapInput.addEventListener('input', function () {
            const val = this.value.trim();
            if (val) {
                btnMap.href = val;
                btnMap.style.display = 'inline-flex';
                btnMap.style.alignItems = 'center';
            } else {
                btnMap.style.display = 'none';
                btnMap.href = '#';
            }
        });
    }

    const webInput = document.getElementById('customerWebsite');
    const btnWeb = document.getElementById('btnOpenWebsite');
    if (webInput && btnWeb) {
        webInput.addEventListener('input', function () {
            let val = this.value.trim();
            if (val) {
                if (!/^https?:\/\//i.test(val)) val = 'https://' + val;
                btnWeb.href = val;
                btnWeb.style.display = 'inline-flex';
                btnWeb.style.alignItems = 'center';
            } else {
                btnWeb.style.display = 'none';
                btnWeb.href = '#';
            }
        });
    }

    if (window.lucide) {
        lucide.createIcons();
    }
});

// 2. Reset Password Modal (Direct SweetAlert2)
function openResetPasswordModal(userId, userName) {
    const isDark = checkAdminDark();
    const bg = isDark ? '#1a221f' : '#ffffff';
    const fg = isDark ? '#f1f5f9' : '#1e293b';

    Swal.fire({
        title: `<div class="d-flex align-items-center justify-content-center gap-2"><i data-lucide="key" class="text-primary"></i> <span>รีเซ็ตรหัสผ่าน</span></div>`,
        html: `
            <div class="text-start mt-2" style="font-size: 0.9rem;">
                <div class="swal-info-box mb-3 p-3 rounded">
                    <div class="fw-bold">สมาชิก: <span class="text-primary">${userName}</span> <span class="text-muted">(C${userId})</span></div>
                    <div class="small text-muted mt-1">รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">รหัสผ่านใหม่ (New Password) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" id="swal_new_password" class="form-control" placeholder="อย่างน้อย 6 ตัวอักษร" autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('swal_new_password', this)">
                            <i data-lucide="eye" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">ยืนยันรหัสผ่านใหม่ (Confirm Password) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" id="swal_confirm_password" class="form-control" placeholder="พิมพ์รหัสผ่านอีกครั้ง" autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('swal_confirm_password', this)">
                            <i data-lucide="eye" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i data-lucide="save" class="me-1"></i> บันทึกรหัสผ่าน',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#10b981',
        background: bg,
        color: fg,
        didOpen: () => {
            if (window.lucide) lucide.createIcons();
            const inp = document.getElementById('swal_new_password');
            if (inp) inp.focus();
        },
        preConfirm: () => {
            const pass = document.getElementById('swal_new_password').value.trim();
            const confirm = document.getElementById('swal_confirm_password').value.trim();

            if (!pass || pass.length < 6) {
                Swal.showValidationMessage('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
                return false;
            }
            if (pass !== confirm) {
                Swal.showValidationMessage('รหัสผ่านทั้งสองช่องไม่ตรงกัน');
                return false;
            }

            return { password: pass };
        }
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังบันทึกรหัสผ่าน...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
                background: bg,
                color: fg
            });

            fetch(`${ADMIN_URL}/api/reset_password.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, password: result.value.password })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: 'รีเซ็ตรหัสผ่านใหม่เรียบร้อยแล้ว',
                        timer: 2000,
                        showConfirmButton: false,
                        background: bg,
                        color: fg
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message || 'ไม่สามารถรีเซ็ตรหัสผ่านได้',
                        background: bg,
                        color: fg
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                    background: bg,
                    color: fg
                });
            });
        }
    });
}

function togglePasswordVisibility(inputId, btnEl) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btnEl.innerHTML = isPassword 
        ? '<i data-lucide="eye-off" style="width:16px;height:16px;"></i>' 
        : '<i data-lucide="eye" style="width:16px;height:16px;"></i>';
    if (window.lucide) lucide.createIcons();
}

// 3. Adjust Tier & Points Modal (SweetAlert2)
function openAdjustTierModal(userId, userName, currentTier, currentPoints, hasLine) {
    const isDark = checkAdminDark();
    const bg = isDark ? '#1a221f' : '#ffffff';
    const fg = isDark ? '#f1f5f9' : '#1e293b';
    const border = isDark ? '#334155' : '#e2e8f0';

    currentTier = currentTier || 'general';
    currentPoints = parseInt(currentPoints) || 0;

    Swal.fire({
        title: '<div class="d-flex align-items-center justify-content-center gap-2"><i data-lucide="award" class="text-warning"></i> <span>ปรับระดับสมาชิก & แต้ม M-Stamp</span></div>',
        html: `
            <div class="text-start" style="font-size: 0.9rem;">
                <div class="swal-info-box mb-3 p-3 rounded">
                    <div class="fw-bold">ลูกค้า: <span class="text-primary">${userName}</span> <span class="text-muted">(C${userId})</span></div>
                    <div class="small text-muted mt-1">แต้มสะสมปัจจุบัน: <strong class="text-success">${currentPoints.toLocaleString()}</strong> M-Stamp (= ฿${currentPoints.toLocaleString()})</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">ระดับสมาชิก (Member Tier):</label>
                    <select id="swal_member_tier" class="form-select form-select-sm">
                        <option value="general" ${currentTier === 'general' ? 'selected' : ''}>🌿 General Member (ลูกค้าปลีกทั่วไป)</option>
                        <option value="vip_spa" ${currentTier === 'vip_spa' ? 'selected' : ''}>👑 VIP Spa Member (ร้านนวด & สปา เรทส่ง 1 กก.)</option>
                        <option value="therapist_partner" ${currentTier === 'therapist_partner' ? 'selected' : ''}>⭐ Yak Pho Partner (หมอนวด Affiliate ค่าคอม)</option>
                        <option value="oem_export" ${currentTier === 'oem_export' ? 'selected' : ''}>💎 OEM & Export (สั่งผลิต 100 กก.+)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">ปรับแต้ม M-Stamp (+ เพิ่ม หรือ - หัก):</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">จำนวนแต้ม</span>
                        <input type="number" id="swal_points_adj" class="form-control" placeholder="0 หรือ +50, -20" value="0">
                        <span class="input-group-text">แต้ม</span>
                    </div>
                    <div class="form-text" style="font-size: 11px;">ใส่ค่า 0 หากไม่ต้องการปรับแต้ม (1 แต้ม = 1 บาท)</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">บันทึกเหตุผล (Note):</label>
                    <input type="text" id="swal_adj_note" class="form-control form-control-sm" placeholder="เช่น โบนัสกิจกรรม, ปรับสถานะร้านนวด VIP">
                </div>

                ${hasLine ? `
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="swal_send_line" checked>
                    <label class="form-check-label small" for="swal_send_line">
                        <i data-lucide="message-circle" class="text-success" style="width:14px;height:14px;"></i> ส่งข้อความแจ้งเตือนผ่าน LINE OA อัตโนมัติ
                    </label>
                </div>
                ` : `
                <div class="small text-muted mt-2">
                    <i data-lucide="info" style="width:12px;height:12px;"></i> สมาชิกยังไม่ได้ผูก LINE (ไม่สามารถส่งแจ้งเตือนได้)
                </div>
                `}
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i data-lucide="check" class="me-1"></i> บันทึกการเปลี่ยนแปลง',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#10b981',
        background: bg,
        color: fg,
        didOpen: () => {
            if (window.lucide) lucide.createIcons();
        },
        preConfirm: () => {
            const newTier = document.getElementById('swal_member_tier').value;
            const pointsAdj = parseInt(document.getElementById('swal_points_adj').value) || 0;
            const adjNote = document.getElementById('swal_adj_note').value.trim();
            const sendLineEl = document.getElementById('swal_send_line');
            const sendLine = sendLineEl ? sendLineEl.checked : false;

            return {
                user_id: userId,
                member_tier: newTier,
                points_adjustment: pointsAdj,
                adjustment_note: adjNote,
                send_line: sendLine
            };
        }
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังบันทึกข้อมูล...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
                background: bg,
                color: fg
            });

            fetch(`${ADMIN_URL}/api/update_member_tier.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(result.value)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: data.message + (data.line_sent ? ' (ส่ง LINE เรียบร้อยแล้ว)' : ''),
                        timer: 2000,
                        showConfirmButton: false,
                        background: bg,
                        color: fg
                    });

                    // Refresh table
                    if (typeof performSearch === 'function') {
                        performSearch();
                    } else {
                        location.reload();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message || 'ไม่สามารถอัปเดตได้',
                        background: bg,
                        color: fg
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: err.message || 'Network error',
                    background: bg,
                    color: fg
                });
            });
        }
    });
}

// 4. Copy VIP Invite Function
function copyVipInvite(userId, btnEl) {
    if (!userId) return;
    const originalHtml = btnEl ? btnEl.innerHTML : '';
    if (btnEl) {
        btnEl.disabled = true;
        btnEl.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="width:12px; height:12px;"></span>';
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
            btnEl.innerHTML = originalHtml;
            if (window.lucide) lucide.createIcons();
        }

        if (data.status === 'success') {
            // Robust clipboard copy with fallback
            const copyText = data.message_template;
            let copySuccess = false;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(copyText).then(() => {
                    copySuccess = true;
                }).catch(() => {});
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = copyText;
                textArea.style.position = 'fixed';
                textArea.style.left = '-9999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    copySuccess = true;
                } catch (e) {}
                document.body.removeChild(textArea);
            }

            const isDark = checkAdminDark();
            Swal.fire({
                icon: 'success',
                title: 'คัดลอกลิงก์เชิญ VIP เรียบร้อยแล้ว!',
                html: `
                    <div class="text-start p-2 rounded mt-2 border" style="font-size: 0.85rem; max-height: 200px; overflow-y: auto; background: ${isDark ? '#0f1715' : '#f8fafc'}; border-color: ${isDark ? '#334155' : '#e2e8f0'};">
                        <p class="mb-1 text-success fw-bold">✓ คัดลอกข้อความและลิงก์สำหรับส่งแชท LINE / Facebook แล้ว:</p>
                        <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit; color: inherit;">${data.message_template}</pre>
                    </div>
                    <div class="mt-2 text-muted small">
                        รหัสสมาชิก: <strong>${data.c_code}</strong> | อายุลิงก์: 30 วัน
                    </div>
                `,
                confirmButtonText: 'ตกลง',
                confirmButtonColor: '#10b981',
                background: isDark ? '#1a221f' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#1e293b'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'ไม่สามารถสร้างลิงก์ได้',
                text: data.message || 'เกิดข้อผิดพลาดในการสร้างลิงก์ VIP'
            });
        }
    })
    .catch(err => {
        if (btnEl) {
            btnEl.disabled = false;
            btnEl.innerHTML = originalHtml;
            if (window.lucide) lucide.createIcons();
        }
        Swal.fire('Error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
    });
}

// 5. Delete User Function
function deleteUser(userId) {
    const isDark = checkAdminDark();
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบสมาชิกคนนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ef4444',
        background: isDark ? '#1a221f' : '#ffffff',
        color: isDark ? '#f1f5f9' : '#1e293b'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.showLoading();
            fetch(`${ADMIN_URL}/api/delete_user.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(`user-row-${userId}`);
                    if (row) row.remove();
                    Swal.fire({
                        icon: 'success',
                        title: 'ลบสำเร็จ',
                        timer: 1500,
                        showConfirmButton: false,
                        background: isDark ? '#1a221f' : '#ffffff',
                        color: isDark ? '#f1f5f9' : '#1e293b'
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
                }
            });
        }
    });
}

// 6. Add Customer via Paste
async function addCustomer() {
    const modalEl = document.getElementById('addCustomerModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    const form = document.getElementById('addCustomerForm');
    if (form) form.reset();
    const paste = document.getElementById('customerDataPaste');
    if (paste) paste.value = '';
}

function parseCustomerData() {
    const textarea = document.getElementById('customerDataPaste');
    if (!textarea) return;
    const data = textarea.value.trim();
    if (!data) return;

    const lines = data.split('\n').map(line => line.trim()).filter(line => line);
    if (lines[0]) {
        const nameParts = lines[0].split(/\s+/);
        const nameEl = document.getElementById('addCustomerName') || document.getElementById('customerName');
        const lastnameEl = document.getElementById('addCustomerLastname') || document.getElementById('customerLastname');
        const nicknameEl = document.getElementById('addCustomerNickname') || document.getElementById('customerNickname');
        if (nameEl) nameEl.value = nameParts[0] || '';
        if (lastnameEl) lastnameEl.value = nameParts[1] || '';
        if (nicknameEl) nicknameEl.value = nameParts[2] || '';
    }
    const addrEl = document.getElementById('addCustomerAddrDetail') || document.getElementById('customerAddrDetail');
    if (lines[1] && addrEl) addrEl.value = lines[1];
    
    // Simple heuristic for phone and email
    for (let line of lines) {
        const mobile = line.replace(/[^0-9]/g, '');
        if (mobile.startsWith('0') && (mobile.length === 9 || mobile.length === 10)) {
            const mobEl = document.getElementById('addCustomerMobile') || document.getElementById('customerMobile');
            if (mobEl) mobEl.value = mobile;
        }
        if (line.includes('@')) {
            const emailEl = document.getElementById('addCustomerEmail') || document.getElementById('customerEmail');
            if (emailEl) emailEl.value = line;
        }
    }
}

function saveCustomer() {
    const form = document.getElementById('addCustomerForm');
    if (!form) return;
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    fetch(`${ADMIN_URL}/api/add_customer.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modalEl = document.getElementById('addCustomerModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
        }
    });
}

// 7. Image Management
function openImageManagement(userId, userName, currentPicUrl) {
    const userIdInput = document.getElementById('imgModalUserId');
    if (userIdInput) userIdInput.value = userId;
    const nameEl = document.getElementById('imgModalUserName');
    if (nameEl) nameEl.textContent = userName;
    
    const preview = document.getElementById('newPicPreview');
    if (preview && currentPicUrl) {
        preview.innerHTML = `<img src="${currentPicUrl}" class="w-100 h-100" style="object-fit:cover;border-radius:50%;">`;
    }
    
    const fileInput = document.getElementById('userPicInput');
    if (fileInput) fileInput.value = '';
    
    const modalEl = document.getElementById('userImageModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        loadUserImageHistory(userId);
    }
}

function loadUserImageHistory(userId) {
    const grid = document.getElementById('imageHistoryGrid');
    if (!grid) return;
    grid.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm"></div></div>';
    fetch(ADMIN_URL + '/api/get_user_image_history.php?user_id=' + userId)
    .then(response => response.json())
    .then(data => {
        grid.innerHTML = '';
        if (data.success && data.images.length > 0) {
            data.images.forEach(img => {
                const col = document.createElement('div');
                col.className = 'col-3';
                col.innerHTML = `
                    <div class="history-img-wrapper">
                        <div class="history-img-item" onclick="setUserPictureFromHistory(${userId}, '${img.filename}')">
                            <img src="${img.url}?t=${Date.now()}" class="w-100 rounded shadow-sm" style="height:100px;object-fit:cover;">
                        </div>
                    </div>`;
                grid.appendChild(col);
            });
        } else {
            grid.innerHTML = '<div class="col-12 text-center text-muted py-3 small">ยังไม่มีประวัติรูปภาพ</div>';
        }
    })
    .catch(() => {
        grid.innerHTML = '<div class="col-12 text-center text-danger py-3 small">โหลดประวัติรูปภาพไม่สำเร็จ</div>';
    });
}

function previewNewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('newPicPreview');
            if (preview) {
                preview.innerHTML = `<img src="${e.target.result}" class="w-100 h-100" style="object-fit:cover;border-radius:50%;">`;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function uploadNewUserPicture() {
    const userId = document.getElementById('imgModalUserId').value;
    const fileInput = document.getElementById('userPicInput');
    const btn = document.getElementById('uploadPicBtn');

    if (!fileInput.files || !fileInput.files[0]) {
        Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไฟล์รูปภาพก่อน', timer: 2000, showConfirmButton: false });
        return;
    }

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('user_picture', fileInput.files[0]);

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังอัปโหลด...';

    fetch(ADMIN_URL + '/api/update_user_picture.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        if (data.success) {
            loadUserImageHistory(userId);
            updateUserRow(userId);
            fileInput.value = '';
            Swal.fire({ icon: 'success', title: 'อัปโหลดสำเร็จ!', timer: 1500, showConfirmButton: false,
                toast: true, position: 'top-end', timerProgressBar: true });
        } else {
            Swal.fire({ icon: 'error', title: 'อัปโหลดไม่สำเร็จ', text: data.message || 'เกิดข้อผิดพลาด' });
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err.message });
    });
}

function setUserPictureFromHistory(userId, filename) {
    Swal.fire({
        title: 'ใช้รูปภาพนี้?',
        showCancelButton: true,
        confirmButtonText: 'ใช่',
        cancelButtonText: 'ยกเลิก'
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            fetch(ADMIN_URL + '/api/set_user_picture_from_history.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, filename: filename })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateUserRow(userId);
                    const modalEl = document.getElementById('userImageModal');
                    if (modalEl) bootstrap.Modal.getInstance(modalEl).hide();
                    Swal.fire({ icon: 'success', title: 'เปลี่ยนรูปภาพสำเร็จ', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' });
            });
        }
    });
}

function updateUserRow(userId) {
    fetch(`${ADMIN_URL}/api/get_user_row.php?id=${userId}`)
    .then(response => response.json())
    .then(rowData => {
        if (rowData.success) {
            const oldRow = document.getElementById(`user-row-${userId}`);
            if (oldRow) {
                const temp = document.createElement('tbody');
                temp.innerHTML = rowData.html;
                oldRow.replaceWith(temp.firstElementChild);
                if (window.lucide) lucide.createIcons();
            }
        }
    });
}

// 8. Tags Management
let cachedTags = [];
async function refreshCachedTags() {
    try {
        const r = await fetch(ADMIN_URL + '/api/get_all_tags.php');
        const d = await r.json();
        if (d.success) cachedTags = d.tags;
    } catch (e) {}
}
refreshCachedTags();

function promptAddTag(userId) {
    let modalCurrentTags = [];
    let pendingTagUpdates = 0;

    function updateFinishButtonState() {
        const btn = Swal.getConfirmButton();
        if (!btn) return;
        if (pendingTagUpdates > 0) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';
        } else {
            btn.disabled = false;
            btn.innerHTML = 'เสร็จสิ้น';
        }
    }

    function renderTagModalContent(currentTags, isLoading = false) {
        if (isLoading) return '<div class="text-center py-5"><div class="spinner-border text-success"></div><p class="mt-2 small text-muted">กำลังโหลดข้อมูลแท็ก...</p></div>';
        
        let tagsHtml = currentTags.map(tag => `
            <span class="tag-badge-inline">
                ${tag} 
                <span class="remove-tag-btn" onclick="optimisticRemoveTag(${userId}, '${tag}')" title="ลบแท็กนี้">&times;</span>
            </span>
        `).join('');

        let suggestions = cachedTags
            .filter(t => !currentTags.includes(t))
            .map(t => `<span class="suggestion-chip" onclick="optimisticAddTag(${userId}, '${t}')">+ ${t}</span>`)
            .join('');

        return `
            <div class="text-start">
                <style>
                    .tag-input-container {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 6px;
                        padding: 8px;
                        border: 1px solid #d1d5db;
                        border-radius: 8px;
                        min-height: 48px;
                        align-items: center;
                        background: #fff;
                    }
                    [data-theme="dark"] .tag-input-container {
                        background: #0f1715;
                        border-color: #334155;
                    }
                    .tag-badge-inline {
                        background: #10b981;
                        color: #fff;
                        padding: 5px 8px 5px 12px;
                        border-radius: 8px;
                        font-size: 0.75rem;
                        display: flex;
                        align-items: center;
                        font-weight: 500;
                    }
                    .remove-tag-btn {
                        cursor: pointer;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 18px;
                        height: 18px;
                        border-radius: 50%;
                        background: rgba(255,255,255,0.2);
                        margin-left: 8px;
                        font-size: 14px;
                        line-height: 1;
                    }
                    .modal-tag-input-field {
                        border: none !important;
                        outline: none !important;
                        flex: 1;
                        min-width: 150px;
                        padding: 5px;
                        font-size: 0.85rem;
                        background: transparent;
                        color: inherit;
                    }
                    .suggestion-chip {
                        background: #f3f4f6;
                        color: #374151;
                        padding: 7px 14px;
                        border-radius: 20px;
                        font-size: 0.75rem;
                        cursor: pointer;
                        border: 1px solid #e5e7eb;
                        margin-right: 8px;
                        margin-bottom: 10px;
                        display: inline-block;
                        font-weight: 500;
                    }
                    [data-theme="dark"] .suggestion-chip {
                        background: #1e293b;
                        color: #cbd5e1;
                        border-color: #334155;
                    }
                </style>

                <div class="mb-4">
                    <label class="small fw-bold text-muted mb-2 d-block">จัดการแท็กของสมาชิก:</label>
                    <div class="tag-input-container" onclick="document.getElementById('modal-tag-input').focus()">
                        ${tagsHtml}
                        <input type="text" id="modal-tag-input" class="modal-tag-input-field" placeholder="พิมพ์ชื่อแท็กใหม่แล้วกด Enter...">
                    </div>
                </div>

                <label class="small fw-bold text-muted mb-2 d-block">แท็กแนะนำที่มีในระบบ:</label>
                <div class="d-flex flex-wrap" style="max-height: 150px; overflow-y: auto; padding: 2px;">
                    ${suggestions || '<span class="text-muted small">ยังไม่มีแท็กอื่นในระบบ</span>'}
                </div>
            </div>`;
    }

    window.optimisticAddTag = (uid, tagName) => {
        if (modalCurrentTags.includes(tagName)) return;
        modalCurrentTags.push(tagName);
        pendingTagUpdates++;
        updateModalUI(uid);
        updateFinishButtonState();

        fetch(ADMIN_URL + '/api/add_user_tag.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: uid, tag_name: tagName })
        })
        .then(r => r.json())
        .then(data => {
            pendingTagUpdates--;
            updateFinishButtonState();
            if (!data.success) {
                modalCurrentTags = modalCurrentTags.filter(t => t !== tagName);
                updateModalUI(uid);
            } else {
                updateUserRow(uid);
                refreshCachedTags();
            }
        })
        .catch(() => {
            pendingTagUpdates--;
            updateFinishButtonState();
            modalCurrentTags = modalCurrentTags.filter(t => t !== tagName);
            updateModalUI(uid);
        });
    };

    window.optimisticRemoveTag = (uid, tagName) => {
        if (!modalCurrentTags.includes(tagName)) return;
        modalCurrentTags = modalCurrentTags.filter(t => t !== tagName);
        pendingTagUpdates++;
        updateModalUI(uid);
        updateFinishButtonState();

        fetch(ADMIN_URL + '/api/remove_user_tag.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: uid, tag_name: tagName })
        })
        .then(r => r.json())
        .then(data => {
            pendingTagUpdates--;
            updateFinishButtonState();
            if (!data.success) {
                modalCurrentTags.push(tagName);
                updateModalUI(uid);
            } else {
                updateUserRow(uid);
            }
        })
        .catch(() => {
            pendingTagUpdates--;
            updateFinishButtonState();
            modalCurrentTags.push(tagName);
            updateModalUI(uid);
        });
    };

    function updateModalUI(uid) {
        const container = Swal.getHtmlContainer();
        if (container) {
            container.innerHTML = renderTagModalContent(modalCurrentTags, false);
            setupInputListener(uid);
        }
    }

    function setupInputListener(uid) {
        const input = document.getElementById('modal-tag-input');
        if (input) {
            input.focus();
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = input.value.trim();
                    if (val) {
                        window.optimisticAddTag(uid, val);
                    }
                }
            });
        }
    }

    const isDark = checkAdminDark();
    Swal.fire({
        title: 'จัดการแท็กกลุ่มลูกค้า',
        html: renderTagModalContent([], true),
        showConfirmButton: true,
        confirmButtonText: 'เสร็จสิ้น',
        confirmButtonColor: '#10b981',
        width: '550px',
        background: isDark ? '#1a221f' : '#ffffff',
        color: isDark ? '#f1f5f9' : '#1e293b',
        didOpen: () => {
            fetch(ADMIN_URL + '/api/get_user_tags.php?user_id=' + userId)
                .then(r => r.json())
                .then(d => {
                    modalCurrentTags = d.success ? d.tags : [];
                    updateModalUI(userId);
                });
        }
    });
}

function filterByTag(tagName) {
    const tagInput = document.getElementById('tagFilter');
    const badgeContainer = document.getElementById('active-tag-filter-container');
    const badgeText = document.getElementById('active-tag-name');
    
    if (tagInput) {
        tagInput.value = (tagInput.value === tagName) ? '' : tagName;
        if (tagInput.value !== '') {
            if (badgeContainer) badgeContainer.style.display = 'block';
            if (badgeText) badgeText.textContent = tagName;
        } else {
            if (badgeContainer) badgeContainer.style.display = 'none';
        }
        
        if (typeof performSearch === 'function') {
            performSearch();
        } else {
            const filterForm = document.getElementById('filterForm');
            if (filterForm) filterForm.submit();
        }
    }
}

// Explicit Window Global Exports
window.openAdjustTierModal = openAdjustTierModal;
window.openResetPasswordModal = openResetPasswordModal;
window.copyVipInvite = copyVipInvite;
window.openCustomerModal = openCustomerModal;
window.openEditCustomerModal = openCustomerModal;
window.editUser = editUser;
window.saveCustomerInfo = saveCustomerInfo;
