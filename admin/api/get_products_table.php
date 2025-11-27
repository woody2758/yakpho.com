<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions/product.php';
require_once __DIR__ . '/../../includes/functions/pagination.php';

$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 0;
$limit = 20;

// Get products
$products = get_all_products($page, $limit, $search, $category, 'th');
$total = count_all_products($search, $category, 'th');
$totalPages = ceil($total / $limit);

if (empty($products)) {
    echo '<div class="alert alert-info">ไม่พบสินค้า</div>';
    exit;
}
?>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th style="width: 80px;">ID</th>
                <th style="width: 100px;">รูป</th>
                <th>ชื่อสินค้า</th>
                <th>หมวดหมู่</th>
                <th style="width: 120px;">ราคา</th>
                <th style="width: 100px;">สต๊อก</th>
                <th style="width: 100px;">สถานะ</th>
                <th style="width: 150px;" class="text-center">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><code><?= $product['product_id'] ?></code></td>
                <td>
                    <?php if (!empty($product['product_picture'])): ?>
                        <img src="<?= URL_PATH ?>/uploads/products/<?= htmlspecialchars($product['product_picture']) ?>" 
                             alt="<?= htmlspecialchars($product['product_name']) ?>" 
                             class="img-thumbnail" 
                             style="width: 60px; height: 60px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" 
                             style="width: 60px; height: 60px; border-radius: 4px;">
                            <i data-lucide="image" style="width: 24px; height: 24px; color: #ccc;"></i>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <div>
                        <strong><?= htmlspecialchars($product['product_name'] ?? 'ไม่มีชื่อ') ?></strong>
                        <?php if (!empty($product['product_code'])): ?>
                            <br><small class="text-muted">รหัส: <?= htmlspecialchars($product['product_code']) ?></small>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <?php if (!empty($product['productcat_name'])): ?>
                        <span class="badge bg-secondary"><?= htmlspecialchars($product['productcat_name']) ?></span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($product['price_tier_id']): ?>
                        <span class="badge bg-info" title="<?= htmlspecialchars($product['tier_name']) ?>">
                            <i data-lucide="trending-up" style="width: 12px; height: 12px;"></i> Dynamic
                        </span>
                    <?php else: ?>
                        <strong><?= number_format($product['product_price']) ?></strong> ฿
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $stockClass = 'text-success';
                    if ($product['stock_alert_enabled'] && $product['product_stock'] <= $product['stock_alert_level']) {
                        $stockClass = 'text-danger';
                    }
                    ?>
                    <span class="<?= $stockClass ?>">
                        <strong><?= number_format($product['product_stock']) ?></strong>
                    </span>
                </td>
                <td>
                    <?php if ($product['product_status'] == 1): ?>
                        <span class="badge bg-success">เปิดใช้งาน</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">ปิดใช้งาน</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="edit.php?id=<?= $product['product_id'] ?>" 
                           class="btn btn-outline-primary" 
                           title="แก้ไข">
                            <i data-lucide="edit" style="width: 14px; height: 14px;"></i>
                        </a>
                        <button type="button" 
                                class="btn btn-outline-danger" 
                                onclick="deleteProduct(<?= $product['product_id'] ?>, '<?= addslashes($product['product_name'] ?? '') ?>')"
                                title="ลบ">
                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted">
        แสดง <?= count($products) ?> จาก <?= number_format($total) ?> รายการ
    </div>
    <nav>
        <?= render_pagination($page, $totalPages, 'loadProductsTable') ?>
    </nav>
</div>
<?php endif; ?>
