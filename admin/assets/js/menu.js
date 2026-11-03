/**
 * Menu Management System
 * Admin interface logic
 */

const Menu = {
    modalId: '#menuModal',
    formId: '#menuForm',
    tbodyId: '#menu-tbody',
    apiPath: `${ADMIN_URL}/api`,

    /**
     * Load menus table
     */
    async loadTable() {
        const column = document.getElementById('columnFilter').value;
        const tbody = document.getElementById(this.tbodyId.replace('#', ''));

        // Show loading state in table
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 mb-0">กำลังโหลดข้อมูล...</p></td></tr>';

        try {
            const response = await fetch(`${this.apiPath}/get_menu_table.php?column=${column}`);
            const data = await response.json();

            if (data.success) {
                this.renderTable(data.menus);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${data.message}</td></tr>`;
            }
        } catch (error) {
            console.error('Error loading menus:', error);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">เกิดข้อผิดพลาดในการเชื่อมต่อ</td></tr>`;
        }
    },

    /**
     * Render table rows
     */
    renderTable(menus) {
        const tbody = document.getElementById(this.tbodyId.replace('#', ''));

        if (menus.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted">ไม่พบข้อมูลรายการเมนู</td></tr>`;
            return;
        }

        const columnMap = {
            'company': 'เกี่ยวกับบริษัท',
            'quicklinks': 'ลิงก์ด่วน',
            'support': 'ช่วยเหลือ',
            'followus': 'ติดตามเรา'
        };

        tbody.innerHTML = menus.map((menu, index) => `
            <tr data-id="${menu.menu_id}">
                <td class="text-center">
                    <div class="drag-handle" style="cursor: move;">
                        <i data-lucide="grip-vertical" class="text-muted" width="16"></i>
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">${columnMap[menu.menu_column] || menu.menu_column}</span>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        ${menu.menu_icon ? `<i data-lucide="${menu.menu_icon}" class="me-2 text-primary" width="18"></i>` : ''}
                        <strong>${menu.menu_text || ' - '}</strong>
                    </div>
                </td>
                <td>
                    <code class="small">${menu.menu_link}</code>
                    ${menu.menu_target === '_blank' ? '<i data-lucide="external-link" class="ms-1 text-muted" width="12"></i>' : ''}
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-flex justify-content-center p-0">
                        <input class="form-check-input" type="checkbox" role="switch" 
                               ${menu.menu_status === 'active' ? 'checked' : ''} 
                               onchange="Menu.toggleStatus(${menu.menu_id}, this.checked)">
                    </div>
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="Menu.edit(${menu.menu_id})" title="แก้ไข">
                            <i data-lucide="edit-3" width="16"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="Menu.delete(${menu.menu_id})" title="ลบ">
                            <i data-lucide="trash-2" width="16"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    },

    /**
     * Open modal for adding/editing
     */
    openModal(id = 0) {
        const form = document.getElementById(this.formId.replace('#', ''));
        form.reset();
        document.getElementById('menu_id').value = id;
        document.getElementById('modalTitle').textContent = id === 0 ? 'เพิ่มรายการเมนู' : 'แก้ไขรายการเมนู';

        // Reset icon preview
        document.getElementById('iconPreview').setAttribute('data-lucide', 'help-circle');
        this.toggleIconField();

        const modal = new bootstrap.Modal(document.getElementById(this.modalId.replace('#', '')));
        modal.show();
    },

    /**
     * Toggle icon field visibility
     */
    toggleIconField() {
        const column = document.getElementById('menu_column').value;
        const iconField = document.getElementById('iconField');
        iconField.style.display = column === 'followus' ? 'block' : 'none';

        if (column !== 'followus') {
            document.getElementById('menu_icon').value = '';
        }
    },

    /**
     * Edit menu item
     */
    async edit(id) {
        // Show modal instantly!
        const modalElement = document.getElementById(this.modalId.replace('#', ''));
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();

        // Show inside loader
        const loader = document.getElementById('menuModalLoading');
        if (loader) loader.classList.add('show');

        try {
            // Reset form before loading new data
            const form = document.getElementById(this.formId.replace('#', ''));
            form.reset();
            document.getElementById('menu_id').value = id;
            document.getElementById('modalTitle').textContent = 'แก้ไขรายการเมนู';

            // Reset icon preview
            document.getElementById('iconPreview').setAttribute('data-lucide', 'help-circle');
            this.toggleIconField();

            // Clear all translations first
            const languages = ['th', 'en', 'cn', 'de', 'fr', 'ja', 'kr', 'ru', 'ar', 'he'];
            languages.forEach(lang => {
                const input = document.getElementById(`menu_text_${lang}`);
                if (input) input.value = '';
            });

            const response = await fetch(`${this.apiPath}/get_menu.php?id=${id}`);
            const data = await response.json();

            if (data.success) {
                const menu = data.menu;

                document.getElementById('menu_id').value = menu.menu_id;
                document.getElementById('menu_column').value = menu.menu_column;
                document.getElementById('menu_link').value = menu.menu_link;
                document.getElementById('menu_icon').value = menu.menu_icon || '';
                document.getElementById('menu_status').value = menu.menu_status;

                if (menu.menu_target === '_blank') {
                    document.getElementById('target_blank').checked = true;
                } else {
                    document.getElementById('target_self').checked = true;
                }

                // Fill translations
                for (const lang in menu.translations) {
                    const input = document.getElementById(`menu_text_${lang}`);
                    if (input) {
                        input.value = menu.translations[lang];
                    }
                }

                // Update icon preview
                const iconName = menu.menu_icon || 'help-circle';
                document.getElementById('iconPreview').setAttribute('data-lucide', iconName);
                if (typeof lucide !== 'undefined') lucide.createIcons();

            } else {
                modal.hide();
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            console.error('Error fetching menu:', error);
            modal.hide();
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
        } finally {
            if (loader) loader.classList.remove('show');
        }
    },

    /**
     * Save menu item
     */
    async save(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const saveBtn = document.getElementById('saveBtn');

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> กำลังบันทึก...';

        try {
            const response = await fetch(`${this.apiPath}/save_menu.php`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById(this.modalId.replace('#', '')));
                modal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                this.loadTable();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            console.error('Error saving menu:', error);
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i data-lucide="save"></i> บันทึกข้อมูล';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    },

    /**
     * Delete menu item
     */
    async delete(id) {
        const result = await Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "คุณต้องการลบรายการเมนูนี้ใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`${this.apiPath}/delete_menu.php?id=${id}`, {
                    method: 'POST'
                });
                const data = await response.json();

                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    this.loadTable();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting menu:', error);
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
            }
        }
    },

    /**
     * Toggle menu status
     */
    async toggleStatus(id, isActive) {
        const status = isActive ? 'active' : 'inactive';
        try {
            const response = await fetch(`${this.apiPath}/save_menu.php`, {
                method: 'POST',
                body: new URLSearchParams({
                    'action': 'toggle_status',
                    'menu_id': id,
                    'menu_status': status
                })
            });
            const data = await response.json();

            if (!data.success) {
                Swal.fire('Error', data.message, 'error');
                this.loadTable(); // Revert toggle
            }
        } catch (error) {
            console.error('Error toggling status:', error);
            this.loadTable();
        }
    },

    /**
     * Save drag-and-drop order
     */
    async saveOrder() {
        const rows = document.querySelectorAll(`${this.tbodyId} tr`);
        const itemIds = Array.from(rows).map(row => row.getAttribute('data-id'));

        try {
            const response = await fetch(`${this.apiPath}/save_menu_order.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ itemIds })
            });
            const data = await response.json();

            if (!data.success) {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            console.error('Error saving order:', error);
        }
    }
};
