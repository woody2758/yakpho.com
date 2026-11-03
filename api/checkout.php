<?php
declare(strict_types=1);

/**
 * ============================================================
 * YakPho & Esther Aroma — Real E-Commerce Checkout JSON API
 * ============================================================
 * Creates customer (user), shipping address (addr), order (orders),
 * and line items (ordetail) with track_token for /track/{token} & /receipt/{token}.
 * Includes M-Stamp Points (Earn/Redeem), Affiliate Commission & Resend Email.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/frontend_data.php';
require_once __DIR__ . '/../includes/member_helper.php';
require_once __DIR__ . '/../includes/resend_helper.php';
require_once __DIR__ . '/../includes/line_helper.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method Not Allowed');
    }

    $rawInput = file_get_contents('php://input');
    $payload = !empty($rawInput) ? json_decode($rawInput, true) : $_POST;
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $lang = trim((string)($payload['lang'] ?? ($_SESSION['lang'] ?? 'th')));
    $customerName = trim((string)($payload['customer_name'] ?? ''));
    $customerPhone = preg_replace('/[^0-9+]/', '', trim((string)($payload['customer_phone'] ?? '')));
    $customerEmail = trim((string)($payload['customer_email'] ?? ''));
    $addressDetail = trim((string)($payload['address_detail'] ?? ''));
    $provinceId = (int)($payload['province_id'] ?? 1);
    $postcode = trim((string)($payload['postcode'] ?? ''));
    $paycatId = (int)($payload['paycat_id'] ?? 1); // 1 = Bank Transfer, 2 = COD
    $orderMsg = trim((string)($payload['order_msg'] ?? ''));
    $lineId = trim((string)($payload['line_id'] ?? ''));
    $redeemStamps = max(0, (int)($payload['redeem_stamps'] ?? 0));
    $destination = trim((string)($payload['shipping_destination'] ?? 'domestic'));
    $destinationCountry = trim((string)($payload['destination_country'] ?? ''));
    $couponCode = strtoupper(trim((string)($payload['coupon_code'] ?? '')));

    // Affiliate code from payload or cookie
    $affiliateCode = trim((string)($payload['affiliate_code'] ?? ($_COOKIE['yp_affiliate'] ?? '')));

    if ($customerName === '' || $customerPhone === '' || $addressDetail === '') {
        throw new Exception($lang === 'th'
            ? 'กรุณากรอกชื่อ-นามสกุล เบอร์โทรศัพท์ และที่อยู่จัดส่งให้ครบถ้วน'
            : 'Please fill in your full name, phone number, and delivery address.');
    }

    if ($destination === 'international' && $destinationCountry === '') {
        throw new Exception($lang === 'th'
            ? 'กรุณาระบุประเทศปลายทางสำหรับการจัดส่งต่างประเทศ'
            : 'Please specify your destination country for international shipping.');
    }

    $cart = get_cart_summary($db, $lang, $destination);
    if (empty($cart['items'])) {
        throw new Exception($lang === 'th'
            ? 'ไม่มีสินค้าในตะกร้า กรุณาเลือกสินค้าก่อนสั่งซื้อ'
            : 'Your shopping cart is empty.');
    }

    $db->beginTransaction();

    // 1. Find or create customer in `user`
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $userRow = null;

    if ($userId > 0) {
        $stmtUser = $db->prepare("SELECT * FROM user WHERE user_id = ? AND user_del = 0 LIMIT 1");
        $stmtUser->execute([$userId]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
    }

    if (!$userRow) {
        $stmtUser = $db->prepare("SELECT * FROM user WHERE (user_mobile = ? OR user_tel = ?) AND user_del = 0 LIMIT 1");
        $stmtUser->execute([$customerPhone, $customerPhone]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
    }

    if ($userRow) {
        $userId = (int)$userRow['user_id'];
        $stmtUpdUser = $db->prepare("
            UPDATE user
            SET user_name = ?, user_email = COALESCE(NULLIF(?, ''), user_email),
                user_address = ?, user_postcode = ?, user_last_active = NOW(), user_update = NOW()
            WHERE user_id = ?
        ");
        $stmtUpdUser->execute([$customerName, $customerEmail, $addressDetail, $postcode, $userId]);
    } else {
        $stmtInsUser = $db->prepare("
            INSERT INTO user (
                user_username, user_password, user_email, user_mobile, user_tel,
                user_name, user_address, user_province, user_postcode,
                user_date, user_update, user_last_active, user_status, user_del, role,
                member_tier, m_stamp_points
            ) VALUES (
                ?, '', ?, ?, ?,
                ?, ?, ?, ?,
                NOW(), NOW(), NOW(), 1, 0, 'customer',
                'general', 0
            )
        ");
        $stmtInsUser->execute([
            $customerPhone, $customerEmail, $customerPhone, $customerPhone,
            $customerName, $addressDetail, (string)$provinceId, $postcode
        ]);
        $userId = (int)$db->lastInsertId();
    }

    // 2. Insert delivery address in `addr`
    $stmtAddr = $db->prepare("
        INSERT INTO addr (
            addr_name, addr_mobile, addr_detail, addr_postcode,
            country_id, provinces_id, addr_status, addr_del, addr_type, user_id, addr_date, addr_update
        ) VALUES (
            ?, ?, ?, ?,
            171, ?, 1, 0, 1, ?, NOW(), NOW()
        )
    ");
    $stmtAddr->execute([
        $customerName, $customerPhone, $addressDetail, $postcode,
        $provinceId > 0 ? $provinceId : 1, $userId
    ]);
    $addrId = (int)$db->lastInsertId();

    // 3. Calculate order totals & M-Stamp redemption
    $subtotal = (float)$cart['subtotal'];
    $shippingCost = ($destination === 'international') ? 0.0 : (float)$cart['shipping_cost'];
    $discount = 0.0;

    // COD calculation: only for domestic
    $codFee = ($paycatId === 2 && $destination === 'domestic') ? round(($subtotal + $shippingCost) * 0.03) : 0.0;

    // Verify Coupon Code if provided
    if ($couponCode !== '') {
        $stmtC = $db->prepare("SELECT * FROM coupons WHERE UPPER(coupon_code) = ? AND status = 1 LIMIT 1");
        $stmtC->execute([$couponCode]);
        $cRow = $stmtC->fetch(PDO::FETCH_ASSOC);
        if ($cRow) {
            $cType = $cRow['discount_type'];
            $cVal = (float)$cRow['discount_value'];
            $cMax = (float)$cRow['max_discount'];
            $cMin = (float)$cRow['min_order'];
            if ($subtotal >= $cMin) {
                $couponDisc = ($cType === 'percent') ? round($subtotal * ($cVal / 100), 2) : min($cVal, $subtotal);
                if ($cMax > 0 && $couponDisc > $cMax) $couponDisc = $cMax;
                $discount += $couponDisc;
                $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE coupon_id = ?")->execute([$cRow['coupon_id']]);
                $orderMsg = trim("Coupon: {$cRow['coupon_code']} (-฿" . number_format($couponDisc, 0) . ") | " . $orderMsg, " |");
            }
        }
    }

    // Check M-Stamp points
    $appliedStamps = 0;
    if ($redeemStamps > 0 && $userId > 0) {
        $stmtPts = $db->prepare("SELECT m_stamp_points FROM user WHERE user_id = ?");
        $stmtPts->execute([$userId]);
        $currPoints = (int)$stmtPts->fetchColumn();

        $maxRedeem = min($redeemStamps, $currPoints, (int)floor($subtotal - $discount));
        if ($maxRedeem > 0) {
            $appliedStamps = $maxRedeem;
            $discount += (float)$appliedStamps;
        }
    }

    $grandTotal = max(0.0, ($subtotal + $shippingCost + $codFee) - $discount);

    $ordersNo = date('Ymd') . random_int(1000, 9999);
    $trackToken = bin2hex(random_bytes(16));
    $fullNote = $orderMsg;
    if ($destination === 'international') {
        $fullNote = trim("จัดส่งต่างประเทศ: {$destinationCountry} (รอแจ้งค่าจัดส่ง) | " . $fullNote, " |");
    }
    if ($lineId !== '') {
        $fullNote = trim("LINE: {$lineId} | " . $fullNote, " |");
    }

    // 4. Insert into `orders`
    $stmtOrder = $db->prepare("
        INSERT INTO orders (
            user_id, orders_no, track_token, orders_date,
            orders_total, orders_delicost, orders_discount, orders_cod, orders_grandtotal,
            orders_status, orders_msg, addr_id, orders_is_vat, paycat_id,
            orders_channel, orders_country_id, orders_wholesale, affiliate_code
        ) VALUES (
            ?, ?, ?, NOW(),
            ?, ?, ?, ?, ?,
            1, ?, ?, 0, ?,
            'Website', 171, ?, ?
        )
    ");
    $isWholesale = ($cart['total_1kg_qty'] >= 6) ? 1 : 0;
    $stmtOrder->execute([
        $userId, $ordersNo, $trackToken,
        $subtotal, $shippingCost, $discount, $codFee, $grandTotal,
        $fullNote, $addrId, $paycatId,
        $isWholesale, $affiliateCode ?: null
    ]);
    $orderId = (int)$db->lastInsertId();

    // 5. Insert order line items into `ordetail`
    $stmtDetail = $db->prepare("
        INSERT INTO ordetail (
            orders_id, product_id, user_id, ordetail_qty, product_price, unit_price, subtotal, item_cost, ordetail_memo
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $itemsHtmlList = [];
    foreach ($cart['items'] as $item) {
        $pid = (int)$item['product_id'];
        $qty = (int)$item['qty'];
        $uPrice = (float)$item['unit_price'];
        $lineSub = (float)$item['line_total'];
        $memo = $item['is_wholesale_1kg'] ? ("Rate {$uPrice} THB/kg") : '';

        $stmtCost = $db->prepare("SELECT product_cprice FROM product WHERE product_id = ?");
        $stmtCost->execute([$pid]);
        $itemCost = (float)($stmtCost->fetchColumn() ?: 0.0);

        $stmtDetail->execute([
            $orderId, $pid, $userId, $qty, $uPrice, $uPrice, $lineSub, $itemCost, $memo
        ]);

        $itemsHtmlList[] = "<tr><td>" . htmlspecialchars($item['product_name']) . "</td><td style='text-align:right;'>{$qty}</td><td style='text-align:right;'>" . number_format($lineSub, 2) . " บาท</td></tr>";
    }

    // 6. Deduct redeemed M-Stamp if any
    if ($appliedStamps > 0) {
        deduct_m_stamp_points($db, $userId, $appliedStamps, $orderId, "ใช้เป็นส่วนลดคำสั่งซื้อ #{$ordersNo}");
    }

    // 7. Earn new M-Stamp points: 100 THB = 1 point
    $earnedStamps = (int)floor($grandTotal / 100);
    if ($earnedStamps > 0) {
        award_m_stamp_points($db, $userId, $earnedStamps, 'order_reward', $orderId, "แต้มสะสมจากคำสั่งซื้อ #{$ordersNo}");
    }

    // 8. Process Therapist Affiliate Commission (10%)
    if (!empty($affiliateCode)) {
        process_order_affiliate_commission($db, $orderId, $grandTotal, $affiliateCode);
    }

    $db->commit();

    // Clear session cart
    $_SESSION['yakpho_cart'] = [];

    // Fetch user's latest point balance
    $newBalance = 0;
    $lineUserId = '';
    try {
        $uStmt = $db->prepare("SELECT m_stamp_points, line_user_id FROM user WHERE user_id = ?");
        $uStmt->execute([$userId]);
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
        $newBalance = (int)($uRow['m_stamp_points'] ?? 0);
        $lineUserId = $uRow['line_user_id'] ?? '';
    } catch (Exception $e) {}

    // 9. Send Resend Email Confirmation if email is available
    $sendToEmail = $customerEmail ?: ($userRow['user_email'] ?? '');
    if (!empty($sendToEmail) && filter_var($sendToEmail, FILTER_VALIDATE_EMAIL)) {
        $paymentName = ($paycatId === 2) ? 'เก็บเงินปลายทาง (COD)' : 'โอนเงินผ่าน PromptPay QR / ธนาคาร';
        send_templated_email($db, $sendToEmail, 'order_confirmation', [
            'customer_name'    => $customerName,
            'order_no'         => $ordersNo,
            'order_date'       => date('d/m/Y H:i'),
            'order_items_html' => implode('', $itemsHtmlList),
            'subtotal'         => number_format($subtotal, 2),
            'discount_amount'  => number_format($discount, 2),
            'delivery_cost'    => number_format($shippingCost, 2),
            'grand_total'      => number_format($grandTotal, 2),
            'payment_method'   => $paymentName,
            'shipping_address' => htmlspecialchars($customerName . ' ' . $customerPhone . ' ' . $addressDetail . ' ' . $postcode),
            'm_stamp_earned'   => (string)$earnedStamps,
            'm_stamp_balance'  => (string)$newBalance,
            'tracking_url'     => ROOT_URL . '/track/' . $trackToken,
        ]);
    }

    // 10. Send LINE Push Message if user linked LINE
    if (!empty($lineUserId)) {
        $lineMsg = "📦 ขอบพระคุณสำหรับคำสั่งซื้อ Yak Pho Aroma!\nเลขที่คำสั่งซื้อ: #{$ordersNo}\nยอดชำระสุทธิ: ฿" . number_format($grandTotal, 2) . " บาท\nแต้ม M-Stamp ที่ได้รับ: +{$earnedStamps} แต้ม (ยอดรวม: {$newBalance} แต้ม)\nตรวจสอบสถานะพัสดุ: " . ROOT_URL . "/track/{$trackToken}";
        send_line_push_message($db, $lineUserId, $lineMsg);
    }

    $urlBase = defined('URL_PATH') ? URL_PATH : '';

    echo json_encode([
        'status' => true,
        'data' => [
            'order_id'              => $orderId,
            'orders_no'             => $ordersNo,
            'track_token'           => $trackToken,
            'grand_total'           => $grandTotal,
            'grand_total_formatted' => number_format($grandTotal, 2),
            'discount'              => $discount,
            'earned_stamps'         => $earnedStamps,
            'stamp_balance'         => $newBalance,
            'track_url'             => $urlBase . '/track/' . $trackToken,
            'receipt_url'           => $urlBase . '/receipt/' . $trackToken,
        ],
        'message' => $lang === 'th'
            ? "สั่งซื้อสำเร็จ! เลขที่ใบสั่งซื้อ #{$ordersNo}"
            : "Order placed successfully! Order #{$ordersNo}",
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'data' => null,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
