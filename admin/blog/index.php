<?php
$page_title = "จัดการบล็อก";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="file-text" class="me-2"></i>จัดการบล็อก</h2>
        <button onclick="addBlog()" class="btn btn-success">
            <i data-lucide="plus" style="width:16px; height:16px; margin-right:6px;"></i> เพิ่มบล็อก
        </button>
    </div>

    <!-- Search & Filter Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="position-relative">
                        <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาชื่อบล็อก...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="categoryFilter" class="form-select">
                        <option value="">ทุกหมวดหมู่</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- View Tabs -->
    <div class="card shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center">
                <ul class="nav nav-tabs border-0 mb-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-all" onclick="switchView('all')" type="button">
                            All Blogs <span class="badge bg-primary ms-1" id="count-all">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-trash" onclick="switchView('trash')" type="button">
                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            Trash <span class="badge bg-danger ms-1" id="count-trash">0</span>
                        </button>
                    </li>
                </ul>
                
                <!-- Empty Trash Button (shown only in trash view) -->
                <button id="emptyTrashBtn" class="btn btn-danger btn-sm" onclick="emptyTrash()" style="display:none;">
                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i> Empty Trash
                </button>
            </div>
        </div>
    </div>

    <!-- Blog Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div id="tableContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="card-footer bg-white py-3" id="paginationContainer">
            <!-- Pagination will be loaded here -->
        </div>
    </div>
</div>

<!-- Add/Edit Blog Modal -->
<div class="modal fade" id="blogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มบล็อก</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="blogForm">
                    <input type="hidden" id="blogId" name="blog_id">
                    
                    <div class="row mb-3">
                        <!-- Category -->
                        <div class="col-md-4">
                            <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select id="blogCategory" name="blogcat_id" class="form-select" required>
                                <option value="">เลือกหมวดหมู่</option>
                            </select>
                        </div>
                        
                        <!-- URL Slug -->
                        <div class="col-md-4">
                            <label class="form-label">URL Slug</label>
                            <input type="text" id="blogUrl" name="blog_url" class="form-control" placeholder="auto-generated">
                        </div>
                        
                        <!-- Date -->
                        <div class="col-md-4">
                            <label class="form-label">วันที่</label>
                            <input type="datetime-local" id="blogDate" name="blog_date" class="form-control" required>
                        </div>
                    </div>

                    <!-- Cover Image with Cropper -->
                    <div class="mb-3">
                        <label class="form-label">รูปภาพปก (จะถูก Crop เป็น 16:9)</label>
                        <input type="file" id="coverImageInput" class="form-control mb-2" accept="image/*">
                        <input type="hidden" id="blogCoverBase64" name="blog_cover_base64">
                        <input type="hidden" id="blogPicture" name="blog_picture">
                        <small class="text-muted">รองรับ: JPG, PNG, GIF (จะแปลงเป็น WebP อัตโนมัติ)</small>
                    </div>
                    
                    <!-- Image Preview & Cropper -->
                    <div id="blogCropperContainer" style="display: none;" class="mb-3">
                        <div class="mb-2">
                            <img id="blogImageToCrop" style="max-width: 100%; display: block;">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-success" onclick="cropBlogCover()">
                                <i data-lucide="check"></i> ยืนยันการ Crop
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="useOriginalBlogCover()">
                                <i data-lucide="image"></i> ใช้รูปนี้เลย
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="cancelBlogCrop()">
                                <i data-lucide="x"></i> ยกเลิก
                            </button>
                        </div>
                    </div>
                    
                    <!-- Cropped Preview -->
                    <div id="blogCroppedPreview" style="display: none;" class="mb-3">
                        <label class="form-label">ตัวอย่างรูปที่ Crop แล้ว:</label>
                        <div class="position-relative" style="max-width: 400px;">
                            <img id="blogCroppedImage" class="img-thumbnail" style="width: 100%;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="removeBlogCroppedImage()">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Existing Cover Preview (for edit mode) -->
                    <div id="coverImagePreview" class="mb-3" style="display: none;">
                        <label class="form-label">รูปปกปัจจุบัน:</label>
                        <div class="position-relative" style="max-width: 400px;">
                            <img id="coverImage" class="img-thumbnail" style="width: 100%;">
                        </div>
                    </div>

                    <!-- Status Toggle -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="blogStatus" name="blog_status" checked>
                            <label class="form-check-label" for="blogStatus">
                                <span class="badge bg-success" id="statusBadge">เปิดใช้งาน</span>
                            </label>
                        </div>
                    </div>

                    <div class="language-dropdown-container mb-3" data-content-selector="#blogLangContent"></div>
                    <div id="blogLangContent">
                        <!-- Thai -->
                        <div data-lang="th">
                            <div class="mb-3">
                                <label class="form-label">ชื่อบล็อก <span class="text-danger">*</span></label>
                                <input type="text" name="blog_name_th" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">สรุปย่อ</label>
                                <textarea name="blog_excerpt_th" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">เนื้อหา</label>
                                <textarea name="blog_detail_th" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">แท็ก (คั่นด้วยเครื่องหมายจุลภาค)</label>
                                <input type="text" name="blog_tag_th" class="form-control" placeholder="แท็ก1, แท็ก2, แท็ก3">
                            </div>
                        </div>

                        <!-- English -->
                        <div class="tab-pane fade" id="lang-en">
                            <div class="mb-3">
                                <label class="form-label">Blog Title (English)</label>
                                <input type="text" name="blog_name_en" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Excerpt (English)</label>
                                <textarea name="blog_excerpt_en" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Content (English)</label>
                                <textarea name="blog_detail_en" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tags (English)</label>
                                <input type="text" name="blog_tag_en" class="form-control" placeholder="tag1, tag2, tag3">
                            </div>
                        </div>

                        <!-- German -->
                        <div data-lang="de">
                            <div class="mb-3">
                                <label class="form-label">Blog-Titel (German)</label>
                                <input type="text" name="blog_name_de" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Auszug (German)</label>
                                <textarea name="blog_excerpt_de" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Inhalt (German)</label>
                                <textarea name="blog_detail_de" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Stichworte (German)</label>
                                <input type="text" name="blog_tag_de" class="form-control">
                            </div>
                        </div>

                        <!-- French -->
                        <div data-lang="fr">
                            <div class="mb-3">
                                <label class="form-label">Titre du blog (French)</label>
                                <input type="text" name="blog_name_fr" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Extrait (French)</label>
                                <textarea name="blog_excerpt_fr" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contenu (French)</label>
                                <textarea name="blog_detail_fr" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mots clés (French)</label>
                                <input type="text" name="blog_tag_fr" class="form-control">
                            </div>
                        </div>

                        <!-- Chinese -->
                        <div data-lang="zh">
                            <div class="mb-3">
                                <label class="form-label">博客标题 (Chinese)</label>
                                <input type="text" name="blog_name_zh" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">摘要</label>
                                <textarea name="blog_excerpt_zh" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">内容 (Chinese)</label>
                                <textarea name="blog_detail_zh" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">标签 (Chinese)</label>
                                <input type="text" name="blog_tag_zh" class="form-control">
                            </div>
                        </div>

                        <!-- Korean -->
                        <div data-lang="ko">
                            <div class="mb-3">
                                <label class="form-label">블로그 제목 (Korean)</label>
                                <input type="text" name="blog_name_ko" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">발췌 (Korean)</label>
                                <textarea name="blog_excerpt_ko" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">내용 (Korean)</label>
                                <textarea name="blog_detail_ko" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">태그 (Korean)</label>
                                <input type="text" name="blog_tag_ko" class="form-control">
                            </div>
                        </div>

                        <!-- Japanese -->
                        <div data-lang="ja">
                            <div class="mb-3">
                                <label class="form-label">ブログタイトル (Japanese)</label>
                                <input type="text" name="blog_name_ja" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">抜粋 (Japanese)</label>
                                <textarea name="blog_excerpt_ja" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">内容</label>
                                <textarea name="blog_detail_ja" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">タグ (Japanese)</label>
                                <input type="text" name="blog_tag_ja" class="form-control">
                            </div>
                        </div>

                        <!-- Russian -->
                        <div data-lang="ru">
                            <div class="mb-3">
                                <label class="form-label">Название блога</label>
                                <input type="text" name="blog_name_ru" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Отрывок</label>
                                <textarea name="blog_excerpt_ru" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Содержание (Russian)</label>
                                <textarea name="blog_detail_ru" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Теги (Russian)</label>
                                <input type="text" name="blog_tag_ru" class="form-control">
                            </div>
                        </div>

                        <!-- Arabic -->
                        <div data-lang="ar">
                            <div class="mb-3">
                                <label class="form-label">عنوان المدونة (Blog Title - Arabic)</label>
                                <input type="text" name="blog_name_ar" class="form-control" dir="rtl">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">مقتطف (Excerpt - Arabic)</label>
                                <textarea name="blog_excerpt_ar" class="form-control" rows="2" dir="rtl"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">المحتوى (Content - Arabic)</label>
                                <textarea name="blog_detail_ar" class="form-control summernote" dir="rtl"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">العلامات (Tags - Arabic)</label>
                                <input type="text" name="blog_tag_ar" class="form-control" dir="rtl">
                            </div>
                        </div>

                        <!-- Hebrew -->
                        <div data-lang="he">
                            <div class="mb-3">
                                <label class="form-label">כותרת הבלוג (Blog Title - Hebrew)</label>
                                <input type="text" name="blog_name_he" class="form-control" dir="rtl">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">תקציר (Excerpt - Hebrew)</label>
                                <textarea name="blog_excerpt_he" class="form-control" rows="2" dir="rtl"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">תוכן (Content - Hebrew)</label>
                                <textarea name="blog_detail_he" class="form-control summernote" dir="rtl"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">תגיות (Tags - Hebrew)</label>
                                <input type="text" name="blog_tag_he" class="form-control" dir="rtl">
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Section -->
                    <div class="mt-4">
                        <h6>แกลเลอรี่</h6>
                        <div class="mb-3">
                            <label class="form-label">
                                <i data-lucide="images"></i> รูปภาพแกลเลอรี่ 
                                <span class="text-muted">(เลือกหลายรูปได้ อัพโหลดอัตโนมัติ)</span>
                            </label>
                            <input type="file" id="galleryInput" class="form-control" accept="image/*" multiple>
                            <small class="text-muted">รองรับ: JPG, PNG, GIF - อัพโหลดและสามารถลาก-วางเรียงลำดับได้</small>
                        </div>
                        <div id="galleryPreview" class="d-flex flex-wrap gap-2">
                            <!-- Gallery images will appear here -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" onclick="saveBlog()" class="btn btn-primary">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

<script>
// Status toggle handler
document.getElementById('blogStatus').addEventListener('change', function() {
    const badge = document.getElementById('statusBadge');
    if (this.checked) {
        badge.textContent = 'เปิดใช้งาน';
        badge.className = 'badge bg-success';
    } else {
        badge.textContent = 'ปิดใช้งาน';
        badge.className = 'badge bg-secondary';
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Set default date to now
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('blogDate').value = now.toISOString().slice(0, 16);
    
    // Initialize Summernote when modal is shown
    document.getElementById('blogModal').addEventListener('shown.bs.modal', function () {
        $('.summernote').summernote({
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });
    
    // Cover image handler - trigger cropper (must be after blog-image-functions.js loads)
    document.getElementById('coverImageInput').addEventListener('change', handleBlogCoverSelect);
});
</script>

<!-- Summernote JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<!-- Blog Image Functions -->
<script src="<?= ADMIN_ASSETS ?>/js/blog-image-functions.js<?= $ver ?>"></script>

<!-- Blog Gallery Auto-Upload -->
<script src="<?= ADMIN_ASSETS ?>/js/blog-gallery-auto.js<?= $ver ?>"></script>

<!-- Blog Gallery Quick Edit -->
<script src="<?= ADMIN_ASSETS ?>/js/blog-gallery-quick.js<?= $ver ?>"></script>

<!-- Blog Trash Management -->
<script src="<?= ADMIN_ASSETS ?>/js/blog-trash.js<?= $ver ?>"></script>

<!-- Blog JS -->
<script src="<?= ADMIN_ASSETS ?>/js/blog.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
