<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $note_text = isset($_POST['note_text']) ? trim($_POST['note_text']) : '';
    $user_id = $_SESSION['admin_id']; // Current logged in staff

    if (!$order_id || empty($note_text)) {
        throw new Exception('กรุณาระบุข้อมูลบันทึก');
    }

    $note_image = null;

    // Handle Image Upload if exists
    if (isset($_FILES['note_image']) && $_FILES['note_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/notes';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['note_image']['name'], PATHINFO_EXTENSION));
        $filename = uniqid('note_') . '_' . time();
        $targetPath = $uploadDir . '/' . $filename . '.webp';

        // Load image
        if ($ext === 'jpg' || $ext === 'jpeg') $sourceImage = imagecreatefromjpeg($_FILES['note_image']['tmp_name']);
        elseif ($ext === 'png') $sourceImage = imagecreatefrompng($_FILES['note_image']['tmp_name']);
        elseif ($ext === 'webp') $sourceImage = imagecreatefromwebp($_FILES['note_image']['tmp_name']);
        else throw new Exception('Format not supported (JPG, PNG, WEBP only)');

        // Resize & Convert to WebP (Space saving)
        $maxWidth = 1000;
        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int)($height * ($maxWidth / $width));
            $tmp = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($tmp, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($sourceImage);
            $sourceImage = $tmp;
        }

        imagewebp($sourceImage, $targetPath, 70);
        imagedestroy($sourceImage);
        $note_image = $filename . '.webp';
    }

    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO order_notes (orders_id, user_id, note_text, note_image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$order_id, $user_id, $note_text, $note_image]);

    // Log Activity
    logStaffAction($db, 'CREATE', 'order_notes', $order_id, "Added new order note");

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'บันทึกเรียบร้อยแล้ว']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
