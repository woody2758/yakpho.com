<?php
$page_title = "จัดการหมวดสินค้า";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="folder-tree" class="me-2"></i>จัดการหมวดสินค้า</h2>
        <button onclick="addCategory()" class="btn btn-success">
            <i data-lucide="plus" style="width:16px; height:16px; margin-right:6px;"></i> เพิ่มหมวดสินค้า
        </button>
    </div>

    <!-- Search Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="position-relative">
                        <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาชื่อหมวดสินค้า...">
                        <span id="clearSearch" class="position-absolute end-0 top-50 translate-middle-y d-none align-items-center justify-content-center" 
                              style="cursor:pointer; margin-right:8px; width:20px; height:20px; border-radius:50%; background-color:rgba(108, 117, 125, 0.2);" 
                              title="ล้างคำค้นหา" onclick="clearSearch()">
                            <i data-lucide="x" style="width:12px; height:12px; color:#6c757d;"></i>
                        </span>
                    </div>
                </div>
            </div>
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
        
        <!-- Pagination -->
        <div class="card-footer bg-white py-3" id="paginationContainer">
            <!-- Pagination will be loaded here -->
        </div>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มหมวดสินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="categoryId" name="productcat_id">
                    
                    <!-- Main Category Selection -->
                    <div class="mb-3">
                        <label class="form-label">หมวดหมู่หลัก <span class="text-danger">*</span></label>
                        <select name="maincat_id" id="maincat_id" class="form-select" required>
                            <option value="">เลือกหมวดหมู่หลัก...</option>
                            <!-- Options will be loaded via JavaScript -->
                        </select>
                        <small class="text-muted">หมวดหมู่หลักที่ประเภทนี้อยู่ภายใต้ (เช่น ยักโพธิ์, เอสเธอร์, อื่นๆ)</small>
                    </div>
                    
                    <!-- Status Toggle -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="categoryStatus" name="productcat_status" checked>
                            <label class="form-check-label" for="categoryStatus">
                                <span class="badge bg-success" id="statusBadge">เปิดใช้งาน</span>
                            </label>
                        </div>
                    </div>

                    <!-- Language Tabs -->
                    <div class="language-dropdown-container mb-3" data-content-selector="#productcatLangContent"></div>
                    <div id="productcatLangContent">
                        <!-- Thai -->
                        <div data-lang="th">
                            <div class="mb-3">
                                <label class="form-label">ชื่อหมวดสินค้า <span class="text-danger">*</span></label>
                                <input type="text" name="productcat_name_th" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="productcat_detail_th" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- English -->
                        <div data-lang="en">
                            <div class="mb-3">
                                <label class="form-label">Category Name (English)</label>
                                <input type="text" name="productcat_name_en" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description (English)</label>
                                <textarea name="productcat_detail_en" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- German -->
                        <div data-lang="de">
                            <div class="mb-3">
                                <label class="form-label">Kategoriename (German)</label>
                                <input type="text" name="productcat_name_de" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Beschreibung (German)</label>
                                <textarea name="productcat_detail_de" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- French -->
                        <div data-lang="fr">
                            <div class="mb-3">
                                <label class="form-label">Nom de catégorie (French)</label>
                                <input type="text" name="productcat_name_fr" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description (French)</label>
                                <textarea name="productcat_detail_fr" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Chinese -->
                        <div data-lang="zh">
                            <div class="mb-3">
                                <label class="form-label">分类名称 (Chinese)</label>
                                <input type="text" name="productcat_name_zh" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">描述 (Chinese)</label>
                                <textarea name="productcat_detail_zh" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Korean -->
                        <div data-lang="ko">
                            <div class="mb-3">
                                <label class="form-label">카테고리 이름 (Korean)</label>
                                <input type="text" name="productcat_name_ko" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">설명 (Korean)</label>
                                <textarea name="productcat_detail_ko" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Japanese -->
                        <div data-lang="ja">
                            <div class="mb-3">
                                <label class="form-label">カテゴリ名 (Japanese)</label>
                                <input type="text" name="productcat_name_ja" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">説明 (Japanese)</label>
                                <textarea name="productcat_detail_ja" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Russian -->
                        <div data-lang="ru">
                            <div class="mb-3">
                                <label class="form-label">Название категории (Russian)</label>
                                <input type="text" name="productcat_name_ru" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Описание (Russian)</label>
                                <textarea name="productcat_detail_ru" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Arabic -->
                        <div data-lang="ar">
                            <div class="mb-3">
                                <label class="form-label">اسم الفئة (Category Name - Arabic)</label>
                                <input type="text" name="productcat_name_ar" class="form-control" dir="rtl">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الوصف (Description - Arabic)</label>
                                <textarea name="productcat_detail_ar" class="form-control" rows="3" dir="rtl"></textarea>
                            </div>
                        </div>

                        <!-- Hebrew -->
                        <div data-lang="he">
                            <div class="mb-3">
                                <label class="form-label">שם קטגוריה (Category Name - Hebrew)</label>
                                <input type="text" name="productcat_name_he" class="form-control" dir="rtl">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">תיאור (Description - Hebrew)</label>
                                <textarea name="productcat_detail_he" class="form-control" rows="3" dir="rtl"></textarea>
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
// Define global variables
const ADMIN_URL = '<?= ADMIN_URL ?>';

// Status toggle handler
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Load initial table
    loadCategoriesTable(1);
    
    // Setup search with debounce
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        const value = this.value.trim();
        const clearBtn = document.getElementById('clearSearch');
        
        // Show/hide clear button
        if (value) {
            clearBtn.classList.remove('d-none');
            clearBtn.classList.add('d-flex');
        } else {
            clearBtn.classList.add('d-none');
            clearBtn.classList.remove('d-flex');
        }
        
        // Debounce search
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadCategoriesTable(1, value);
        }, 300);
    });
});

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('clearSearch').classList.add('d-none');
    loadCategoriesTable(1);
}
</script>

<!-- Load external JS file -->
<script src="<?= ADMIN_ASSETS ?>/js/productcat.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
