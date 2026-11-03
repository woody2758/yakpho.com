let channelModal;
let currentPage = 1;

document.addEventListener('DOMContentLoaded', function() {
    channelModal = new bootstrap.Modal(document.getElementById('channelModal'));
});

function loadTable(page = 1, search = '') {
    currentPage = page;
    const tableContainer = document.getElementById('tableContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    
    // Show loading
    tableContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    fetch(`${ADMIN_URL}/api/get_sales_channels_table.php?page=${page}&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tableContainer.innerHTML = data.html;
                document.getElementById('paginationContainer').innerHTML = data.pagination;
                
                // Re-initialize icons
                lucide.createIcons();
                
                // Initialize drag & drop sorting
                initializeSortable();
            } else {
                tableContainer.innerHTML = `<div class="alert alert-danger m-3">${data.message}</div>`;
                paginationContainer.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tableContainer.innerHTML = '<div class="alert alert-danger m-3">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            paginationContainer.innerHTML = '';
        });
}

function addChannel() {
    document.getElementById('channelForm').reset();
    document.getElementById('channelId').value = '';
    document.getElementById('channelIndex').value = '0';
    document.getElementById('modalTitle').textContent = 'เพิ่มช่องทางขาย';
    
    document.getElementById('channelActive').checked = true;
    document.getElementById('statusBadge').textContent = 'เปิดใช้งาน';
    document.getElementById('statusBadge').className = 'badge bg-success';
    
    channelModal.show();
}

function editChannel(id) {
    fetch(`${ADMIN_URL}/api/get_sales_channel.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const channel = data.channel;
                document.getElementById('channelId').value = channel.channel_id;
                document.getElementById('channelName').value = channel.channel_name;
                document.getElementById('channelCode').value = channel.channel_code;
                document.getElementById('channelIndex').value = channel.channel_index;
                
                const activeCheckbox = document.getElementById('channelActive');
                const badge = document.getElementById('statusBadge');
                
                if (channel.channel_status == 1) {
                    activeCheckbox.checked = true;
                    badge.textContent = 'เปิดใช้งาน';
                    badge.className = 'badge bg-success';
                } else {
                    activeCheckbox.checked = false;
                    badge.textContent = 'ปิดใช้งาน';
                    badge.className = 'badge bg-secondary';
                }
                
                document.getElementById('modalTitle').textContent = 'แก้ไขช่องทางขาย';
                channelModal.show();
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
        });
}

function saveChannel() {
    const form = document.getElementById('channelForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    
    // Checkbox is only sent if checked, so handle explicitly
    if (!document.getElementById('channelActive').checked) {
        formData.set('channel_status', '0');
    }

    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`${ADMIN_URL}/api/save_sales_channel.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: 'บันทึกข้อมูลเรียบร้อยแล้ว',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                channelModal.hide();
                loadTable(1, document.getElementById('searchInput').value);
            });
        } else {
            Swal.fire('ข้อผิดพลาด', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
    });
}

function deleteChannel(id, name) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบช่องทางขาย "${name}" ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
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

            fetch(`${ADMIN_URL}/api/delete_sales_channel.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ลบเรียบร้อย!',
                        text: 'ข้อมูลถูกลบแล้ว',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        loadTable(1, document.getElementById('searchInput').value);
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถลบข้อมูลได้', 'error');
            });
        }
    });
}

/**
 * Initialize Sortable.js for drag & drop
 */
function initializeSortable() {
    const tbody = document.querySelector('#tableContainer tbody');
    if (!tbody) return;

    if (tbody.sortable) {
        tbody.sortable.destroy();
    }

    tbody.sortable = new Sortable(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: function (evt) {
            saveNewOrder();
        }
    });
}

/**
 * Save new category order to backend
 */
function saveNewOrder() {
    const rows = document.querySelectorAll('.sortable-row');
    const order = Array.from(rows).map(row => row.dataset.id);

    if (order.length === 0) return;

    const overlay = document.getElementById('tableLoadingOverlay');
    if (overlay) overlay.style.setProperty('display', 'flex', 'important');

    fetch(`${ADMIN_URL}/api/save_sales_channel_order.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            order: order,
            page: currentPage,
            limit: 15
        })
    })
    .then(res => res.json())
    .then(data => {
        if (overlay) overlay.style.setProperty('display', 'none', 'important');
        if (data.success) {
            loadTable(currentPage, document.getElementById('searchInput').value);
        } else {
            Swal.fire({ icon: 'error', title: 'ไม่สามารถบันทึกลำดับได้', text: data.message });
        }
    })
    .catch(error => {
        if (overlay) overlay.style.setProperty('display', 'none', 'important');
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
    });
}
