/**
 * Blog Gallery Auto-Upload System
 * Features: Auto upload, Live preview, Drag-drop reordering, Instant delete
 */

let gallerySortable = null;

/**
 * Initialize gallery auto-upload
 */
function initGalleryAutoUpload() {
    const galleryInput = document.getElementById('galleryInput');
    const galleryPreview = document.getElementById('galleryPreview');

    if (!galleryInput || !galleryPreview) return;

    // Auto upload on file select
    galleryInput.addEventListener('change', async function (e) {
        const files = e.target.files;

        if (files.length === 0) return;

        // Check if blog is saved
        if (!currentBlogId || currentBlogId === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาบันทึกบล็อกก่อน',
                text: 'คุณต้องบันทึกบล็อกก่อนอัพโหลดแกลเลอรี่',
                confirmButtonText: 'รับทราบ'
            });
            e.target.value = '';
            return;
        }

        // Upload files one by one with preview
        for (let i = 0; i < files.length; i++) {
            await uploadSingleGalleryImage(files[i]);
        }

        // Clear input
        e.target.value = '';
    });

    // Initialize drag-drop sorting
    initGallerySorting();
}

/**
 * Upload single image with live preview
 */
async function uploadSingleGalleryImage(file) {
    const galleryPreview = document.getElementById('galleryPreview');

    // Create preview item immediately
    const previewId = 'preview-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    const previewDiv = createGalleryPreviewItem(file, previewId);
    galleryPreview.appendChild(previewDiv);

    // Get elements
    const img = previewDiv.querySelector('img');
    const loadingOverlay = previewDiv.querySelector('.loading-overlay');
    const successIcon = previewDiv.querySelector('.success-icon');

    // Read file for preview
    const reader = new FileReader();
    reader.onload = function (e) {
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);

    // Upload to server
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

        // Update preview with server image
        previewDiv.dataset.galleryId = result.gallery_id;
        img.src = result.image_url;

        // Hide loading spinner
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }

        // Show success checkmark
        if (successIcon) {
            successIcon.style.display = 'flex';

            // Hide checkmark after 1.5 seconds
            setTimeout(() => {
                successIcon.style.display = 'none';
            }, 1500);
        }

    } catch (error) {
        console.error('Upload error:', error);

        // Show error state
        previewDiv.classList.add('upload-error');

        if (loadingOverlay) {
            loadingOverlay.innerHTML = '<i data-lucide="alert-circle" class="text-danger"></i>';
        }

        // Remove after 3 seconds
        setTimeout(() => {
            previewDiv.remove();
        }, 3000);

        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: error.message,
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Reinitialize icons
    if (window.lucide) lucide.createIcons();
}

/**
 * Create gallery preview item HTML
 */
function createGalleryPreviewItem(file, previewId) {
    const div = document.createElement('div');
    div.className = 'gallery-item position-relative';
    div.dataset.previewId = previewId;
    div.style.cssText = 'width: 120px; height: 120px; cursor: move;';

    div.innerHTML = `
        <img src="" class="img-thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
        
        <!-- Loading overlay -->
        <div class="loading-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50">
            <div class="spinner-border spinner-border-sm text-light" role="status"></div>
        </div>
        
        <!-- Success icon -->
        <div class="success-icon position-absolute top-0 start-0 w-100 h-100 align-items-center justify-content-center bg-success bg-opacity-75" style="display: none;">
            <i data-lucide="check" class="text-white" style="width:32px;height:32px;"></i>
        </div>
        
        <!-- Delete button -->
        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 delete-gallery-btn" 
                style="padding: 2px 6px; opacity: 0; transition: opacity 0.2s;" 
                title="ลบ">
            <i data-lucide="x" style="width:14px;height:14px;"></i>
        </button>
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

    return div;
}

/**
 * Initialize drag-drop sorting with SortableJS
 */
function initGallerySorting() {
    const galleryPreview = document.getElementById('galleryPreview');

    if (!galleryPreview) return;

    // Destroy existing sortable
    if (gallerySortable) {
        gallerySortable.destroy();
    }

    // Create new sortable
    gallerySortable = Sortable.create(galleryPreview, {
        animation: 150,
        handle: '.gallery-item',
        ghostClass: 'gallery-ghost',
        dragClass: 'gallery-drag',
        onEnd: function (evt) {
            // Save new order to server
            saveGalleryOrder();
        }
    });
}

/**
 * Save gallery order to server
 */
async function saveGalleryOrder() {
    const galleryPreview = document.getElementById('galleryPreview');
    const items = galleryPreview.querySelectorAll('.gallery-item[data-gallery-id]');

    const order = Array.from(items).map((item, index) => ({
        id: parseInt(item.dataset.galleryId),
        order: index + 1
    }));

    if (order.length === 0) return;

    try {
        const response = await fetch('../api/save_blog_gallery_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                blog_id: currentBlogId,
                order: order
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        console.log('Gallery order saved');

    } catch (error) {
        console.error('Error saving order:', error);
    }
}

/**
 * Delete gallery image instantly (no confirmation)
 */
async function deleteGalleryImageInstant(itemDiv) {
    const galleryId = itemDiv.dataset.galleryId;

    if (!galleryId) {
        // Not uploaded yet, just remove preview
        itemDiv.remove();
        return;
    }

    // Show deleting state
    itemDiv.style.opacity = '0.5';
    itemDiv.style.pointerEvents = 'none';

    try {
        const response = await fetch('../api/delete_blog_gallery.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(galleryId) })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        // Remove with animation
        itemDiv.style.transition = 'all 0.3s';
        itemDiv.style.transform = 'scale(0)';
        setTimeout(() => {
            itemDiv.remove();
        }, 300);

    } catch (error) {
        console.error('Error deleting:', error);

        // Restore state on error
        itemDiv.style.opacity = '1';
        itemDiv.style.pointerEvents = 'auto';

        Swal.fire({
            icon: 'error',
            title: 'ไม่สามารถลบได้',
            text: error.message,
            timer: 2000,
            showConfirmButton: false
        });
    }
}

/**
 * Load existing gallery images
 */
function loadGalleryAuto(images) {
    const container = document.getElementById('galleryPreview');
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

    // Reinit icons and sorting
    if (window.lucide) lucide.createIcons();
    initGallerySorting();
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGalleryAutoUpload);
} else {
    initGalleryAutoUpload();
}
