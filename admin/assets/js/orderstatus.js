/**
 * Order Status Management - Full AJAX
 * รองรับ 6 ภาษา: th, en, de, fr, zh, ko
 */

let statusModal;
let currentPage = 1;

// Initialize modal on page load
document.addEventListener('DOMContentLoaded', function () {
    statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
});

/**
 * Load status table via AJAX
 */
function loadStatusTable(page = 1, search = '') {
    currentPage = page;
    const tableContainer = document.getElementById('tableContainer');

    // Show loading
    tableContainer.style.opacity = '0.5';
    tableContainer.style.pointerEvents = 'none';

    const params = new URLSearchParams({
        page: page,
        search: search
    });

    fetch(`${ADMIN_URL}/api/get_orderstatus_table.php?${params}`)
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
 * Add new status
 */
function addStatus() {
    // Reset form
    document.getElementById('statusForm').reset();
    document.getElementById('statusId').value = '';
    document.getElementById('modalTitle').textContent = 'เพิ่มสถานะการสั่งซื้อ';

    // Defaults
    document.getElementById('statusColor').value = '#000000';
    document.getElementById('statusIndex').value = '0';
    document.getElementById('statusUser').checked = true;
    document.getElementById('statusActive').checked = true;
    document.getElementById('statusBadge').textContent = 'เปิดใช้งาน';
    document.getElementById('statusBadge').className = 'badge bg-success';

    // Show first tab
    const firstTab = document.querySelector('[data-bs-target="#lang-th"]');
    if (firstTab) {
        const tab = new bootstrap.Tab(firstTab);
        tab.show();
    }

    // Initialize Summernote
    $('.summernote').summernote({
        height: 150,
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']]
        ]
    });
    $('.summernote').summernote('code', '');

    statusModal.show();

    // Re-initialize icons
    setTimeout(() => {
        lucide.createIcons();
    }, 100);
}

/**
 * Edit status
 */
async function editStatus(statusId) {
    // Show loading
    Swal.fire({
        title: 'กำลังโหลด...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`${ADMIN_URL}/api/get_orderstatus.php?id=${statusId}`);
        const data = await response.json();

        Swal.close();

        if (data.success) {
            const status = data.status;

            // Fill form
            document.getElementById('statusId').value = status.orsts_id;
            document.getElementById('statusCode').value = status.orsts_code;
            document.getElementById('statusColor').value = status.orsts_color || '#000000';
            document.getElementById('statusIndex').value = status.orsts_index || 0;
            document.getElementById('statusUser').checked = status.orsts_user == 1;
            document.getElementById('statusActive').checked = status.orsts_status == 1;

            // Update status badge
            const statusBadge = document.getElementById('statusBadge');
            if (status.orsts_status == 1) {
                statusBadge.textContent = 'เปิดใช้งาน';
                statusBadge.className = 'badge bg-success';
            } else {
                statusBadge.textContent = 'ปิดใช้งาน';
                statusBadge.className = 'badge bg-secondary';
            }

            // Fill translations for all languages
            const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko'];
            languages.forEach(lang => {
                const translation = data.translations.find(t => t.lang_code === lang);
                if (translation) {
                    const nameInput = document.querySelector(`[name="orsts_name_${lang}"]`);
                    const msgInput = document.querySelector(`[name="orsts_msg_${lang}"]`);

                    if (nameInput) nameInput.value = translation.orsts_name || '';
                    if (msgInput) {
                        $(msgInput).summernote({
                            height: 150,
                            toolbar: [
                                ['style', ['bold', 'italic', 'underline', 'clear']],
                                ['font', ['strikethrough', 'superscript', 'subscript']],
                                ['fontsize', ['fontsize']],
                                ['color', ['color']],
                                ['para', ['ul', 'ol', 'paragraph']],
                                ['height', ['height']]
                            ]
                        });
                        $(msgInput).summernote('code', translation.orsts_msg || '');
                    }
                }
            });

            // Update modal title
            document.getElementById('modalTitle').textContent = 'แก้ไขสถานะการสั่งซื้อ';

            // Show modal
            statusModal.show();

            // Re-initialize icons
            setTimeout(() => {
                lucide.createIcons();
            }, 100);

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
 * Save status (Add/Edit)
 */
async function saveStatus() {
    const form = document.getElementById('statusForm');

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

        const response = await fetch(`${ADMIN_URL}/api/save_orderstatus.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        Swal.close();

        if (data.success) {
            // Close modal
            statusModal.hide();

            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: document.getElementById('statusId').value ? 'บันทึกการแก้ไขเรียบร้อย' : 'เพิ่มสถานะเรียบร้อย',
                timer: 1500,
                showConfirmButton: false
            });

            // Reload table
            loadStatusTable(currentPage, document.getElementById('searchInput').value);

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
 * Delete status
 */
function deleteStatus(statusId, statusName) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบสถานะ "${statusName}" ใช่หรือไม่?`,
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

            fetch(`${ADMIN_URL}/api/delete_orderstatus.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ orsts_id: statusId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบเรียบร้อย',
                            text: 'ลบสถานะเรียบร้อยแล้ว',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Reload table
                        loadStatusTable(currentPage, document.getElementById('searchInput').value);
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
 * Toggle status status
 */
function toggleStatus(statusId, currentStatus) {
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

            fetch(`${ADMIN_URL}/api/save_orderstatus.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `orsts_id=${statusId}&orsts_status=${newStatus}&quick_update=1`
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
                        loadStatusTable(currentPage, document.getElementById('searchInput').value);
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
        loadStatusTable(page, document.getElementById('searchInput').value);
    }
});
