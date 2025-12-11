<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_id = isset($_GET['status']) ? (int)$_GET['status'] : 0;

    // Base query
    $sql = "SELECT o.*, 
            u.user_name, u.user_lastname, u.user_picture, u.user_mobile,
            s.orsts_detail, s.orsts_color, s.orsts_code,
            t.transcat_name, t.transcat_link,
            (SELECT SUM(subtotal) FROM ordetail WHERE orders_id = o.orders_id) as total_amount,
            (SELECT COUNT(*) FROM orders WHERE user_id = o.user_id) as user_order_count
            FROM orders o
            LEFT JOIN user u ON o.user_id = u.user_id
            LEFT JOIN orsts s ON o.orders_status = s.orsts_id
            LEFT JOIN transcat t ON o.transcat_id = t.transcat_id
            WHERE 1=1";
    
    $params = [];

    if ($search) {
        $cleanSearch = str_replace('C', '', $search); // Remove 'C' prefix if present
        $sql .= " AND (o.orders_no LIKE :search 
                  OR u.user_name LIKE :search 
                  OR u.user_lastname LIKE :search
                  OR o.user_id = :exact_id)"; // Add exact ID match
        $params[':search'] = "%$search%";
        $params[':exact_id'] = $cleanSearch;
    }

    if ($status_id > 0) {
        $sql .= " AND o.orders_status = :status_id";
        $params[':status_id'] = $status_id;
    }

    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $sql .= " AND o.user_id = :user_id";
        $params[':user_id'] = $_GET['user_id'];
    }

    // Count total for pagination
    $countSql = "SELECT COUNT(*) FROM orders o 
                 LEFT JOIN user u ON o.user_id = u.user_id 
                 WHERE 1=1";
    if ($search) {
        $cleanSearch = str_replace('C', '', $search);
        $countSql .= " AND (o.orders_no LIKE :search 
                       OR u.user_name LIKE :search 
                       OR u.user_lastname LIKE :search
                       OR o.user_id = :exact_id)";
        $params[':exact_id'] = $cleanSearch;
    }
    if ($status_id > 0) {
        $countSql .= " AND o.orders_status = :status_id";
    }
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $countSql .= " AND o.user_id = :user_id";
    }

    $stmt = $db->prepare($countSql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Get data
    $sql .= " ORDER BY o.orders_date DESC LIMIT $offset, $limit";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML
    ob_start();
    ?>
    <div class="d-flex flex-column gap-3">
        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $order): 
                // Fetch Items
                $stmt_items = $db->prepare("SELECT d.*, p.product_name, p.product_code, p.product_picture 
                                          FROM ordetail d 
                                          LEFT JOIN product p ON d.product_id = p.product_id 
                                          WHERE d.orders_id = ?");
                $stmt_items->execute([$order['orders_id']]);
                $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

                // Fetch Address
                $stmt_addr = $db->prepare("SELECT a.*, p.name_th as province_name 
                                           FROM addr a 
                                           LEFT JOIN provinces p ON a.provinces_id = p.provinces_id 
                                           WHERE a.addr_id = ?");
                $stmt_addr->execute([$order['addr_id']]);
                $address = $stmt_addr->fetch(PDO::FETCH_ASSOC);

                // Prepare Tracking Link
                $trackingLink = '#';
                if ($order['transcat_link'] && $order['orders_tracking']) {
                    $trackings = explode(',', $order['orders_tracking']);
                    $firstTracking = trim($trackings[0]);
                    $trackingLink = str_replace('xxx', $firstTracking, $order['transcat_link']);
                    if (strpos($order['transcat_link'], 'xxx') === false) {
                         $trackingLink = $order['transcat_link'] . $firstTracking;
                    }
                }

                // Determine Sender Name
                $senderName = 'Shop Default'; // Fallback
                // Fetch Shop Name (Ideally this should be cached or fetched once outside loop, but for now inside is safer for context)
                // To optimize, we can fetch shop name once at top of file, but let's do a quick query here or assume global constant if available.
                // Better: Fetch shop info once at top.
                // Let's assume we use a placeholder for now or fetch it.
                // Actually, let's use the DB query.
                if ($order['addrsender_id'] > 0) {
                    $stmt_sender = $db->prepare("SELECT addr_name FROM addr WHERE addr_id = ?");
                    $stmt_sender->execute([$order['addrsender_id']]);
                    $sender = $stmt_sender->fetch(PDO::FETCH_ASSOC);
                    if ($sender) $senderName = $sender['addr_name'];
                } else {
                    // Fetch Shop Name
                    // We can use a static variable or fetch once.
                    static $shopNameCache = null;
                    if ($shopNameCache === null) {
                        $stmt_shop = $db->prepare("SELECT st.shop_name FROM shop_info s 
                                                 JOIN shop_info_translations st ON s.shop_id = st.shop_id 
                                                 WHERE st.lang_code = 'th' LIMIT 1");
                        $stmt_shop->execute();
                        $shop = $stmt_shop->fetch(PDO::FETCH_ASSOC);
                        $shopNameCache = $shop ? $shop['shop_name'] : 'Shop Default';
                    }
                    $senderName = $shopNameCache;
                }
            ?>
                <div class="card shadow-sm border-0 overflow-hidden">
                    <!-- Header Status Bar -->
                    <div class="card-header py-2 d-flex justify-content-between align-items-center" 
                         style="background-color: <?= $order['orsts_color'] ?>20; border-left: 5px solid <?= $order['orsts_color'] ?>;">
                        <div class="fw-bold" style="color: <?= $order['orsts_color'] ?>;">
                            <i data-lucide="circle-dot" style="width:16px; height:16px; vertical-align:text-bottom;"></i> 
                            <?= htmlspecialchars($order['orsts_detail']) ?>
                        </div>
                        <div class="text-muted small">
                            <i data-lucide="clock" style="width:14px; height:14px;"></i> 
                            <?= date('d/m/Y H:i', strtotime($order['orders_date'])) ?>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="row g-0">
                            <!-- Left: User & Shipping (Col-2) -->
                            <div class="col-md-2 bg-light p-3 border-end text-center d-flex flex-column align-items-center justify-content-start gap-2">
                                <!-- User Image -->
                                <div class="position-relative cursor-pointer" onclick="openUserImageModal(<?= $order['user_id'] ?>, '<?= $order['user_picture'] ?>')">
                                    <?php if(!empty($order['user_picture'])): ?>
                                        <img src="<?= ROOT_URL ?>/uploads/profile/<?= $order['user_id'] ?>/<?= $order['user_picture'] ?>" class="rounded border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded bg-white border shadow-sm d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                            <i data-lucide="user" class="text-muted" style="width: 40px; height: 40px;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="position-absolute bottom-0 end-0 bg-white rounded-circle border p-1" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                                        <i data-lucide="camera" style="width: 14px; height: 14px;"></i>
                                    </div>
                                </div>

                                <!-- Shipping Method -->
                                <button class="btn btn-sm w-100 text-white fw-bold" 
                                        style="background-color: #007bff; font-size: 0.75rem;"
                                        onclick="openShippingModal(<?= $order['orders_id'] ?>, <?= $order['transcat_id'] ?>, '<?= $order['orders_tracking'] ?>')">
                                    <?= $order['transcat_name'] ? $order['transcat_name'] : 'เลือกขนส่ง' ?>
                                </button>

                                <!-- Tracking Number -->
                                <?php if($order['orders_tracking']): ?>
                                    <div class="bg-success text-white rounded px-2 py-1 w-100 small text-truncate" title="<?= $order['orders_tracking'] ?>">
                                        <?= $order['orders_tracking'] ?>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-warning text-dark rounded px-2 py-1 w-100 small">
                                        ยังไม่ระบุเลข
                                    </div>
                                <?php endif; ?>

                                <!-- Copy Button -->
                                <button class="btn btn-sm btn-warning w-100 text-dark fw-bold" style="font-size: 0.75rem;"
                                        onclick="copyOrderInfo(<?= $order['orders_id'] ?>)"
                                        <?= empty($order['orders_tracking']) ? 'disabled title="กรุณาระบุเลขพัสดุก่อน"' : '' ?>>
                                    <i data-lucide="copy" style="width:12px; height:12px;"></i> คัดลอกข้อมูลลูกค้า
                                </button>

                                <!-- Print Status -->
                                <button class="btn btn-sm w-100 fw-bold <?= $order['orders_print'] == 1 ? 'btn-danger' : 'btn-success' ?>" 
                                        style="font-size: 0.75rem;"
                                        onclick="togglePrintStatus(<?= $order['orders_id'] ?>, <?= $order['orders_print'] ?>, this)">
                                    <i data-lucide="printer" style="width:12px; height:12px;"></i> 
                                    <?= $order['orders_print'] == 1 ? 'พิมพ์แล้ว' : 'ยังไม่พิมพ์' ?>
                                </button>

                                <!-- Re-order Button (Moved here) -->
                                <button class="btn btn-sm btn-outline-primary w-100 fw-bold mt-1" style="font-size: 0.75rem;" onclick="reorder(<?= $order['orders_id'] ?>)">
                                    <i data-lucide="shopping-cart" style="width:12px; height:12px;"></i> ซื้อรายการนี้อีกครั้ง
                                </button>

                                <!-- User History -->
                                <div class="text-danger small fw-bold mt-1" style="cursor: pointer;" onclick="filterByCustomer(<?= $order['user_id'] ?>)">
                                    <i data-lucide="heart" style="width:12px; height:12px; fill: red;"></i> 
                                    [ <?= $order['user_order_count'] ?> ]
                                </div>
                            </div>

                            <!-- Center: Order Info & Items (Col-7) -->
                            <div class="col-md-7 p-3 border-end">
                                <!-- Order ID & Copy -->
                                <div class="d-flex align-items-center mb-2">
                                    <span class="fw-bold me-2 fs-5">#<?= $order['orders_no'] ?></span>
                                    <span class="cursor-pointer" style="cursor: pointer;" data-bs-toggle="tooltip" title="คัดลอก" onclick="copyToClipboard('<?= $order['orders_no'] ?>', this)">
                                        <i data-lucide="copy" class="text-muted" style="width:16px; height:16px;"></i>
                                    </span>
                                </div>

                                <!-- Customer Info -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-light text-dark border me-2">C<?= $order['user_id'] ?></span>
                                        <span class="fw-bold text-primary cursor-pointer fs-6" style="cursor: pointer;" onclick="openCustomerModal(<?= $order['user_id'] ?>, <?= $order['orders_id'] ?>)">
                                            <?= $order['user_name'] ?> <?= $order['user_lastname'] ?>
                                            <i data-lucide="pencil" style="width:14px; height:14px;" class="ms-1"></i>
                                        </span>
                                    </div>
                                    <?php if(!empty($order['orders_msg'])): ?>
                                        <div class="alert alert-info py-1 px-2 mt-2 mb-0 small">
                                            <i data-lucide="message-square" style="width:12px; height:12px;" class="me-1"></i>
                                            <?= htmlspecialchars($order['orders_msg']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Addresses Row -->
                                <div class="row g-2 mb-3">
                                    <!-- Sender -->
                                    <div class="col-md-6">
                                        <div class="p-2 bg-light rounded border position-relative h-100">
                                            <small class="text-muted d-block mb-1">ผู้ส่ง (Sender):</small>
                                            <div class="d-flex justify-content-between align-items-start">
                                                <span style="font-size: 0.85rem;">
                                                    <i data-lucide="store" class="me-1" style="width:12px; height:12px;"></i>
                                                    <?= htmlspecialchars($senderName) ?>
                                                </span>
                                                <span class="cursor-pointer" style="cursor: pointer;" data-bs-toggle="tooltip" title="เปลี่ยนผู้ส่ง" onclick="changeSender(<?= $order['orders_id'] ?>, <?= $order['user_id'] ?>)">
                                                    <i data-lucide="refresh-cw" class="text-muted" style="width:14px; height:14px;"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Receiver -->
                                    <div class="col-md-6">
                                        <div class="p-2 bg-light rounded border position-relative h-100">
                                            <small class="text-muted d-block mb-1">ผู้รับ (Receiver):</small>
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div style="font-size: 0.85rem; line-height: 1.4;">
                                                    <?php if($address): ?>
                                                        <strong><?= $address['addr_name'] ?></strong><br>
                                                        <?= $address['addr_detail'] ?> 
                                                        <?= $address['addr_detail2'] ? $address['addr_detail2'] : '' ?>
                                                        <?= isset($address['province_name']) ? $address['province_name'] : '' ?> <?= $address['addr_postcode'] ?><br>
                                                        Tel: <?= $address['addr_mobile'] ?>
                                                    <?php else: ?>
                                                        - ไม่พบที่อยู่ -
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <span class="cursor-pointer" style="cursor: pointer;" data-bs-toggle="tooltip" title="แก้ไขที่อยู่" onclick="editAddress(<?= $order['orders_id'] ?>, <?= $order['user_id'] ?>)">
                                                        <i data-lucide="edit-2" class="text-primary" style="width:14px; height:14px;"></i>
                                                    </span>
                                                    <span class="cursor-pointer" style="cursor: pointer;" data-bs-toggle="tooltip" title="เปลี่ยนที่อยู่จัดส่ง" onclick="changeAddress(<?= $order['orders_id'] ?>, <?= $order['user_id'] ?>)">
                                                        <i data-lucide="map-pin" class="text-success" style="width:14px; height:14px;"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Table -->
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tbody>
                                            <?php foreach ($items as $item): 
                                                $img = $item['product_picture'] ? ROOT_URL . "/uploads/products/small-" . $item['product_picture'] : ADMIN_ASSETS . "/images/placeholder.png";
                                            ?>
                                                <tr>
                                                    <td style="width: 50px;">
                                                        <img src="<?= $img ?>" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-truncate" style="max-width: 250px;"><?= htmlspecialchars($item['product_name']) ?></div>
                                                        <div class="text-muted"><?= htmlspecialchars($item['product_code']) ?></div>
                                                        <?php if(!empty($item['ordetail_memo'])): ?>
                                                            <div class="text-muted small fst-italic"><i data-lucide="sticky-note" style="width:10px; height:10px;"></i> <?= htmlspecialchars($item['ordetail_memo']) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">x <?= $item['ordetail_qty'] ?></td>
                                                    <td class="text-end"><?= number_format((float)($item['product_price'] * $item['ordetail_qty']), 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Re-order Button (Removed from here) -->
                            </div>

                            <!-- Right: Totals & Actions (Col-3) -->
                            <div class="col-md-3 p-3 bg-light d-flex flex-column justify-content-between">
                                <div class="small">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">น้ำหนัก:</span>
                                        <span><?= number_format((float)$order['orders_weight']) ?> g</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">ยอดรวม:</span>
                                        <span><?= number_format((float)$order['orders_total'], 2) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">ส่วนลด:</span>
                                        <span class="text-danger">-<?= number_format((float)$order['orders_discount'], 2) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">ค่าส่ง:</span>
                                        <span><?= number_format((float)$order['orders_delicost'], 2) ?></span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between fw-bold fs-6">
                                        <span>สุทธิ:</span>
                                        <span class="text-primary"><?= number_format((float)$order['orders_grandtotal'], 2) ?></span>
                                    </div>
                                </div>

                                <!-- Payment Status -->
                                <div class="mt-2">
                                    <?php 
                                    // Fetch Payment Info
                                    $stmt_pay = $db->prepare("SELECT * FROM pay WHERE orders_id = ? ORDER BY pay_id DESC LIMIT 1");
                                    $stmt_pay->execute([$order['orders_id']]);
                                    $payment = $stmt_pay->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    
                                    <?php if ($order['orders_cod'] > 0): ?>
                                        <div class="badge bg-warning text-dark w-100 py-2">
                                            <i data-lucide="banknote" style="width:14px; height:14px;"></i> เก็บเงินปลายทาง (COD)
                                        </div>
                                    <?php elseif ($payment && !empty($payment['pay_picture'])): ?>
                                        <a href="<?= ROOT_URL ?>/uploads/payment/<?= $payment['pay_picture'] ?>" target="_blank" class="btn btn-sm btn-outline-info w-100">
                                            <i data-lucide="file-text" style="width:14px; height:14px;"></i> ดูสลิปโอนเงิน
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-3 d-grid gap-2">
                                    <button onclick="viewOrder(<?= $order['orders_id'] ?>)" class="btn btn-primary btn-sm">
                                        <i data-lucide="eye" style="width:14px; height:14px;"></i> รายละเอียด / จัดการ
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5 text-muted bg-white rounded shadow-sm">
                <i data-lucide="inbox" class="mx-auto mb-2" style="width: 48px; height: 48px; opacity: 0.5;"></i>
                <p class="mb-0 fs-5">ไม่พบรายการสั่งซื้อ</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $tableHtml = ob_get_clean();

    // Generate Pagination
    ob_start();
    if ($totalPages > 1):
        $range = 2; // Number of pages to show around current page
        $showFirst = $page > ($range + 2);
        $showLast = $page < ($totalPages - ($range + 1));
    ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-end mb-0">
            <!-- Previous -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="javascript:void(0)" onclick="loadOrderTable(<?= $page - 1 ?>)" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <!-- First Page -->
            <?php if ($showFirst): ?>
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" onclick="loadOrderTable(1)">1</a>
                </li>
                <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)): ?>
                    <?php if ($showFirst && $i == 1) continue; ?>
                    <?php if ($showLast && $i == $totalPages) continue; ?>
                    
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="javascript:void(0)" onclick="loadOrderTable(<?= $i ?>)"><?= $i ?></a>
                    </li>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Last Page -->
            <?php if ($showLast): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" onclick="loadOrderTable(<?= $totalPages ?>)"><?= $totalPages ?></a>
                </li>
            <?php endif; ?>

            <!-- Next -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="javascript:void(0)" onclick="loadOrderTable(<?= $page + 1 ?>)" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
    <?php
    endif;
    $paginationHtml = ob_get_clean();

    // Get Status Counts for Dashboard
    $statusCounts = [];
    $stmt = $db->query("SELECT orders_status as orsts_id, COUNT(*) as count FROM orders GROUP BY orders_status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $statusCounts[$row['orsts_id']] = $row['count'];
    }

    echo json_encode([
        'success' => true,
        'table' => $tableHtml,
        'pagination' => $paginationHtml,
        'statusCounts' => $statusCounts
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
