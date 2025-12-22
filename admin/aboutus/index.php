<?php
$page_title = "About Us Management";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

ob_start();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="file-text"></i> About Us Management</h4>
        <p class="text-muted mb-0">จัดการข้อมูลเกี่ยวกับบริษัท, นโยบาย, เงื่อนไขต่างๆ</p>
    </div>
    <button class="btn btn-primary" onclick="addAboutUs()">
        <i data-lucide="plus"></i> Add New
    </button>
</div>

<!-- About Us Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="aboutusTable">
                <thead>
                    <tr>
                        <th style="width: 50px">#</th>
                        <th>Heading</th>
                        <th>Title (TH)</th>
                        <th>Title (EN)</th>
                        <th style="width: 100px" class="text-center">Status</th>
                        <th style="width: 150px" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="aboutusTableBody">
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="aboutusModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add About Us</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-y: auto; max-height: 70vh;">
                <form id="aboutusForm">
                    <input type="hidden" id="aboutusId" name="aboutus_id">
                    
                    <!-- Basic Info -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i data-lucide="settings"></i> Basic Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Heading <span class="text-danger">*</span></label>
                                    <input type="text" id="aboutusHeading" name="aboutus_heading" class="form-control" 
                                           placeholder="e.g., About Us, Privacy Policy" required>
                                    <small class="text-muted">Main identifier for this page</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch" style="padding-top: 8px;">
                                        <input class="form-check-input" type="checkbox" id="aboutusStatus" checked>
                                        <label class="form-check-label" for="aboutusStatus">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Language Tabs -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i data-lucide="languages"></i> Content (Multi-Language)</h6>
                        </div>
                        <div class="card-body">
                            <div class="language-dropdown-container mb-3" data-content-selector="#aboutusLangContent"></div>
                            <div id="aboutusLangContent">
                                <?php
                                $languages = [
                                    'th' => 'Thai',
                                    'en' => 'English',
                                    'de' => 'German',
                                    'fr' => 'French',
                                    'zh' => 'Chinese',
                                    'ko' => 'Korean',
                                    'ja' => 'Japanese',
                                    'ru' => 'Russian',
                                    'ar' => 'Arabic',
                                    'he' => 'Hebrew'
                                ];
                                
                                foreach ($languages as $code => $name) {
                                    // Determine label suffix
                                    $labelSuffix = '';
                                    if (in_array($code, ['ar', 'he'])) {
                                        $labelSuffix = ' (' . $name . ')';
                                    }
                                    
                                    // RTL support
                                    $dirAttr = in_array($code, ['ar', 'he']) ? 'dir="rtl"' : '';
                                    
                                    echo "
                                    <div data-lang='$code'>
                                        <div class='mb-3'>
                                            <label class='form-label'>Title" . ($code === 'ar' || $code === 'he' ? "$labelSuffix" : " ($name)") . "</label>
                                            <input type='text' class='form-control' id='title_$code' " . (in_array($code, ['ar', 'he']) ? "dir='rtl'" : "") . "
                                                   placeholder='Enter title in $name'>
                                        </div>
                                        <div class='mb-3'>
                                            <label class='form-label'>Subtitle" . ($code === 'ar' || $code === 'he' ? "$labelSuffix" : "") . "</label>
                                            <input type='text' class='form-control' id='subtitle_$code' " . (in_array($code, ['ar', 'he']) ? "dir='rtl'" : "") . "
                                                   placeholder='Enter subtitle (optional)'>
                                        </div>
                                        <div class='mb-3'>
                                            <label class='form-label'>Content" . ($code === 'ar' || $code === 'he' ? "$labelSuffix" : "") . "</label>
                                            <textarea class='form-control summernote' id='content_$code' " . (in_array($code, ['ar', 'he']) ? "dir='rtl'" : "") . "
                                                      rows='10'></textarea>
                                        </div>
                                    </div>
                                    ";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAboutUs()">
                    <i data-lucide="save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.css" rel="stylesheet">

<!-- Sortable.js -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<!-- Summernote JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.js"></script>

<!-- About Us JS -->
<script src="<?= ADMIN_ASSETS ?>/js/aboutus.js?v=<?= $ver ?>"></script>

<script>
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAboutUsTable();
    initLanguageTabs();
});
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
