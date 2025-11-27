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
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="position-relative">
                        <input type="text" 
                               class="form-control" 
                               name="search" 
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
                    <select name="category" class="form-select" id="categoryFilter">
                        <option value="0">ทุกหมวดหมู่</option>
                        <?php
                        $stmt = $db->query("SELECT pc.productcat_id, pct.productcat_name 
                                           FROM productcat pc
                                           LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = 'th'
                                           WHERE pc.productcat_del = 0
                                           ORDER BY pct.productcat_name");
                        while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($category == $cat['productcat_id']) ? 'selected' : '';
                            echo '<option value="' . $cat['productcat_id'] . '" ' . $selected . '>' . 
                                 htmlspecialchars($cat['productcat_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i data-lucide="search"></i> ค้นหา
                    </button>
                </div>
            </form>
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
                                            <input type="text" id="productCode" name="product_code" class="form-control" required>
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
                                                <textarea name="product_detail_th" class="form-control" rows="4"></textarea>
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
                                                <textarea name="product_detail_en" class="form-control" rows="4"></textarea>
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
                                                <textarea name="product_detail_de" class="form-control" rows="4"></textarea>
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
                                                               ORDER BY pct.productcat_name");
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
function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.querySelector('form').submit();
}
</script>

<!-- Load Products JS -->
<script src="<?= ADMIN_ASSETS ?>/js/products.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
