<?php
declare(strict_types=1);

/**
 * ============================================================
 * YakPho & Esther Aroma — Shopping Cart JSON API
 * ============================================================
 * Strictly returns { "status": bool, "data": array, "message": string }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/frontend_data.php';

try {
    $rawInput = file_get_contents('php://input');
    $payload = !empty($rawInput) ? json_decode($rawInput, true) : [];
    if (!is_array($payload)) {
        $payload = [];
    }

    $action = trim((string)($payload['action'] ?? ($_REQUEST['action'] ?? 'get')));
    $lang = trim((string)($payload['lang'] ?? ($_REQUEST['lang'] ?? ($_SESSION['lang'] ?? 'th'))));

    if (!isset($_SESSION['yakpho_cart']) || !is_array($_SESSION['yakpho_cart'])) {
        $_SESSION['yakpho_cart'] = [];
    }

    $message = 'ดึงข้อมูลตะกร้าสินค้าสำเร็จ';

    switch ($action) {
        case 'add':
            $productId = (int)($payload['product_id'] ?? ($_POST['product_id'] ?? 0));
            $qty = max(1, (int)($payload['qty'] ?? ($_POST['qty'] ?? 1)));
            if ($productId <= 0) {
                throw new Exception($lang === 'th' ? 'รหัสสินค้าไม่ถูกต้อง' : 'Invalid product ID');
            }
            $currentQty = (int)($_SESSION['yakpho_cart'][$productId] ?? 0);
            $_SESSION['yakpho_cart'][$productId] = $currentQty + $qty;
            $message = $lang === 'th' ? 'เพิ่มสินค้าลงในตะกร้าเรียบร้อยแล้ว' : 'Added item to your cart';
            break;

        case 'add_bulk':
            $items = $payload['items'] ?? [];
            if (!is_array($items) || empty($items)) {
                throw new Exception($lang === 'th' ? 'กรุณาระบุจำนวนสินค้าอย่างน้อย 1 รายการ' : 'Please select at least 1 item');
            }
            $addedCount = 0;
            foreach ($items as $row) {
                $pid = (int)($row['product_id'] ?? 0);
                $q = (int)($row['qty'] ?? 0);
                if ($pid > 0 && $q > 0) {
                    $_SESSION['yakpho_cart'][$pid] = (int)($_SESSION['yakpho_cart'][$pid] ?? 0) + $q;
                    $addedCount += $q;
                }
            }
            if ($addedCount <= 0) {
                throw new Exception($lang === 'th' ? 'กรุณาระบุจำนวนสินค้าอย่างน้อย 1 กก.' : 'Please enter quantity of at least 1 kg');
            }
            $message = $lang === 'th'
                ? "เพิ่มรายการสั่งซื้อเรทส่งรวม {$addedCount} กก. ลงในตะกร้าแล้ว"
                : "Added {$addedCount} kg wholesale items to your cart";
            break;

        case 'update':
            $productId = (int)($payload['product_id'] ?? ($_POST['product_id'] ?? 0));
            $qty = (int)($payload['qty'] ?? ($_POST['qty'] ?? 0));
            if ($productId <= 0) {
                throw new Exception($lang === 'th' ? 'รหัสสินค้าไม่ถูกต้อง' : 'Invalid product ID');
            }
            if ($qty <= 0) {
                unset($_SESSION['yakpho_cart'][$productId]);
                $message = $lang === 'th' ? 'นำสินค้าออกจากตะกร้าแล้ว' : 'Item removed from cart';
            } else {
                $_SESSION['yakpho_cart'][$productId] = min(999, $qty);
                $message = $lang === 'th' ? 'อัปเดตจำนวนสินค้าเรียบร้อยแล้ว' : 'Cart quantity updated';
            }
            break;

        case 'remove':
            $productId = (int)($payload['product_id'] ?? ($_POST['product_id'] ?? 0));
            unset($_SESSION['yakpho_cart'][$productId]);
            $message = $lang === 'th' ? 'นำสินค้าออกจากตะกร้าแล้ว' : 'Item removed from cart';
            break;

        case 'clear':
            $_SESSION['yakpho_cart'] = [];
            $message = $lang === 'th' ? 'ล้างตะกร้าสินค้าเรียบร้อยแล้ว' : 'Cart cleared';
            break;

        case 'get':
        default:
            $message = 'OK';
            break;
    }

    $cartSummary = get_cart_summary($db, $lang);

    echo json_encode([
        'status' => true,
        'data' => array_merge($cartSummary, ['cart' => $cartSummary]),
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'data' => null,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

