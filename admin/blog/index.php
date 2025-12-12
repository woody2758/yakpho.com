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
    <div class="modal-dialog modal-xl">
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

                    <!-- Cover Image -->
                    <div class="mb-3">
                        <label class="form-label">รูปภาพปก</label>
                        <div class="d-flex gap-2 align-items-start">
                            <div id="coverImagePreview" class="border rounded p-2" style="width: 200px; height: 150px; display: none;">
                                <img id="coverImage" src="" class="img-fluid" style="max-height: 130px; object-fit: cover;">
                            </div>
                            <div>
                                <input type="file" id="coverImageInput" class="form-control mb-2" accept="image/*">
                                <button type="button" onclick="uploadCoverImage()" class="btn btn-sm btn-primary">อัพโหลด</button>
                                <input type="hidden" id="blogPicture" name="blog_picture">
                            </div>
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

                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#lang-th" type="button">
                                🇹🇭 ไทย
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-en" type="button">
                                🇬🇧 English
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-de" type="button">
                                🇩🇪 Deutsch
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-fr" type="button">
                                🇫🇷 Français
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-zh" type="button">
                                🇨🇳 中文
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-ko" type="button">
                                🇰🇷 한국어
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-ja" type="button">
                                🇯🇵 日本語
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-ru" type="button">
                                🇷🇺 Русский
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Thai -->
                        <div class="tab-pane fade show active" id="lang-th">
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
                                <label class="form-label">Blog Title</label>
                                <input type="text" name="blog_name_en" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Excerpt</label>
                                <textarea name="blog_excerpt_en" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Content</label>
                                <textarea name="blog_detail_en" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tags (comma-separated)</label>
                                <input type="text" name="blog_tag_en" class="form-control" placeholder="tag1, tag2, tag3">
                            </div>
                        </div>

                        <!-- German -->
                        <div class="tab-pane fade" id="lang-de">
                            <div class="mb-3">
                                <label class="form-label">Blog-Titel</label>
                                <input type="text" name="blog_name_de" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Auszug</label>
                                <textarea name="blog_excerpt_de" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Inhalt</label>
                                <textarea name="blog_detail_de" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Stichworte</label>
                                <input type="text" name="blog_tag_de" class="form-control">
                            </div>
                        </div>

                        <!-- French -->
                        <div class="tab-pane fade" id="lang-fr">
                            <div class="mb-3">
                                <label class="form-label">Titre du blog</label>
                                <input type="text" name="blog_name_fr" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Extrait</label>
                                <textarea name="blog_excerpt_fr" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contenu</label>
                                <textarea name="blog_detail_fr" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mots clés</label>
                                <input type="text" name="blog_tag_fr" class="form-control">
                            </div>
                        </div>

                        <!-- Chinese -->
                        <div class="tab-pane fade" id="lang-zh">
                            <div class="mb-3">
                                <label class="form-label">博客标题</label>
                                <input type="text" name="blog_name_zh" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">摘要</label>
                                <textarea name="blog_excerpt_zh" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">内容</label>
                                <textarea name="blog_detail_zh" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">标签</label>
                                <input type="text" name="blog_tag_zh" class="form-control">
                            </div>
                        </div>

                        <!-- Korean -->
                        <div class="tab-pane fade" id="lang-ko">
                            <div class="mb-3">
                                <label class="form-label">블로그 제목</label>
                                <input type="text" name="blog_name_ko" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">발췌</label>
                                <textarea name="blog_excerpt_ko" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">내용</label>
                                <textarea name="blog_detail_ko" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">태그</label>
                                <input type="text" name="blog_tag_ko" class="form-control">
                            </div>
                        </div>

                        <!-- Japanese -->
                        <div class="tab-pane fade" id="lang-ja">
                            <div class="mb-3">
                                <label class="form-label">ブログタイトル</label>
                                <input type="text" name="blog_name_ja" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">抜粋</label>
                                <textarea name="blog_excerpt_ja" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">内容</label>
                                <textarea name="blog_detail_ja" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">タグ</label>
                                <input type="text" name="blog_tag_ja" class="form-control">
                            </div>
                        </div>

                        <!-- Russian -->
                        <div class="tab-pane fade" id="lang-ru">
                            <div class="mb-3">
                                <label class="form-label">Название блога</label>
                                <input type="text" name="blog_name_ru" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Отрывок</label>
                                <textarea name="blog_excerpt_ru" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Содержание</label>
                                <textarea name="blog_detail_ru" class="form-control summernote"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Теги</label>
                                <input type="text" name="blog_tag_ru" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Section -->
                    <div class="mt-4">
                        <h6>แกลเลอรี่</h6>
                        <div class="mb-3">
                            <input type="file" id="galleryInput" class="form-control" accept="image/*" multiple>
                            <button type="button" onclick="uploadGallery()" class="btn btn-sm btn-secondary mt-2">อัพโหลดภาพ</button>
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
});

// Cover image preview
document.getElementById('coverImageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('coverImage').src = e.target.result;
            document.getElementById('coverImagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>

<!-- Summernote JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<!-- Blog JS -->
<script src="<?= ADMIN_ASSETS ?>/js/blog.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
