<?php
$page_title = "จัดการสินค้า";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../../includes/functions/product.php";
require_once __DIR__ . "/../../includes/functions/attribute.php";
require_once __DIR__ . "/../../includes/functions/price_tier.php";

// Get filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 0;
$currentPage = $_GET['page'] ?? 1;

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="package"></i> จัดการสินค้า</h2>
        <button onclick="addProduct()" class="btn btn-success">
            <i data-lucide="plus" style="width:16px; height:16px; margin-right:6px;"></i> เพิ่มสินค้า
        </button>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="position-relative">
                        <input type="text" 
                               class="form-control" 
                               id="searchInput"
                               placeholder="ค้นหาสินค้า (ชื่อ, รหัส, ID)" 
                               value="<?= htmlspecialchars($search) ?>">
                        <?php if (!empty($search)): ?>
                        <span onclick="clearSearch()" 
                              class="position-absolute end-0 top-50 translate-middle-y d-flex align-items-center justify-content-center" 
                              style="cursor:pointer; margin-right:8px; width:20px; height:20px; border-radius:50%; background-color:rgba(108, 117, 125, 0.2);"
                              title="ล้างคำค้นหา">
                            <i data-lucide="x" style="width:12px; height:12px; color:#6c757d;"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="categoryFilter" class="form-select">
                        <option value="0">ทุกหมวดหมู่</option>
                        <?php
                        $stmt = $db->query("
                            SELECT pc.productcat_id, pct.productcat_name,
                                   COUNT(p.product_id) as product_count
                            FROM productcat pc
                            LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = 'th'
                            LEFT JOIN product p ON pc.productcat_id = p.productcat_id AND p.product_del = 0
                            WHERE pc.productcat_del = 0
                            GROUP BY pc.productcat_id, pct.productcat_name
                            ORDER BY pc.productcat_index ASC, pc.productcat_id ASC
                        ");
                        while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($category == $cat['productcat_id']) ? 'selected' : '';
                            echo '<option value="' . $cat['productcat_id'] . '" ' . $selected . '>' . 
                                 htmlspecialchars($cat['productcat_name']) . ' (' . $cat['product_count'] . ')</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div id="productsTableContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Summary Statistics -->
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-center">
                        <i data-lucide="package" style="width:20px;height:20px;" class="text-primary me-2"></i>
                        <div>
                            <small class="text-muted d-block">สินค้าทั้งหมด</small>
                            <strong class="fs-5" id="totalProductCount">
                                <?php
                                $totalCount = $db->query("SELECT COUNT(*) FROM product WHERE product_del = 0")->fetchColumn();
                                echo number_format($totalCount);
                                ?>
                            </strong> <span class="text-muted">รายการ</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" id="filteredProductSection" style="display: none;">
                    <div class="d-flex align-items-center justify-content-center">
                        <i data-lucide="filter" style="width:20px;height:20px;" class="text-success me-2"></i>
                        <div>
                            <small class="text-muted d-block">กรองแสดง</small>
                            <strong class="fs-5 text-success" id="filteredProductCount">0</strong> <span class="text-muted">รายการ</span>
                            <small class="text-muted d-block" id="filteredCategoryName"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Add/Edit Product Modal (Fullscreen) -->
<div class="modal fade" id="productModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalTitle">
                    <i data-lucide="package"></i> <span id="modalTitleText">เพิ่มสินค้า</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-y: auto;">
                <form id="productForm">
                    <input type="hidden" id="productId" name="product_id">
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            
                            <!-- Basic Info -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i data-lucide="info"></i> ข้อมูลพื้นฐาน</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">รหัสสินค้า <span class="text-danger">*</span></label>
                                            <input type="text" id="productCode" name="product_code" class="form-control" readonly required>
                                            <small class="text-muted">รหัสจะถูกสร้างอัตโนมัติ (YP + ID)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Slug (URL)</label>
                                            <input type="text" id="productSlug" name="product_slug" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Multi-language Tabs -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i data-lucide="languages"></i> ข้อมูลหลายภาษา</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="nav nav-tabs mb-3" id="langTabs" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#lang-th" type="button">
                                                🇹🇭 ไทย
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-en" type="button">
                                                🇬🇧 English
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-de" type="button">
                                                🇩🇪 Deutsch
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-fr" type="button">
                                                🇫🇷 Français
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-zh" type="button">
                                                🇨🇳 中文
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lang-ko" type="button">
                                                🇰🇷 한국어
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <!-- Thai -->
                                        <div class="tab-pane fade show active" id="lang-th">
                                            <div class="mb-3">
                                                <label class="form-label">ชื่อสินค้า <span class="text-danger">*</span></label>
                                                <input type="text" name="product_name_th" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">คำอธิบายสั้น</label>
                                                <textarea name="product_excerpt_th" class="form-control" rows="2"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">รายละเอียด</label>
                                                <textarea name="product_detail_th" class="form-control tinymce-editor" rows="4"></textarea>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">หน่วยนับ</label>
                                                    <input type="text" name="product_unit_th" class="form-control" placeholder="กก., ขวด, ชิ้น">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Tags</label>
                                                    <input type="text" name="product_tag_th" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- English -->
                                        <div class="tab-pane fade" id="lang-en">
                                            <div class="mb-3">
                                                <label class="form-label">Product Name</label>
                                                <input type="text" name="product_name_en" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Short Description</label>
                                                <textarea name="product_excerpt_en" class="form-control" rows="2"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Details</label>
                                                <textarea name="product_detail_en" class="form-control tinymce-editor" rows="4"></textarea>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Unit</label>
                                                    <input type="text" name="product_unit_en" class="form-control" placeholder="kg, bottle, piece">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Tags</label>
                                                    <input type="text" name="product_tag_en" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- German -->
                                        <div class="tab-pane fade" id="lang-de">
                                            <div class="mb-3">
                                                <label class="form-label">Produktname</label>
                                                <input type="text" name="product_name_de" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Kurzbeschreibung</label>
                                                <textarea name="product_excerpt_de" class="form-control" rows="2"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Details</label>
                                                <textarea name="product_detail_de" class="form-control tinymce-editor" rows="4"></textarea>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Einheit</label>
                                                    <input type="text" name="product_unit_de" class="form-control" placeholder="kg, Flasche, Stück">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Tags</label>
                                                    <input type="text" name="product_tag_de" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- French -->
                                        <div class="tab-pane fade" id="lang-fr">
                                            <div class="mb-3">
                                                <label class="form-label">Nom du produit</label>
                                                <input type="text" name="product_name_fr" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Description courte</label>
                                                <textarea name="product_excerpt_fr" class="form-control" rows="2"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Détails</label>
                                                <textarea name="product_detail_fr" class="form-control tinymce-editor" rows="4"></textarea>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Unité</label>
                                                    <input type="text" name="product_unit_fr" class="form-control" placeholder="kg, bouteille, pièce">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Tags</label>
                                                    <input type="text" name="product_tag_fr" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chinese -->
                                        <div class="tab-pane fade" id="lang-zh">
                                            <div class="mb-3">
                                                <label class="form-label">产品名称</label>
                                                <input type="text" name="product_name_zh" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">简短描述</label>
                                                <textarea name="product_excerpt_zh" class="form-control" rows="2"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">详细信息</label>
                                                <textarea name="product_detail_zh" class="form-control tinymce-editor" rows="4"></textarea>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">单位</label>
                                                    <input type="text" name="product_unit_zh" class="form-control" placeholder="公斤, 瓶, 件">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">标签</label>
                                                    <input type="text" name="product_tag_zh" class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Korean -->
                                        <div class="tab-pane fade" id="lang-ko">
                                            <div class="mb-3">
                                                <label class="form-label">제품명</label>
                                                <input type="text" name="product_name_ko" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">간단한 설명</label>
                                                <textarea name="product_excerpt_ko" class="form-control" rows="2"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">상세 정보</label>
                                                <textarea name="product_detail_ko" class="form-control tinymce-editor" rows="4"></textarea>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">단위</label>
                                                    <input type="text" name="product_unit_ko" class="form-control" placeholder="kg, 병, 개">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">태그</label>
                                                    <input type="text" name="product_tag_ko" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Attributes -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i data-lucide="sliders"></i> Attributes</h6>
                                </div>
                                <div class="card-body">
                                    <div id="attributeGroupsContainer">
                                        <!-- Will be loaded dynamically -->
                                    </div>
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" id="generateVariants" name="generate_variants" value="1" checked>
                                        <label class="form-check-label" for="generateVariants">
                                            <strong>สร้าง Variants อัตโนมัติ</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-4">
                            
                            <!-- Category & Status -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i data-lucide="folder"></i> หมวดหมู่ & สถานะ</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">หมวดหมู่</label>
                                        <select id="productCategory" name="productcat_id" class="form-select">
                                            <option value="">-- เลือกหมวดหมู่ --</option>
                                            <?php
                                            $stmt = $db->query("SELECT pc.productcat_id, pct.productcat_name 
                                                               FROM productcat pc
                                                               LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = 'th'
                                                               WHERE pc.productcat_del = 0
                                                               ORDER BY pc.productcat_index ASC, pc.productcat_id ASC");
                                            while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<option value="' . $cat['productcat_id'] . '">' . 
                                                     htmlspecialchars($cat['productcat_name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="productStatus" name="product_status" checked>
                                        <label class="form-check-label" for="productStatus">เปิดใช้งาน</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Images -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i data-lucide="image"></i> รูปภาพสินค้า</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Main Product Image -->
                                    <div class="mb-3">
                                        <label class="form-label">รูปหลัก (จะถูก Crop เป็นสี่เหลี่ยมจัตุรัส)</label>
                                        <input type="file" id="mainImageInput" class="form-control" accept="image/*">
                                        <input type="hidden" id="productImageBase64" name="product_image_base64">
                                        <input type="hidden" id="oldProductPicture" name="old_product_picture">
                                        <small class="text-muted">รองรับ: JPG, PNG, GIF (จะแปลงเป็น WebP อัตโนมัติ)</small>
                                    </div>
                                    
                                    <!-- Image Preview & Cropper -->
                                    <div id="imageCropperContainer" style="display: none;">
                                        <div class="mb-2">
                                            <img id="imageToCrop" style="max-width: 100%; display: block;">
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-success" onclick="cropAndSave()">
                                                <i data-lucide="check"></i> ยืนยันการ Crop
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="useOriginalImage()">
                                                <i data-lucide="image"></i> ใช้รูปนี้เลย
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="cancelCrop()">
                                                <i data-lucide="x"></i> ยกเลิก
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Cropped Preview -->
                                    <div id="croppedPreview" style="display: none;">
                                        <label class="form-label">ตัวอย่างรูปที่ Crop แล้ว:</label>
                                        <div class="position-relative" style="max-width: 200px;">
                                            <img id="croppedImage" class="img-thumbnail" style="width: 100%;">
                                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="removeCroppedImage()">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-3">
                                    
                                    <!-- Gallery Images -->
                                    <div class="mb-3">
                                        <label class="form-label">รูป Gallery (หลายรูป)</label>
                                        <input type="file" id="galleryImagesInput" name="gallery_images[]" class="form-control" accept="image/*" multiple>
                                        <small class="text-muted">เลือกได้หลายรูปพร้อมกัน (จะแปลงเป็น WebP อัตโนมัติ)</small>
                                    </div>
                                    
                                    <!-- Gallery Preview -->
                                    <div id="galleryPreview" class="row g-2"></div>
                                </div>
                            </div>

                            <!-- Pricing -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i data-lucide="dollar-sign"></i> ราคา</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Price Tier</label>
                                        <select id="priceTier" name="price_tier_id" class="form-select">
                                            <option value="">ไม่ใช้ (ราคาคงที่)</option>
                                            <?php
                                            $tiers = get_all_price_tiers();
                                            foreach ($tiers as $tier) {
                                                echo '<option value="' . $tier['tier_id'] . '">' . 
                                                     htmlspecialchars($tier['tier_name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div id="fixedPriceSection">
                                        <div class="mb-3">
                                            <label class="form-label">ราคาขาย</label>
                                            <input type="number" id="productPrice" name="product_price" class="form-control" step="0.01" value="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">ราคาปกติ (ขีดฆ่า)</label>
                                            <input type="number" id="productNPrice" name="product_nprice" class="form-control" step="0.01" value="0">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">ต้นทุน</label>
                                        <input type="number" id="productCPrice" name="product_cprice" class="form-control" step="0.01" value="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">น้ำหนัก (กรัม)</label>
                                        <input type="number" id="productWeight" name="product_weight" class="form-control" value="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Stock -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0"><i data-lucide="package"></i> สต๊อก</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">จำนวนสต๊อก</label>
                                        <input type="number" id="productStock" name="product_stock" class="form-control" value="0">
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="stockAlertEnabled" name="stock_alert_enabled">
                                        <label class="form-check-label" for="stockAlertEnabled">เปิดการแจ้งเตือนสต๊อกต่ำ</label>
                                    </div>
                                    <div id="stockAlertSection" style="display: none;">
                                        <label class="form-label">แจ้งเตือนเมื่อสต๊อกต่ำกว่า</label>
                                        <input type="number" id="stockAlertLevel" name="stock_alert_level" class="form-control" value="10">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveProduct()">
                    <i data-lucide="save"></i> บันทึก
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ✅ CRITICAL: Prevent Summernote links from navigating/reloading page
document.addEventListener('click', function(e) {
    // Check if clicked on a link inside Summernote
    if (e.target.tagName === 'A' && e.target.closest('.note-editor')) {
        e.preventDefault(); // Stop navigation
        // Let Summernote handle the rest normally
    }
}, true); // Capture phase

// ✅ CRITICAL: Prevent form submit when clicking inside Summernote
const productForm = document.getElementById('productForm');
if (productForm) {
    productForm.addEventListener('submit', function(e) {
        // Check if submit is allowed
        if (!window.allowFormSubmit) {
            console.log('⛔ Blocked form submit - not from save button');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }
        
        // Reset flag after use
        window.allowFormSubmit = false;
    }, true); // Use capture to catch early
}

// Block any Enter key inside Summernote from submitting form
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.closest('.note-editable')) {
        // Allow Enter in editable area, but stop propagation to form
        e.stopPropagation();
    }
}, true);

// Clear search
function clearSearch() {
    const url = new URL(window.location);
    url.searchParams.delete('search');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Search input - trigger on Enter or after typing stops
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300); // Reduced from 500ms to 300ms for faster response
});

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchTimeout);
        applyFilters();
    }
});

// Initialize summary on page load
const initialCategory = new URLSearchParams(window.location.search).get('category') || '0';
updateProductSummary(initialCategory);

// Category filter - trigger immediately
document.getElementById('categoryFilter').addEventListener('change', function() {
    applyFilters();
});

// Apply filters function
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;
    
    const url = new URL(window.location);
    if (search) {
        url.searchParams.set('search', search);
    } else {
        url.searchParams.delete('search');
    }
    
    if (category && category !== '0') {
        url.searchParams.set('category', category);
    } else {
        url.searchParams.delete('category');
    }
    
    url.searchParams.delete('page'); // Reset to page 1
    window.history.pushState({}, '', url);
    loadProductsTable(1);
    
    // Update summary stats
    updateProductSummary(category);
}

// Update product summary statistics
function updateProductSummary(categoryId) {
    const filteredSection = document.getElementById('filteredProductSection');
    
    if (!categoryId || categoryId === '0') {
        // Show all products
        filteredSection.style.display = 'none';
    } else {
        // Show filtered
        const categorySelect = document.getElementById('categoryFilter');
        const selectedOption = categorySelect.selectedOptions[0];
        const categoryText = selectedOption.textContent;
        
        // Extract count from option text (format: "Category Name (123)")
        const countMatch = categoryText.match(/\((\d+)\)/);
        const count = countMatch ? countMatch[1] : '0';
        
        document.getElementById('filteredProductCount').textContent = count;
        document.getElementById('filteredCategoryName').textContent = categoryText.replace(/\s*\(\d+\)/, '');
        filteredSection.style.display = 'block';
        
        // Reinitialize icons
        if (window.lucide) lucide.createIcons();
    }
}
</script>

<script>
// ... existing script content ...
</script>

<?php 
$extra_scripts = '<script src="' . ADMIN_ASSETS . '/js/products.js' . $ver . '"></script>';
$extra_scripts .= '<script src="' . ADMIN_ASSETS . '/js/image-functions.js' . $ver . '"></script>';
$extra_scripts .= '<script src="' . ADMIN_ASSETS . '/js/product-code-generator.js' . $ver . '"></script>';
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
