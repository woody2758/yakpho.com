<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — Customer Payment Slip Upload API
 * ============================================================
 * Connects to the existing Admin slip system:
 *  - Saves WebP slip into `uploads/slips/`
 *  - Inserts record into `order_slips (orders_id, slip_filename, uploaded_by)`
 *  - Updates `orders` set `orders_slip = ?, orders_paid_amount = orders_grandtotal, orders_status = 3, orders_update = NOW()`
 *    (Status 3 = C - เตรียมสินค้า / ชำระเงินเรียบร้อยแล้ว)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions/image.php';
require_once __DIR__ . '/../includes/member_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method Not Allowed');
    }

    $token = trim((string)($_POST['track_token'] ?? $_POST['v'] ?? ''));
    $orderIdInput = (int)($_POST['order_id'] ?? 0);

    if ($token === '' && $orderIdInput <= 0) {
        throw new Exception('ไม่พบรหัสอ้างอิงคำสั่งซื้อ');
    }

    // Lookup order
    if ($token !== '') {
        $stmtOrd = $db->prepare("SELECT * FROM orders WHERE (track_token = ? OR orders_no = ?) AND orders_del = 0 LIMIT 1");
        $stmtOrd->execute([$token, $token]);
    } else {
        $stmtOrd = $db->prepare("SELECT * FROM orders WHERE orders_id = ? AND orders_del = 0 LIMIT 1");
        $stmtOrd->execute([$orderIdInput]);
    }
    $order = $stmtOrd->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('ไม่พบข้อมูลคำสั่งซื้อในระบบ');
    }

    $orderId = (int)$order['orders_id'];
    $ordersNo = (string)$order['orders_no'];
    $trackKey = !empty($order['track_token']) ? (string)$order['track_token'] : $ordersNo;

    if (empty($_FILES['slip_image']) || $_FILES['slip_image']['error'] !== UPLOAD_ERR_OK) {
        $errCode = $_FILES['slip_image']['error'] ?? -1;
        throw new Exception('กรุณาเลือกไฟล์รูปภาพสลิปโอนเงิน (รหัสข้อผิดพลาด: ' . $errCode . ')');
    }

    $file = $_FILES['slip_image'];

    // Validate MIME type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/bmp', 'image/x-ms-bmp', 'image/heic', 'image/heif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string)$finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes, true)) {
        throw new Exception('ไฟล์ที่อัปโหลดต้องเป็นรูปภาพสลิปเท่านั้น (JPG, PNG, WEBP, GIF)');
    }

    // Ensure uploads/slips directory exists
    $uploadDir = __DIR__ . '/../uploads/slips';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'slip_' . $orderId . '_' . time() . '_' . bin2hex(random_bytes(4));
    $targetFilename = $filename . '.webp';
    $targetPath = $uploadDir . '/' . $targetFilename;

    $saved = false;
    if (function_exists('load_image')) {
        $sourceImage = @load_image($file['tmp_name']);
        if ($sourceImage) {
            $maxWidth = 1000;
            $maxHeight = 1000;
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);

            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                $resizedImage = resize_image($sourceImage, $newWidth, $newHeight);
                imagedestroy($sourceImage);
                $sourceImage = $resizedImage;
            }

            if (@save_as_webp($sourceImage, $targetPath, 75)) {
                $saved = true;
            }
            imagedestroy($sourceImage);
        }
    }

    // Fallback to raw move if GD WebP conversion fails
    if (!$saved) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }
        $targetFilename = $filename . '.' . $ext;
        $targetPath = $uploadDir . '/' . $targetFilename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('ไม่สามารถบันทึกไฟล์สลิปลงเซิร์ฟเวอร์ได้');
        }
    }

    // Auto-ensure order_slips table exists
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `order_slips` (
                `slip_id` INT AUTO_INCREMENT PRIMARY KEY,
                `orders_id` INT NOT NULL,
                `slip_filename` VARCHAR(255) NOT NULL,
                `slip_uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `uploaded_by` INT DEFAULT 0,
                INDEX `idx_orders_id` (`orders_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {}

    // 1. Insert into order_slips (used by Admin Orders Table)
    $stmtSlip = $db->prepare("INSERT INTO order_slips (orders_id, slip_filename, uploaded_by) VALUES (?, ?, 0)");
    $stmtSlip->execute([$orderId, $targetFilename]);

    // 2. Update orders table: set orders_slip, orders_paid_amount = orders_grandtotal, and advance status to 3 (C - เตรียมสินค้า / ชำระแล้ว)
    $currentStatus = (int)($order['orders_status'] ?? 1);
    $newStatus = in_array($currentStatus, [1, 2], true) ? 3 : $currentStatus;
    $paidAmount = (float)($order['orders_grandtotal'] ?? 0);

    $stmtUpd = $db->prepare("
        UPDATE orders
        SET orders_slip = ?,
            orders_paid_amount = ?,
            orders_status = ?,
            orders_update = NOW()
        WHERE orders_id = ?
    ");
    $stmtUpd->execute([$targetFilename, $paidAmount, $newStatus, $orderId]);

    $urlBase = defined('URL_PATH') ? URL_PATH : '';
    $trackUrl = $urlBase . '/track/' . urlencode($trackKey) . '?paid=1';

    echo json_encode([
        'success'   => true,
        'status'    => true,
        'message'   => 'อัปโหลดสลิปแจ้งชำระเงินเรียบร้อยแล้ว!',
        'filename'  => $targetFilename,
        'track_url' => $trackUrl,
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'status'  => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
