<?php
$page_title = "เพิ่มสินค้าใหม่";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../../includes/functions/product.php";
require_once __DIR__ . "/../../includes/functions/attribute.php";
require_once __DIR__ . "/../../includes/functions/price_tier.php";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Create product
        $productData = [
            'product_code' => $_POST['product_code'] ?? '',
            'productcat_id' => $_POST['productcat_id'] ?? null,
            'product_slug' => $_POST['product_slug'] ?? null,
            'product_price' => $_POST['product_price'] ?? 0,
            'product_nprice' => $_POST['product_nprice'] ?? 0,
            'product_cprice' => $_POST['product_cprice'] ?? 0,
            'product_weight' => $_POST['product_weight'] ?? 0,
            'price_tier_id' => $_POST['price_tier_id'] ?? null,
            'product_stock' => $_POST['product_stock'] ?? 0,
            'stock_alert_enabled' => isset($_POST['stock_alert_enabled']) ? 1 : 0,
            'stock_alert_level' => $_POST['stock_alert_level'] ?? 10,
            'product_status' => isset($_POST['product_status']) ? 1 : 0,
            'save_id' => $_SESSION['admin_id'] ?? 0
        ];
        
        $productId = create_product($productData);
        
        // Save translations
        $languages = $db->query("SELECT lang_code FROM languages WHERE lang_status = 1")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($languages as $langCode) {
            if (!empty($_POST["product_name_{$langCode}"])) {
                save_product_translation($productId, $langCode, [
                    'product_name' => $_POST["product_name_{$langCode}"] ?? '',
                    'product_excerpt' => $_POST["product_excerpt_{$langCode}"] ?? '',
                    'product_detail' => $_POST["product_detail_{$langCode}"] ?? '',
                    'product_unit' => $_POST["product_unit_{$langCode}"] ?? '',
                    'product_tag' => $_POST["product_tag_{$langCode}"] ?? '',
                    'seo_title' => $_POST["seo_title_{$langCode}"] ?? '',
                    'seo_description' => $_POST["seo_description_{$langCode}"] ?? ''
                ]);
            }
        }
        
        // Assign attributes
        if (!empty($_POST['attribute_groups'])) {
            foreach ($_POST['attribute_groups'] as $groupId) {
                assign_attribute_to_product($productId, $groupId);
            }
            
            // Generate variants if requested
            if (isset($_POST['generate_variants'])) {
                generate_product_variants($productId, $_POST['product_stock'] ?? 0);
            }
        }
        
        $db->commit();
        
        $_SESSION['success_message'] = 'เพิ่มสินค้าเรียบร้อยแล้ว';
        header('Location: edit.php?id=' . $productId);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Get data for form
$categories = $db->query("SELECT pc.productcat_id, pct.productcat_name 
                         FROM productcat pc
                         LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = 'th'
                         WHERE pc.productcat_del = 0
                         ORDER BY pct.productcat_name")->fetchAll(PDO::FETCH_ASSOC);

$priceTiers = get_all_price_tiers();
$languages = $db->query("SELECT * FROM languages WHERE lang_status = 1 ORDER BY lang_order")->fetchAll(PDO::FETCH_ASSOC);
$attributeGroups = get_attribute_groups('th');

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="plus"></i> เพิ่มสินค้าใหม่</h2>
        <a href="index.php" class="btn btn-outline-secondary">
            <i data-lucide="arrow-left"></i> กลับ
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>เกิดข้อผิดพลาด!</strong> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" id="productForm">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                
                <!-- Basic Info -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i data-lucide="info"></i> ข้อมูลพื้นฐาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">รหัสสินค้า <span class="text-danger">*</span></label>
                                <input type="text" name="product_code" class="form-control" required>
                                <small class="text-muted">เช่น TH1, BALM001</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug (URL)</label>
                                <input type="text" name="product_slug" class="form-control">
                                <small class="text-muted">ใช้สำหรับ URL (ถ้าไม่ระบุจะสร้างอัตโนมัติ)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Translations -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i data-lucide="languages"></i> ข้อมูลหลายภาษา</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <?php foreach ($languages as $i => $lang): ?>
                            <li class="nav-item">
                                <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#lang-<?= $lang['lang_code'] ?>" 
                                        type="button">
                                    <?= $lang['lang_flag'] ?> <?= $lang['lang_name'] ?>
                                </button>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="tab-content">
                            <?php foreach ($languages as $i => $lang): ?>
                            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" 
                                 id="lang-<?= $lang['lang_code'] ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">ชื่อสินค้า <?= $i === 0 ? '<span class="text-danger">*</span>' : '' ?></label>
                                    <input type="text" 
                                           name="product_name_<?= $lang['lang_code'] ?>" 
                                           class="form-control" 
                                           <?= $i === 0 ? 'required' : '' ?>>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">คำอธิบายสั้น</label>
                                    <textarea name="product_excerpt_<?= $lang['lang_code'] ?>" 
                                              class="form-control" 
                                              rows="2"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">รายละเอียด</label>
                                    <textarea name="product_detail_<?= $lang['lang_code'] ?>" 
                                              class="form-control" 
                                              rows="5"></textarea>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">หน่วยนับ</label>
                                        <input type="text" 
                                               name="product_unit_<?= $lang['lang_code'] ?>" 
                                               class="form-control"
                                               placeholder="กก., ขวด, ชิ้น">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tags (คั่นด้วย comma)</label>
                                        <input type="text" 
                                               name="product_tag_<?= $lang['lang_code'] ?>" 
                                               class="form-control">
                                    </div>
                                </div>

                                <hr class="my-3">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">SEO Title</label>
                                        <input type="text" 
                                               name="seo_title_<?= $lang['lang_code'] ?>" 
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SEO Description</label>
                                        <textarea name="seo_description_<?= $lang['lang_code'] ?>" 
                                                  class="form-control" 
                                                  rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Attributes -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i data-lucide="sliders"></i> Attributes (คุณสมบัติสินค้า)</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">เลือก attributes ที่สินค้านี้มี (เช่น สูตร, กลิ่น, ขนาด)</p>
                        
                        <?php foreach ($attributeGroups as $group): ?>
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="attribute_groups[]" 
                                   value="<?= $group['group_id'] ?>" 
                                   id="group_<?= $group['group_id'] ?>">
                            <label class="form-check-label" for="group_<?= $group['group_id'] ?>">
                                <strong><?= htmlspecialchars($group['group_name']) ?></strong>
                                <?php if ($group['group_description']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($group['group_description']) ?></small>
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>

                        <div class="form-check mt-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="generate_variants" 
                                   value="1" 
                                   id="generate_variants"
                                   checked>
                            <label class="form-check-label" for="generate_variants">
                                <strong>สร้าง Variants อัตโนมัติ</strong>
                                <br><small class="text-muted">สร้าง SKU ทุกชุดค่าผสมของ attributes ที่เลือก</small>
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                
                <!-- Category & Status -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i data-lucide="folder"></i> หมวดหมู่ & สถานะ</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">หมวดหมู่</label>
                            <select name="productcat_id" class="form-select">
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['productcat_id'] ?>">
                                    <?= htmlspecialchars($cat['productcat_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="product_status" 
                                   id="product_status"
                                   checked>
                            <label class="form-check-label" for="product_status">
                                เปิดใช้งาน
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i data-lucide="dollar-sign"></i> ราคา</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Price Tier (ราคาขายส่ง)</label>
                            <select name="price_tier_id" class="form-select" id="price_tier_select">
                                <option value="">ไม่ใช้ (ราคาคงที่)</option>
                                <?php foreach ($priceTiers as $tier): ?>
                                <option value="<?= $tier['tier_id'] ?>">
                                    <?= htmlspecialchars($tier['tier_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="fixed_price_section">
                            <div class="mb-3">
                                <label class="form-label">ราคาขาย</label>
                                <input type="number" name="product_price" class="form-control" step="0.01" value="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ราคาปกติ (ขีดฆ่า)</label>
                                <input type="number" name="product_nprice" class="form-control" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ต้นทุน</label>
                            <input type="number" name="product_cprice" class="form-control" step="0.01" value="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">น้ำหนัก (กรัม)</label>
                            <input type="number" name="product_weight" class="form-control" value="0">
                        </div>
                    </div>
                </div>

                <!-- Stock -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i data-lucide="package"></i> สต๊อก</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">จำนวนสต๊อกเริ่มต้น</label>
                            <input type="number" name="product_stock" class="form-control" value="0">
                            <small class="text-muted">ใช้สำหรับ variants ที่สร้างอัตโนมัติ</small>
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="stock_alert_enabled" 
                                   id="stock_alert_enabled">
                            <label class="form-check-label" for="stock_alert_enabled">
                                เปิดการแจ้งเตือนสต๊อกต่ำ
                            </label>
                        </div>

                        <div id="stock_alert_level_section" style="display: none;">
                            <label class="form-label">แจ้งเตือนเมื่อสต๊อกต่ำกว่า</label>
                            <input type="number" name="stock_alert_level" class="form-control" value="10">
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100">
                            <i data-lucide="save"></i> บันทึกสินค้า
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>

</div>

<script>
// Toggle price tier / fixed price
document.getElementById('price_tier_select').addEventListener('change', function() {
    const fixedPriceSection = document.getElementById('fixed_price_section');
    if (this.value) {
        fixedPriceSection.style.display = 'none';
    } else {
        fixedPriceSection.style.display = 'block';
    }
});

// Toggle stock alert level
document.getElementById('stock_alert_enabled').addEventListener('change', function() {
    const section = document.getElementById('stock_alert_level_section');
    section.style.display = this.checked ? 'block' : 'none';
});
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
