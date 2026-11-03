<?php
declare(strict_types=1);

/**
 * ============================================================
 * YakPho Aroma — Discount Coupon Verification & Application API
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method Not Allowed');
    }

    $rawInput = file_get_contents('php://input');
    $payload = !empty($rawInput) ? json_decode($rawInput, true) : $_POST;
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $code = strtoupper(trim((string)($payload['coupon_code'] ?? '')));
    $subtotal = max(0.0, (float)($payload['subtotal'] ?? 0.0));

    if ($code === '') {
        throw new Exception('กรุณาระบุโค้ดส่วนลด');
    }

    $stmt = $db->prepare("
        SELECT * FROM coupons
        WHERE UPPER(coupon_code) = ? AND status = 1
        LIMIT 1
    ");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        throw new Exception('โค้ดส่วนลดไม่ถูกต้อง หรือหมดอายุแล้ว');
    }

    // Check dates
    $now = date('Y-m-d H:i:s');
    if (!empty($coupon['start_date']) && $now < $coupon['start_date']) {
        throw new Exception('โค้ดส่วนลดยังไม่เริ่มใช้งาน');
    }
    if (!empty($coupon['end_date']) && $now > $coupon['end_date']) {
        throw new Exception('โค้ดส่วนลดหมดอายุการใช้งานแล้ว');
    }

    // Check usage limit
    $limit = (int)$coupon['usage_limit'];
    $used = (int)$coupon['used_count'];
    if ($limit > 0 && $used >= $limit) {
        throw new Exception('โค้ดส่วนลดถูกใช้ครบตามสิทธิ์แล้ว');
    }

    // Check minimum order
    $minOrder = (float)$coupon['min_order'];
    if ($minOrder > 0 && $subtotal < $minOrder) {
        throw new Exception("ยอดสั่งซื้อขั้นต่ำ ฿" . number_format($minOrder, 0) . " สำหรับโค้ดนี้");
    }

    // Calculate discount
    $discType = $coupon['discount_type'];
    $discVal = (float)$coupon['discount_value'];
    $maxDisc = (float)$coupon['max_discount'];
    $discount = 0.0;

    if ($discType === 'percent') {
        $discount = round($subtotal * ($discVal / 100), 2);
        if ($maxDisc > 0 && $discount > $maxDisc) {
            $discount = $maxDisc;
        }
    } else {
        $discount = min($discVal, $subtotal);
    }

    echo json_encode([
        'status' => true,
        'data' => [
            'code' => $coupon['coupon_code'],
            'discount_type' => $discType,
            'discount_value' => $discVal,
            'discount' => $discount,
            'message' => 'ใช้โค้ดส่วนลด ' . $coupon['coupon_code'] . ' ประหยัด ฿' . number_format($discount, 0) . ' สำเร็จ!'
        ],
        'message' => 'ใช้โค้ดส่วนลดสำเร็จ'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

