<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    require_once __DIR__ . '/../../includes/functions/image.php';

    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    if (!$orderId) throw new Exception('Order ID is required');

    if (empty($_FILES['slip_image'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['slip_image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and WebP are allowed.');
    }

    // Create upload directory if not exists
    $uploadDir = __DIR__ . '/../../uploads/slips';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $filename = 'slip_' . $orderId . '_' . time() . '_' . bin2hex(random_bytes(4));
    
    // Convert to WebP and Resize (max 1200px width/height to keep readable but small)
    $sourceImage = load_image($file['tmp_name']);
    if (!$sourceImage) {
        throw new Exception('Failed to load image');
    }

    // Resize if too large (e.g. > 1200px)
    $maxWidth = 1200;
    $maxHeight = 1200;
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

    $targetFilename = $filename . '.webp';
    $targetPath = $uploadDir . '/' . $targetFilename;

    if (!save_as_webp($sourceImage, $targetPath, 85)) {
        throw new Exception('Failed to save WebP image');
    }
    imagedestroy($sourceImage);

    // Insert into order_slips table
    $stmt = $db->prepare("INSERT INTO order_slips (orders_id, slip_filename, uploaded_by) VALUES (?, ?, ?)");
    $stmt->execute([$orderId, $targetFilename, $_SESSION['admin_id'] ?? 0]);

    // Also update the main orders table for backward compatibility (optional, or just use the latest one)
    $stmt = $db->prepare("UPDATE orders SET orders_slip = ? WHERE orders_id = ?");
    $stmt->execute([$targetFilename, $orderId]);

    echo json_encode([
        'success' => true,
        'message' => 'อัพโหลดสลิปเรียบร้อยแล้ว',
        'filename' => $targetFilename
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
