/**
 * Hero Slider Management JavaScript
 * CRUD operations for hero slides
 */

const Hero = {
    currentSlideId: null,

    /**
     * Initialize hero management
     */
    init() {
        this.loadTable();
    },

    /**
     * Load hero slides table
     */
    async loadTable() {
        try {
            // Show loading if it's the first load or requested
            const tbody = document.getElementById('hero-tbody');
            if (tbody.innerHTML.trim() === '' || tbody.innerText.includes('กำลังโหลด')) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div><div class="mt-2">กำลังโหลดข้อมูล...</div></td></tr>';
            }

            const response = await fetch(`${ADMIN_URL}/api/get_hero_table.php`);
            const data = await response.json();

            if (data.success && data.slides.length > 0) {
                tbody.innerHTML = data.slides.map((slide, index) => `
                    <tr data-id="${slide.slide_id}">
                        <td class="text-center">
                            <i data-lucide="grip-vertical" style="cursor:move;color:#999;"></i>
                        </td>
                        <td><code>${slide.slide_id}</code></td>
                        <td>
                            ${slide.slide_image ?
                        `<img src="${BASE_URL}/uploads/hero/${slide.slide_image}" style="width:100px;height:60px;object-fit:cover;border-radius:4px;">` :
                        '<div style="width:100px;height:60px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;"><i data-lucide="image" width="24" height="24"></i></div>'
                    }
                        </td>
                        <td>
                            <div class="fw-bold mb-1">${slide.slide_title || '-'}</div>
                            <div class="text-muted small">${this.truncate(slide.slide_subtitle, 100)}</div>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm ${slide.slide_status === 'active' ? 'btn-success' : 'btn-secondary'}" 
                                    style="min-width: 70px;"
                                    onclick="Hero.toggleStatus(${slide.slide_id}, '${slide.slide_status}')">
                                ${slide.slide_status === 'active' ? 'Active' : 'Inactive'}
                            </button>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="Hero.edit(${slide.slide_id})" title="แก้ไข">
                                    <i data-lucide="edit-3" width="16" height="16"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="Hero.delete(${slide.slide_id})" title="ลบ">
                                    <i data-lucide="trash-2" width="16" height="16"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                lucide.createIcons();
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">ยังไม่มีข้อมูล</td></tr>';
            }
        } catch (error) {
            console.error('Error loading hero slides:', error);
        }
    },



    /**
     * Open modal for new slide
     */
    openModal() {
        this.currentSlideId = null;
        document.getElementById('heroForm').reset();
        document.getElementById('slide_id').value = '';
        document.getElementById('heroModalLabel').textContent = 'เพิ่ม Hero Slide';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('uploadPlaceholder').style.display = 'block';

        const modal = new bootstrap.Modal(document.getElementById('heroModal'));
        modal.show();
    },

    /**
     * Edit slide
     */
    async edit(slideId) {
        // Open modal instantly!
        const modalElement = document.getElementById('heroModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();

        // Show the inside loader overlay!
        const loader = document.getElementById('heroModalLoading');
        if (loader) loader.classList.add('show');

        try {
            // Reset form before loading new data
            document.getElementById('heroForm').reset();
            document.getElementById('heroImageBase64').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('uploadPlaceholder').style.display = 'block';

            // Clear all translations
            const languages = ['th', 'en', 'zh', 'de', 'fr', 'ja', 'ko', 'ru', 'ar', 'he'];
            languages.forEach(lang => {
                const title = document.getElementById(`slide_title_${lang}`);
                const subtitle = document.getElementById(`slide_subtitle_${lang}`);
                const b1 = document.getElementById(`button1_text_${lang}`);
                const b2 = document.getElementById(`button2_text_${lang}`);
                if (title) title.value = '';
                if (subtitle) subtitle.value = '';
                if (b1) b1.value = '';
                if (b2) b2.value = '';
            });

            const response = await fetch(`${ADMIN_URL}/api/get_hero.php?id=${slideId}`);
            const data = await response.json();

            if (data.success) {
                this.currentSlideId = slideId;
                const slide = data.slide;

                // Set main fields
                document.getElementById('slide_id').value = slideId;
                document.getElementById('slide_bg_color').value = slide.slide_bg_color || '#0A2F2A';
                document.getElementById('slide_status').value = slide.slide_status;
                document.getElementById('button1_link').value = slide.button1_link || '';
                document.getElementById('button2_link').value = slide.button2_link || '';

                // Set image
                if (slide.slide_image) {
                    document.getElementById('imagePreview').src = `${BASE_URL}/uploads/hero/${slide.slide_image}`;
                    document.getElementById('imagePreview').style.display = 'block';
                    document.getElementById('uploadPlaceholder').style.display = 'none';
                    document.getElementById('existing_image').value = slide.slide_image;
                }

                // Set translations
                languages.forEach(lang => {
                    const trans = slide.translations.find(t => t.lang_code === lang);
                    if (trans) {
                        document.getElementById(`slide_title_${lang}`).value = trans.slide_title || '';
                        document.getElementById(`slide_subtitle_${lang}`).value = trans.slide_subtitle || '';
                        document.getElementById(`button1_text_${lang}`).value = trans.button1_text || '';
                        document.getElementById(`button2_text_${lang}`).value = trans.button2_text || '';
                    }
                });

                document.getElementById('heroModalLabel').textContent = 'แก้ไข Hero Slide';
            } else {
                modal.hide();
                Swal.fire('ผิดพลาด!', data.message || 'ไม่สามารถโหลดข้อมูลได้', 'error');
            }
        } catch (error) {
            console.error('Error loading slide:', error);
            modal.hide();
            Swal.fire('ผิดพลาด!', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        } finally {
            // Hide the inside loader overlay
            if (loader) loader.classList.remove('show');
        }
    },

    /**
     * Save slide
     */
    async save(event) {
        event.preventDefault();

        const formData = new FormData(document.getElementById('heroForm'));

        // Handle save button loading spinner instead of Swal
        const saveBtn = document.querySelector('#heroForm button[type="submit"]');
        let originalBtnHtml = '';
        if (saveBtn) {
            originalBtnHtml = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>กำลังบันทึก...';
        }

        try {
            const response = await fetch(`${ADMIN_URL}/api/save_hero.php`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('สำเร็จ!', 'บันทึกข้อมูลเรียบร้อยแล้ว', 'success');
                const modalElement = document.getElementById('heroModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
                this.loadTable();
            } else {
                Swal.fire('ผิดพลาด!', data.message || 'ไม่สามารถบันทึกได้', 'error');
            }
        } catch (error) {
            console.error('Error saving slide:', error);
            Swal.fire('ผิดพลาด!', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
        } finally {
            // Restore save button state
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnHtml;
                if (window.lucide) lucide.createIcons();
            }
        }
    },

    /**
     * Toggle slide status
     */
    async toggleStatus(slideId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

        try {
            const response = await fetch(`${ADMIN_URL}/api/save_hero.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `slide_id=${slideId}&slide_status=${newStatus}&quick_toggle=1`
            });

            const data = await response.json();

            if (data.success) {
                this.loadTable();
            }
        } catch (error) {
            console.error('Error toggling status:', error);
        }
    },

    /**
     * Delete slide
     */
    async delete(slideId) {
        const result = await Swal.fire({
            title: 'ยืนยันการลบ?',
            text: 'คุณต้องการลบ slide นี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`${ADMIN_URL}/api/delete_hero.php?slide_id=${slideId}`, {
                    method: 'POST'
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire('สำเร็จ!', 'ลบข้อมูลเรียบร้อยแล้ว', 'success');
                    this.loadTable();
                }
            } catch (error) {
                console.error('Error deleting slide:', error);
            }
        }
    },

    /**
     * Save drag-and-drop order
     */
    async saveOrder() {
        const rows = document.querySelectorAll('#hero-tbody tr');
        const order = Array.from(rows).map((row, index) => ({
            id: row.dataset.id,
            order: index + 1
        }));

        try {
            const response = await fetch(`${ADMIN_URL}/api/save_hero_order.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order })
            });

            const data = await response.json();

            if (data.success) {
                console.log('Order saved');
            }
        } catch (error) {
            console.error('Error saving order:', error);
        }
    },

    /**
     * Truncate text
     */
    truncate(text, length) {
        if (!text) return '-';
        return text.length > length ? text.substring(0, length) + '...' : text;
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    Hero.init();

    // Form submit handler
    document.getElementById('heroForm').addEventListener('submit', (e) => Hero.save(e));
});
