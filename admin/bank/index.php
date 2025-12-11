<?php
$page_title = "จัดการบัญชีธนาคาร";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="landmark" class="me-2"></i>จัดการบัญชีธนาคาร</h2>
        <button onclick="addBank()" class="btn btn-success">
            <i data-lucide="plus" style="width:16px; height:16px; margin-right:6px;"></i> เพิ่มบัญชีธนาคาร
        </button>
    </div>

    <!-- Search Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="position-relative">
                        <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาชื่อธนาคาร, ชื่อบัญชี หรือ เลขที่บัญชี...">
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

    <!-- Bank Table -->
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="bankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มบัญชีธนาคาร</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bankForm" enctype="multipart/form-data">
                    <input type="hidden" id="bankId" name="bank_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-4 text-center">
                            <div class="mb-2">
                                <label class="form-label">โลโก้ธนาคาร</label>
                                <div class="position-relative mx-auto" style="width: 120px; height: 120px;">
                                    <img id="bankPreview" src="<?= ADMIN_ASSETS ?>/images/placeholder.png" 
                                         class="rounded border shadow-sm" style="width: 100%; height: 100%; object-fit: contain; background: #fff;">
                                    <label for="bankPicture" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 shadow-sm" 
                                           style="cursor: pointer; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <i data-lucide="camera" style="width: 16px; height: 16px;"></i>
                                    </label>
                                    <input type="file" id="bankPicture" name="bank_picture" class="d-none" accept="image/*" onchange="previewImage(this)">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">เลขที่บัญชี <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_accountnumber" id="bankAccountNumber" class="form-control" required placeholder="XXX-X-XXXXX-X">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Swift Code</label>
                                    <input type="text" name="bank_swiftcode" id="bankSwiftCode" class="form-control" placeholder="เช่น KASITHBK">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ลำดับ (Index)</label>
                                    <input type="number" name="bank_index" id="bankIndex" class="form-control" value="0">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" id="bankActive" name="bank_status" checked>
                                        <label class="form-check-label" for="bankActive">
                                            <span class="badge bg-success" id="statusBadge">เปิดใช้งาน</span>
                                        </label>
                                    </div>
                                </div>
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
                                <label class="form-label">ชื่อธนาคาร <span class="text-danger">*</span></label>
                                <input type="text" name="bank_bankname_th" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ชื่อบัญชี <span class="text-danger">*</span></label>
                                <input type="text" name="bank_accountname_th" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ประเภทบัญชี</label>
                                    <input type="text" name="bank_accounttype_th" class="form-control" placeholder="เช่น ออมทรัพย์">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">สาขา</label>
                                    <input type="text" name="bank_accountbranch_th" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- English -->
                        <div class="tab-pane fade" id="lang-en">
                            <div class="mb-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_bankname_en" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Name</label>
                                <input type="text" name="bank_accountname_en" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Account Type</label>
                                    <input type="text" name="bank_accounttype_en" class="form-control" placeholder="e.g. Savings">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Branch</label>
                                    <input type="text" name="bank_accountbranch_en" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- German -->
                        <div class="tab-pane fade" id="lang-de">
                            <div class="mb-3">
                                <label class="form-label">Bankname</label>
                                <input type="text" name="bank_bankname_de" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kontoinhaber</label>
                                <input type="text" name="bank_accountname_de" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kontoart</label>
                                    <input type="text" name="bank_accounttype_de" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Filiale</label>
                                    <input type="text" name="bank_accountbranch_de" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- French -->
                        <div class="tab-pane fade" id="lang-fr">
                            <div class="mb-3">
                                <label class="form-label">Nom de la banque</label>
                                <input type="text" name="bank_bankname_fr" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nom du compte</label>
                                <input type="text" name="bank_accountname_fr" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Type de compte</label>
                                    <input type="text" name="bank_accounttype_fr" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Agence</label>
                                    <input type="text" name="bank_accountbranch_fr" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Chinese -->
                        <div class="tab-pane fade" id="lang-zh">
                            <div class="mb-3">
                                <label class="form-label">银行名称</label>
                                <input type="text" name="bank_bankname_zh" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">账户名称</label>
                                <input type="text" name="bank_accountname_zh" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">账户类型</label>
                                    <input type="text" name="bank_accounttype_zh" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">分行</label>
                                    <input type="text" name="bank_accountbranch_zh" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Korean -->
                        <div class="tab-pane fade" id="lang-ko">
                            <div class="mb-3">
                                <label class="form-label">은행명</label>
                                <input type="text" name="bank_bankname_ko" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">예금주</label>
                                <input type="text" name="bank_accountname_ko" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">계좌 유형</label>
                                    <input type="text" name="bank_accounttype_ko" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">지점</label>
                                    <input type="text" name="bank_accountbranch_ko" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" onclick="saveBank()" class="btn btn-primary">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<script>
// Define global variables
const ADMIN_URL = '<?= ADMIN_URL ?>';
const ROOT_URL = '<?= ROOT_URL ?>';

// Status toggle handler
document.getElementById('bankActive').addEventListener('change', function() {
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
    loadBankTable(1);
    
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
            loadBankTable(1, value);
        }, 300);
    });
});

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('clearSearch').classList.add('d-none');
    loadBankTable(1);
}
</script>

<!-- Load external JS file -->
<script src="<?= ADMIN_ASSETS ?>/js/bank.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
