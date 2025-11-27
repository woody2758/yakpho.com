<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions/product.php';
require_once __DIR__ . '/../../includes/functions/attribute.php';
require_once __DIR__ . '/../../includes/functions/price_tier.php';

$productId = $_GET['id'] ?? 0;

if (!$productId) {
    header('Location: index.php');
    exit;
}

// Get product
$product = get_product_by_id($productId, 'th');
if (!$product) {
    $_SESSION['error_message'] = 'ไม่พบสินค้า';
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Update product
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
            'update_id' => $_SESSION['admin_id'] ?? 0
        ];
        
        update_product($productId, $productData);
        
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
        
        $db->commit();
        
        $_SESSION['success_message'] = 'บันทึกข้อมูลเรียบร้อยแล้ว';
        header('Location: edit.php?id=' . $productId);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Get data
$translations = get_product_translations($productId);
$categories = $db->query("SELECT pc.productcat_id, pct.productcat_name 
                         FROM productcat pc
                         LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = 'th'
                         WHERE pc.productcat_del = 0
                         ORDER BY pct.productcat_name")->fetchAll(PDO::FETCH_ASSOC);
$priceTiers = get_all_price_tiers();
$languages = $db->query("SELECT * FROM languages WHERE lang_status = 1 ORDER BY lang_order")->fetchAll(PDO::FETCH_ASSOC);
$attributeSets = get_product_attribute_sets($productId, 'th');
$variants = get_product_variants($productId, 'th');

// Convert translations to array for easy access
$translationsData = [];
foreach ($translations as $trans) {
    $translationsData[$trans['lang_code']] = $trans;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i data-lucide="edit"></i> แก้ไขสินค้า #<?= $productId ?></h1>
                <div class="btn-toolbar">
                    <a href="index.php" class="btn btn-outline-secondary me-2">
                        <i data-lucide="arrow-left"></i> กลับ
                    </a>
                    <a href="add.php" class="btn btn-outline-primary">
                        <i data-lucide="plus"></i> เพิ่มสินค้าใหม่
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>เกิดข้อผิดพลาด!</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">
                        <i data-lucide="info"></i> ข้อมูลพื้นฐาน
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-variants" type="button">
                        <i data-lucide="layers"></i> Variants 
                        <?php if (count($variants) > 0): ?>
                        <span class="badge bg-primary"><?= count($variants) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                
                <!-- Basic Info Tab -->
                <div class="tab-pane fade show active" id="tab-basic">
                    <form method="POST" id="productForm">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-lg-8">
                                
                                <!-- Basic Info -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">ข้อมูลพื้นฐาน</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">รหัสสินค้า <span class="text-danger">*</span></label>
                                                <input type="text" name="product_code" class="form-control" 
                                                       value="<?= htmlspecialchars($product['product_code']) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Slug (URL)</label>
                                                <input type="text" name="product_slug" class="form-control"
                                                       value="<?= htmlspecialchars($product['product_slug'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Translations -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">ข้อมูลหลายภาษา</h5>
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
                                            <?php foreach ($languages as $i => $lang): 
                                                $trans = $translationsData[$lang['lang_code']] ?? [];
                                            ?>
                                            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" 
                                                 id="lang-<?= $lang['lang_code'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">ชื่อสินค้า</label>
                                                    <input type="text" 
                                                           name="product_name_<?= $lang['lang_code'] ?>" 
                                                           class="form-control"
                                                           value="<?= htmlspecialchars($trans['product_name'] ?? '') ?>">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">คำอธิบายสั้น</label>
                                                    <textarea name="product_excerpt_<?= $lang['lang_code'] ?>" 
                                                              class="form-control" 
                                                              rows="2"><?= htmlspecialchars($trans['product_excerpt'] ?? '') ?></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">รายละเอียด</label>
                                                    <textarea name="product_detail_<?= $lang['lang_code'] ?>" 
                                                              class="form-control" 
                                                              rows="5"><?= htmlspecialchars($trans['product_detail'] ?? '') ?></textarea>
                                                </div>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">หน่วยนับ</label>
                                                        <input type="text" 
                                                               name="product_unit_<?= $lang['lang_code'] ?>" 
                                                               class="form-control"
                                                               value="<?= htmlspecialchars($trans['product_unit'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Tags</label>
                                                        <input type="text" 
                                                               name="product_tag_<?= $lang['lang_code'] ?>" 
                                                               class="form-control"
                                                               value="<?= htmlspecialchars($trans['product_tag'] ?? '') ?>">
                                                    </div>
                                                </div>

                                                <hr class="my-3">

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">SEO Title</label>
                                                        <input type="text" 
                                                               name="seo_title_<?= $lang['lang_code'] ?>" 
                                                               class="form-control"
                                                               value="<?= htmlspecialchars($trans['seo_title'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">SEO Description</label>
                                                        <textarea name="seo_description_<?= $lang['lang_code'] ?>" 
                                                                  class="form-control" 
                                                                  rows="2"><?= htmlspecialchars($trans['seo_description'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Right Column -->
                            <div class="col-lg-4">
                                
                                <!-- Category & Status -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">หมวดหมู่ & สถานะ</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">หมวดหมู่</label>
                                            <select name="productcat_id" class="form-select">
                                                <option value="">-- เลือกหมวดหมู่ --</option>
                                                <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['productcat_id'] ?>"
                                                        <?= $product['productcat_id'] == $cat['productcat_id'] ? 'selected' : '' ?>>
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
                                                   <?= $product['product_status'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="product_status">
                                                เปิดใช้งาน
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pricing -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">ราคา</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Price Tier</label>
                                            <select name="price_tier_id" class="form-select" id="price_tier_select">
                                                <option value="">ไม่ใช้ (ราคาคงที่)</option>
                                                <?php foreach ($priceTiers as $tier): ?>
                                                <option value="<?= $tier['tier_id'] ?>"
                                                        <?= $product['price_tier_id'] == $tier['tier_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tier['tier_name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div id="fixed_price_section" style="<?= $product['price_tier_id'] ? 'display:none' : '' ?>">
                                            <div class="mb-3">
                                                <label class="form-label">ราคาขาย</label>
                                                <input type="number" name="product_price" class="form-control" step="0.01" 
                                                       value="<?= $product['product_price'] ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">ราคาปกติ (ขีดฆ่า)</label>
                                                <input type="number" name="product_nprice" class="form-control" step="0.01" 
                                                       value="<?= $product['product_nprice'] ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">ต้นทุน</label>
                                            <input type="number" name="product_cprice" class="form-control" step="0.01" 
                                                   value="<?= $product['product_cprice'] ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">น้ำหนัก (กรัม)</label>
                                            <input type="number" name="product_weight" class="form-control" 
                                                   value="<?= $product['product_weight'] ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Stock -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">สต๊อก</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">จำนวนสต๊อก</label>
                                            <input type="number" name="product_stock" class="form-control" 
                                                   value="<?= $product['product_stock'] ?>">
                                        </div>

                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="stock_alert_enabled" 
                                                   id="stock_alert_enabled"
                                                   <?= $product['stock_alert_enabled'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="stock_alert_enabled">
                                                เปิดการแจ้งเตือนสต๊อกต่ำ
                                            </label>
                                        </div>

                                        <div id="stock_alert_level_section" style="<?= $product['stock_alert_enabled'] ? '' : 'display:none' ?>">
                                            <label class="form-label">แจ้งเตือนเมื่อสต๊อกต่ำกว่า</label>
                                            <input type="number" name="stock_alert_level" class="form-control" 
                                                   value="<?= $product['stock_alert_level'] ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <div class="card">
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i data-lucide="save"></i> บันทึกการแก้ไข
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </form>
                </div>

                <!-- Variants Tab -->
                <div class="tab-pane fade" id="tab-variants">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">จัดการ Variants</h5>
                            <button type="button" class="btn btn-sm btn-primary" onclick="generateVariants()">
                                <i data-lucide="zap"></i> สร้าง Variants อัตโนมัติ
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($attributeSets)): ?>
                            <div class="alert alert-info">
                                <strong>Attributes ที่กำหนด:</strong>
                                <?php foreach ($attributeSets as $set): ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($set['group_name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <div id="variantsContainer">
                                <?php if (empty($variants)): ?>
                                <div class="text-center text-muted py-5">
                                    <i data-lucide="package" style="width: 48px; height: 48px;"></i>
                                    <p class="mt-3">ยังไม่มี Variants<br>คลิกปุ่ม "สร้าง Variants อัตโนมัติ" เพื่อเริ่มต้น</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>SKU</th>
                                                <th>Formula</th>
                                                <th>Scent</th>
                                                <th>Stock</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($variants as $variant): ?>
                                            <tr>
                                                <td><code><?= htmlspecialchars($variant['variant_sku']) ?></code></td>
                                                <td><?= htmlspecialchars($variant['formula_name'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($variant['scent_name'] ?? '-') ?></td>
                                                <td>
                                                    <input type="number" 
                                                           class="form-control form-control-sm" 
                                                           value="<?= $variant['variant_stock'] ?>"
                                                           style="width: 80px;"
                                                           onchange="updateVariantStock(<?= $variant['variant_id'] ?>, this.value)">
                                                </td>
                                                <td>
                                                    <?php if ($variant['variant_status']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteVariant(<?= $variant['variant_id'] ?>)">
                                                        <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<script>
// Toggle price tier / fixed price
document.getElementById('price_tier_select').addEventListener('change', function() {
    const fixedPriceSection = document.getElementById('fixed_price_section');
    fixedPriceSection.style.display = this.value ? 'none' : 'block';
});

// Toggle stock alert level
document.getElementById('stock_alert_enabled').addEventListener('change', function() {
    const section = document.getElementById('stock_alert_level_section');
    section.style.display = this.checked ? 'block' : 'none';
});

// Generate variants
function generateVariants() {
    if (!confirm('สร้าง Variants อัตโนมัติจาก Attributes ที่กำหนด?')) return;
    
    fetch('../api/generate_variants.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({product_id: <?= $productId ?>})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`สร้าง Variants สำเร็จ ${data.count} รายการ`);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    });
}

// Update variant stock
function updateVariantStock(variantId, newStock) {
    fetch('../api/update_variant_stock.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({variant_id: variantId, stock: newStock})
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    });
}

// Delete variant
function deleteVariant(variantId) {
    if (!confirm('ต้องการลบ Variant นี้?')) return;
    
    fetch('../api/delete_variant.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({variant_id: variantId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
