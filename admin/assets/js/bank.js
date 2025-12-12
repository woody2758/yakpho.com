/**
 * Bank Account Management - Full AJAX
 * รองรับ 6 ภาษา: th, en, de, fr, zh, ko
 */

let bankModal;
let currentPage = 1;

// Initialize modal on page load
document.addEventListener('DOMContentLoaded', function () {
    bankModal = new bootstrap.Modal(document.getElementById('bankModal'));
});

/**
 * Load bank table via AJAX
 */
function loadBankTable(page = 1, search = '') {
    currentPage = page;
    const tableContainer = document.getElementById('tableContainer');

    // Show loading
    tableContainer.style.opacity = '0.5';
    tableContainer.style.pointerEvents = 'none';

    const params = new URLSearchParams({
        page: page,
        search: search
    });

    fetch(`${ADMIN_URL}/api/get_bank_table.php?${params}`)
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
 * Preview Image
 */
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('bankPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Add new bank
 */
function addBank() {
    // Reset form
    document.getElementById('bankForm').reset();
    document.getElementById('bankId').value = '';
    document.getElementById('modalTitle').textContent = 'เพิ่มบัญชีธนาคาร';

    // Reset Image
    document.getElementById('bankPreview').src = `${ADMIN_URL}/assets/images/placeholder.png`;

    // Defaults
    document.getElementById('bankIndex').value = '0';
    document.getElementById('bankActive').checked = true;
    document.getElementById('statusBadge').textContent = 'เปิดใช้งาน';
    document.getElementById('statusBadge').className = 'badge bg-success';

    // Show first tab
    const firstTab = document.querySelector('[data-bs-target="#lang-th"]');
    if (firstTab) {
        const tab = new bootstrap.Tab(firstTab);
        tab.show();
    }

    bankModal.show();
}

/**
 * Edit bank
 */
async function editBank(id) {
    // Show loading
    Swal.fire({
        title: 'กำลังโหลด...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`${ADMIN_URL}/api/get_bank.php?id=${id}`);
        const data = await response.json();

        Swal.close();

        if (data.success) {
            const bank = data.bank;

            // Fill form
            document.getElementById('bankId').value = bank.bank_id;
            document.getElementById('bankAccountNumber').value = bank.bank_accountnumber;
            document.getElementById('bankSwiftCode').value = bank.bank_swiftcode || '';
            document.getElementById('bankIndex').value = bank.bank_index || 0;
            document.getElementById('bankActive').checked = bank.bank_status == 1;

            // Image
            if (bank.bank_picture) {
                document.getElementById('bankPreview').src = `${ROOT_URL}/uploads/banks/${bank.bank_picture}`;
            } else {
                document.getElementById('bankPreview').src = `${ADMIN_URL}/assets/images/placeholder.png`;
            }

            // Update status badge
            const statusBadge = document.getElementById('statusBadge');
            if (bank.bank_status == 1) {
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
                if (translation) {
                    const bankNameInput = document.querySelector(`[name="bank_bankname_${lang}"]`);
                    const accountNameInput = document.querySelector(`[name="bank_accountname_${lang}"]`);
                    const accountTypeInput = document.querySelector(`[name="bank_accounttype_${lang}"]`);
                    const accountBranchInput = document.querySelector(`[name="bank_accountbranch_${lang}"]`);

                    if (bankNameInput) bankNameInput.value = translation.bank_bankname || '';
                    if (accountNameInput) accountNameInput.value = translation.bank_accountname || '';
                    if (accountTypeInput) accountTypeInput.value = translation.bank_accounttype || '';
                    if (accountBranchInput) accountBranchInput.value = translation.bank_accountbranch || '';
                }
            });

            // Update modal title
            document.getElementById('modalTitle').textContent = 'แก้ไขบัญชีธนาคาร';

            // Show modal
            bankModal.show();

        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถโหลดข้อมูลได้'
            });
        }
    } catch (error) {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถโหลดข้อมูลได้'
        });
    }
}

/**
 * Save bank (Add/Edit)
 */
async function saveBank() {
    const form = document.getElementById('bankForm');

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

        const response = await fetch(`${ADMIN_URL}/api/save_bank.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        Swal.close();

        if (data.success) {
            // Close modal
            bankModal.hide();

            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: document.getElementById('bankId').value ? 'บันทึกการแก้ไขเรียบร้อย' : 'เพิ่มข้อมูลเรียบร้อย',
                timer: 1500,
                showConfirmButton: false
            });

            // Reload table
            loadBankTable(currentPage, document.getElementById('searchInput').value);

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
 * Delete bank
 */
function deleteBank(id, name) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบ "${name}" ใช่หรือไม่?`,
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

            fetch(`${ADMIN_URL}/api/delete_bank.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ bank_id: id })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบเรียบร้อย',
                            text: 'ลบข้อมูลเรียบร้อยแล้ว',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Reload table
                        loadBankTable(currentPage, document.getElementById('searchInput').value);
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
 * Toggle status
 */
function toggleStatus(id, currentStatus) {
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

            fetch(`${ADMIN_URL}/api/save_bank.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `bank_id=${id}&bank_status=${newStatus}&quick_update=1`
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
                        loadBankTable(currentPage, document.getElementById('searchInput').value);
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
 * Handle pagination clicks
 */
document.addEventListener('click', function (e) {
    const pageLink = e.target.closest('.page-link');
    if (pageLink && !pageLink.parentElement.classList.contains('disabled')) {
        e.preventDefault();
        const url = new URL(pageLink.href);
        const page = url.searchParams.get('page') || 1;
        loadBankTable(page, document.getElementById('searchInput').value);
    }
});
