<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions/product.php';
require_once __DIR__ . '/../../includes/functions/pagination.php';

$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 0;
$limit = 20;

// Get products with gallery count
$limit = (int)$limit;
$offset = (int)(($page - 1) * $limit);

$sql = "
    SELECT p.*, pt.product_name, pt.product_excerpt, pt.product_unit,
           pc.productcat_id, pct.productcat_name,
           tier.tier_name,
           COUNT(DISTINCT pi.image_id) as gallery_count
    FROM product p
    LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = 'th'
    LEFT JOIN productcat pc ON p.productcat_id = pc.productcat_id
    LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = 'th'
    LEFT JOIN price_tiers tier ON p.price_tier_id = tier.tier_id
    LEFT JOIN product_images pi ON p.product_id = pi.product_id
    WHERE p.product_del = 0
";

$params = [];
if (!empty($search)) {
    $sql .= " AND (pt.product_name LIKE ? OR p.product_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category > 0) {
    $sql .= " AND p.productcat_id = ?";
    $params[] = $category;
}

$sql .= " GROUP BY p.product_id ORDER BY p.product_id DESC LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <div class="position-relative" style="display: inline-block;">
                            <img src="<?= URL_PATH ?>/uploads/products/small-<?= htmlspecialchars($product['product_picture']) ?>" 
                                 alt="<?= htmlspecialchars($product['product_name']) ?>" 
                                 class="img-thumbnail" 
                                 style="width: 60px; height: 60px; object-fit: cover;">
                            
                            <?php if (!empty($product['gallery_count']) && $product['gallery_count'] > 0): ?>
                                <span class="badge bg-primary position-absolute bottom-0 end-0 m-1" 
                                      style="font-size: 10px; padding: 2px 6px;"
                                      title="มีรูป Gallery <?= $product['gallery_count'] ?> รูป">
                                    <i data-lucide="images" style="width: 10px; height: 10px;"></i> <?= $product['gallery_count'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
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
                        <?php if (!empty($product['productcat_name'])): ?>
                            <br><span class="badge bg-secondary"><?= htmlspecialchars($product['productcat_name']) ?></span>
                        <?php endif; ?>
                    </div>
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
                        <button type="button"
                                class="btn btn-outline-primary" 
                                onclick="editProduct(<?= $product['product_id'] ?>)"
                                title="แก้ไข">
                            <i data-lucide="edit" style="width: 14px; height: 14px;"></i>
                        </button>
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
        <ul class="pagination justify-content-end mb-0">
            <!-- Previous -->
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(<?= $page - 1 ?>)">Prev</a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Prev</span>
            </li>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php
            $adjacents = 2;
            if ($totalPages <= 7) {
                for ($i = 1; $i <= $totalPages; $i++) {
                    $active = ($i == $page) ? 'active' : '';
                    echo '<li class="page-item ' . $active . '">
                            <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(' . $i . ')">' . $i . '</a>
                          </li>';
                }
            } else {
                if ($page < 4) {
                    for ($i = 1; $i <= 5; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">
                                <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(' . $i . ')">' . $i . '</a>
                              </li>';
                    }
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    echo '<li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(' . $totalPages . ')">' . $totalPages . '</a>
                          </li>';
                } elseif ($page > ($totalPages - 3)) {
                    echo '<li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(1)">1</a>
                          </li>';
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    for ($i = $totalPages - 4; $i <= $totalPages; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">
                                <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(' . $i . ')">' . $i . '</a>
                              </li>';
                    }
                } else {
                    echo '<li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(1)">1</a>
                          </li>';
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    for ($i = $page - 1; $i <= $page + 1; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">
                                <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(' . $i . ')">' . $i . '</a>
                              </li>';
                    }
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    echo '<li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(' . $totalPages . ')">' . $totalPages . '</a>
                          </li>';
                }
            }
            ?>

            <!-- Next -->
            <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(<?= $page + 1 ?>)">Next</a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Next</span>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>
