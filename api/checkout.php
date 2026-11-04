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

    // 1. Determine Country & Province IDs (Domestic vs International)
    $countryId = 171; // Default Thailand
    $provinceFinalId = $provinceId > 0 ? $provinceId : 1;
    $countryNameDisplay = 'Thailand';
    $addressDetail2 = trim((string)($payload['address_detail2'] ?? ''));

    if ($destination === 'international') {
        $countryInput = trim((string)($payload['destination_country'] ?? ''));
        $countryInputId = (int)($payload['destination_country_id'] ?? 0);
        
        if ($countryInputId > 0) {
            $stC = $db->prepare("SELECT country_id, country_name FROM countries WHERE country_id = ? LIMIT 1");
            $stC->execute([$countryInputId]);
            $cRow = $stC->fetch(PDO::FETCH_ASSOC);
            if ($cRow) {
                $countryId = (int)$cRow['country_id'];
                $countryNameDisplay = $cRow['country_name'];
            }
        } elseif ($countryInput !== '') {
            $stC = $db->prepare("SELECT country_id, country_name FROM countries WHERE LOWER(country_name) = LOWER(?) OR LOWER(country_code) = LOWER(?) LIMIT 1");
            $stC->execute([$countryInput, $countryInput]);
            $cRow = $stC->fetch(PDO::FETCH_ASSOC);
            if ($cRow) {
                $countryId = (int)$cRow['country_id'];
                $countryNameDisplay = $cRow['country_name'];
            } else {
                $countryNameDisplay = $countryInput;
            }
        }
        $provinceFinalId = 79; // 'ต่างประเทศ (Foreign Country)'
    }

    // 2. Find or create customer in `user` (Smart CRM Match)
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $userRow = null;

    if ($userId > 0) {
        $stmtUser = $db->prepare("SELECT * FROM user WHERE user_id = ? AND user_del = 0 LIMIT 1");
        $stmtUser->execute([$userId]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
    }

    if (!$userRow) {
        // Match by phone number or email in existing customer database
        $stmtUser = $db->prepare("
            SELECT * FROM user 
            WHERE (user_mobile = ? OR user_tel = ? OR user_username = ? OR (user_email != '' AND user_email = ?)) 
              AND user_del = 0 
            ORDER BY user_id DESC 
            LIMIT 1
        ");
        $stmtUser->execute([$customerPhone, $customerPhone, $customerPhone, $customerEmail]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
    }

    $fullCombinedAddr = trim($addressDetail . ($addressDetail2 !== '' ? (' ' . $addressDetail2) : ''));

    if ($userRow) {
        $userId = (int)$userRow['user_id'];
        $stmtUpdUser = $db->prepare("
            UPDATE user
            SET user_name = COALESCE(NULLIF(?, ''), user_name),
                user_email = COALESCE(NULLIF(?, ''), user_email),
                user_address = ?, user_postcode = ?, user_province = ?, user_country = ?,
                user_last_active = NOW(), user_update = NOW()
            WHERE user_id = ?
        ");
        $stmtUpdUser->execute([$customerName, $customerEmail, $fullCombinedAddr, $postcode, (string)$provinceFinalId, $countryNameDisplay, $userId]);
    } else {
        $stmtInsUser = $db->prepare("
            INSERT INTO user (
                user_username, user_password, user_email, user_mobile, user_tel,
                user_name, user_address, user_province, user_postcode, user_country,
                user_date, user_update, user_last_active, user_status, user_del, role,
                member_tier, m_stamp_points, user_stars
            ) VALUES (
                ?, '', ?, ?, ?,
                ?, ?, ?, ?, ?,
                NOW(), NOW(), NOW(), 1, 0, 'customer',
                'general', 0, 0
            )
        ");
        $stmtInsUser->execute([
            $customerPhone, $customerEmail, $customerPhone, $customerPhone,
            $customerName, $fullCombinedAddr, (string)$provinceFinalId, $postcode, $countryNameDisplay
        ]);
        $userId = (int)$db->lastInsertId();
    }

    // Auto set session so user is logged in
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $customerName;
    $_SESSION['user_mobile'] = $customerPhone;

    // 3. Match or Insert delivery address in `addr` (Domestic & International)
    $stmtFindAddr = $db->prepare("
        SELECT addr_id FROM addr 
        WHERE user_id = ? AND addr_detail = ? AND addr_postcode = ? AND country_id = ? AND addr_del = 0 
        LIMIT 1
    ");
    $stmtFindAddr->execute([$userId, $addressDetail, $postcode, $countryId]);
    $existingAddrId = (int)$stmtFindAddr->fetchColumn();

    if ($existingAddrId > 0) {
        $addrId = $existingAddrId;
        $db->prepare("
            UPDATE addr 
            SET addr_name = ?, addr_mobile = ?, addr_detail2 = COALESCE(NULLIF(?, ''), addr_detail2), provinces_id = ?, addr_update = NOW() 
            WHERE addr_id = ?
        ")->execute([$customerName, $customerPhone, $addressDetail2, $provinceFinalId, $addrId]);
    } else {
        $stmtAddr = $db->prepare("
            INSERT INTO addr (
                addr_name, addr_mobile, addr_detail, addr_detail2, addr_postcode,
                country_id, provinces_id, addr_status, addr_del, addr_type, user_id, addr_date, addr_update
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, 1, 0, 1, ?, NOW(), NOW()
            )
        ");
        $stmtAddr->execute([
            $customerName, $customerPhone, $addressDetail, $addressDetail2, $postcode,
            $countryId, $provinceFinalId, $userId
        ]);
        $addrId = (int)$db->lastInsertId();
    }

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
        $currPoints = get_m_stamp_balance($db, $userId);

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
    if ($appliedStamps > 0) {
        $fullNote = trim("ใช้แต้ม E-Stamp: {$appliedStamps} แต้ม (-฿{$appliedStamps}) | " . $fullNote, " |");
    }

    // Determine initial order status:
    // - paycat_id == 2 (COD): status 4 (D - เตรียมสินค้า-COD) so payment step is bypassed
    // - paycat_id == 1 (Bank Transfer / PromptPay): status 2 (B - ที่ต้องชำระ) if grandTotal > 0, else 3 (C - เตรียมสินค้า)
    // /* TODO: paycat_id == 3 (Credit Card Gateway - not connected yet) */
    $initialOrderStatus = 2;
    if ($paycatId === 2) {
        $initialOrderStatus = 4;
    } elseif ($grandTotal <= 0) {
        $initialOrderStatus = 3;
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
            ?, ?, ?, 0, ?,
            'Website', {$countryId}, ?, ?
        )
    ");
    $isWholesale = ($cart['total_1kg_qty'] >= 6) ? 1 : 0;
    $stmtOrder->execute([
        $userId, $ordersNo, $trackToken,
        $subtotal, $shippingCost, $discount, $codFee, $grandTotal,
        $initialOrderStatus, $fullNote, $addrId, $paycatId,
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
    $itemsTextLines = [];
    $totalOrderQty = 0;
    foreach ($cart['items'] as $item) {
        $pid = (int)$item['product_id'];
        $qty = (int)$item['qty'];
        $totalOrderQty += $qty;
        $uPrice = (float)$item['unit_price'];
        $lineSub = (float)$item['line_total'];
        // Keep ordetail_memo empty for customer web orders (reserved strictly for Admin production notes e.g. ใส่กระปุก, ไม่ผสมสีเขียว)
        $memo = '';

        $stmtCost = $db->prepare("SELECT product_cprice FROM product WHERE product_id = ?");
        $stmtCost->execute([$pid]);
        $itemCost = (float)($stmtCost->fetchColumn() ?: 0.0);

        $stmtDetail->execute([
            $orderId, $pid, $userId, $qty, $uPrice, $uPrice, $lineSub, $itemCost, $memo
        ]);

        $itemsHtmlList[] = "<tr><td style='padding:8px;border-bottom:1px solid #eee;'>" . htmlspecialchars($item['product_name']) . "</td><td style='text-align:right;padding:8px;border-bottom:1px solid #eee;'>{$qty}</td><td style='text-align:right;padding:8px;border-bottom:1px solid #eee;'>" . number_format($lineSub, 2) . " บาท</td></tr>";
        $itemsTextLines[] = "• " . $item['product_name'] . " x{$qty} (฿" . number_format($lineSub, 0) . ")";
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

    // Fetch user's latest point balance, email, and LINE ID from `user` table
    $newBalance = 0;
    $lineUserId = '';
    $uRow = [];
    try {
        $newBalance = get_m_stamp_balance($db, $userId);
        $uStmt = $db->prepare("SELECT * FROM user WHERE user_id = ? LIMIT 1");
        $uStmt->execute([$userId]);
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $lineUserId = (string)($uRow['line_user_id'] ?? '');
    } catch (Throwable $e) {}

    // Lookup province name for full address formatting
    $provinceNameTh = '';
    if ($destination === 'domestic' && $provinceFinalId > 0) {
        try {
            $stProv = $db->prepare("SELECT name_in_thai FROM provinces WHERE id = ? LIMIT 1");
            $stProv->execute([$provinceFinalId]);
            $provinceNameTh = (string)($stProv->fetchColumn() ?: '');
        } catch (Throwable $e) {}
    }
    $formattedAddress = trim($addressDetail . ($addressDetail2 !== '' ? ' ' . $addressDetail2 : '') . ($provinceNameTh !== '' ? ' จ.' . $provinceNameTh : '') . ' ' . $postcode);
    if ($destination === 'international') {
        $formattedAddress = trim($addressDetail . ' ' . $addressDetail2 . ' ' . $postcode . ' (' . $countryNameDisplay . ')');
    }

    $paymentName = ($paycatId === 2) ? 'เก็บเงินปลายทาง (COD +3%)' : 'โอนเงินผ่าน PromptPay QR / บัญชีธนาคาร';
    $statusTextTh = ($paycatId === 2) ? 'เตรียมสินค้า-COD (ข้ามขั้นตอนชำระเงิน)' : ($grandTotal > 0 ? 'รอชำระเงิน / รอแนบสลิป' : 'ชำระแล้ว / เตรียมสินค้า');
    $customerActionUrl = ($paycatId === 1 && $grandTotal > 0)
        ? (ROOT_URL . '/pay/' . $trackToken)
        : (ROOT_URL . '/track/' . $trackToken);

    // 9.1 Send Email to Customer (if customer has an email in form or in `user` profile)
    $sendToEmail = trim((string)($customerEmail ?: ($uRow['user_email'] ?? ($userRow['user_email'] ?? ''))));
    if ($sendToEmail !== '' && filter_var($sendToEmail, FILTER_VALIDATE_EMAIL)) {
        try {
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
                'shipping_address' => htmlspecialchars($customerName . ' (' . $customerPhone . ') ' . $formattedAddress),
                'm_stamp_earned'   => (string)$earnedStamps,
                'm_stamp_balance'  => (string)$newBalance,
                'tracking_url'     => $customerActionUrl,
            ]);
        } catch (Throwable $e) {}
    }

    // 9.2 Send Order Notification Email to Admin (thaiherbfc@gmail.com)
    try {
        $otherDiscVal = max(0.0, $discount - (float)$appliedStamps);
        send_admin_order_email($db, [
            'orders_no'        => $ordersNo,
            'order_date'       => date('d/m/Y H:i'),
            'customer_name'    => $customerName,
            'customer_code'    => $userId > 0 ? ('C' . $userId) : 'Guest',
            'customer_phone'   => $customerPhone,
            'customer_email'   => $sendToEmail ?: '-',
            'customer_line'    => $lineId ?: ($uRow['line_display_name'] ?? '-'),
            'shipping_address' => $formattedAddress,
            'payment_method'   => $paymentName,
            'status_text'      => $statusTextTh,
            'order_items_html' => implode('', $itemsHtmlList),
            'total_qty'        => $totalOrderQty,
            'total_weight_g'   => (int)($cart['total_weight_grams'] ?? 0),
            'subtotal'         => number_format($subtotal, 2),
            'delivery_cost'    => number_format($shippingCost, 2),
            'cod_fee'          => number_format($codFee, 2),
            'stamp_discount'   => (string)$appliedStamps,
            'other_discount'   => number_format($otherDiscVal, 2),
            'grand_total'      => number_format($grandTotal, 2),
            'order_note'       => $fullNote ?: '-',
            'tracking_url'     => ROOT_URL . '/track/' . $trackToken,
            'admin_url'        => ROOT_URL . '/admin/orders/?search=' . urlencode($ordersNo),
        ]);
    } catch (Throwable $e) {}

    // 10.1 Send LINE Push Notification to Customer (if customer linked LINE)
    if (!empty($lineUserId)) {
        try {
            $custItemsSummary = implode("\n", array_slice($itemsTextLines, 0, 8));
            if (count($itemsTextLines) > 8) {
                $custItemsSummary .= "\n...และอื่นๆ อีก " . (count($itemsTextLines) - 8) . " รายการ";
            }
            $discountLineText = '';
            if ($appliedStamps > 0) {
                $discountLineText .= "\n🪙 ส่วนลด E-Stamp: -฿" . number_format($appliedStamps, 2) . " ({$appliedStamps} แต้ม)";
            }
            if (($discount - $appliedStamps) > 0) {
                $discountLineText .= "\n🏷️ ส่วนลดคูปอง/โปรโมชั่น: -฿" . number_format($discount - $appliedStamps, 2);
            }
            $actionLineHint = ($paycatId === 1 && $grandTotal > 0)
                ? "📲 สแกน QR พร้อมเพย์ / แนบสลิปโอนเงิน:\n" . ROOT_URL . "/pay/{$trackToken}"
                : "🚚 ติดตามสถานะการจัดส่ง:\n" . ROOT_URL . "/track/{$trackToken}";

            $lineMsg = "🌿 ขอบพระคุณสำหรับคำสั่งซื้อ Yak Pho Aroma!\n"
                     . "━━━━━━━━━━━━━━\n"
                     . "🧾 เลขที่คำสั่งซื้อ: #{$ordersNo}\n"
                     . "💳 ช่องทางชำระ: {$paymentName}\n"
                     . "📌 สถานะ: {$statusTextTh}\n"
                     . "━━━━━━━━━━━━━━\n"
                     . "🛒 รายการสินค้า ({$totalOrderQty} ชิ้น):\n{$custItemsSummary}\n"
                     . "━━━━━━━━━━━━━━\n"
                     . "📦 ค่าจัดส่ง: ฿" . number_format($shippingCost, 2)
                     . ($codFee > 0 ? "\n💵 ค่าธรรมเนียม COD: ฿" . number_format($codFee, 2) : "")
                     . $discountLineText . "\n"
                     . "💰 ยอดชำระสุทธิ: ฿" . number_format($grandTotal, 2) . " บาท\n"
                     . "✨ ได้รับสะสม: +{$earnedStamps} E-Stamp (คงเหลือ {$newBalance} แต้ม)\n"
                     . "━━━━━━━━━━━━━━\n"
                     . $actionLineHint;
            send_line_push_message($db, $lineUserId, $lineMsg);
        } catch (Throwable $e) {}
    }

    // 10.2 Send LINE Push Notification Report to Admin
    try {
        $adminItemsSummary = implode("\n", array_slice($itemsTextLines, 0, 12));
        if (count($itemsTextLines) > 12) {
            $adminItemsSummary .= "\n...และอื่นๆ อีก " . (count($itemsTextLines) - 12) . " รายการ";
        }
        $adminDiscountInfo = '';
        if ($appliedStamps > 0) {
            $adminDiscountInfo .= "\n• ใช้แต้ม E-Stamp: {$appliedStamps} แต้ม (-฿" . number_format($appliedStamps, 2) . ")";
        }
        if (($discount - $appliedStamps) > 0) {
            $adminDiscountInfo .= "\n• ส่วนลดคูปอง/เรทส่ง: -฿" . number_format($discount - $appliedStamps, 2);
        }

        $adminLineMsg = "🔔 [มีคำสั่งซื้อใหม่เข้าสู่ระบบ!]\n"
                      . "เลขที่: #{$ordersNo}\n"
                      . "เวลา: " . date('d/m/Y H:i') . " น.\n"
                      . "━━━━━━━━━━━━━━\n"
                      . "👤 ลูกค้า: {$customerName}" . ($userId > 0 ? " (C{$userId})" : "") . "\n"
                      . "📞 โทร: {$customerPhone}\n"
                      . ($sendToEmail !== '' ? "✉️ อีเมล: {$sendToEmail}\n" : "")
                      . "📍 ที่อยู่: {$formattedAddress}\n"
                      . "━━━━━━━━━━━━━━\n"
                      . "🛒 รายการสินค้า ({$totalOrderQty} ชิ้น | " . number_format((int)($cart['total_weight_grams'] ?? 0)) . "g):\n"
                      . $adminItemsSummary . "\n"
                      . "━━━━━━━━━━━━━━\n"
                      . "• ยอดรวมสินค้า: ฿" . number_format($subtotal, 2) . "\n"
                      . "• ค่าจัดส่ง: ฿" . number_format($shippingCost, 2)
                      . ($codFee > 0 ? "\n• ค่าธรรมเนียม COD: ฿" . number_format($codFee, 2) : "")
                      . $adminDiscountInfo . "\n"
                      . "💰 ยอดสุทธิ: ฿" . number_format($grandTotal, 2) . " บาท\n"
                      . "💳 ชำระโดย: {$paymentName}\n"
                      . "📌 สถานะ: {$statusTextTh}\n"
                      . ($fullNote !== '' ? "📝 หมายเหตุ: {$fullNote}\n" : "")
                      . "━━━━━━━━━━━━━━\n"
                      . "🔗 จัดการออเดอร์:\n" . ROOT_URL . "/admin/orders/?search={$ordersNo}";
        send_admin_line_notification($db, $adminLineMsg);
    } catch (Throwable $e) {}

    $urlBase = defined('URL_PATH') ? URL_PATH : '';

    echo json_encode([
        'status' => true,
        'data' => [
            'order_id'              => $orderId,
            'orders_no'             => $ordersNo,
            'track_token'           => $trackToken,
            'paycat_id'             => $paycatId,
            'grand_total'           => $grandTotal,
            'grand_total_formatted' => number_format($grandTotal, 2),
            'discount'              => $discount,
            'applied_stamps'        => $appliedStamps,
            'earned_stamps'         => $earnedStamps,
            'stamp_balance'         => $newBalance,
            'pay_url'               => $urlBase . '/pay/' . $trackToken,
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
