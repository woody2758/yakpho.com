/**
 * Hero Slider Image Cropping Functions
 * Handles hero slide image cropping with 16:9 aspect ratio
 */

let heroCropper = null; // Cropper.js instance for hero slider

/**
 * Handle hero image selection - initialize cropper
 */
function handleHeroImageSelect(e) {
    const file = e.target.files[0];

    if (!file) return;

    // Validate file type
    if (!file.type.match('image.*')) {
        Swal.fire({
            icon: 'error',
            title: 'ไฟล์ไม่ถูกต้อง',
            text: 'กรุณาเลือกไฟล์รูปภาพเท่านั้น (JPG, PNG, WEBP)'
        });
        e.target.value = '';
        return;
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        Swal.fire({
            icon: 'error',
            title: 'ไฟล์ใหญ่เกินไป',
            text: 'ขนาดไฟล์ต้องไม่เกิน 5MB'
        });
        e.target.value = '';
        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {
        const imageToCrop = document.getElementById('heroImageToCrop');
        const cropperContainer = document.getElementById('heroCropperContainer');
        const previewContainer = document.getElementById('imagePreview').parentElement;

        // Hide existing preview
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('uploadPlaceholder').style.display = 'none';

        // Show cropper
        imageToCrop.src = event.target.result;
        cropperContainer.style.display = 'block';

        // Destroy existing cropper if any
        if (heroCropper) {
            heroCropper.destroy();
        }

        // Initialize Cropper.js with strict 16:9 aspect ratio lock
        heroCropper = new Cropper(imageToCrop, {
            aspectRatio: 16 / 9, // Strict 16:9 ratio - LOCKED
            viewMode: 1, // Crop box must be within the canvas
            dragMode: 'move', // Allow dragging the image to reposition
            autoCropArea: 0.8, // Initial crop area covers 80% of canvas
            responsive: true,
            restore: false,
            guides: true,
            center: true,
            highlight: true,
            cropBoxResizable: false, // LOCK size - user can only move position
            cropBoxMovable: true, // Allow moving crop box
            toggleDragModeOnDblclick: false,
            background: true,
            modal: true,
            scalable: true,
            zoomable: true,
            zoomOnWheel: true,
            wheelZoomRatio: 0.1,
            minContainerWidth: 400,
            minContainerHeight: 225,
            ready: function () {
                console.log('✅ Cropper ready - 16:9 ratio locked');
            }
        });
    };

    reader.readAsDataURL(file);
}

/**
 * Crop and save hero image as Base64
 */
function cropHeroImage() {
    if (!heroCropper) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่พบ Cropper'
        });
        return;
    }

    // Show loading
    Swal.fire({
        title: 'กำลังประมวลผล...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Get cropped canvas with target dimensions
    const canvas = heroCropper.getCroppedCanvas({
        width: 1920,
        height: 1080, // 16:9 ratio
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });

    // Convert to Base64 (use JPEG for smaller file size)
    const base64Image = canvas.toDataURL('image/jpeg', 0.9);

    // Store in hidden input
    document.getElementById('heroImageBase64').value = base64Image;

    // Show preview
    const imagePreview = document.getElementById('imagePreview');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const cropperContainer = document.getElementById('heroCropperContainer');

    imagePreview.src = base64Image;
    imagePreview.style.display = 'block';
    uploadPlaceholder.style.display = 'none';
    cropperContainer.style.display = 'none';

    // Destroy cropper
    heroCropper.destroy();
    heroCropper = null;

    // Clear file input
    document.getElementById('slide_image').value = '';

    Swal.close();

    // Reinitialize icons
    if (window.lucide) lucide.createIcons();
}

/**
 * Use original hero image without cropping (auto-resize to 1920x1080)
 */
function useOriginalHeroImage() {
    if (!heroCropper) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่พบรูปภาพ'
        });
        return;
    }

    // Show loading
    Swal.fire({
        title: 'กำลังประมวลผล...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Get the original image from cropper
    const imageElement = heroCropper.image;

    // Create canvas for resizing
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    // Set canvas size to 1920x1080 (16:9)
    const targetWidth = 1920;
    const targetHeight = 1080;
    canvas.width = targetWidth;
    canvas.height = targetHeight;

    // Load and resize image
    const img = new Image();
    img.onload = function () {
        // Calculate dimensions to maintain aspect ratio (cover mode)
        const scale = Math.max(targetWidth / img.width, targetHeight / img.height);
        const scaledWidth = img.width * scale;
        const scaledHeight = img.height * scale;

        // Center the image
        const x = (targetWidth - scaledWidth) / 2;
        const y = (targetHeight - scaledHeight) / 2;

        // Draw image on canvas
        ctx.drawImage(img, x, y, scaledWidth, scaledHeight);

        // Convert to Base64 (JPEG for smaller size)
        const base64Image = canvas.toDataURL('image/jpeg', 0.9);

        // Store in hidden input
        document.getElementById('heroImageBase64').value = base64Image;

        // Show preview
        const imagePreview = document.getElementById('imagePreview');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        const cropperContainer = document.getElementById('heroCropperContainer');

        imagePreview.src = base64Image;
        imagePreview.style.display = 'block';
        uploadPlaceholder.style.display = 'none';
        cropperContainer.style.display = 'none';

        // Destroy cropper
        heroCropper.destroy();
        heroCropper = null;

        // Clear file input
        document.getElementById('slide_image').value = '';

        Swal.close();

        // Reinitialize icons
        if (window.lucide) lucide.createIcons();
    };

    img.src = imageElement.src;
}

/**
 * Cancel hero image cropping
 */
function cancelHeroCrop() {
    const cropperContainer = document.getElementById('heroCropperContainer');
    cropperContainer.style.display = 'none';

    if (heroCropper) {
        heroCropper.destroy();
        heroCropper = null;
    }

    // Clear file input
    document.getElementById('slide_image').value = '';

    // Show placeholder again
    document.getElementById('uploadPlaceholder').style.display = 'block';
}

/**
 * Remove hero cropped image
 */
function removeHeroCroppedImage() {
    document.getElementById('heroImageBase64').value = '';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('uploadPlaceholder').style.display = 'block';
    document.getElementById('slide_image').value = '';
    document.getElementById('existing_image').value = '';

    // Show remove button when needed
    const removeBtn = document.getElementById('removeImageBtn');
    if (removeBtn) {
        removeBtn.style.display = 'none';
    }
}

/**
 * Initialize upload area click handler
 */
document.addEventListener('DOMContentLoaded', function () {
    // Make upload area clickable
    const uploadArea = document.getElementById('imageUploadArea');
    const fileInput = document.getElementById('slide_image');

    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', function (e) {
            // Don't trigger if clicking on cropper or preview
            if (e.target.closest('#heroCropperContainer') || e.target.closest('#imagePreview')) {
                return;
            }
            fileInput.click();
        });
    }

    console.log('🎨 Hero image cropper initialized');
});
