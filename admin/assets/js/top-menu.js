/**
 * Top Menu Management JavaScript
 * Handles AJAX operations for header navigation with Hierarchical Builder
 */

const TopMenu = {
    apiPath: `${ADMIN_URL}/api`,

    /**
     * Load menu builder
     */
    async loadTable() {
        const container = document.getElementById('top-menu-builder');
        container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 mb-0">กำลังโหลดระบบจัดการเมนู...</p></div>';

        try {
            const response = await fetch(`${this.apiPath}/get_top_menu_table.php`);
            const data = await response.json();

            if (data.success) {
                if (!data.menus || data.menus.length === 0) {
                    container.innerHTML = '<div class="text-center py-4">ไม่พบข้อมูลเมนู <button class="btn btn-sm btn-primary ms-2" onclick="TopMenu.openModal()">เพิ่มเมนูแรก</button></div>';
                    return;
                }

                container.innerHTML = `<ul id="menu-builder-root" class="menu-list">${this.renderMenuItems(data.menus)}</ul>`;

                this.initSortable();
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        } catch (error) {
            console.error('Error loading builder:', error);
            container.innerHTML = '<div class="text-center py-4 text-danger">เกิดข้อผิดพลาดในการโหลดระบบ</div>';
        }
    },

    /**
     * Render menu items recursively
     */
    renderMenuItems(items) {
        if (!items || items.length === 0) return '';

        return items.map(item => `
            <li data-id="${item.menu_id}" class="menu-item-node">
                <div class="menu-item-content">
                    <div class="d-flex align-items-center flex-grow-1">
                        <i data-lucide="grip-vertical" class="menu-item-handle me-2"></i>
                        <span class="menu-item-title">${item.menu_text || '-'}</span>
                        <code class="ms-2 small text-muted">#${item.menu_id}</code>
                    </div>
                    
                    <div class="menu-item-actions">
                        <span class="badge ${item.menu_status === 'active' ? 'bg-success' : 'bg-secondary'} me-2 cursor-pointer" 
                              onclick="TopMenu.toggleStatus(${item.menu_id}, '${item.menu_status}')">
                            ${item.menu_status === 'active' ? 'Active' : 'Inactive'}
                        </span>
                        <button class="btn btn-sm btn-outline-primary shadow-sm" onclick="TopMenu.edit(${item.menu_id})" title="แก้ไข">
                            <i data-lucide="edit-3" width="14" height="14"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger shadow-sm" onclick="TopMenu.delete(${item.menu_id})" title="ลบ">
                            <i data-lucide="trash-2" width="14" height="14"></i>
                        </button>
                    </div>
                </div>
                <ul class="submenu-list" data-parent-id="${item.menu_id}">
                    ${item.children ? this.renderMenuItems(item.children) : ''}
                </ul>
            </li>
        `).join('');
    },

    /**
     * Initialize Sortable for nesting
     */
    initSortable() {
        const lists = document.querySelectorAll('.nested-menu-container ul');
        lists.forEach(list => {
            new Sortable(list, {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                handle: '.menu-item-handle',
                ghostClass: 'ghost',
                onEnd: (evt) => {
                    this.saveOrder();
                }
            });
        });
    },

    /**
     * Load parent menus for dropdown
     */
    async loadParents() {
        try {
            const response = await fetch(`${this.apiPath}/get_top_menu_table.php?parents_only=1`);
            const data = await response.json();

            if (data.success) {
                const select = document.getElementById('parent_id');
                const currentValue = select.value;

                let html = '<option value="0">--- ไม่มี (เป็นเมนูหลัก) ---</option>';
                data.menus.forEach(menu => {
                    html += `<option value="${menu.menu_id}">${menu.menu_text}</option>`;
                });

                select.innerHTML = html;
                select.value = currentValue;
            }
        } catch (error) {
            console.error('Error loading parents:', error);
        }
    },

    /**
     * Open modal
     */
    openModal(id = 0) {
        document.getElementById('topMenuForm').reset();
        document.getElementById('menu_id').value = id;
        document.getElementById('modalTitle').textContent = id ? 'แก้ไขรายการเมนู' : 'เพิ่มรายการเมนู';

        // Reset icon preview
        document.getElementById('iconPreview').setAttribute('data-lucide', 'help-circle');
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Ensure language panes reset
        const languages = ['th', 'en', 'zh', 'de', 'fr', 'ja', 'ko', 'ru', 'ar', 'he'];
        languages.forEach(lang => {
            const input = document.getElementById(`menu_text_${lang}`);
            if (input) input.value = '';
        });

        const modal = new bootstrap.Modal(document.getElementById('topMenuModal'));
        modal.show();
    },

    /**
     * Edit menu
     */
    async edit(id) {
        // Show modal instantly!
        const modalElement = document.getElementById('topMenuModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();

        // Show inside loader
        const loader = document.getElementById('topMenuModalLoading');
        if (loader) loader.classList.add('show');

        try {
            // Reset form before loading new data
            document.getElementById('topMenuForm').reset();
            document.getElementById('menu_id').value = id;
            document.getElementById('modalTitle').textContent = 'แก้ไขรายการเมนู';

            // Reset icon preview
            document.getElementById('iconPreview').setAttribute('data-lucide', 'help-circle');
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Clear all translations first
            const languages = ['th', 'en', 'zh', 'de', 'fr', 'ja', 'ko', 'ru', 'ar', 'he'];
            languages.forEach(lang => {
                const input = document.getElementById(`menu_text_${lang}`);
                if (input) input.value = '';
            });

            const response = await fetch(`${this.apiPath}/get_top_menu.php?id=${id}`);
            const data = await response.json();

            if (data.success) {
                const menu = data.menu;

                document.getElementById('parent_id').value = menu.parent_id;
                document.getElementById('menu_link').value = menu.menu_link;
                document.getElementById('menu_icon').value = menu.menu_icon || '';
                document.getElementById('menu_status').value = menu.menu_status;

                for (const lang in menu.translations) {
                    const input = document.getElementById(`menu_text_${lang}`);
                    if (input) input.value = menu.translations[lang];
                }

                const iconName = menu.menu_icon || 'help-circle';
                document.getElementById('iconPreview').setAttribute('data-lucide', iconName);
                if (typeof lucide !== 'undefined') lucide.createIcons();

            } else {
                modal.hide();
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            modal.hide();
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
        } finally {
            if (loader) loader.classList.remove('show');
        }
    },

    /**
     * Save menu
     */
    async save(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const saveBtn = document.getElementById('saveBtn');

        saveBtn.disabled = true;
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> กำลังบันทึก...';

        try {
            const response = await fetch(`${this.apiPath}/save_top_menu.php`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('topMenuModal')).hide();
                Swal.fire('สำเร็จ', 'บันทึกข้อมูลเรียบร้อยแล้ว', 'success');
                this.loadTable();
                this.loadParents();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    },

    /**
     * Toggle status
     */
    async toggleStatus(id, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

        try {
            const formData = new FormData();
            formData.append('menu_id', id);
            formData.append('menu_status', newStatus);
            formData.append('quick_toggle', 1);

            const response = await fetch(`${this.apiPath}/save_top_menu.php`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                this.loadTable();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    },

    /**
     * Delete menu
     */
    async delete(id) {
        const result = await Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "ข้อมูลนี้จะถูกย้ายไปที่ถังขยะ",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`${this.apiPath}/delete_top_menu.php?id=${id}`, {
                    method: 'POST'
                });
                const data = await response.json();

                if (data.success) {
                    Swal.fire('ลบแล้ว!', 'ข้อมูลถูกย้ายไปที่ถังขยะเรียบร้อย', 'success');
                    this.loadTable();
                    this.loadParents();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
            }
        }
    },

    /**
     * Save order and hierarchy
     */
    async saveOrder() {
        const root = document.getElementById('menu-builder-root');
        if (!root) return;

        const orderData = [];

        const traverse = (ul, parentId = 0) => {
            const items = ul.children;
            Array.from(items).forEach((li, index) => {
                const id = li.dataset.id;
                orderData.push({
                    id: id,
                    parent_id: parentId,
                    order: index + 1
                });

                const subUl = li.querySelector('.submenu-list');
                if (subUl) {
                    traverse(subUl, id);
                }
            });
        };

        traverse(root);

        try {
            const response = await fetch(`${this.apiPath}/save_top_menu_order.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order: orderData })
            });
            const data = await response.json();
            if (data.success) {
                console.log('Hierarchy saved');
                // Optional: Refresh parents list because hierarchy might have changed
                this.loadParents();
                // We don't necessarily need to reload high-res table unless we want to clean up labels
            }
        } catch (error) {
            console.error('Error saving order:', error);
        }
    }
};
