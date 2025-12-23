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
        this.initializeImageUpload();
    },

    /**
     * Load hero slides table
     */
    async loadTable() {
        try {
            const response = await fetch('/admin/api/get_hero_table.php');
            const data = await response.json();

            const tbody = document.getElementById('hero-tbody');

            if (data.success && data.slides.length > 0) {
                tbody.innerHTML = data.slides.map((slide, index) => `
                    <tr data-id="${slide.slide_id}">
                        <td>
                            <i data-lucide="grip-vertical" style="cursor:move;color:#999;"></i>
                            ${index + 1}
                        </td>
                        <td>
                            ${slide.slide_image ?
                        `<img src="/yakpho.com/uploads/hero/${slide.slide_image}" style="width:100px;height:60px;object-fit:cover;border-radius:4px;">` :
                        '<div style="width:100px;height:60px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;"><i data-lucide="image" width="24" height="24"></i></div>'
                    }
                        </td>
                        <td><strong>${slide.slide_title || '-'}</strong></td>
                        <td class="text-muted small">${this.truncate(slide.slide_subtitle, 60)}</td>
                        <td>
                            <button class="btn btn-sm ${slide.slide_status === 'active' ? 'btn-success' : 'btn-secondary'}" 
                                    onclick="Hero.toggleStatus(${slide.slide_id}, '${slide.slide_status}')">
                                ${slide.slide_status === 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน'}
                            </button>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="Hero.edit(${slide.slide_id})" title="แก้ไข">
                                <i data-lucide="edit-2" width="16" height="16"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="Hero.delete(${slide.slide_id})" title="ลบ">
                                <i data-lucide="trash-2" width="16" height="16"></i>
                            </button>
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
     * Initialize image upload
     */
    initializeImageUpload() {
        const uploadArea = document.getElementById('imageUploadArea');
        const fileInput = document.getElementById('slide_image');
        const preview = document.getElementById('imagePreview');
        const placeholder = document.getElementById('uploadPlaceholder');

        uploadArea.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
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
        try {
            const response = await fetch(`/admin/api/get_hero.php?id=${slideId}`);
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
                    document.getElementById('imagePreview').src = `/yakpho.com/uploads/hero/${slide.slide_image}`;
                    document.getElementById('imagePreview').style.display = 'block';
                    document.getElementById('uploadPlaceholder').style.display = 'none';
                    document.getElementById('existing_image').value = slide.slide_image;
                }

                // Set translations
                const languages = ['th', 'en', 'zh', 'de', 'fr', 'ja', 'ko', 'ru', 'ar', 'he'];
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
                const modal = new bootstrap.Modal(document.getElementById('heroModal'));
                modal.show();
            }
        } catch (error) {
            console.error('Error loading slide:', error);
        }
    },

    /**
     * Save slide
     */
    async save(event) {
        event.preventDefault();

        const formData = new FormData(document.getElementById('heroForm'));

        try {
            const response = await fetch('/admin/api/save_hero.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('สำเร็จ!', 'บันทึกข้อมูลเรียบร้อยแล้ว', 'success');
                bootstrap.Modal.getInstance(document.getElementById('heroModal')).hide();
                this.loadTable();
            } else {
                Swal.fire('ผิดพลาด!', data.message || 'ไม่สามารถบันทึกได้', 'error');
            }
        } catch (error) {
            console.error('Error saving slide:', error);
            Swal.fire('ผิดพลาด!', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
        }
    },

    /**
     * Toggle slide status
     */
    async toggleStatus(slideId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

        try {
            const response = await fetch('/admin/api/save_hero.php', {
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
                const response = await fetch('/admin/api/delete_hero.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `slide_id=${slideId}`
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
            const response = await fetch('/admin/api/save_hero_order.php', {
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
