<?php
$page_title = "จัดการหมวด FAQ";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="help-circle" class="me-2"></i>จัดการหมวด FAQ</h2>
        <button onclick="addCategory()" class="btn btn-success">
            <i data-lucide="plus" style="width:16px; height:16px; margin-right:6px;"></i> เพิ่มหมวด FAQ
        </button>
    </div>

    <!-- Search Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาชื่อหมวด FAQ...">
        </div>
    </div>

    <!-- Categories Table -->
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
        
        <div class="card-footer bg-white py-3" id="paginationContainer"></div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มหมวด FAQ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="categoryId">
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="categoryStatus" checked>
                            <label class="form-check-label">
                                <span class="badge bg-success" id="statusBadge">เปิดใช้งาน</span>
                            </label>
                        </div>
                    </div>

                    <!-- Language Tabs -->
                    <div class="language-dropdown-container mb-3" data-content-selector="#faqscatLangContent"></div>
                    <div id="faqscatLangContent">
                        <div data-lang="th">
                            <div class="mb-3">
                                <label class="form-label">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                                <input type="text" name="faqscat_name_th" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="faqscat_detail_th" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div data-lang="en">
                            <div class="mb-3">
                                <label class="form-label">Category Name (English)</label>
                                <input type="text" name="faqscat_name_en" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description (English)</label>
                                <textarea name="faqscat_detail_en" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div data-lang="de">
                            <div class="mb-3">
                                <label class="form-label">Kategoriename (German)</label>
                                <input type="text" name="faqscat_name_de" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Beschreibung (German)</label>
                                <textarea name="faqscat_detail_de" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div data-lang="fr">
                            <div class="mb-3">
                                <label class="form-label">Nom de catégorie (French)</label>
                                <input type="text" name="faqscat_name_fr" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description (French)</label>
                                <textarea name="faqscat_detail_fr" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div data-lang="zh">
                            <div class="mb-3">
                                <label class="form-label">分类名称 (Chinese)</label>
                                <input type="text" name="faqscat_name_zh" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">描述 (Chinese)</label>
                                <textarea name="faqscat_detail_zh" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div data-lang="ko">
                            <div class="mb-3">
                                <label class="form-label">카테고리 이름 (Korean)</label>
                                <input type="text" name="faqscat_name_ko" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">설명 (Korean)</label>
                                <textarea name="faqscat_detail_ko" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div data-lang="ja">
                            <div class="mb-3">
                                <label class="form-label">カテゴリ名 (Japanese)</label>
                                <input type="text" name="faqscat_name_ja" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">説明 (Japanese)</label>
                                <textarea name="faqscat_detail_ja" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div data-lang="ru">
                            <div class="mb-3">
                                <label class="form-label">Название категории (Russian)</label>
                                <input type="text" name="faqscat_name_ru" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Описание (Russian)</label>
                                <textarea name="faqscat_detail_ru" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div data-lang="ar">
                            <div class="mb-3">
                                <label class="form-label">اسم الفئة (Category Name - Arabic)</label>
                                <input type="text" name="faqscat_name_ar" class="form-control" dir="rtl">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الوصف (Description - Arabic)</label>
                                <textarea name="faqscat_detail_ar" class="form-control" rows="3" dir="rtl"></textarea>
                            </div>
                        </div>

                        <div data-lang="he">
                            <div class="mb-3">
                                <label class="form-label">שם קטגוריה (Category Name - Hebrew)</label>
                                <input type="text" name="faqscat_name_he" class="form-control" dir="rtl">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">תיאור (Description - Hebrew)</label>
                                <textarea name="faqscat_detail_he" class="form-control" rows="3" dir="rtl"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" onclick="saveCategory()" class="btn btn-primary">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('categoryStatus').addEventListener('change', function() {
    const badge = document.getElementById('statusBadge');
    if (this.checked) {
        badge.textContent = 'เปิดใช้งาน';
        badge.className = 'badge bg-success';
    } else {
        badge.textContent = 'ปิดใช้งาน';
        badge.className = 'badge bg-secondary';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="<?= ADMIN_ASSETS ?>/js/faqscat.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
