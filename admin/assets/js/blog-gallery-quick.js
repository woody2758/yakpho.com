
/**
 * Quick Edit Gallery - Open modal for gallery management only
 */
async function quickEditGallery(blogId, blogTitle) {
    currentBlogId = blogId;

    // Show loading
    Swal.fire({
        title: 'Loading Gallery...',
        html: 'กำลังโหลดแกลเลอรี่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        // Get blog data (for gallery)
        const response = await fetch(`../api/get_blog.php?id=${blogId}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        Swal.close();

        // Create quick gallery modal
        const modalHtml = `
            <div class="modal fade" id="quickGalleryModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i data-lucide="images"></i> Gallery: ${blogTitle}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i data-lucide="images"></i> เลือกรูปภาพ 
                                    <span class="text-muted">(เลือกหลายรูปได้ อัพโหลดอัตโนมัติ)</span>
                                </label>
                                <input type="file" id="quickGalleryInput" class="form-control" accept="image/*" multiple>
                                <small class="text-muted">รองรับ: JPG, PNG, GIF - อัพโหลดและสามารถลาก-วางเรียงลำดับได้</small>
                            </div>
                            <div id="quickGalleryPreview" class="d-flex flex-wrap gap-2">
                                <!-- Gallery images will appear here -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="button" class="btn btn-primary" onclick="closeQuickGallery()">
                                <i data-lucide="check"></i> บันทึกและปิด
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        const existingModal = document.getElementById('quickGalleryModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Initialize modal
        const quickGalleryModal = new bootstrap.Modal(document.getElementById('quickGalleryModal'));

        // Load gallery images
        loadQuickGallery(result.data.gallery || []);

        // Initialize icons
        if (window.lucide) lucide.createIcons();

        // Setup auto-upload for quick gallery
        setupQuickGalleryUpload();

        // Show modal
        quickGalleryModal.show();

        // Clean up on close - refresh table to show updated counts
        document.getElementById('quickGalleryModal').addEventListener('hidden.bs.modal', function () {
            this.remove();
            currentBlogId = 0;

            // Refresh table to update gallery count
            if (typeof loadBlogTable === 'function') {
                loadBlogTable(currentPage);
            }
        }, { once: true });

    } catch (error) {
        console.error('Error loading gallery:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Close quick gallery and refresh table
 */
function closeQuickGallery() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('quickGalleryModal'));
    if (modal) {
        modal.hide();
    }
}

/**
 * Load gallery for quick edit modal
 */
function loadQuickGallery(images) {
    const container = document.getElementById('quickGalleryPreview');
    if (!container) return;

    container.innerHTML = '';

    images.forEach((img, index) => {
        const div = document.createElement('div');
        div.className = 'gallery-item position-relative';
        div.dataset.galleryId = img.id;
        div.style.cssText = 'width: 120px; height: 120px; cursor: move;';

        div.innerHTML = `
            <img src="${img.gallery_image}" class="img-thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
            
            <!-- Delete button -->
            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 delete-gallery-btn" 
                    style="padding: 2px 6px; opacity: 0; transition: opacity 0.2s;" 
                    title="ลบ">
                <i data-lucide="x" style="width:14px;height:14px;"></i>
            </button>
            
            <!-- Order badge -->
            <span class="badge bg-secondary position-absolute bottom-0 start-0 m-1" style="font-size: 10px;">${index + 1}</span>
        `;

        // Show delete button on hover
        div.addEventListener('mouseenter', function () {
            this.querySelector('.delete-gallery-btn').style.opacity = '1';
        });

        div.addEventListener('mouseleave', function () {
            this.querySelector('.delete-gallery-btn').style.opacity = '0';
        });

        // Delete handler
        div.querySelector('.delete-gallery-btn').addEventListener('click', function (e) {
            e.stopPropagation();
            deleteGalleryImageInstant(div);
        });

        container.appendChild(div);
    });

    // Reinit icons
    if (window.lucide) lucide.createIcons();

    // Initialize sorting for quick gallery
    initQuickGallerySorting();
}

/**
 * Initialize sorting for quick gallery modal
 */
function initQuickGallerySorting() {
    const container = document.getElementById('quickGalleryPreview');
    if (!container) return;

    if (window.Sortable) {
        Sortable.create(container, {
            animation: 150,
            handle: '.gallery-item',
            ghostClass: 'gallery-ghost',
            dragClass: 'gallery-drag',
            onEnd: function (evt) {
                saveGalleryOrder();
            }
        });
    }
}

/**
 * Setup auto-upload for quick gallery modal
 */
function setupQuickGalleryUpload() {
    const fileInput = document.getElementById('quickGalleryInput');
    if (!fileInput) return;

    fileInput.addEventListener('change', async function (e) {
        const files = e.target.files;
        if (files.length === 0) return;

        // Upload files one by one
        for (let i = 0; i < files.length; i++) {
            await uploadQuickGalleryImage(files[i]);
        }

        fileInput.value = '';

        // Reload table to update count
        if (typeof loadBlogTable === 'function') {
            loadBlogTable(currentPage);
        }
    });
}

/**
 * Upload single image in quick gallery
 */
async function uploadQuickGalleryImage(file) {
    const container = document.getElementById('quickGalleryPreview');

    // Create preview
    const previewId = 'preview-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    const previewDiv = createQuickGalleryPreview(file, previewId);
    container.appendChild(previewDiv);

    const img = previewDiv.querySelector('img');
    const loadingOverlay = previewDiv.querySelector('.loading-overlay');
    const successIcon = previewDiv.querySelector('.success-icon');

    // Read file
    const reader = new FileReader();
    reader.onload = function (e) {
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);

    // Upload
    try {
        const formData = new FormData();
        formData.append('image', file);
        formData.append('blog_id', currentBlogId);

        const response = await fetch('../api/upload_blog_gallery.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        previewDiv.dataset.galleryId = result.gallery_id;
        img.src = result.image_url;

        // REMOVE loading overlay completely (not just hide)
        if (loadingOverlay) {
            loadingOverlay.remove();
        }

        // Show success checkmark
        if (successIcon) {
            successIcon.style.display = 'flex';
            setTimeout(() => {
                successIcon.remove(); // Remove it completely
            }, 1500);
        }


    } catch (error) {
        console.error('Upload error:', error);
        previewDiv.classList.add('upload-error');
        if (loadingOverlay) {
            loadingOverlay.innerHTML = '<i data-lucide="alert-circle" class="text-danger"></i>';
        }
        setTimeout(() => previewDiv.remove(), 3000);
    }

    if (window.lucide) lucide.createIcons();
}

/**
 * Create preview for quick gallery
 */
function createQuickGalleryPreview(file, previewId) {
    const div = document.createElement('div');
    div.className = 'gallery-item position-relative';
    div.dataset.previewId = previewId;
    div.style.cssText = 'width: 120px; height: 120px; cursor: move;';

    div.innerHTML = `
        <img src="" class="img-thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
        
        <div class="loading-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50">
            <div class="spinner-border spinner-border-sm text-light" role="status"></div>
        </div>
        
        <div class="success-icon position-absolute top-0 start-0 w-100 h-100 align-items-center justify-content-center bg-success bg-opacity-75" style="display: none;">
            <i data-lucide="check" class="text-white" style="width:32px;height:32px;"></i>
        </div>
        
        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 delete-gallery-btn" 
                style="padding: 2px 6px; opacity: 0; transition: opacity 0.2s;" title="ลบ">
            <i data-lucide="x" style="width:14px;height:14px;"></i>
        </button>
    `;

    div.addEventListener('mouseenter', function () {
        this.querySelector('.delete-gallery-btn').style.opacity = '1';
    });

    div.addEventListener('mouseleave', function () {
        this.querySelector('.delete-gallery-btn').style.opacity = '0';
    });

    div.querySelector('.delete-gallery-btn').addEventListener('click', function (e) {
        e.stopPropagation();
        deleteGalleryImageInstant(div);
    });

    return div;
}
