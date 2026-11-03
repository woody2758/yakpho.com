<?php
declare(strict_types=1);

/**
 * ============================================================
 * YakPho & Esther Aroma — Order Tracking Lookup API
 * ============================================================
 * Resolves orders_no, track_token, or customer phone number into
 * a valid /track/{track_token} URL.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';

try {
    $q = trim((string)($_GET['q'] ?? ($_POST['q'] ?? '')));
    $lang = trim((string)($_GET['lang'] ?? ($_SESSION['lang'] ?? 'th')));

    if ($q === '') {
        throw new Exception($lang === 'th' ? 'กรุณากรอกเลขที่ใบสั่งซื้อ หรือรหัสติดตามพัสดุ' : 'Please enter an Order Number or Tracking Token');
    }

    $cleanQ = ltrim($q, '#');
    $stmt = $db->prepare("
        SELECT o.orders_id, o.orders_no, o.track_token
        FROM orders o
        LEFT JOIN addr a ON o.addr_id = a.addr_id
        WHERE o.orders_del = 0
          AND (o.orders_no = ? OR o.track_token = ? OR o.orders_tracking = ? OR a.addr_mobile = ?)
        ORDER BY o.orders_id DESC
        LIMIT 1
    ");
    $stmt->execute([$cleanQ, $cleanQ, $cleanQ, $cleanQ]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception($lang === 'th'
            ? "ไม่พบข้อมูลออเดอร์สำหรับ '{$q}' กรุณาตรวจสอบเลขใบสั่งซื้อหรือเบอร์โทรศัพท์อีกครั้ง"
            : "No order found matching '{$q}'. Please check your order number or phone.");
    }

    $token = !empty($order['track_token']) ? $order['track_token'] : $order['orders_no'];
    $urlBase = defined('URL_PATH') ? URL_PATH : '';

    echo json_encode([
        'status' => true,
        'data' => [
            'orders_no' => $order['orders_no'],
            'track_token' => $token,
            'track_url' => $urlBase . '/track/' . $token,
            'receipt_url' => $urlBase . '/receipt/' . $token,
        ],
        'message' => 'OK',
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'data' => null,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

