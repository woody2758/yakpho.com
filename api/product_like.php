<?php
declare(strict_types=1);

/**
 * ============================================================
 * YakPho Aroma — Product Wishlist & Like Counter JSON API
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

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

    $productId = (int)($payload['product_id'] ?? 0);
    if ($productId <= 0) {
        throw new Exception('รหัสสินค้าไม่ถูกต้อง');
    }

    $sessionId = session_id();
    $userId = (int)($_SESSION['user_id'] ?? 0);

    // Check if user already liked this product
    $stmtCheck = $db->prepare("
        SELECT id FROM user_wishlist 
        WHERE (session_id = ? OR (user_id > 0 AND user_id = ?)) AND product_id = ? 
        LIMIT 1
    ");
    $stmtCheck->execute([$sessionId, $userId, $productId]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    $db->beginTransaction();

    if ($existing) {
        // Un-like
        $del = $db->prepare("DELETE FROM user_wishlist WHERE id = ?");
        $del->execute([$existing['id']]);

        $upd = $db->prepare("UPDATE product SET product_likes = GREATEST(0, product_likes - 1) WHERE product_id = ?");
        $upd->execute([$productId]);
        $liked = false;
        $message = 'ยกเลิกถูกใจแล้ว';
    } else {
        // Like
        $ins = $db->prepare("
            INSERT INTO user_wishlist (user_id, session_id, product_id, created_at) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE created_at = NOW()
        ");
        $ins->execute([$userId > 0 ? $userId : null, $sessionId, $productId]);

        $upd = $db->prepare("UPDATE product SET product_likes = product_likes + 1 WHERE product_id = ?");
        $upd->execute([$productId]);
        $liked = true;
        $message = 'ถูกใจสินค้านี้แล้ว';
    }

    $db->commit();

    // Get current likes count
    $stmtCount = $db->prepare("SELECT product_likes FROM product WHERE product_id = ?");
    $stmtCount->execute([$productId]);
    $newCount = (int)$stmtCount->fetchColumn();

    echo json_encode([
        'status' => true,
        'data' => [
            'product_id' => $productId,
            'liked' => $liked,
            'likes_count' => $newCount,
        ],
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

