/**
 * Image Cropping and Gallery Functions
 * Appended to products.js
 */

/**
 * Handle main image selection - initialize cropper
 */
function handleMainImageSelect(e) {
    const file = e.target.files[0];

    if (!file) return;

    // Validate file type
    if (!file.type.match('image.*')) {
        Swal.fire({
            icon: 'error',
            title: 'ไฟล์ไม่ถูกต้อง',
            text: 'กรุณาเลือกไฟล์รูปภาพเท่านั้น'
        });
        e.target.value = '';
        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {
        const imageToCrop = document.getElementById('imageToCrop');
        const cropperContainer = document.getElementById('imageCropperContainer');
        const croppedPreview = document.getElementById('croppedPreview');

        // Hide preview if exists
        croppedPreview.style.display = 'none';

        // Show cropper
        imageToCrop.src = event.target.result;
        cropperContainer.style.display = 'block';

        // Destroy existing cropper if any
        if (cropper) {
            cropper.destroy();
        }

        // Initialize Cropper.js
        cropper = new Cropper(imageToCrop, {
            aspectRatio: 1, // Square
            viewMode: 1,
            autoCropArea: 1,
            responsive: true,
            guides: true,
            center: true,
            highlight: true,
            cropBoxResizable: true,
            cropBoxMovable: true,
            toggleDragModeOnDblclick: false
        });
    };

    reader.readAsDataURL(file);
}

/**
 * Crop and save the image as Base64
 */
function cropAndSave() {
    if (!cropper) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่พบ Cropper'
        });
        return;
    }

    // Get cropped canvas
    const canvas = cropper.getCroppedCanvas({
        width: 800,
        height: 800,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });

    // Convert to Base64
    const base64Image = canvas.toDataURL('image/png');

    // Store in hidden input
    document.getElementById('productImageBase64').value = base64Image;

    // Show preview
    const croppedImage = document.getElementById('croppedImage');
    const croppedPreview = document.getElementById('croppedPreview');
    const cropperContainer = document.getElementById('imageCropperContainer');

    croppedImage.src = base64Image;
    croppedPreview.style.display = 'block';
    cropperContainer.style.display = 'none';

    // Destroy cropper
    cropper.destroy();
    cropper = null;

    // Clear file input
    document.getElementById('mainImageInput').value = '';

    // Reinitialize icons
    if (window.lucide) lucide.createIcons();
}

/**
 * Use original image without cropping (auto-resize to 800x800)
 */
function useOriginalImage() {
    if (!cropper) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่พบรูปภาพ'
        });
        return;
    }

    // Get the original image from cropper
    const imageElement = cropper.image;

    // Create canvas for resizing
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    // Set canvas size to 800x800 (square)
    const targetSize = 800;
    canvas.width = targetSize;
    canvas.height = targetSize;

    // Calculate dimensions to maintain aspect ratio (cover mode)
    const img = new Image();
    img.onload = function () {
        const scale = Math.max(targetSize / img.width, targetSize / img.height);
        const scaledWidth = img.width * scale;
        const scaledHeight = img.height * scale;

        // Center the image
        const x = (targetSize - scaledWidth) / 2;
        const y = (targetSize - scaledHeight) / 2;

        // Draw image on canvas
        ctx.drawImage(img, x, y, scaledWidth, scaledHeight);

        // Convert to Base64
        const base64Image = canvas.toDataURL('image/png');

        // Store in hidden input
        document.getElementById('productImageBase64').value = base64Image;

        // Show preview
        const croppedImage = document.getElementById('croppedImage');
        const croppedPreview = document.getElementById('croppedPreview');
        const cropperContainer = document.getElementById('imageCropperContainer');

        croppedImage.src = base64Image;
        croppedPreview.style.display = 'block';
        cropperContainer.style.display = 'none';

        // Destroy cropper
        cropper.destroy();
        cropper = null;

        // Clear file input
        document.getElementById('mainImageInput').value = '';

        // Reinitialize icons
        if (window.lucide) lucide.createIcons();
    };

    img.src = imageElement.src;
}

/**
 * Cancel cropping
 */
function cancelCrop() {
    const cropperContainer = document.getElementById('imageCropperContainer');
    cropperContainer.style.display = 'none';

    if (cropper) {
        cropper.destroy();
        cropper = null;
    }

    // Clear file input
    document.getElementById('mainImageInput').value = '';
}

/**
 * Remove cropped image
 */
function removeCroppedImage() {
    document.getElementById('productImageBase64').value = '';
    document.getElementById('croppedPreview').style.display = 'none';
    document.getElementById('mainImageInput').value = '';
}

/**
 * Handle gallery images selection - show preview (append to existing)
 */
function handleGalleryImagesSelect(e) {
    const files = e.target.files;
    const galleryPreview = document.getElementById('galleryPreview');

    if (!files || files.length === 0) return;

    // Get current number of images (for numbering)
    const existingImages = galleryPreview.querySelectorAll('.col-4').length;

    // DON'T clear existing previews - just append new ones
    // galleryPreview.innerHTML = ''; // REMOVED

    Array.from(files).forEach((file, index) => {
        if (!file.type.match('image.*')) return;

        const reader = new FileReader();

        reader.onload = function (event) {
            const col = document.createElement('div');
            col.className = 'col-4';
            col.classList.add('new-gallery-image'); // Mark as new
            col.innerHTML = `
                <div class="position-relative">
                    <img src="${event.target.result}" class="img-thumbnail" style="width: 100%; height: 100px; object-fit: cover;">
                    <span class="badge bg-success position-absolute top-0 start-0 m-1">ใหม่ ${index + 1}</span>
                    <span class="badge bg-info position-absolute top-0 end-0 m-1" style="font-size: 10px;">รอบันทึก</span>
                </div>
            `;
            galleryPreview.appendChild(col);
        };

        reader.readAsDataURL(file);
    });
}

/**
 * Display existing gallery images (for edit mode)
 */
function displayGalleryImages(images) {
    const galleryPreview = document.getElementById('galleryPreview');

    if (!galleryPreview) return;

    galleryPreview.innerHTML = '';

    images.forEach((image, index) => {
        const col = document.createElement('div');
        col.className = 'col-4';
        col.id = `gallery-item-${image.image_id}`;
        col.innerHTML = `
            <div class="position-relative">
                <img src="../../uploads/products/gallery/${image.image_filename}" 
                     class="img-thumbnail" 
                     style="width: 100%; height: 100px; object-fit: cover;">
                <span class="badge bg-primary position-absolute top-0 start-0 m-1">${index + 1}</span>
                <button type="button" 
                        class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                        onclick="deleteGalleryImage(${image.image_id})"
                        title="ลบรูปนี้">
                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                </button>
            </div>
        `;
        galleryPreview.appendChild(col);
    });

    // Reinitialize icons
    if (window.lucide) lucide.createIcons();
}

/**
 * Delete gallery image via AJAX
 */
async function deleteGalleryImage(imageId) {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'ยืนยันการลบ',
        text: 'ต้องการลบรูปนี้ใช่หรือไม่?',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#dc3545'
    });

    if (!result.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('image_id', imageId);

        const response = await fetch('../api/delete_product_image.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Remove from DOM
            const item = document.getElementById(`gallery-item-${imageId}`);
            if (item) {
                item.remove();
            }

            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: 'ลบรูปเรียบร้อยแล้ว',
                timer: 1500,
                showConfirmButton: false
            });

            // Refresh gallery display (re-number badges)
            const productId = document.getElementById('productId').value;
            if (productId) {
                refreshGalleryDisplay(productId);
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถลบรูปได้'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถลบรูปได้'
        });
    }
}

/**
 * Refresh gallery display after delete
 */
async function refreshGalleryDisplay(productId) {
    try {
        const response = await fetch(`../api/get_product.php?id=${productId}`);
        const data = await response.json();

        if (data.success && data.gallery_images) {
            displayGalleryImages(data.gallery_images);
        }
    } catch (error) {
        console.error('Error refreshing gallery:', error);
    }
}

