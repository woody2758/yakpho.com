<?php
$page_title = "จัดการช่องทางขาย";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="share-2" class="me-2"></i>จัดการช่องทางขาย (Sales Channels)</h2>
        <button onclick="addChannel()" class="btn btn-success">
            <i data-lucide="plus" style="width:16px; height:16px; margin-right:6px;"></i> เพิ่มช่องทางขาย
        </button>
    </div>

    <!-- Search Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="position-relative">
                        <input type="search" id="searchInput" onsearch="if(this.value==='') { if(typeof performSearch === 'function') performSearch(); else if(this.form) this.form.submit(); else window.location.search=''; }" class="form-control" placeholder="ค้นหาชื่อช่องทางขาย หรือ รหัส...">
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm position-relative" style="overflow: hidden;">
        <!-- Table Loading Overlay -->
        <div id="tableLoadingOverlay" class="position-absolute d-flex align-items-center justify-content-center bg-white bg-opacity-75" 
             style="z-index: 1000; top: 0; left: 0; right: 0; bottom: 0; display: none !important;">
            <div class="text-center">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div class="text-primary fw-bold">กำลังอัปเดตลำดับ...</div>
            </div>
        </div>
        
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="channelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มช่องทางขาย</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="channelForm">
                    <input type="hidden" id="channelId" name="channel_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อช่องทางขาย <span class="text-danger">*</span></label>
                            <input type="text" name="channel_name" id="channelName" class="form-control" required placeholder="เช่น LINE OA, Facebook">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">รหัสช่องทาง (Code) <span class="text-danger">*</span></label>
                            <input type="text" name="channel_code" id="channelCode" class="form-control" required placeholder="เช่น LINE, FB">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ลำดับ (Index)</label>
                            <input type="number" name="channel_index" id="channelIndex" class="form-control" value="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="channelActive" name="channel_status" checked value="1">
                            <label class="form-check-label" for="channelActive">
                                <span class="badge bg-success" id="statusBadge">เปิดใช้งาน</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" onclick="saveChannel()" class="btn btn-primary">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<script>
// Define global variables
const ADMIN_URL = '<?= ADMIN_URL ?>';

// Status toggle handler
document.getElementById('channelActive').addEventListener('change', function() {
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
    loadTable(1);
    
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
            loadTable(1, value);
        }, 300);
    });
});

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('clearSearch').classList.add('d-none');
    loadTable(1);
}
</script>

<!-- Load external JS file -->
<script src="<?= ADMIN_ASSETS ?>/js/sales_channels.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
