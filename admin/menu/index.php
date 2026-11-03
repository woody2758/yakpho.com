<?php
/**
 * Footer Menu Management
 * Admin interface for managing footer menu links and social icons
 */

$page_title = 'Footer Menu Management';
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
        <h1 class="h3 mb-0"><i data-lucide="menu"></i> จัดการเมนูส่วนท้าย (Footer Menu)</h1>
        <p class="text-muted">จัดการลิงก์ใน Footer ทั้ง 4 คอลัมน์</p>
    </div>
    <button class="btn btn-primary" onclick="Menu.openModal()">
        <i data-lucide="plus"></i> เพิ่มรายการเมนู
    </button>
</div>

<!-- Column Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0">แสดงคอลัมน์:</label>
            </div>
            <div class="col-auto">
                <select class="form-select" id="columnFilter" onchange="Menu.loadTable()">
                    <option value="all">ทั้งหมด</option>
                    <option value="company">เกี่ยวกับบริษัท (Company)</option>
                    <option value="quicklinks">ลิงก์ด่วน (Quick Links)</option>
                    <option value="support">ช่วยเหลือ (Support)</option>
                    <option value="followus">ติดตามเรา (Follow Us)</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Menus Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="40" class="text-center">#</th>
                        <th>คอลัมน์</th>
                        <th>ข้อความเมนู (TH)</th>
                        <th>ลิงก์ / URL</th>
                        <th width="100" class="text-center">สถานะ</th>
                        <th width="120" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="menu-tbody">
                    <!-- Data will be loaded via AJAX -->
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 mb-0">กำลังโหลดข้อมูล...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="menuModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="position: relative;">
            <!-- Modal Premium Loading Overlay -->
            <div class="modal-loading-overlay" id="menuModalLoading">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                <h5 class="text-white mt-3 fw-bold">กำลังโหลดข้อมูล...</h5>
            </div>
            
            <form id="menuForm" onsubmit="Menu.save(event)">
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
                                <label class="form-label">กลุ่มคอลัมน์ <span class="text-danger">*</span></label>
                                <select class="form-select" name="menu_column" id="menu_column" required onchange="Menu.toggleIconField()">
                                    <option value="company">เกี่ยวกับบริษัท (Company)</option>
                                    <option value="quicklinks">ลิงก์ด่วน (Quick Links)</option>
                                    <option value="support">ช่วยเหลือ (Support)</option>
                                    <option value="followus">ติดตามเรา (Follow Us)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">ลิงก์ / URL <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="menu_link" id="menu_link" required placeholder="/shop หรือ https://...">
                                <small class="text-muted">ใช้ / นำหน้าสำหรับลิงก์ภายใน</small>
                            </div>
                            
                            <div class="mb-3" id="iconField" style="display: none;">
                                <label class="form-label">Icon (สำหรับ Follow Us)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="menu_icon" id="menu_icon" placeholder="facebook, instagram...">
                                    <span class="input-group-text"><i id="iconPreview" data-lucide="help-circle"></i></span>
                                </div>
                                <small class="text-muted">ใช้ชื่อ icon จาก <a href="https://lucide.dev" target="_blank">Lucide</a></small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">การเปิดลิงก์</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="menu_target" id="target_self" value="_self" checked>
                                        <label class="form-check-label" for="target_self">หน้าเดิม</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="menu_target" id="target_blank" value="_blank">
                                        <label class="form-check-label" for="target_blank">แท็บใหม่</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">สถานะ</label>
                                <select class="form-select" name="menu_status" id="menu_status">
                                    <option value="active">เปิดใช้งาน</option>
                                    <option value="inactive">ปิดใช้งาน</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Right Column: Languages -->
                        <div class="col-md-7">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">ข้อความเมนู (Multi-Language) <span class="text-danger">*</span></label>
                                <!-- Language Dropdown Component placeholder -->
                                <div id="languageDropdownContainer"></div>
                            </div>

                            <div id="menuLangContent">
                                <?php
                                $languages = [
                                    'th' => 'Thai',
                                    'en' => 'English',
                                    'cn' => 'Chinese',
                                    'de' => 'German',
                                    'fr' => 'French',
                                    'ja' => 'Japanese',
                                    'kr' => 'Korean',
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
                                        <label class="form-label">ข้อความเมนู (<?= $name ?>)</label>
                                        <input type="text" class="form-control" 
                                               name="menu_text_<?= $code ?>" 
                                               id="menu_text_<?= $code ?>" 
                                               <?= $dirAttr ?>
                                               <?= $code === 'th' ? 'required' : '' ?>
                                               placeholder="ระบุข้อความเมนู...">
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
<script src="<?= ADMIN_URL ?>/assets/js/menu.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Language Dropdown
        const langDropdown = new LanguageDropdown({
            container: '#languageDropdownContainer',
            contentSelector: '#menuLangContent',
            onChange: (lang) => {
                console.log('Language changed to:', lang);
            }
        });
        
        // Load table data
        Menu.loadTable();
        
        // Initialize Sortable for drag-and-drop
        new Sortable(document.getElementById('menu-tbody'), {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function() {
                Menu.saveOrder();
            }
        });
        
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
