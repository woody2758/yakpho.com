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

    // --- Auto-Fix: ตรวจสอบและเพิ่มคอลัมน์ track_token ถ้ายังไม่มี ---
    try {
        $db->query("SELECT track_token FROM orders LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE orders ADD COLUMN track_token VARCHAR(64) UNIQUE AFTER orders_no");
    }

    // --- Auto-Fix: ตรวจสอบและเพิ่มคอลัมน์ orders_bill ถ้ายังไม่มี ---
    try {
        $db->query("SELECT orders_bill FROM orders LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE orders ADD COLUMN orders_bill INT DEFAULT 1 AFTER orders_print");
    }
    // --------------------------------------------------------

    // --- Auto-Fix: Add user_stars column
    try {
        $db->query("SELECT user_stars FROM user LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE user ADD COLUMN user_stars INT DEFAULT 0 AFTER user_lastname");
    }

    // --- Auto-Fix: Migrate orsts_index to INT and ensure orsts_status exists ---
    try {
        $db->exec("ALTER TABLE orsts MODIFY COLUMN orsts_index INT DEFAULT 0");
    } catch (Exception $e) { }
    try {
        $db->query("SELECT orsts_status FROM orsts LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE orsts ADD COLUMN orsts_status INT DEFAULT 1 AFTER orsts_del");
    }
    // --------------------------------------------------------

    // Fetch all statuses for quick dropdown (Hide if disabled in Admin, hide if soft deleted)
    $all_statuses = $db->query("SELECT * FROM orsts WHERE orsts_del = 0 AND orsts_status = 1 ORDER BY orsts_index ASC")->fetchAll(PDO::FETCH_ASSOC);

    // --- OPTIMIZATION: Late Row Lookups & Single Card Refresh ---
    $single_order_id = isset($_GET['single_order_id']) ? (int)$_GET['single_order_id'] : 0;
    $is_single_card = ($single_order_id > 0);

    if ($is_single_card) {
        $orderIds = [$single_order_id];
        $totalRecords = 1;
        $totalPages = 1;
    } else {
        // 1. Build the base conditions for counting and ID fetching
        $where = " WHERE 1=1";
        $joins = " LEFT JOIN user u ON o.user_id = u.user_id LEFT JOIN addr a ON o.addr_id = a.addr_id";
        $params = [];

        if ($search !== '') {
            $searchConds = [
                "o.orders_no LIKE :search",
                "o.orders_tracking LIKE :search",
                "u.user_name LIKE :search",
                "u.user_lastname LIKE :search",
                "CONCAT(COALESCE(u.user_name,''), ' ', COALESCE(u.user_lastname,'')) LIKE :search",
                "u.user_mobile LIKE :search",
                "u.user_tel LIKE :search",
                "u.user_email LIKE :search",
                "a.addr_name LIKE :search",
                "a.addr_mobile LIKE :search",
                "a.addr_detail LIKE :search"
            ];
            $params[':search'] = "%{$search}%";

            // If query contains customer ID (e.g. C7221, c7221, 7221)
            $cleanDigits = preg_replace('/[^0-9]/', '', $search);
            if ($cleanDigits !== '' && is_numeric($cleanDigits)) {
                $searchConds[] = "o.user_id = :exact_uid";
                $params[':exact_uid'] = (int)$cleanDigits;
            }

            $where .= " AND (" . implode(" OR ", $searchConds) . ")";
        }

        if ($status_id > 0 || (isset($_GET['status']) && $_GET['status'] === 'cancelled')) {
            if (isset($_GET['status']) && $_GET['status'] === 'cancelled') {
                $where .= " AND o.orders_status IN (8, 9)";
            } else {
                $where .= " AND o.orders_status = :status_id";
                $params[':status_id'] = $status_id;
            }
        }

        if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
            $where .= " AND o.user_id = :user_id";
            $params[':user_id'] = $_GET['user_id'];
        }

        if (isset($_GET['payment']) && $_GET['payment'] === 'cod') {
            $where .= " AND o.paycat_id = 2";
        }

        // 2. Count total for pagination
        $countSql = "SELECT COUNT(*) FROM orders o $joins $where";
        $stmt = $db->prepare($countSql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $totalRecords = $stmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // 3. Fetch only the IDs for the current page
        $idSql = "SELECT o.orders_id FROM orders o $joins $where ORDER BY o.orders_date DESC LIMIT $offset, $limit";
        $stmt = $db->prepare($idSql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $orderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // 4. Fetch the full row data only for those IDs
    if (empty($orderIds)) {
        $orders = [];
    } else {
        $inPlaceholders = str_repeat('?,', count($orderIds) - 1) . '?';
        
        $sql = "SELECT o.*, 
                u.user_name, u.user_lastname, u.user_picture, u.user_mobile, u.user_stars, u.user_vip, u.user_note, u.member_tier,
                s.orsts_detail, s.orsts_color, s.orsts_code,
                t.transcat_name, t.transcat_link,
                c.country_name, c.country_code,
                (SELECT SUM(subtotal) FROM ordetail WHERE orders_id = o.orders_id) as total_amount,
                (SELECT COUNT(*) FROM orders WHERE user_id = o.user_id) as user_order_count,
                (SELECT COUNT(*) FROM order_notes WHERE orders_id = o.orders_id) as notes_count
                FROM orders o
                LEFT JOIN user u ON o.user_id = u.user_id
                LEFT JOIN orsts s ON o.orders_status = s.orsts_id
                LEFT JOIN transcat t ON o.transcat_id = t.transcat_id
                LEFT JOIN countries c ON o.orders_country_id = c.country_id
                WHERE o.orders_id IN ($inPlaceholders)
                ORDER BY FIELD(o.orders_id, " . implode(',', $orderIds) . ")";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($orderIds);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ensure tokens exist for all fetched orders
    foreach ($orders as &$o) {
        if (empty($o['track_token'])) {
            $token = bin2hex(random_bytes(16));
            $db->prepare("UPDATE orders SET track_token = ? WHERE orders_id = ?")->execute([$token, $o['orders_id']]);
            $o['track_token'] = $token;
        }
    }
    unset($o);

    // Pre-fetch all items for the current page orders
    $itemsByOrder = [];
    if (!empty($orderIds)) {
        $inPlaceholders = str_repeat('?,', count($orderIds) - 1) . '?';
        $stmt_all_items = $db->prepare("SELECT d.*, COALESCE(pt.product_name, p.product_name, 'Unknown') as product_name, p.product_code, p.product_picture 
                                        FROM ordetail d 
                                        LEFT JOIN product p ON d.product_id = p.product_id 
                                        LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = 'th'
                                        WHERE d.orders_id IN ($inPlaceholders)");
        $stmt_all_items->execute($orderIds);
        $all_items = $stmt_all_items->fetchAll(PDO::FETCH_ASSOC);
        foreach ($all_items as $itm) {
            $itemsByOrder[$itm['orders_id']][] = $itm;
        }
    }

    // Pre-fetch all addresses for the current page orders
    $addressesById = [];
    $addrIdsToFetch = [];
    $senderAddrIdsToFetch = [];
    foreach ($orders as $o) {
        if ($o['addr_id'] > 0) $addrIdsToFetch[] = $o['addr_id'];
        if ($o['addrsender_id'] > 0) $senderAddrIdsToFetch[] = $o['addrsender_id'];
    }
    
    $allAddrIds = array_unique(array_merge($addrIdsToFetch, $senderAddrIdsToFetch));
    if (!empty($allAddrIds)) {
        $inPlaceholdersAddr = str_repeat('?,', count($allAddrIds) - 1) . '?';
        $stmt_all_addr = $db->prepare("SELECT a.*, p.name_th as province_name 
                                       FROM addr a 
                                       LEFT JOIN provinces p ON a.provinces_id = p.provinces_id 
                                       WHERE a.addr_id IN ($inPlaceholdersAddr)");
        $stmt_all_addr->execute(array_values($allAddrIds));
        $all_addresses = $stmt_all_addr->fetchAll(PDO::FETCH_ASSOC);
        foreach ($all_addresses as $addr) {
            $addressesById[$addr['addr_id']] = $addr;
        }
    }

    // Pre-fetch Shop Name (cached once)
    $stmt_shop = $db->prepare("SELECT st.shop_name FROM shop_info s 
                               JOIN shop_info_translations st ON s.shop_id = st.shop_id 
                               WHERE st.lang_code = 'th' LIMIT 1");
    $stmt_shop->execute();
    $shop = $stmt_shop->fetch(PDO::FETCH_ASSOC);
    $defaultShopName = $shop ? $shop['shop_name'] : 'Shop Default';

    // Generate HTML
    ob_start();
    if (!$is_single_card):
    ?>
    <style>
    .order-msg-alert {
        background-color: #fff3cd !important;
        color: #856404 !important;
        border-color: #ffe8a1 !important;
    }
    .order-msg-alert .lucide {
        color: #856404 !important;
    }
    [data-bs-theme="dark"] .order-msg-alert,
    body.dark-mode .order-msg-alert {
        background-color: rgba(255, 193, 7, 0.15) !important;
        color: #ffda6a !important;
        border-color: rgba(255, 193, 7, 0.3) !important;
    }
    [data-bs-theme="dark"] .order-msg-alert .lucide,
    body.dark-mode .order-msg-alert .lucide {
        color: #ffda6a !important;
    }
    </style>
    <!-- Bulk Actions Header -->
    <div id="bulkActions" class="bg-white p-3 rounded shadow-sm border mb-3 d-none">
        <div class="d-flex justify-content-between align-items-center">
            <div class="fw-bold text-primary">
                เลือกแล้ว <span id="selectedCount">0</span> รายการ
            </div>
            <div class="d-flex gap-2">
                <button onclick="printSelectedOrders()" class="btn btn-dark btn-sm">
                    <i data-lucide="printer" class="me-1" style="width:14px; height:14px;"></i> พิมพ์ที่เลือก (Selected)
                </button>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column gap-3">
    <?php endif; ?>
        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $order): 
                // Fetch Items
                $items = isset($itemsByOrder[$order['orders_id']]) ? $itemsByOrder[$order['orders_id']] : [];

                // Fetch Address
                $address = isset($addressesById[$order['addr_id']]) ? $addressesById[$order['addr_id']] : null;

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
                $senderName = $defaultShopName; // Fallback
                if ($order['addrsender_id'] > 0 && isset($addressesById[$order['addrsender_id']])) {
                    $senderName = $addressesById[$order['addrsender_id']]['addr_name'];
                }
            ?>
                <?php
                    $displayColor = $order['orsts_color'];
                    if (trim($order['orsts_detail']) === 'ได้รับแล้ว') {
                        $displayColor = '#28a745';
                    }

                    // Sales Channel badge formatting
                    $channel = !empty($order['orders_channel']) ? $order['orders_channel'] : '';
                    $channel_badge = '';
                    if ($channel) {
                        $channel_color = '#6c757d'; // Default
                        $channel_label = $channel;
                        $channel_icon = 'tag'; // Default icon
                        $lower_channel = strtolower($channel);
                        if (strpos($lower_channel, 'line') !== false) {
                            $channel_color = '#06C755';
                            $channel_icon = 'message-circle';
                        } else if (strpos($lower_channel, 'facebook') !== false || strpos($lower_channel, 'fb') !== false) {
                            $channel_color = '#1877F2';
                            $channel_icon = 'facebook';
                        } else if (strpos($lower_channel, 'tiktok') !== false) {
                            $channel_color = '#010101';
                            $channel_icon = 'music';
                        } else if (strpos($lower_channel, 'shopee') !== false) {
                            $channel_color = '#EE4D2D';
                            $channel_icon = 'shopping-bag';
                        } else if (strpos($lower_channel, 'lazada') !== false) {
                            $channel_color = '#101566';
                            $channel_icon = 'shopping-cart';
                        } else if (strpos($lower_channel, 'website') !== false) {
                            $channel_color = '#0ea5e9';
                            $channel_icon = 'globe';
                        } else if (strpos($lower_channel, 'phone') !== false) {
                            $channel_color = '#0d6efd';
                            $channel_icon = 'phone';
                        } else if (strpos($lower_channel, 'therapist') !== false) {
                            $channel_color = '#6f42c1';
                            $channel_icon = 'heart';
                        }
                        $icon_markup = ($channel_icon === 'facebook')
                            ? '<svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'
                            : '<i data-lucide="' . $channel_icon . '" style="width:10px; height:10px;"></i>';
                        $channel_badge = '<span class="badge ms-1 shadow-sm-hover" style="background-color: ' . $channel_color . '; font-size: 0.65rem; padding: 2px 5px; vertical-align: middle; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;" onclick="changeOrderChannel(' . $order['orders_id'] . ', \'' . htmlspecialchars(addslashes($channel)) . '\')" title="คลิกเพื่อเปลี่ยนช่องทางการขาย">' . $icon_markup . ' ' . htmlspecialchars($channel_label) . '</span>';
                    } else {
                        $channel_badge = '<span class="badge bg-secondary ms-1 shadow-sm-hover" style="font-size: 0.65rem; padding: 2px 5px; vertical-align: middle; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;" onclick="changeOrderChannel(' . $order['orders_id'] . ', \'\')" title="คลิกเพื่อเปลี่ยนช่องทางการขาย"><i data-lucide="help-circle" style="width:10px; height:10px;"></i> ไม่ระบุช่องทาง</span>';
                    }
                    
                    // Country badge formatting
                    $country_badge = '';
                    $country_id = isset($order['orders_country_id']) ? (int)$order['orders_country_id'] : 0;
                    if ($country_id === 0) {
                        $country_badge = '<span class="badge bg-secondary ms-1 shadow-sm-hover" style="font-size: 0.65rem; padding: 2px 5px; vertical-align: middle; cursor: pointer;" onclick="changeOrderCountry(' . $order['orders_id'] . ', ' . $country_id . ')" title="คลิกเพื่อเปลี่ยนประเทศ">ยังไม่ได้ระบุประเทศ</span>';
                    } else {
                        $country_name = !empty($order['country_name']) ? $order['country_name'] : 'ไม่ทราบประเทศ';
                        $country_code = !empty($order['country_code']) ? $order['country_code'] : '';
                        $country_label = $country_name . ($country_code ? " ({$country_code})" : "");
                        
                        $flag = '';
                        if ($country_code) {
                            $flag = '<img src="https://flagcdn.com/16x12/' . strtolower($country_code) . '.png" class="me-1" style="vertical-align: text-bottom; margin-bottom: 2px;" alt="' . $country_code . '">';
                        }

                        if ($country_id === 171) {
                            $country_badge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-1 shadow-sm-hover" style="font-size: 0.65rem; padding: 2px 5px; vertical-align: middle; cursor: pointer;" onclick="changeOrderCountry(' . $order['orders_id'] . ', ' . $country_id . ')" title="คลิกเพื่อเปลี่ยนประเทศ">' . $flag . htmlspecialchars($country_label) . '</span>';
                        } else {
                            $country_badge = '<span class="badge border ms-1 fw-bold shadow-sm-hover" style="font-size: 0.65rem; padding: 2px 5px; vertical-align: middle; cursor: pointer; background-color: #d39e00; color: #fff; border-color: #c69500 !important;" onclick="changeOrderCountry(' . $order['orders_id'] . ', ' . $country_id . ')" title="คลิกเพื่อเปลี่ยนประเทศ">' . $flag . htmlspecialchars($country_label) . '</span>';
                        }
                    }
                ?>
                <div class="card shadow-sm border-0 overflow-hidden mb-3 order-card" id="order-card-<?= $order['orders_id'] ?>" style="border-radius: 10px;">
                    <!-- Header Status Bar -->
                    <div class="card-header py-1 d-flex justify-content-between align-items-center flex-nowrap" 
                         style="background-color: <?= $displayColor ?>15; border-left: 4px solid <?= $displayColor ?>; font-size: 0.8rem;">
                        <div class="fw-bold d-flex align-items-center flex-shrink-0 gap-1" style="color: <?= $displayColor ?>;">
                            <div>
                                <i data-lucide="circle-dot" style="width:16px; height:16px; vertical-align:text-bottom;"></i> 
                                <?= htmlspecialchars($order['orsts_detail']) ?>
                            </div>
                            <span class="ms-1 badge bg-secondary bg-opacity-25 border border-secondary shadow-sm" style="font-size: 0.75rem; padding: 2px 5px; color: var(--bs-heading-color, inherit);">#<?= $order['orders_no'] ?></span>
                            <?= $channel_badge ?>
                            <?= $country_badge ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-end gap-2 ms-2 overflow-auto" style="scrollbar-width: none; -ms-overflow-style: none; white-space: nowrap;">
                            <div class="text-muted small flex-shrink-0">
                                <i data-lucide="clock" style="width:14px; height:14px; margin-bottom: 2px;"></i> 
                                <?= date('d/m/Y H:i', strtotime($order['orders_date'])) ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="row g-0">
                            <!-- Left: User & Shipping -->
                            <div class="col-md-2 bg-light p-2 border-end text-center d-flex flex-column align-items-center justify-content-start gap-1">
                                <!-- Customer Name & Code -->
                                <div class="w-100 text-center mb-1 lh-sm" style="font-size: 0.8rem;">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <span class="fw-bold text-primary cursor-pointer d-inline-block text-truncate" style="max-width: 100%; vertical-align: middle; cursor: pointer;" onclick="openCustomerModal(<?= $order['user_id'] ?>, <?= $order['orders_id'] ?>)" title="คลิกเพื่อแก้ไขข้อมูล: <?= $order['user_name'] ?> <?= $order['user_lastname'] ?>">
                                            <i data-lucide="edit-2" class="me-1" style="width:12px; height:12px;"></i><?= $order['user_name'] ?> <?= $order['user_lastname'] ?>
                                        </span>
                                        
                                        <!-- 5-Star Rating System -->
                                        <?php $user_stars = isset($order['user_stars']) ? (int)$order['user_stars'] : 0; ?>
                                        <div class="user-stars-container d-flex align-items-center" id="stars-container-orders-<?= $order['user_id'] ?>">
                                            <?php for($i=1; $i<=5; $i++): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" 
                                                     fill="<?= $i <= $user_stars ? '#ffc107' : 'none' ?>" 
                                                     stroke="<?= $i <= $user_stars ? '#ffc107' : '#adb5bd' ?>" 
                                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                                     class="cursor-pointer star-icon" 
                                                     onclick="updateUserStars(<?= $order['user_id'] ?>, <?= $i ?>)"
                                                     style="transition: transform 0.1s; margin: 0 1px;">
                                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <span class="badge bg-secondary cursor-pointer shadow-sm-hover mt-1" style="font-size: 0.65rem; padding: 2px 4px;" onclick="copyToClipboard('C<?= $order['user_id'] ?>', this)" title="รหัสลูกค้า (คลิกเพื่อคัดลอก)">C<?= $order['user_id'] ?></span>
                                </div>
                                <!-- User Image -->
                                <div class="position-relative cursor-pointer mb-1 shadow-sm-hover" 
                                     onclick="openUserImageModal(<?= $order['user_id'] ?>, '<?= $order['user_picture'] ?>')"
                                     style="width: 60px; height: 60px; transition: transform 0.2s; z-index: 10;">
                                    <?php if(!empty($order['user_picture'])): ?>
                                        <img src="<?= ROOT_URL ?>/uploads/profile/<?= $order['user_id'] ?>/<?= $order['user_picture'] ?>" 
                                             class="rounded border shadow-sm" 
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded bg-white border shadow-sm d-flex align-items-center justify-content-center mx-auto" 
                                             style="width: 100%; height: 100%; color: #dee2e6;">
                                            <i data-lucide="user" style="width: 30px; height: 30px;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="position-absolute bottom-0 end-0 bg-white rounded-circle border shadow-sm" 
                                         style="width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; transform: translate(25%, 25%);">
                                        <i data-lucide="camera" class="text-primary" style="width: 12px; height: 12px;"></i>
                                    </div>
                                </div>

                                <!-- Shipping Method -->
                                <button class="btn btn-xs w-100 text-white fw-bold" 
                                        style="background-color: #007bff;"
                                        onclick="openShippingModal(<?= $order['orders_id'] ?>, <?= $order['transcat_id'] ?: 0 ?>, '<?= addslashes($order['orders_tracking'] ?? '') ?>', '<?= $order['orders_delicosttrue'] ?? '' ?>')">
                                    <?= $order['transcat_name'] ? $order['transcat_name'] : 'เลือกขนส่ง' ?>
                                </button>

                                <!-- Tracking Number -->
                                <?php if($order['orders_tracking']): ?>
                                    <?php 
                                        $trackingsArray = array_filter(array_map('trim', explode(',', $order['orders_tracking'])));
                                        if(count($trackingsArray) > 1): 
                                    ?>
                                        <div class="dropdown w-100">
                                            <button class="btn btn-sm btn-success w-100 dropdown-toggle small d-flex align-items-center justify-content-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                <i data-lucide="external-link" style="width:10px; height:10px; flex-shrink:0;"></i>
                                                <span class="text-truncate">หลายกล่อง (<?= count($trackingsArray) ?>)</span>
                                            </button>
                                            <ul class="dropdown-menu shadow-sm" style="font-size: 0.8rem; min-width: 100%;">
                                                <?php foreach($trackingsArray as $index => $tk): 
                                                    $tkLink = '#';
                                                    if ($order['transcat_link']) {
                                                        $tkLink = str_replace('xxx', $tk, $order['transcat_link']);
                                                        if (strpos($order['transcat_link'], 'xxx') === false) {
                                                            $tkLink = $order['transcat_link'] . $tk;
                                                        }
                                                    }
                                                ?>
                                                    <li>
                                                        <?php if($tkLink !== '#'): ?>
                                                            <a class="dropdown-item py-1" href="<?= htmlspecialchars($tkLink) ?>" target="_blank" rel="noopener noreferrer">
                                                                <i data-lucide="package" style="width:12px; height:12px; margin-right: 4px;"></i> กล่องที่ <?= $index + 1 ?>: <?= htmlspecialchars($tk) ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="dropdown-item py-1">
                                                                <i data-lucide="package" style="width:12px; height:12px; margin-right: 4px;"></i> กล่องที่ <?= $index + 1 ?>: <?= htmlspecialchars($tk) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <?php if($trackingLink !== '#'): ?>
                                            <a href="<?= htmlspecialchars($trackingLink) ?>" target="_blank" rel="noopener noreferrer"
                                               class="d-flex align-items-center justify-content-center gap-1 bg-success text-white rounded px-2 py-1 w-100 small text-decoration-none"
                                               title="คลิกตรวจสอบ: <?= htmlspecialchars($order['orders_tracking']) ?>"
                                               style="cursor: pointer;">
                                                <i data-lucide="external-link" style="width:10px; height:10px; flex-shrink:0;"></i>
                                                <span class="text-truncate"><?= htmlspecialchars($order['orders_tracking']) ?></span>
                                            </a>
                                        <?php else: ?>
                                            <div class="bg-success text-white rounded px-2 py-1 w-100 small text-truncate"
                                                 title="<?= htmlspecialchars($order['orders_tracking']) ?>">
                                                <?= htmlspecialchars($order['orders_tracking']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="bg-warning text-dark rounded px-2 py-1 w-100 small">
                                        ยังไม่ระบุเลข
                                    </div>
                                <?php endif; ?>

                                <!-- Copy Button -->
                                <button class="btn btn-xs fw-bold w-100" style="background-color: #ffcc00; border-color: #ffcc00; color: #000;"
                                        onclick="copyOrderInfo(<?= $order['orders_id'] ?>)"
                                        <?= empty($order['orders_tracking']) ? 'disabled title="กรุณาระบุเลขพัสดุก่อน"' : '' ?>>
                                    <i data-lucide="copy" style="width:10px; height:10px;"></i> คัดลอกข้อมูลลูกค้า
                                </button>

                                <!-- Copy VIP Invite Link Button (Hides once customer completes VIP registration) -->
                                <?php
                                $isVipRegistered = !empty($order['user_vip']) || strpos((string)($order['user_note'] ?? ''), '"vip_bonus_50_awarded":true') !== false;
                                if (!$isVipRegistered):
                                ?>
                                <button class="btn btn-xs fw-bold w-100 text-white" style="background-color: #0B3B35; border-color: #0B3B35;"
                                        onclick="copyVipInvite(<?= $order['user_id'] ?>, this)"
                                        title="คัดลอกลิงก์เชิญลูกค้าอัปเดตข้อมูลและสะสมแต้ม (VIP Link)">
                                    <i data-lucide="crown" style="width:10px; height:10px; color:#ffc107;"></i> ลิงก์เชิญ VIP (30 วัน)
                                </button>
                                <?php else: ?>
                                <div class="d-flex align-items-center justify-content-between rounded px-2 py-1 w-100" style="background-color: #ecfdf5; border: 1px solid #10b981; color: #065f46; font-size: 0.72rem; font-weight: 700;">
                                    <span><i data-lucide="crown" style="width:10px; height:10px; color:#d97706;"></i> เป็นสมาชิก VIP แล้ว</span>
                                    <button type="button" class="btn btn-link p-0 m-0 text-success" style="font-size: 0.7rem;" onclick="copyVipInvite(<?= $order['user_id'] ?>, this)" title="คัดลอกลิงก์ VIP อีกครั้ง">
                                        <i data-lucide="copy" style="width:10px; height:10px;"></i>
                                    </button>
                                </div>
                                <?php endif; ?>

                                <!-- Print Status -->
                                <button class="btn btn-xs w-100 fw-bold <?= $order['orders_print'] == 1 ? 'btn-danger' : 'btn-success' ?>" 
                                        onclick="togglePrintStatus(<?= $order['orders_id'] ?>, <?= $order['orders_print'] ?>, this)">
                                    <i data-lucide="printer" style="width:10px; height:10px;"></i> 
                                    <?= $order['orders_print'] == 1 ? 'พิมพ์แล้ว' : 'ยังไม่พิมพ์' ?>
                                </button>

                                <!-- Re-order Button -->
                                <button class="btn btn-xs btn-outline-dark w-100 fw-bold bg-white" onclick="reorder(<?= $order['orders_id'] ?>)">
                                    <i data-lucide="shopping-cart" style="width:10px; height:10px;"></i> ซื้อรายการเดิมอีกครั้ง
                                </button>

                                <!-- Change Status Button -->
                                <div class="dropdown w-100 mt-1">
                                    <button class="btn btn-xs w-100 fw-bold text-white dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" 
                                            style="background-color: <?= $displayColor ?>; border-color: <?= $displayColor ?>; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i data-lucide="refresh-cw" style="width:10px; height:10px;"></i> <?= $order['orsts_code'] ?>-<?= $order['orsts_detail'] ?></span>
                                    </button>
                                    <ul class="dropdown-menu shadow-sm" style="font-size: 0.8rem; min-width: 100%;">
                                        <?php foreach ($all_statuses as $st): ?>
                                            <li><a class="dropdown-item py-1 fw-bold" href="#" onclick="quickChangeStatus(<?= $order['orders_id'] ?>, <?= $st['orsts_id'] ?>); return false;" style="color: <?= $st['orsts_color'] ?>;">
                                                <i data-lucide="circle-dot" style="width:12px; height:12px; margin-right: 4px;"></i> <?= htmlspecialchars($st['orsts_detail']) ?>
                                            </a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <!-- User History -->
                                <div class="text-danger small fw-bold mt-1" style="cursor: pointer;" onclick="filterByCustomer(<?= $order['user_id'] ?>)">
                                    <i data-lucide="heart" style="width:12px; height:12px; fill: red;"></i> 
                                    [ <?= $order['user_order_count'] ?> ]
                                </div>
                                <!-- Customer Note (Prominent on Left) -->
                                <?php if (!empty($order['orders_forword'])): ?>
                                    <div class="mt-2 p-1 border border-danger rounded bg-white text-danger text-start shadow-sm" style="font-size: 0.65rem; border-width: 1px !important;">
                                        <div class="fw-bold mb-1 border-bottom border-danger pb-1"><i data-lucide="alert-circle" style="width:10px; height:10px;"></i> หมายเหตุ:</div>
                                        <div class="lh-sm"><?= nl2br(htmlspecialchars($order['orders_forword'])) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Center: Customer Info & Items -->
                            <div class="col-md-6 p-2 border-end">
                                <!-- Customer Info & Meta -->
                                <div class="d-flex justify-content-between align-items-center mb-1 flex-nowrap">
                                    <div class="d-flex align-items-center flex-nowrap gap-1 flex-shrink-0">
                                        <!-- Print Button -->
                                        <div class="d-flex align-items-center justify-content-center border shadow-sm-hover" 
                                             style="width: 24px; height: 24px; background-color: #e7f1ff; border-radius: 4px; border: 1px solid #b8daff !important; cursor: pointer !important;" 
                                             onclick="printOrder(<?= $order['orders_id'] ?>, <?= $order['orders_is_vat'] ?>)"
                                             title="พิมพ์บิล (Print)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#007bff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                        </div>
                                        
                                        <!-- Checkbox Button -->
                                        <div class="border d-flex align-items-center justify-content-center me-1" 
                                             style="width: 24px; height: 24px; background-color: #ffffff; border-radius: 4px; border: 1px solid #dee2e6 !important; cursor: pointer !important;"
                                             title="เลือกรายการเพื่อพิมพ์ (Select to Print)"
                                             onclick="this.querySelector('input').click()">
                                            <input class="form-check-input order-checkbox m-0" type="checkbox" value="<?= $order['orders_id'] ?>" 
                                                   style="width: 14px; height: 14px; cursor: pointer !important; border-radius: 2px;"
                                                   title="เลือกรายการเพื่อพิมพ์ (Select to Print)"
                                                   onclick="event.stopPropagation()">
                                        </div>

                                        <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer flex-shrink-0" 
                                             style="width: 24px; height: 24px; border-radius: 4px; cursor: pointer !important;" 
                                             onclick="copyToClipboard('<?= $order['orders_no'] ?>', this)"
                                             title="คัดลอกเลขที่สั่งซื้อ">
                                            <i data-lucide="copy" class="text-muted" style="width:12px; height:12px;"></i>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer position-relative flex-shrink-0" 
                                             style="width: 24px; height: 24px; border-radius: 4px; cursor: pointer !important;"
                                             onclick="editBoxCount(<?= $order['orders_id'] ?>, <?= $order['orders_bill'] ?? 1 ?>)"
                                             title="จำนวนกล่อง / ใบปะหน้า (คลิกเพื่อแก้ไข)">
                                            <i data-lucide="package" class="text-secondary" style="width:12px; height:12px;"></i>
                                            <?php if (($order['orders_bill'] ?? 1) > 1): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.45rem; padding: 0.2em 0.4em; transform: translate(-30%, -30%) !important;">
                                                    <?= $order['orders_bill'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer flex-shrink-0" 
                                             style="width: 24px; height: 24px; border-radius: 4px; cursor: pointer !important;" 
                                             onclick="copyToClipboard('<?= ROOT_URL ?>/track/<?= $order['track_token'] ?>', this)"
                                             title="คัดลอกลิงก์ติดตามออเดอร์ให้ลูกค้า">
                                            <i data-lucide="link" class="text-primary" style="width:12px; height:12px;"></i>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer flex-shrink-0" 
                                             style="width: 24px; height: 24px; border-radius: 4px; cursor: pointer !important;" 
                                             onclick="copyToClipboard('<?= ROOT_URL ?>/receipt/<?= $order['track_token'] ?>', this)"
                                             title="คัดลอกลิงก์ใบเสร็จให้ลูกค้า">
                                            <i data-lucide="file-text" class="text-success" style="width:12px; height:12px;"></i>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer flex-shrink-0" 
                                             style="width: 24px; height: 24px; border-radius: 4px; cursor: pointer !important;"
                                             onclick="openMoveOrderModal(<?= $order['orders_id'] ?>)"
                                             title="ย้ายออเดอร์ไปลูกค้ารายอื่น">
                                            <i data-lucide="corner-up-right" class="text-danger" style="width:12px; height:12px;"></i>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-end flex-nowrap gap-1" style="min-width: 0; overflow: hidden;">
                                        <?php if ($order['paycat_id'] == 2): ?>
                                             <span class="badge bg-danger flex-shrink-0" style="font-size: 0.65rem; padding: 2px 4px;">COD</span>
                                        <?php endif; ?>
                                        <div class="d-flex align-items-center gap-1 overflow-auto text-nowrap" style="scrollbar-width: none; -ms-overflow-style: none;">
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($order['orders_msg'])): ?>
                                    <div class="alert py-1 px-2 mb-2 small d-flex justify-content-between align-items-center cursor-pointer shadow-sm border order-msg-alert" 
                                         style="font-size: 0.7rem;"
                                         onclick="editOrderMsg(<?= $order['orders_id'] ?>, <?= htmlspecialchars(json_encode($order['orders_msg'])) ?>)"
                                         title="คลิกเพื่อแก้ไขหมายเหตุ">
                                        <div class="text-truncate fw-bold" style="white-space: normal;">
                                            <i data-lucide="message-square" style="width:10px; height:10px;" class="me-1"></i>
                                            <?= htmlspecialchars($order['orders_msg']) ?>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center border bg-transparent cursor-pointer rounded" 
                                             style="width: 20px; height: 20px; cursor: pointer !important; flex-shrink: 0; border-color: inherit !important;">
                                            <i data-lucide="edit-3" style="width:10px; height:10px;"></i>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-light py-1 px-2 mb-2 small text-center text-muted cursor-pointer" 
                                         style="font-size: 0.7rem; border: 1px dashed #ccc;"
                                         onclick="editOrderMsg(<?= $order['orders_id'] ?>, '')"
                                         title="เพิ่มหมายเหตุหน้าบิล">
                                        <i data-lucide="plus-circle" style="width:10px; height:10px;" class="me-1"></i> เพิ่มหมายเหตุ (พิมพ์หน้าบิล)
                                    </div>
                                <?php endif; ?>

                                <!-- Addresses (Dense) -->
                                <div class="row g-1 mb-2">
                                    <div class="col-md-6">
                                        <div class="p-1 px-2 bg-light border rounded small h-100" style="font-size: 0.7rem;">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">ผู้ส่ง: <strong class="text-dark"><?= htmlspecialchars($senderName) ?></strong></span>
                                                <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer" 
                                                     style="width: 20px; height: 20px; border-radius: 3px; cursor: pointer !important;" 
                                                     onclick="changeSender(<?= $order['orders_id'] ?>, <?= $order['user_id'] ?>)"
                                                     title="เปลี่ยนผู้ส่ง">
                                                    <i data-lucide="refresh-cw" class="text-muted" style="width:10px; height:10px;"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-1 px-2 bg-light border rounded small h-100" style="font-size: 0.7rem;">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-muted">ผู้รับ: <strong class="text-dark"><?= $address['addr_name'] ?? '-' ?></strong></span>
                                                <div class="d-flex gap-1">
                                                    <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer" 
                                                         style="width: 22px; height: 22px; border-radius: 4px; cursor: pointer !important;" 
                                                         onclick="editAddress(<?= $order['orders_id'] ?>, <?= $order['user_id'] ?>)"
                                                         title="แก้ไขที่อยู่">
                                                        <i data-lucide="edit-2" class="text-primary" style="width:11px; height:11px;"></i>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-center border bg-white cursor-pointer" 
                                                         style="width: 22px; height: 22px; border-radius: 4px; cursor: pointer !important;" 
                                                         onclick="changeAddress(<?= $order['orders_id'] ?>, <?= $order['user_id'] ?>)"
                                                         title="เลือกที่อยู่จากสมุดรายชื่อ">
                                                        <i data-lucide="map-pin" class="text-success" style="width:11px; height:11px;"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-truncate text-muted" style="max-width: 250px;">
                                                <?= $address['addr_detail'] ?? '' ?> <?= $address['province_name'] ?? '' ?>
                                            </div>
                                            <?php if (!empty($address['addr_forword'])): ?>
                                                <div class="small fw-bold mt-1" style="font-size: 0.65rem; color: #157347;">
                                                    <i data-lucide="info" style="width:10px; height:10px; vertical-align: middle;"></i> 
                                                    <?= htmlspecialchars($address['addr_forword']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Table (Narrow & Strong Borders) -->
                                <div class="table-responsive border border-dark border-opacity-25 rounded overflow-hidden">
                                    <table class="table table-sm table-hover mb-0 border-dark border-opacity-10" style="font-size: 0.7rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 30px;" class="border-end border-dark border-opacity-10 text-center">รูป</th>
                                                <th class="border-end border-dark border-opacity-10">รายการ</th>
                                                <th class="text-end border-end border-dark border-opacity-10" style="width: 40px;">ชุด</th>
                                                <th class="text-end" style="width: 70px;">ราคา</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $item): 
                                                $img = $item['product_picture'] ? ROOT_URL . "/uploads/products/small-" . $item['product_picture'] : ADMIN_ASSETS . "/images/placeholder.png";
                                            ?>
                                                <tr class="border-bottom border-dark border-opacity-10">
                                                    <td class="border-end border-dark border-opacity-10 text-center p-1">
                                                        <img src="<?= $img ?>" class="rounded border" style="width: 20px; height: 20px; object-fit: cover;">
                                                    </td>
                                                    <td class="border-end border-dark border-opacity-10 py-1">
                                                        <div style="line-height: 1.2;"><?= htmlspecialchars($item['product_name']) ?></div>
                                                        <div class="text-muted d-flex align-items-center gap-1" style="font-size: 0.6rem;">
                                                            <?= htmlspecialchars($item['product_code']) ?>
                                                            <?php if (!empty($item['ordetail_memo'])): ?>
                                                                <span class="text-warning fw-bold" style="font-size: 0.65rem;"><i data-lucide="info" style="width:8px; height:8px;"></i> <?= htmlspecialchars($item['ordetail_memo']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-end border-end border-dark border-opacity-10"><?= $item['ordetail_qty'] ?></td>
                                                    <td class="text-end"><?= number_format((float)($item['product_price'] * $item['ordetail_qty']), 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Right: Totals & Actions -->
                            <div class="col-md-4 p-2 bg-light d-flex flex-column justify-content-between border-start">
                                <div class="small" style="font-size: 0.7rem;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">น้ำหนัก:</span>
                                        <span><?= number_format((float)$order['orders_weight']) ?> g</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">ยอดรวม:</span>
                                        <span><?= number_format((float)$order['orders_total'], 2) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 text-danger">
                                        <span>ส่วนลด:</span>
                                        <span>-<?= number_format((float)$order['orders_discount'], 2) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">ค่าส่ง:</span>
                                        <span><?= number_format((float)$order['orders_delicost'], 2) ?></span>
                                    </div>
                                    <?php 
                                        $delicosttrue = isset($order['orders_delicosttrue']) && $order['orders_delicosttrue'] !== null ? (float)$order['orders_delicosttrue'] : null;
                                    ?>
                                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.65rem;">
                                        <span class="text-danger opacity-75">ต้นทุนค่าส่งจริง:</span>
                                        <span class="text-danger opacity-75 d-flex align-items-center gap-1">
                                            <?= $delicosttrue !== null ? number_format($delicosttrue, 2) : '-' ?>
                                            <i data-lucide="edit-3" class="cursor-pointer shadow-sm-hover" style="width:10px; height:10px; cursor: pointer;" onclick="editInlineShippingCost(<?= $order['orders_id'] ?>, <?= $delicosttrue !== null ? $delicosttrue : 'null' ?>)" title="แก้ไขต้นทุนค่าส่ง"></i>
                                        </span>
                                    </div>
                                    
                                    <hr class="my-1 border-dark opacity-10">
                                    
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-dark fw-bold">ชำระแล้ว:</span>
                                        <span class="text-success fw-bold"><?= number_format((float)$order['orders_paid_amount'], 2) ?></span>
                                    </div>
                                    <?php 
                                        $balance = (float)$order['orders_grandtotal'] - (float)$order['orders_paid_amount'];
                                        if ($balance > 0.01):
                                    ?>
                                        <div class="d-flex justify-content-between mb-1 text-danger fw-bold">
                                            <span>ยอดคงเหลือ:</span>
                                            <span><?= number_format($balance, 2) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <hr class="my-1 border-dark opacity-10">
                                    <div class="d-flex justify-content-between fw-bold pt-1 border-top border-dark border-opacity-10 mt-1">
                                        <span class="text-dark" style="font-size: 0.85rem;">สุทธิ:</span>
                                        <div class="text-end">
                                            <div class="text-primary" style="font-size: 1rem;"><?= number_format((float)$order['orders_grandtotal'], 2) ?></div>
                                            <?php if ($order['paycat_id'] == 2): ?>
                                                <div class="badge bg-danger mt-1 fw-bold" style="font-size: 0.6rem; padding: 2px 5px;">COD</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <?php 
                                    $stmt_slips = $db->prepare("SELECT * FROM order_slips WHERE orders_id = ? ORDER BY slip_uploaded_at ASC");
                                    $stmt_slips->execute([$order['orders_id']]);
                                    $allSlips = $stmt_slips->fetchAll(PDO::FETCH_ASSOC);
                                    $slipCount = count($allSlips);
                                    ?>
                                    <?php 
                                    $packImgs = !empty($order['packing_image']) ? array_values(array_filter(array_map('trim', explode(',', $order['packing_image'])))) : [];
                                    $packCount = count($packImgs);
                                    ?>
                                    <div class="mb-1">
                                        <button onclick="uploadParcelImage(<?= $order['orders_id'] ?>)" 
                                                class="btn btn-xs <?= $packCount > 0 ? 'btn-info text-white' : 'btn-outline-info' ?> w-100 fw-bold">
                                            <i data-lucide="box" style="width:10px; height:10px;"></i> 
                                            <?= $packCount > 0 ? "รูปพัสดุ ({$packCount})" : "แนบรูปพัสดุ" ?>
                                        </button>
                                    </div>
                                    <div class="mb-1">
                                        <button onclick="viewOrderSlips(<?= $order['orders_id'] ?>)" 
                                                class="btn btn-xs <?= $slipCount > 0 ? 'btn-primary' : 'btn-outline-success' ?> w-100 fw-bold">
                                            <i data-lucide="<?= $slipCount > 0 ? 'images' : 'upload' ?>" style="width:10px; height:10px;"></i> 
                                            <?= $slipCount > 0 ? "สลิป ({$slipCount})" : "บันทึกสลิป" ?>
                                        </button>
                                    </div>
                                    
                                    <button onclick="editPaidAmount(<?= $order['orders_id'] ?>, <?= (float)$order['orders_paid_amount'] ?>)" 
                                            class="btn btn-xs btn-success w-100 text-white fw-bold">
                                        <i data-lucide="edit-3" style="width:10px; height:10px;"></i>  ระบุยอดมัดจำ/ชำระแล้ว
                                    </button>
                                    <button onclick="openOrderNotes(<?= $order['orders_id'] ?>)" class="btn btn-xs <?= $order['notes_count'] > 0 ? 'btn-outline-primary fw-bold' : 'btn-outline-dark' ?> w-100 mt-1">
                                        <i data-lucide="message-square" style="width:10px; height:10px;"></i>  บันทึกภายใน <?= $order['notes_count'] > 0 ? "({$order['notes_count']})" : "" ?>
                                    </button>


                                </div>

                                <div class="mt-1 d-grid gap-1">
                                    <button onclick="viewOrder(<?= $order['orders_id'] ?>)" class="btn btn-primary btn-xs">
                                        <i data-lucide="eye" style="width:10px; height:10px;"></i> แก้ไขรายการคำสั่งซื้อ
                                    </button>
                                    <button onclick="openTimelineModal(<?= $order['orders_id'] ?>)" class="btn btn-outline-dark btn-xs">
                                        <i data-lucide="clock" style="width:10px; height:10px;"></i> บันทึกภายใน / Timeline (<?= $order['notes_count'] ?? 0 ?>)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php 
                $status_name_for_empty = '';
                if (isset($_GET['payment']) && $_GET['payment'] === 'cod') {
                    $status_name_for_empty = 'COD';
                } else if (isset($_GET['status']) && $_GET['status'] === 'cancelled') {
                    $status_name_for_empty = 'ยกเลิก';
                } else if ($status_id > 0) {
                    foreach ($all_statuses as $st) {
                        if ($st['orsts_id'] == $status_id) {
                            $status_name_for_empty = $st['orsts_detail'];
                            break;
                        }
                    }
                } else if ($status_id === 0) {
                    $status_name_for_empty = 'ทั้งหมด';
                }
            ?>
            <div class="text-center py-5 text-muted bg-white rounded shadow-sm">
                <i data-lucide="inbox" class="mx-auto mb-2" style="width: 48px; height: 48px; opacity: 0.5;"></i>
                <p class="mb-0 fs-5">ไม่พบรายการสั่งซื้อ</p>
                <?php if ($status_name_for_empty && $status_name_for_empty !== 'ทั้งหมด'): ?>
                    <p class="text-muted mt-1 small">ในหมวดหมู่ "<?= htmlspecialchars($status_name_for_empty) ?>"</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php if (!$is_single_card): ?>
    </div>
    <?php endif; ?>
    <?php
    $tableHtml = ob_get_clean();

    // Generate Pagination
    ob_start();
    if ($totalPages > 1):
        $range = 2; // Number of pages to show around current page
        $showFirst = $page > ($range + 2);
        $showLast = $page < ($totalPages - ($range + 1));
        $searchJsArg = htmlspecialchars(json_encode($search, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-end mb-0">
            <!-- Previous -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="javascript:void(0)" onclick="loadOrderTable(<?= $page - 1 ?>, <?= $searchJsArg ?>)" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <!-- First Page -->
            <?php if ($showFirst): ?>
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" onclick="loadOrderTable(1, <?= $searchJsArg ?>)">1</a>
                </li>
                <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)): ?>
                    <?php if ($showFirst && $i == 1) continue; ?>
                    <?php if ($showLast && $i == $totalPages) continue; ?>
                    
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="javascript:void(0)" onclick="loadOrderTable(<?= $i ?>, <?= $searchJsArg ?>)"><?= $i ?></a>
                    </li>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Last Page -->
            <?php if ($showLast): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" onclick="loadOrderTable(<?= $totalPages ?>, <?= $searchJsArg ?>)"><?= $totalPages ?></a>
                </li>
            <?php endif; ?>

            <!-- Next -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="javascript:void(0)" onclick="loadOrderTable(<?= $page + 1 ?>, <?= $searchJsArg ?>)" aria-label="Next">
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
    
    // Add COD special count
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE paycat_id = 2");
    $statusCounts['cod'] = $stmt->fetchColumn();

    if ($is_single_card) {
        echo json_encode([
            'success' => true,
            'is_single' => true,
            'order_id' => $single_order_id,
            'card_html' => $tableHtml,
            'statusCounts' => $statusCounts
        ]);
        exit;
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
