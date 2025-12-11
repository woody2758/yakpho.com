<?php
$page_title = "จัดการสถานะการสั่งซื้อ";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="list-checks" class="me-2"></i>จัดการสถานะการสั่งซื้อ</h2>
        <button onclick="addStatus()" class="btn btn-success">
            <i data-lucide="plus" style="width:16px; height:16px; margin-right:6px;"></i> เพิ่มสถานะ
        </button>
    </div>

    <!-- Search Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="position-relative">
                        <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาชื่อสถานะ หรือ รหัส...">
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

    <!-- Status Table -->
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

<!-- Add/Edit Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มสถานะการสั่งซื้อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="statusForm">
                    <input type="hidden" id="statusId" name="orsts_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">รหัสสถานะ (Code) <span class="text-danger">*</span></label>
                            <input type="text" name="orsts_code" id="statusCode" class="form-control text-uppercase" required placeholder="เช่น PENDING, PAID">
                            <div class="form-text">ภาษาอังกฤษตัวพิมพ์ใหญ่ ห้ามซ้ำ</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">สีสถานะ</label>
                            <input type="color" name="orsts_color" id="statusColor" class="form-control form-control-color w-100" value="#000000" title="เลือกสีสถานะ">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ลำดับ (Index)</label>
                            <input type="number" name="orsts_index" id="statusIndex" class="form-control" value="0">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="statusUser" name="orsts_user" checked>
                                <label class="form-check-label" for="statusUser">
                                    แสดงให้ลูกค้าเห็น (Customer Visible)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="statusActive" name="orsts_status" checked>
                                <label class="form-check-label" for="statusActive">
                                    <span class="badge bg-success" id="statusBadge">เปิดใช้งาน</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#lang-th" type="button">🇹🇭 ไทย</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-en" type="button">🇬🇧 English</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-de" type="button">🇩🇪 Deutsch</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-fr" type="button">🇫🇷 Français</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-zh" type="button">🇨🇳 中文</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-ko" type="button">🇰🇷 한국어</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Thai -->
                        <div class="tab-pane fade show active" id="lang-th">
                            <div class="mb-3">
                                <label class="form-label">ชื่อสถานะ <span class="text-danger">*</span></label>
                                <input type="text" name="orsts_name_th" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ข้อความแจ้งเตือน (Email/SMS)</label>
                                <textarea name="orsts_msg_th" class="form-control summernote"></textarea>
                            </div>
                        </div>

                        <!-- English -->
                        <div class="tab-pane fade" id="lang-en">
                            <div class="mb-3">
                                <label class="form-label">Status Name</label>
                                <input type="text" name="orsts_name_en" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notification Message</label>
                                <textarea name="orsts_msg_en" class="form-control summernote"></textarea>
                            </div>
                        </div>

                        <!-- German -->
                        <div class="tab-pane fade" id="lang-de">
                            <div class="mb-3">
                                <label class="form-label">Statusname</label>
                                <input type="text" name="orsts_name_de" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Benachrichtigungstext</label>
                                <textarea name="orsts_msg_de" class="form-control summernote"></textarea>
                            </div>
                        </div>

                        <!-- French -->
                        <div class="tab-pane fade" id="lang-fr">
                            <div class="mb-3">
                                <label class="form-label">Nom du statut</label>
                                <input type="text" name="orsts_name_fr" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message de notification</label>
                                <textarea name="orsts_msg_fr" class="form-control summernote"></textarea>
                            </div>
                        </div>

                        <!-- Chinese -->
                        <div class="tab-pane fade" id="lang-zh">
                            <div class="mb-3">
                                <label class="form-label">状态名称</label>
                                <input type="text" name="orsts_name_zh" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">通知消息</label>
                                <textarea name="orsts_msg_zh" class="form-control summernote"></textarea>
                            </div>
                        </div>

                        <!-- Korean -->
                        <div class="tab-pane fade" id="lang-ko">
                            <div class="mb-3">
                                <label class="form-label">상태 이름</label>
                                <input type="text" name="orsts_name_ko" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">알림 메시지</label>
                                <textarea name="orsts_msg_ko" class="form-control summernote"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" onclick="saveStatus()" class="btn btn-primary">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<script>
// Define global variables
const ADMIN_URL = '<?= ADMIN_URL ?>';

// Status toggle handler
document.getElementById('statusActive').addEventListener('change', function() {
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
    loadStatusTable(1);
    
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
            loadStatusTable(1, value);
        }, 300);
    });
});

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('clearSearch').classList.add('d-none');
    loadStatusTable(1);
}
</script>

<!-- Load external JS file -->
<script src="<?= ADMIN_ASSETS ?>/js/orderstatus.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
