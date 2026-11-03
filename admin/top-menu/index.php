<?php
/**
 * Top Menu (Header) Management
 * Admin interface for managing main navigation links
 */

$page_title = 'Top Menu Management';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

ob_start();
?>

<script>
    const ADMIN_URL = '<?= ADMIN_URL ?>';
    const BASE_URL = '<?= BASE_URL ?>';
</script>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0"><i data-lucide="menu"></i> จัดการเมนูหลัก (Top Menu)</h1>
        <p class="text-muted">จัดการลิงก์ใน Header Navigation (รองรับเมนูย่อย 1 ระดับ)</p>
    </div>
    <button class="btn btn-primary" onclick="TopMenu.openModal()">
        <i data-lucide="plus"></i> เพิ่มรายการเมนู
    </button>
</div>

<!-- Menu Builder Container -->
<div class="card">
    <div class="card-body">
        <div id="top-menu-builder" class="nested-menu-container">
            <!-- Data will be loaded via AJAX as nested <ul> <li> -->
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 mb-0">กำลังโหลดระบบจัดการเมนู...</p>
            </div>
        </div>
    </div>
</div>

<style>
/* Hierarchical Menu Builder Styles */
.nested-menu-container ul {
    list-style: none;
    padding-left: 0;
    margin-bottom: 0;
}

.nested-menu-container li {
    margin-bottom: 15px;
}

.menu-item-content {
    background: var(--color-surface);
    border: 1px solid rgba(11, 59, 53, 0.15);
    border-radius: var(--radius-md);
    padding: 12px 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: var(--shadow-sm);
    transition: all 0.2s;
}

.menu-item-content:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--esther-gold);
}

.menu-item-handle {
    cursor: move;
    margin-right: 15px;
    color: var(--color-text-muted);
}

.menu-item-title {
    flex-grow: 1;
    font-weight: 500;
}

.menu-item-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.submenu-list {
    padding-left: 40px;
    margin-top: 15px;
    border-left: 2px dashed rgba(11, 59, 53, 0.1);
}

.ghost {
    opacity: 0.4;
    background: #c8ebfb;
}

.drag-handle {
    cursor: move;
}
</style>

<!-- Add/Edit Modal -->
<div class="modal fade" id="topMenuModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="position: relative;">
            <!-- Modal Premium Loading Overlay -->
            <div class="modal-loading-overlay" id="topMenuModalLoading">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                <h5 class="text-white mt-3 fw-bold">กำลังโหลดข้อมูล...</h5>
            </div>
            
            <form id="topMenuForm" onsubmit="TopMenu.save(event)">
                <input type="hidden" name="menu_id" id="menu_id" value="0">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">เพิ่มรายการเมนู</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <!-- Left Column: Settings -->
                        <div class="col-md-5 border-end">
                            <div class="mb-3">
                                <label class="form-label">เมนูหลัก (Parent Menu)</label>
                                <select class="form-select" name="parent_id" id="parent_id">
                                    <option value="0">--- ไม่มี (เป็นเมนูหลัก) ---</option>
                                    <!-- Parent options will be loaded via AJAX -->
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">ลิงก์ / URL <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="menu_link" id="menu_link" required placeholder="/shop หรือ https://...">
                                <small class="text-muted">ใช้ / นำหน้าสำหรับลิงก์ภายใน</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Icon (ถ้ามี)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="menu_icon" id="menu_icon" placeholder="home, shopping-bag...">
                                    <span class="input-group-text"><i id="iconPreview" data-lucide="help-circle"></i></span>
                                </div>
                                <small class="text-muted">ใช้ชื่อ icon จาก <a href="https://lucide.dev" target="_blank">Lucide</a></small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">สถานะ</label>
                                <select class="form-select" name="menu_status" id="menu_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Right Column: Languages -->
                        <div class="col-md-7">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">ข้อความเมนู (10 ภาษา) <span class="text-danger">*</span></label>
                                <div class="language-dropdown-container" data-content-selector="#menuLangContent"></div>
                            </div>

                            <div id="menuLangContent">
                                <?php
                                $languages = [
                                    'th' => 'Thai',
                                    'en' => 'English',
                                    'zh' => 'Chinese',
                                    'de' => 'German',
                                    'fr' => 'French',
                                    'ja' => 'Japanese',
                                    'ko' => 'Korean',
                                    'ru' => 'Russian',
                                    'ar' => 'Arabic',
                                    'he' => 'Hebrew'
                                ];
                                
                                foreach ($languages as $code => $name):
                                    $isRTL = in_array($code, ['ar', 'he']);
                                    $dirAttr = $isRTL ? 'dir="rtl"' : '';
                                ?>
                                <div data-lang="<?= $code ?>">
                                    <div class="mb-3">
                                        <label class="form-label">ชื่อเมนู (<?= $name ?>)</label>
                                        <input type="text" class="form-control" 
                                               name="menu_text_<?= $code ?>" 
                                               id="menu_text_<?= $code ?>" 
                                               <?= $dirAttr ?>
                                               <?= $code === 'th' ? 'required' : '' ?>
                                               placeholder="ระบุชื่อเมนูภาษา<?= $name ?>...">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i data-lucide="save"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= ADMIN_URL ?>/assets/js/language-dropdown.js"></script>
<script src="<?= ADMIN_URL ?>/assets/js/top-menu.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load menu builder data
        TopMenu.loadTable();
        TopMenu.loadParents();
        
        // Icon preview handler
        document.getElementById('menu_icon').addEventListener('input', function(e) {
            const iconName = e.target.value.trim() || 'help-circle';
            const iconPreview = document.getElementById('iconPreview');
            iconPreview.setAttribute('data-lucide', iconName);
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
