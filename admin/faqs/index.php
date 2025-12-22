<?php
$page_title = "จัดการ FAQ";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="help-circle" class="me-2"></i>จัดการ FAQ</h2>
        <button onclick="addFAQ()" class="btn btn-success">
            <i data-lucide="plus" style="width:16px; height:16px; margin-right:6px;"></i> เพิ่ม FAQ
        </button>
    </div>

    <!-- Search & Filter Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <input type="text" id="searchInput" class="form-control" placeholder="ค้นหา FAQ...">
                </div>
                <div class="col-md-4">
                    <select id="categoryFilter" class="form-select">
                        <option value="0">ทั้งหมด</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQs Table -->
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
<div class="modal fade" id="faqModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่ม FAQ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="faqForm">
                    <input type="hidden" id="faqId">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select id="faqCategory" class="form-select" required>
                                <option value="">เลือกหมวดหมู่...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">สถานะ</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="faqStatus" checked>
                                <label class="form-check-label">
                                    <span class="badge bg-success" id="statusBadge">เปิดใช้งาน</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="language-dropdown-container mb-3" data-content-selector="#faqsLangContent"></div>
                    <div id="faqsLangContent">
                        <!-- Thai -->
                        <div data-lang="th">
                            <div class="mb-3">
                                <label class="form-label">คำถาม <span class="text-danger">*</span></label>
                                <input type="text" name="faqs_name_th" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">คำตอบ</label>
                                <textarea name="faqs_detail_th" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- English -->
                        <div data-lang="en">
                            <div class="mb-3">
                                <label class="form-label">Question (English)</label>
                                <input type="text" name="faqs_name_en" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Answer (English)</label>
                                <textarea name="faqs_detail_en" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- German -->
                        <div data-lang="de">
                            <div class="mb-3">
                                <label class="form-label">Frage (German)</label>
                                <input type="text" name="faqs_name_de" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Antwort (German)</label>
                                <textarea name="faqs_detail_de" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- French -->
                        <div data-lang="fr">
                            <div class="mb-3">
                                <label class="form-label">Question (French)</label>
                                <input type="text" name="faqs_name_fr" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Réponse (French)</label>
                                <textarea name="faqs_detail_fr" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- Chinese -->
                        <div data-lang="zh">
                            <div class="mb-3">
                                <label class="form-label">问题 (Chinese)</label>
                                <input type="text" name="faqs_name_zh" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">回答 (Chinese)</label>
                                <textarea name="faqs_detail_zh" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- Korean -->
                        <div data-lang="ko">
                            <div class="mb-3">
                                <label class="form-label">질문 (Korean)</label>
                                <input type="text" name="faqs_name_ko" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">대답 (Korean)</label>
                                <textarea name="faqs_detail_ko" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- Japanese -->
                        <div data-lang="ja">
                            <div class="mb-3">
                                <label class="form-label">質問 (Japanese)</label>
                                <input type="text" name="faqs_name_ja" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">答え (Japanese)</label>
                                <textarea name="faqs_detail_ja" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- Russian -->
                        <div data-lang="ru">
                            <div class="mb-3">
                                <label class="form-label">Вопрос (Russian)</label>
                                <input type="text" name="faqs_name_ru" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ответ (Russian)</label>
                                <textarea name="faqs_detail_ru" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- Arabic -->
                        <div data-lang="ar">
                            <div class="mb-3">
                                <label class="form-label">سؤال (Question - Arabic)</label>
                                <input type="text" name="faqs_name_ar" class="form-control" dir="rtl">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">إجابة (Answer - Arabic)</label>
                                <textarea name="faqs_detail_ar" class="form-control" rows="4" dir="rtl"></textarea>
                            </div>
                        </div>

                        <!-- Hebrew -->
                        <div data-lang="he">
                            <div class="mb-3">
                                <label class="form-label">שאלה (Question - Hebrew)</label>
                                <input type="text" name="faqs_name_he" class="form-control" dir="rtl">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">תשובה (Answer - Hebrew)</label>
                                <textarea name="faqs_detail_he" class="form-control" rows="4" dir="rtl"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" onclick="saveFAQ()" class="btn btn-primary">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('faqStatus').addEventListener('change', function() {
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

<!-- Summernote CSS/JS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="<?= ADMIN_ASSETS ?>/js/faqs.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php'; 
?>
