/**
 * Blog Posts Management JavaScript
 * Handles CRUD operations for blog posts with 8 languages
 */

let blogModal;
let currentPage = 1;
let currentSearch = '';
let currentCategory = 0;
let currentBlogId = 0;
let currentView = 'all'; // 'all' or 'trash'

document.addEventListener('DOMContentLoaded', function () {
    blogModal = new bootstrap.Modal(document.getElementById('blogModal'));
    loadBlogTable(1);
    loadCategories();

    // Search with debounce
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function () {
        const value = this.value.trim();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = value;
            loadBlogTable(1);
        }, 300);
    });

    // Category filter
    document.getElementById('categoryFilter').addEventListener('change', function () {
        currentCategory = parseInt(this.value) || 0;
        loadBlogTable(1);
    });
});

/**
 * Load categories for filter and dropdown
 */
async function loadCategories() {
    try {
        const response = await fetch('../api/get_blog_categories.php');
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        const categories = result.data;

        // Populate filter dropdown
        const filterSelect = document.getElementById('categoryFilter');
        categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.blogcat_id;
            option.textContent = cat.blogcat_name;
            filterSelect.appendChild(option);
        });

        // Populate modal dropdown
        const modalSelect = document.getElementById('blogCategory');
        categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.blogcat_id;
            option.textContent = cat.blogcat_name;
            modalSelect.appendChild(option);
        });

    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

/**
 * Load blog table
 */
async function loadBlogTable(page = 1) {
    currentPage = page;

    try {
        const url = `../api/get_blog_table.php?page=${page}&search=${encodeURIComponent(currentSearch)}&category=${currentCategory}&view=${currentView}`;
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        renderTable(result.data);
        renderPagination(result.pagination);

        // Update counts
        if (result.counts) {
            document.getElementById('count-all').textContent = result.counts.all;
            document.getElementById('count-trash').textContent = result.counts.trash;
        }

    } catch (error) {
        console.error('Error loading blogs:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Render blog table
 */
function renderTable(blogs) {
    const container = document.getElementById('tableContainer');

    if (blogs.length === 0) {
        const emptyMessage = currentView === 'trash'
            ? 'ถังขยะว่างเปล่า'
            : 'ไม่พบข้อมูลบล็อก';

        container.innerHTML = `
            <div class="text-center py-5">
                <i data-lucide="inbox" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                <p class="text-muted">${emptyMessage}</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }

    let html = `
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width: 80px">รูป</th>
                    <th>ชื่อบล็อก</th>
                    <th style="width: 120px">หมวดหมู่</th>
                    <th style="width: 100px" class="text-center">ยอดเข้าชม</th>
                    <th style="width: 100px" class="text-center">สถานะ</th>
                    <th style="width: 210px" class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
    `;

    blogs.forEach((blog) => {
        const statusBadge = blog.blog_status == 1
            ? '<span class="badge bg-success">เปิดใช้งาน</span>'
            : '<span class="badge bg-secondary">ปิดใช้งาน</span>';

        const thumbnail = blog.blog_picture
            ? `<img src="${blog.blog_picture}" class="img-thumbnail" style="width:60px;height:60px;object-fit:cover;">`
            : '<div class="bg-light d-flex align-items-center justify-content-center" style="width:60px;height:60px;"><i data-lucide="image" class="text-muted"></i></div>';

        // Combine TH and EN titles in same column
        const titleHtml = `
            <div>
                <div class="fw-medium">${blog.name_th || '-'}</div>
                <div class="small text-muted">${blog.name_en || '-'}</div>
                ${blog.gallery_count > 0 ? `<span class="badge bg-info mt-1" style="font-size:10px;"><i data-lucide="images" style="width:12px;height:12px;"></i> ${blog.gallery_count}</span>` : ''}
            </div>
        `;

        // Different actions based on view
        let actionsHtml = '';
        if (currentView === 'trash') {
            // Trash view: Restore | Delete Permanently
            actionsHtml = `
                <button onclick="restoreBlog(${blog.blog_id})" class="btn btn-sm btn-success me-1" title="Restore">
                    <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i>
                </button>
                <button onclick="deleteBlogPermanently(${blog.blog_id})" class="btn btn-sm btn-danger" title="Delete Forever">
                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                </button>
            `;
        } else {
            // All view: Edit | Gallery | Delete
            actionsHtml = `
                <button onclick="editBlog(${blog.blog_id})" class="btn btn-sm btn-primary me-1" title="Edit">
                    <i data-lucide="edit" style="width:14px;height:14px;"></i>
                </button>
                <button onclick="quickEditGallery(${blog.blog_id}, '${(blog.name_th || 'Blog').replace(/'/g, "\\'")}')" class="btn btn-sm btn-info me-1" title="Manage Gallery">
                    <i data-lucide="images" style="width:14px;height:14px;"></i>
                </button>
                <button onclick="deleteBlog(${blog.blog_id})" class="btn btn-sm btn-danger" title="Delete">
                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                </button>
            `;
        }

        html += `
            <tr>
                <td>${thumbnail}</td>
                <td>${titleHtml}</td>
                <td>${blog.category_name || '-'}</td>
                <td class="text-center">${blog.blog_view || 0}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">${actionsHtml}</td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
    `;

    container.innerHTML = html;
    lucide.createIcons();
}

/**
 * Render pagination
 */
function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');

    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '<nav><ul class="pagination pagination-sm mb-0">';

    // Previous button
    html += `
        <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadBlogTable(${pagination.current_page - 1}); return false;">
                Previous
            </a>
        </li>
    `;

    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || Math.abs(i - pagination.current_page) <= 2) {
            html += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadBlogTable(${i}); return false;">${i}</a>
                </li>
            `;
        } else if (Math.abs(i - pagination.current_page) === 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // Next button
    html += `
        <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadBlogTable(${pagination.current_page + 1}); return false;">
                Next
            </a>
        </li>
    `;

    html += '</ul></nav>';
    container.innerHTML = html;
}

/**
 * Clear all image states (cropper, previews, base64)
 */
function clearImageStates() {
    // Clear cropper if exists
    if (typeof blogCropper !== 'undefined' && blogCropper) {
        blogCropper.destroy();
        blogCropper = null;
    }

    // Hide cropper container
    const cropperContainer = document.getElementById('blogCropperContainer');
    if (cropperContainer) {
        cropperContainer.style.display = 'none';
    }

    // Hide and clear cropped preview
    const croppedPreview = document.getElementById('blogCroppedPreview');
    if (croppedPreview) {
        croppedPreview.style.display = 'none';
    }

    const croppedImage = document.getElementById('blogCroppedImage');
    if (croppedImage) {
        croppedImage.src = '';
    }

    // Clear base64 data
    const base64Input = document.getElementById('blogCoverBase64');
    if (base64Input) {
        base64Input.value = '';
    }

    // Clear file input
    const fileInput = document.getElementById('coverImageInput');
    if (fileInput) {
        fileInput.value = '';
    }
}

/**
 * Add new blog
 */
function addBlog() {
    // Clear all image states first
    clearImageStates();
    document.getElementById('modalTitle').textContent = 'เพิ่มบล็อก';
    document.getElementById('blogForm').reset();
    document.getElementById('blogId').value = '';
    document.getElementById('blogStatus').checked = true;
    document.getElementById('coverImagePreview').style.display = 'none';
    document.getElementById('galleryPreview').innerHTML = '';
    currentBlogId = 0;

    // Clear all language fields
    const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    languages.forEach(lang => {
        document.querySelector(`[name="blog_name_${lang}"]`).value = '';
        document.querySelector(`[name="blog_excerpt_${lang}"]`).value = '';
        document.querySelector(`[name="blog_tag_${lang}"]`).value = '';

        // Clear Summernote
        const detailField = document.querySelector(`[name="blog_detail_${lang}"]`);
        if ($(detailField).summernote) {
            $(detailField).summernote('code', '');
        }
    });

    // Set current date
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('blogDate').value = now.toISOString().slice(0, 16);

    // Switch to Thai tab
    const firstTab = document.querySelector('[data-bs-target="#lang-th"]');
    if (firstTab) {
        new bootstrap.Tab(firstTab).show();
    }

    blogModal.show();
}

/**
 * Edit blog
 */
async function editBlog(id) {
    currentBlogId = id;

    // Clear all image states first
    clearImageStates();

    // Show loading
    Swal.fire({
        title: 'Loading...',
        html: 'กำลังโหลดข้อมูล',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`../api/get_blog.php?id=${id}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        const data = result.data;

        // Close loading
        Swal.close();

        document.getElementById('modalTitle').textContent = 'แก้ไขบล็อก';
        document.getElementById('blogId').value = data.blog_id;
        document.getElementById('blogCategory').value = data.blogcat_id;
        document.getElementById('blogUrl').value = data.blog_url || '';
        document.getElementById('blogPicture').value = data.blog_picture || '';
        document.getElementById('blogStatus').checked = data.blog_status == 1;

        // Set date
        if (data.blog_date) {
            const date = new Date(data.blog_date);
            date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
            document.getElementById('blogDate').value = date.toISOString().slice(0, 16);
        }

        // Show cover image
        if (data.blog_picture) {
            let imagePath = data.blog_picture;

            // Add full URL if relative path
            if (!imagePath.startsWith('http')) {
                // Remove leading slash if exists
                imagePath = imagePath.replace(/^\//, '');
                // Construct full URL (assuming ROOT_URL or current domain)
                const protocol = window.location.protocol;
                const host = window.location.host;
                const pathParts = window.location.pathname.split('/');
                const basePath = pathParts.slice(0, pathParts.indexOf('admin')).join('/');
                imagePath = `${protocol}//${host}${basePath}/${imagePath}`;
            }

            document.getElementById('coverImage').src = imagePath;
            document.getElementById('coverImagePreview').style.display = 'block';
        } else {
            document.getElementById('coverImagePreview').style.display = 'none';
        }

        // Fill translations
        const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
        languages.forEach(lang => {
            const trans = data.translations[lang] || {};
            document.querySelector(`[name="blog_name_${lang}"]`).value = trans.blog_name || '';
            document.querySelector(`[name="blog_excerpt_${lang}"]`).value = trans.blog_excerpt || '';
            document.querySelector(`[name="blog_tag_${lang}"]`).value = trans.blog_tag || '';

            // Set Summernote content
            const detailField = document.querySelector(`[name="blog_detail_${lang}"]`);
            if ($(detailField).summernote) {
                $(detailField).summernote('code', trans.blog_detail || '');
            }
        });

        // Load gallery images
        loadGallery(data.gallery || []);

        blogModal.show();

    } catch (error) {
        console.error('Error loading blog:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Load gallery images (use new auto-upload system)
 */
function loadGallery(images) {
    // Use new auto-upload gallery function
    if (typeof loadGalleryAuto === 'function') {
        loadGalleryAuto(images);
    } else {
        // Fallback to old method
        const container = document.getElementById('galleryPreview');
        container.innerHTML = '';

        images.forEach(img => {
            const div = document.createElement('div');
            div.className = 'position-relative';
            div.innerHTML = `
                <img src="${img.gallery_image}" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">
                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" 
                        onclick="deleteGalleryImage(${img.id})" style="padding:2px 6px;">
                    <i data-lucide="x" style="width:14px;height:14px;"></i>
                </button>
            `;
            container.appendChild(div);
        });
        lucide.createIcons();
    }
}

/**
 * Save blog
 */
async function saveBlog() {
    // Check if cropper modal is still open - auto-crop if yes
    const cropperContainer = document.getElementById('blogCropperContainer');
    if (cropperContainer && cropperContainer.style.display !== 'none') {
        // User selected image but didn't confirm - auto-crop with current selection
        console.log('Auto-cropping image before save...');

        try {
            // Call crop function and wait for upload to complete
            await cropBlogCover();

            // Brief delay to ensure UI updates
            await new Promise(resolve => setTimeout(resolve, 500));
        } catch (error) {
            console.error('Auto-crop error:', error);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาดในการครอปรูป',
                text: 'กรุณาครอปรูปภาพอีกครั้ง',
                confirmButtonText: 'รับทราบ'
            });
            return;
        }
    }

    const blog_id = document.getElementById('blogId').value;
    const blogcat_id = document.getElementById('blogCategory').value;
    const blog_url = document.getElementById('blogUrl').value.trim();
    const blog_picture = document.getElementById('blogPicture').value.trim();
    const blog_status = document.getElementById('blogStatus').checked ? 1 : 0;
    const blog_date = document.getElementById('blogDate').value;

    if (!blogcat_id) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกหมวดหมู่', 'warning');
        return;
    }

    // Collect translations
    const languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    const translations = {};

    languages.forEach(lang => {
        const detailField = document.querySelector(`[name="blog_detail_${lang}"]`);
        const detail = $(detailField).summernote ? $(detailField).summernote('code') : detailField.value;

        translations[lang] = {
            blog_name: document.querySelector(`[name="blog_name_${lang}"]`).value.trim(),
            blog_excerpt: document.querySelector(`[name="blog_excerpt_${lang}"]`).value.trim(),
            blog_detail: detail,
            blog_tag: document.querySelector(`[name="blog_tag_${lang}"]`).value.trim()
        };
    });

    // Validate: Thai name is required
    if (!translations.th.blog_name) {
        Swal.fire('แจ้งเตือน', 'กรุณากรอกชื่อบล็อกภาษาไทย', 'warning');
        return;
    }

    // Show loading
    Swal.fire({
        title: 'กำลังบันทึก...',
        html: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('../api/save_blog.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                blog_id: blog_id ? parseInt(blog_id) : 0,
                blogcat_id: parseInt(blogcat_id),
                blog_url,
                blog_picture,
                blog_status,
                blog_date,
                translations
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'บันทึกเรียบร้อย',
            timer: 1500,
            showConfirmButton: false
        });

        // Wait for modal to close before reloading
        const modalElement = document.getElementById('blogModal');
        modalElement.addEventListener('hidden.bs.modal', function () {
            // Destroy Summernote before hiding
            $('.summernote').summernote('destroy');
            loadBlogTable(currentPage);
        }, { once: true });

        blogModal.hide();

    } catch (error) {
        console.error('Error saving blog:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Delete blog
 */
async function deleteBlog(id) {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบบล็อกนี้หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ย้อนกลับ'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('../api/delete_blog.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ blog_id: id })
        });

        const apiResult = await response.json();

        if (!apiResult.success) {
            throw new Error(apiResult.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'ลบเรียบร้อย',
            timer: 1500,
            showConfirmButton: false
        });

        loadBlogTable(currentPage);

    } catch (error) {
        console.error('Error deleting blog:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Upload cover image
 * Now handles both Base64 (from cropper) and direct file upload
 */
async function uploadCoverImage() {
    // Check if we have a cropped image (Base64)
    const base64Data = document.getElementById('blogCoverBase64').value;

    if (base64Data) {
        // Upload cropped Base64 image
        await uploadBase64Cover(base64Data);
        return;
    }

    // Fallback: direct file upload (if cropper was bypassed)
    const fileInput = document.getElementById('coverImageInput');
    const file = fileInput.files[0];

    if (!file) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกรูปภาพ', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('image', file);
    formData.append('blog_id', currentBlogId || 0);

    Swal.fire({
        title: 'กำลังอัพโหลด...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('../api/upload_blog_cover.php', {
            method: 'POST',
            body: formData
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

        document.getElementById('blogPicture').value = result.image_url;
        document.getElementById('coverImage').src = result.image_url;
        document.getElementById('coverImagePreview').style.display = 'block';

    } catch (error) {
        console.error('Error uploading image:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Upload Base64 cover image (from cropper)
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
        const response = await fetch('../api/upload_blog_cover.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                base64_image: base64Data,
                blog_id: currentBlogId || 0
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

        // Update the blog picture field with full path
        document.getElementById('blogPicture').value = result.image_url;

        // Show cropped preview with the uploaded image
        document.getElementById('blogCroppedImage').src = result.full_path || result.image_url;
        document.getElementById('blogCroppedPreview').style.display = 'block';

        // Hide cover image preview (for edit mode)
        document.getElementById('coverImagePreview').style.display = 'none';

        // DO NOT clear Base64 - keep it for form submission
        // document.getElementById('blogCoverBase64').value = '';

    } catch (error) {
        console.error('Error uploading image:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Upload gallery images
 */
async function uploadGallery() {
    const fileInput = document.getElementById('galleryInput');
    const files = fileInput.files;

    if (files.length === 0) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกรูปภาพ', 'warning');
        return;
    }

    if (!currentBlogId) {
        Swal.fire('แจ้งเตือน', 'กรุณาบันทึกบล็อกก่อนอัพโหลดแกลเลอรี่', 'warning');
        return;
    }

    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('images[]', files[i]);
    }
    formData.append('blog_id', currentBlogId);

    Swal.fire({
        title: 'กำลังอัพโหลด...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('../api/upload_blog_gallery.php', {
            method: 'POST',
            body: formData
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

        // Reload gallery
        loadGallery(result.gallery);
        fileInput.value = '';

    } catch (error) {
        console.error('Error uploading gallery:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Delete gallery image
 */
async function deleteGalleryImage(imageId) {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบรูปภาพนี้หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ย้อนกลับ'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('../api/delete_blog_gallery.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: imageId })
        });

        const apiResult = await response.json();

        if (!apiResult.success) {
            throw new Error(apiResult.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'ลบเรียบร้อย',
            timer: 1500,
            showConfirmButton: false
        });

        // Reload gallery
        const blogResponse = await fetch(`../api/get_blog.php?id=${currentBlogId}`);
        const blogResult = await blogResponse.json();
        if (blogResult.success) {
            loadGallery(blogResult.data.gallery || []);
        }

    } catch (error) {
        console.error('Error deleting gallery image:', error);
        Swal.fire('Error', error.message, 'error');
    }
}
