<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../includes/functions/product.php';
require_once __DIR__ . '/../../includes/functions/pagination.php';

$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 0;
$sort_col = $_GET['sort_col'] ?? 'default';
$sort_dir = $_GET['sort_dir'] ?? 'ASC';
$limit = 15;

// Get products with gallery count
$limit = (int)$limit;
$offset = (int)(($page - 1) * $limit);

$sql = "
    SELECT p.*, 
           pt.product_name AS translated_name, 
           pt.product_excerpt AS translated_excerpt, 
           pt.product_unit AS translated_unit,
           pc.productcat_id, pct.productcat_name,
           tier.tier_name,
           scent_t.attribute_name AS scent_name,
           formula_t.attribute_name AS formula_name,
           (SELECT COUNT(*) FROM product_images WHERE product_id = p.product_id) as gallery_count
    FROM product p
    LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = 'th'
    LEFT JOIN productcat pc ON p.productcat_id = pc.productcat_id
    LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = 'th'
    LEFT JOIN price_tiers tier ON p.price_tier_id = tier.tier_id
    LEFT JOIN product_attribute_translations scent_t ON p.scent_id = scent_t.attribute_id AND scent_t.lang_code = 'th'
    LEFT JOIN product_attribute_translations formula_t ON p.formula_id = formula_t.attribute_id AND formula_t.lang_code = 'th'
    WHERE p.product_del = 0
";

$params = [];
if (!empty($search)) {
    $sql .= " AND (pt.product_name LIKE ? OR p.product_code LIKE ? OR p.product_id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}
if ($category > 0) {
    $sql .= " AND p.productcat_id = ?";
    $params[] = $category;
}

// Determine ORDER BY
$order_by = "p.productcat_id ASC, p.product_index ASC, p.product_id ASC";
$sort_dir = strtoupper($sort_dir) === 'DESC' ? 'DESC' : 'ASC';

if ($sort_col === 'product_price') {
    $order_by = "p.product_price $sort_dir, p.product_id ASC";
} elseif ($sort_col === 'product_nprice') {
    $order_by = "p.product_nprice $sort_dir, p.product_id ASC";
} elseif ($sort_col === 'product_cprice') {
    $order_by = "p.product_cprice $sort_dir, p.product_id ASC";
} elseif ($sort_col === 'product_stock') {
    $order_by = "p.product_stock $sort_dir, p.product_id ASC";
} elseif ($sort_col === 'product_status') {
    $order_by = "p.product_status $sort_dir, p.product_id ASC";
}

$sql .= " ORDER BY $order_by LIMIT $limit OFFSET $offset";

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

<style>
    /* บังคับให้ Scroll ได้จริงบนมือถือ */
    .force-responsive {
        width: 100% !important;
        overflow-x: auto !important;
        display: block !important;
        -webkit-overflow-scrolling: touch;
        position: relative;
    }
    .product-table {
        min-width: 1100px !important; /* บังคับความกว้างตาราง */
        table-layout: auto !important;
    }
    .product-table td, .product-table th {
        display: table-cell !important; /* ห้ามแตกแถว */
        white-space: nowrap !important;
        padding: 8px 4px !important; /* ลด Gap ระหว่างคอลัมน์ */
    }
    @media (max-width: 768px) {
        .product-img {
            width: 40px !important;
            height: 40px !important;
        }
        .product-name-text {
            max-width: 180px !important;
            white-space: normal !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        /* Pagination Compact on Mobile */
        @media (max-width: 576px) {
            .pagination .page-link {
                padding: 0.25rem 0.4rem !important;
                font-size: 0.7rem !important;
            }
            .pagination-info-text {
                font-size: 0.75rem !important;
                margin-bottom: 5px !important;
                text-align: center;
                width: 100%;
            }
            .pagination-wrapper {
                flex-direction: column !important;
                align-items: center !important;
            }
        }
    }
</style>

<div class="force-responsive bg-white shadow-sm rounded">
    <table class="table table-hover align-middle mb-0 product-table">
        <thead class="table-light">
            <tr>
                <th style="width: 50px;" class="text-center">
                    <i data-lucide="grip-vertical" style="width:16px;height:16px;color:#6c757d;"></i>
                </th>
                <th style="width: 50px;">ID</th>
                <th style="width: 65px;">รูป</th>
                <th>ชื่อสินค้า</th>
                <th style="width: 150px;">สูตร / กลิ่น</th>
                <th style="width: 120px; cursor: pointer;" onclick="toggleSort('product_price')">ราคาขาย <?= $sort_col === 'product_price' ? ($sort_dir === 'ASC' ? '▲' : '▼') : '↕' ?></th>
                <th style="width: 120px; cursor: pointer;" onclick="toggleSort('product_nprice')">ราคาปกติ <?= $sort_col === 'product_nprice' ? ($sort_dir === 'ASC' ? '▲' : '▼') : '↕' ?></th>
                <th style="width: 120px; cursor: pointer;" onclick="toggleSort('product_cprice')">ต้นทุน <?= $sort_col === 'product_cprice' ? ($sort_dir === 'ASC' ? '▲' : '▼') : '↕' ?></th>
                <th style="width: 100px; cursor: pointer;" onclick="toggleSort('product_stock')">สต๊อก <?= $sort_col === 'product_stock' ? ($sort_dir === 'ASC' ? '▲' : '▼') : '↕' ?></th>
                <th style="width: 100px; cursor: pointer;" onclick="toggleSort('product_status')">สถานะ <?= $sort_col === 'product_status' ? ($sort_dir === 'ASC' ? '▲' : '▼') : '↕' ?></th>
                <th style="width: 120px;" class="text-center">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr data-id="<?= $product['product_id'] ?>" class="sortable-row" data-category="<?= $product['productcat_id'] ?? 0 ?>">
                <td class="drag-handle text-center" style="cursor: grab;">
                    <i data-lucide="grip-vertical" style="width:16px;height:16px;color:#6c757d;"></i>
                </td>
                <td><code><?= $product['product_id'] ?></code></td>
                <td>
                    <?php 
                    $displayName = !empty($product['translated_name']) ? $product['translated_name'] : ($product['product_name'] ?? 'ไม่มีชื่อ');
                    $noImageUrl = URL_PATH . '/uploads/products/no-image.png';
                    if (!empty($product['product_picture'])): 
                        $imgUrl = URL_PATH . '/uploads/products/small-' . htmlspecialchars($product['product_picture']);
                        $fullImgUrl = URL_PATH . '/uploads/products/large-' . htmlspecialchars($product['product_picture']);
                    ?>
                        <div class="position-relative" style="display: inline-block;">
                            <img src="<?= $imgUrl ?>" 
                                 alt="<?= htmlspecialchars($displayName) ?>" 
                                 class="img-thumbnail product-img" 
                                 style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;"
                                 onerror="this.src='<?= $noImageUrl ?>'; this.onerror=null;"
                                 onclick="Swal.fire({imageUrl: '<?= $fullImgUrl ?>', imageAlt: '<?= htmlspecialchars($displayName, ENT_QUOTES) ?>', showConfirmButton: false, showCloseButton: true, width: 'auto', padding: '1em'})">
                            
                            <?php if (!empty($product['gallery_count']) && $product['gallery_count'] > 0): ?>
                                <span class="badge bg-primary position-absolute bottom-0 end-0 m-1" 
                                      style="font-size: 10px; padding: 2px 6px;"
                                      title="มีรูป Gallery <?= $product['gallery_count'] ?> รูป">
                                    <i data-lucide="images" style="width: 10px; height: 10px;"></i> <?= $product['gallery_count'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <img src="<?= $noImageUrl ?>" 
                             alt="No Image" 
                             class="img-thumbnail product-img" 
                             style="width: 60px; height: 60px; object-fit: cover; opacity: 0.7;">
                    <?php endif; ?>
                </td>
                <td>
                    <div>
                        <strong class="product-name-text" title="<?= htmlspecialchars($displayName) ?>" style="display: block; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: help;"><?= htmlspecialchars($displayName) ?></strong>
                        <?php if (!empty($product['product_code'])): ?>
                            <small class="text-muted">รหัส: <?= htmlspecialchars($product['product_code']) ?></small>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div class="small fw-bold text-primary"><?= htmlspecialchars($product['formula_name'] ?: '-') ?></div>
                    <div class="text-muted" style="font-size: 0.85rem;"><?= htmlspecialchars($product['scent_name'] ?: '-') ?></div>
                </td>
                <!-- Quick Edit Prices -->
                <td>
                    <input type="number" 
                           class="form-control form-control-sm text-end fw-bold" 
                           value="<?= (int)$product['product_price'] ?>"
                           onchange="quickUpdate(<?= $product['product_id'] ?>, 'product_price', this.value)"
                           style="width: 80px;">
                </td>
                <td>
                    <input type="number" 
                           class="form-control form-control-sm text-end text-muted" 
                           value="<?= (int)$product['product_nprice'] ?>"
                           onchange="quickUpdate(<?= $product['product_id'] ?>, 'product_nprice', this.value)"
                           style="width: 80px;">
                </td>
                <td>
                    <input type="number" 
                           class="form-control form-control-sm text-end text-info" 
                           value="<?= (int)$product['product_cprice'] ?>"
                           onchange="quickUpdate(<?= $product['product_id'] ?>, 'product_cprice', this.value)"
                           style="width: 80px;">
                </td>
                <!-- Quick Edit Stock -->
                <td>
                    <input type="number" 
                           class="form-control form-control-sm text-center <?= ($product['stock_alert_enabled'] && $product['product_stock'] <= $product['stock_alert_level']) ? 'is-invalid text-danger fw-bold' : '' ?>" 
                           value="<?= (int)$product['product_stock'] ?>"
                           onchange="quickUpdate(<?= $product['product_id'] ?>, 'product_stock', this.value)"
                           style="width: 60px;">
                </td>
                <td>
                    <div id="status-container-<?= $product['product_id'] ?>">
                        <?php if ($product['product_status'] == 1): ?>
                            <button type="button" 
                                    class="badge bg-success border-0" 
                                    onclick="toggleStatus(<?= $product['product_id'] ?>, 0)"
                                    style="cursor: pointer;"
                                    title="คลิกเพื่อปิดการใช้งาน">
                                เปิดใช้งาน
                            </button>
                        <?php else: ?>
                            <button type="button" 
                                    class="badge bg-secondary border-0" 
                                    onclick="toggleStatus(<?= $product['product_id'] ?>, 1)"
                                    style="cursor: pointer;"
                                    title="คลิกเพื่อเปิดการใช้งาน">
                                ปิดใช้งาน
                            </button>
                        <?php endif; ?>
                    </div>
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
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-3 gap-2 pagination-wrapper">
    <div class="text-muted pagination-info-text">
        แสดง <?= count($products) ?> จาก <?= number_format($total) ?> รายการ
    </div>
    <nav>
        <ul class="pagination pagination-sm justify-content-end mb-0">
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
                    $limit_pages = 3; // Show only 3 pages on left
                    for ($i = 1; $i <= $limit_pages; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">
                                <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(' . $i . ')">' . $i . '</a>
                              </li>';
                    }
                    echo '<li class="page-item disabled d-none d-sm-inline-block"><span class="page-link">...</span></li>';
                    echo '<li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(' . $totalPages . ')">' . $totalPages . '</a>
                          </li>';
                } elseif ($page > ($totalPages - 3)) {
                    echo '<li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(1)">1</a>
                          </li>';
                    echo '<li class="page-item disabled d-none d-sm-inline-block"><span class="page-link">...</span></li>';
                    for ($i = $totalPages - 2; $i <= $totalPages; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">
                                <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(' . $i . ')">' . $i . '</a>
                              </li>';
                    }
                } else {
                    echo '<li class="page-item">
                            <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(1)">1</a>
                          </li>';
                    echo '<li class="page-item disabled d-none d-sm-inline-block"><span class="page-link">...</span></li>';
                    for ($i = $page - 1; $i <= $page + 1; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">
                                <a class="page-link" href="javascript:void(0)" onclick="loadProductsTable(' . $i . ')">' . $i . '</a>
                              </li>';
                    }
                    echo '<li class="page-item disabled d-none d-sm-inline-block"><span class="page-link">...</span></li>';
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
