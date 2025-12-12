/**
 * Blog Posts Management JavaScript
 * Handles CRUD operations for blog posts with 8 languages
 */

let blogModal;
let currentPage = 1;
let currentSearch = '';
let currentCategory = 0;
let currentBlogId = 0;

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
        const url = `../api/get_blog_table.php?page=${page}&search=${encodeURIComponent(currentSearch)}&category=${currentCategory}`;
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        renderTable(result.data);
        renderPagination(result.pagination);

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
        container.innerHTML = `
            <div class="text-center py-5">
                <i data-lucide="inbox" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                <p class="text-muted">ไม่พบข้อมูลบล็อก</p>
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
                    <th>ชื่อบล็อก (TH)</th>
                    <th>Title (EN)</th>
                    <th style="width: 120px">หมวดหมู่</th>
                    <th style="width: 100px" class="text-center">ยอดเข้าชม</th>
                    <th style="width: 100px" class="text-center">สถานะ</th>
                    <th style="width: 150px" class="text-center">จัดการ</th>
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

        html += `
            <tr>
                <td>${thumbnail}</td>
                <td>${blog.name_th || '-'}</td>
                <td>${blog.name_en || '-'}</td>
                <td>${blog.category_name || '-'}</td>
                <td class="text-center">${blog.blog_view || 0}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">
                    <button onclick="editBlog(${blog.blog_id})" class="btn btn-sm btn-primary me-1">
                        <i data-lucide="edit" style="width:14px;height:14px;"></i>
                    </button>
                    <button onclick="deleteBlog(${blog.blog_id})" class="btn btn-sm btn-danger">
                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                    </button>
                </td>
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
 * Add new blog
 */
function addBlog() {
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
            document.getElementById('coverImage').src = data.blog_picture;
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
 * Load gallery images
 */
function loadGallery(images) {
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

/**
 * Save blog
 */
async function saveBlog() {
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
 */
async function uploadCoverImage() {
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
