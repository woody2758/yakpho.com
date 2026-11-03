<?php
$page_title = "จัดการหมวดหมู่หลัก";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<style>
/* บังคับ Dark Mode ให้เข้มแน่นอน ตามหน้า order_status.php */
[data-theme="dark"] .modal-content {
    background-color: #1a1a1a !important;
    color: #f0f0f0 !important;
    border: 1px solid #333 !important;
}
[data-theme="dark"] .form-control, [data-theme="dark"] .form-select {
    background-color: #262626 !important;
    border-color: #444 !important;
    color: #fff !important;
}
[data-theme="dark"] .form-control:focus, [data-theme="dark"] .form-select:focus {
    background-color: #2d2d2d !important;
    color: #fff !important;
}
[data-theme="dark"] .modal-header, [data-theme="dark"] .modal-footer {
    border-color: #333 !important;
}
[data-theme="dark"] .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}
[data-theme="dark"] .modal-title {
    color: #3498db !important;
}
[data-theme="dark"] #modalLoadingOverlay {
    background-color: rgba(26, 26, 26, 0.9) !important;
}
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i data-lucide="folder-tree" class="me-2"></i>
            <span class="d-none d-sm-inline">จัดการ</span>หมวดหมู่หลัก
        </h2>
        <button onclick="addMainCategory()" class="btn btn-success">
            <i data-lucide="plus" style="width:16px; height:16px; margin-right:4px;"></i> 
            <span class="d-none d-sm-inline">เพิ่มหมวดหมู่หลัก</span>
            <span class="d-inline d-sm-none">เพิ่ม</span>
        </button>
    </div>

    <!-- Search Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="position-relative">
                        <input type="search" id="searchInput" onsearch="if(this.value==='') { if(typeof performSearch === 'function') performSearch(); else if(this.form) this.form.submit(); else window.location.search=''; }" class="form-control" placeholder="ค้นหาชื่อหมวดหมู่หลัก...">
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Categories Table -->
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

<!-- Add/Edit Main Category Modal -->
<div class="modal fade" id="maincatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Loading Overlay -->
            <div id="modalLoadingOverlay" class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-white" style="z-index: 1060; border-radius: .5rem; display: none !important;">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div class="text-muted">กำลังโหลดข้อมูล...</div>
                </div>
            </div>
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มหมวดหมู่หลัก</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="maincatForm">
                    <input type="hidden" id="maincatId" name="maincat_id">
                    
                    <!-- Status Toggle -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="maincatStatus" name="maincat_status" checked>
                            <label class="form-check-label" for="maincatStatus">
                                <span class="badge bg-success" id="statusBadge">เปิดใช้งาน</span>
                            </label>
                        </div>
                    </div>

                    <!-- Slug -->
                    <div class="mb-3">
                        <label class="form-label">URL Slug <span class="text-danger">*</span></label>
                        <input type="text" name="maincat_slug" id="maincat_slug" class="form-control" required 
                               placeholder="e.g., yakpho, esther, others" pattern="[a-z0-9-]+">
                        <small class="text-muted">ใช้ได้เฉพาะตัวอักษรภาษาอังกฤษตัวเล็ก ตัวเลข และ -(ขีด)</small>
                    </div>

                    <!-- Icon -->
                    <div class="mb-3">
                        <label class="form-label">Icon (Lucide Icon Name)</label>
                        <input type="text" name="maincat_icon" id="maincat_icon" class="form-control" 
                               placeholder="e.g., package, sparkles, layers">
                        <small class="text-muted">ดู icon ได้ที่: <a href="https://lucide.dev/icons" target="_blank">lucide.dev/icons</a></small>
                    </div>

                    <!-- Language Tabs -->
                    <div class="language-dropdown-container mb-3" data-content-selector="#maincatLangContent"></div>
                    <div id="maincatLangContent">
                        <!-- Thai -->
                        <div data-lang="th">
                            <div class="mb-3">
                                <label class="form-label">ชื่อหมวดหมู่หลัก <span class="text-danger">*</span></label>
                                <input type="text" name="maincat_name_th" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="maincat_detail_th" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- English -->
                        <div data-lang="en">
                            <div class="mb-3">
                                <label class="form-label">Main Category Name (English)</label>
                                <input type="text" name="maincat_name_en" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description (English)</label>
                                <textarea name="maincat_detail_en" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Other languages (de, fr, zh, ko, ja, ru, ar, he) -->
                        <?php
                        $otherLangs = [
                            'de' => 'German',
                            'fr' => 'French',
                            'zh' => 'Chinese',
                            'ko' => 'Korean',
                            'ja' => 'Japanese',
                            'ru' => 'Russian',
                            'ar' => 'Arabic',
                            'he' => 'Hebrew'
                        ];
                        foreach ($otherLangs as $code => $name):
                            $isRTL = in_array($code, ['ar', 'he']);
                        ?>
                        <div data-lang="<?= $code ?>">
                            <div class="mb-3">
                                <label class="form-label">Category Name (<?= $name ?>)</label>
                                <input type="text" name="maincat_name_<?= $code ?>" class="form-control"<?= $isRTL ? ' dir="rtl"' : '' ?>>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description (<?= $name ?>)</label>
                                <textarea name="maincat_detail_<?= $code ?>" class="form-control" rows="3"<?= $isRTL ? ' dir="rtl"' : '' ?>></textarea>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" id="saveMainCatBtn" onclick="saveMainCategory()" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                    <span class="btn-text">บันทึก</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Define global variables
const ADMIN_URL = '<?= ADMIN_URL ?>';

// Status toggle handler
document.getElementById('maincatStatus').addEventListener('change', function() {
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
    loadMainCategoriesTable(1);
    
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
            loadMainCategoriesTable(1, value);
        }, 300);
    });
});

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('clearSearch').classList.add('d-none');
    loadMainCategoriesTable(1);
}
</script>

<!-- Load external JS file -->
<script src="<?= ADMIN_ASSETS ?>/js/maincat.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
