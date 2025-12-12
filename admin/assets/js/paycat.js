/**
 * Payment Category Management - Full AJAX
 * รองรับ 6 ภาษา: th, en, de, fr, zh, ko
 */

let paycatModal;
let currentPage = 1;

// Initialize modal on page load
document.addEventListener('DOMContentLoaded', function () {
    paycatModal = new bootstrap.Modal(document.getElementById('paycatModal'));
});

/**
 * Load paycat table via AJAX
 */
function loadPaycatTable(page = 1, search = '') {
    currentPage = page;
    const tableContainer = document.getElementById('tableContainer');

    // Show loading
    tableContainer.style.opacity = '0.5';
    tableContainer.style.pointerEvents = 'none';

    const params = new URLSearchParams({
        page: page,
        search: search
    });

    fetch(`${ADMIN_URL}/api/get_paycat_table.php?${params}`)
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
 * Add new paycat
 */
function addPaycat() {
    // Reset form
    document.getElementById('paycatForm').reset();
    document.getElementById('paycatId').value = '';
    document.getElementById('modalTitle').textContent = 'เพิ่มวิธีการชำระเงิน';

    // Defaults
    document.getElementById('paycatIndex').value = '0';
    document.getElementById('paycatActive').checked = true;
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

    paycatModal.show();
}

/**
 * Edit paycat
 */
async function editPaycat(id) {
    // Show loading
    Swal.fire({
        title: 'กำลังโหลด...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`${ADMIN_URL}/api/get_paycat.php?id=${id}`);
        const data = await response.json();

        Swal.close();

        if (data.success) {
            const paycat = data.paycat;

            // Fill form
            document.getElementById('paycatId').value = paycat.paycat_id;
            document.getElementById('paycatShort').value = paycat.paycat_nshort;
            document.getElementById('paycatIndex').value = paycat.paycat_index || 0;
            document.getElementById('paycatActive').checked = paycat.paycat_status == 1;

            // Update status badge
            const statusBadge = document.getElementById('statusBadge');
            if (paycat.paycat_status == 1) {
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
                    const nameInput = document.querySelector(`[name="paycat_name_${lang}"]`);
                    const detailsInput = document.querySelector(`[name="paycat_details_${lang}"]`);

                    if (nameInput) nameInput.value = translation.paycat_name || '';
                    if (detailsInput) {
                        $(detailsInput).summernote({
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
                        $(detailsInput).summernote('code', translation.paycat_details || '');
                    }
                }
            });

            // Update modal title
            document.getElementById('modalTitle').textContent = 'แก้ไขวิธีการชำระเงิน';

            // Show modal
            paycatModal.show();

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
 * Save paycat (Add/Edit)
 */
async function savePaycat() {
    const form = document.getElementById('paycatForm');

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

        const response = await fetch(`${ADMIN_URL}/api/save_paycat.php`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        Swal.close();

        if (data.success) {
            // Close modal
            paycatModal.hide();

            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: document.getElementById('paycatId').value ? 'บันทึกการแก้ไขเรียบร้อย' : 'เพิ่มข้อมูลเรียบร้อย',
                timer: 1500,
                showConfirmButton: false
            });

            // Reload table
            loadPaycatTable(currentPage, document.getElementById('searchInput').value);

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
 * Delete paycat
 */
function deletePaycat(id, name) {
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

            fetch(`${ADMIN_URL}/api/delete_paycat.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ paycat_id: id })
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
                        loadPaycatTable(currentPage, document.getElementById('searchInput').value);
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

            fetch(`${ADMIN_URL}/api/save_paycat.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `paycat_id=${id}&paycat_status=${newStatus}&quick_update=1`
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
                        loadPaycatTable(currentPage, document.getElementById('searchInput').value);
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
        loadPaycatTable(page, document.getElementById('searchInput').value);
    }
});
