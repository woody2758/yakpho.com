/**
 * Blog Image Cropping Functions
 * Handles cover image and gallery cropping for blog posts
 */

let blogCropper = null; // Cropper.js instance for blog

/**
 * Handle blog cover image selection - initialize cropper
 */
function handleBlogCoverSelect(e) {
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
        const imageToCrop = document.getElementById('blogImageToCrop');
        const cropperContainer = document.getElementById('blogCropperContainer');
        const croppedPreview = document.getElementById('blogCroppedPreview');

        // Hide preview if exists
        croppedPreview.style.display = 'none';

        // Show cropper
        imageToCrop.src = event.target.result;
        cropperContainer.style.display = 'block';

        // Destroy existing cropper if any
        if (blogCropper) {
            blogCropper.destroy();
        }

        // Initialize Cropper.js
        blogCropper = new Cropper(imageToCrop, {
            aspectRatio: 16 / 9, // Blog cover aspect ratio
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
 * Crop and save blog cover as Base64, then auto-upload
 */
async function cropBlogCover() {
    if (!blogCropper) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่พบ Cropper'
        });
        return;
    }

    // Get cropped canvas
    const canvas = blogCropper.getCroppedCanvas({
        width: 1200,
        height: 675, // 16:9 ratio
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });

    // Convert to Base64
    const base64Image = canvas.toDataURL('image/png');

    // Store in hidden input
    document.getElementById('blogCoverBase64').value = base64Image;

    // Show preview immediately
    const croppedImage = document.getElementById('blogCroppedImage');
    const croppedPreview = document.getElementById('blogCroppedPreview');
    const cropperContainer = document.getElementById('blogCropperContainer');

    croppedImage.src = base64Image;
    croppedPreview.style.display = 'block';
    cropperContainer.style.display = 'none';

    // Destroy cropper
    blogCropper.destroy();
    blogCropper = null;

    // Clear file input
    document.getElementById('coverImageInput').value = '';

    // Reinitialize icons
    if (window.lucide) lucide.createIcons();

    // Auto-upload to server
    await uploadBase64Cover(base64Image);
}

/**
 * Use original blog cover without cropping (auto-resize to 1200x675), then auto-upload
 */
async function useOriginalBlogCover() {
    if (!blogCropper) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่พบรูปภาพ'
        });
        return;
    }

    // Get the original image from cropper
    const imageElement = blogCropper.image;

    // Create canvas for resizing
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    // Set canvas size to 1200x675 (16:9)
    const targetWidth = 1200;
    const targetHeight = 675;
    canvas.width = targetWidth;
    canvas.height = targetHeight;

    // Calculate dimensions to maintain aspect ratio (cover mode)
    const img = new Image();
    img.onload = async function () {
        const scale = Math.max(targetWidth / img.width, targetHeight / img.height);
        const scaledWidth = img.width * scale;
        const scaledHeight = img.height * scale;

        // Center the image
        const x = (targetWidth - scaledWidth) / 2;
        const y = (targetHeight - scaledHeight) / 2;

        // Draw image on canvas
        ctx.drawImage(img, x, y, scaledWidth, scaledHeight);

        // Convert to Base64
        const base64Image = canvas.toDataURL('image/png');

        // Store in hidden input
        document.getElementById('blogCoverBase64').value = base64Image;

        // Show preview immediately
        const croppedImage = document.getElementById('blogCroppedImage');
        const croppedPreview = document.getElementById('blogCroppedPreview');
        const cropperContainer = document.getElementById('blogCropperContainer');

        croppedImage.src = base64Image;
        croppedPreview.style.display = 'block';
        cropperContainer.style.display = 'none';

        // Destroy cropper
        blogCropper.destroy();
        blogCropper = null;

        // Clear file input
        document.getElementById('coverImageInput').value = '';

        // Reinitialize icons
        if (window.lucide) lucide.createIcons();

        // Auto-upload to server
        await uploadBase64Cover(base64Image);
    };

    img.src = imageElement.src;
}

/**
 * Cancel blog cover cropping
 */
function cancelBlogCrop() {
    const cropperContainer = document.getElementById('blogCropperContainer');
    cropperContainer.style.display = 'none';

    if (blogCropper) {
        blogCropper.destroy();
        blogCropper = null;
    }

    // Clear file input
    document.getElementById('coverImageInput').value = '';
}

/**
 * Remove cropped blog cover image
 */
function removeBlogCroppedImage() {
    document.getElementById('blogCoverBase64').value = '';
    document.getElementById('blogCroppedPreview').style.display = 'none';
    document.getElementById('coverImageInput').value = '';

    // Also clear the blog picture field
    document.getElementById('blogPicture').value = '';

    // Hide the old cover preview if exists
    const coverImagePreview = document.getElementById('coverImagePreview');
    if (coverImagePreview) {
        coverImagePreview.style.display = 'none';
    }
}

/**
 * Upload Base64 cover image to server (called from cropping functions)
 */
async function uploadBase64Cover(base64Data) {
    Swal.fire({
        title: 'กำลังอัพโหลด...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        // Get currentBlogId from parent scope (blog.js)
        const blogId = typeof currentBlogId !== 'undefined' ? currentBlogId : 0;

        const response = await fetch('../api/upload_blog_cover.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                base64_image: base64Data,
                blog_id: blogId
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'อัพโหลดเรียบร้อย',
            timer: 1500,
            showConfirmButton: false
        });

        // Update the blog picture field
        document.getElementById('blogPicture').value = result.image_url;

        // Hide cropped preview, show existing cover preview with uploaded image
        document.getElementById('blogCroppedPreview').style.display = 'none';
        document.getElementById('coverImage').src = result.image_url;
        document.getElementById('coverImagePreview').style.display = 'block';

        // Clear the Base64 field
        document.getElementById('blogCoverBase64').value = '';

    } catch (error) {
        console.error('Error uploading image:', error);
        Swal.fire('Error', error.message, 'error');
    }
}
