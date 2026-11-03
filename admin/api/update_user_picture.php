<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['user_id']) || !isset($_FILES['user_picture'])) {
        throw new Exception('Invalid input');
    }

    $user_id = (int)$_POST['user_id'];
    $file = $_FILES['user_picture'];

    // Validate file
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'jfif', 'svg', 'bmp', 'heic', 'heif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        throw new Exception('Invalid file type. Supported: ' . implode(', ', $allowed));
    }

    require_once __DIR__ . '/../../includes/functions/image.php';

    // Upload
    $uploadDir = '../../uploads/profile/' . $user_id . '/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if ($ext === 'svg') {
        $filename = 'user_' . $user_id . '_' . time() . '.svg';
        $target = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new Exception('Upload failed');
        }
    } else {
        $filename = 'user_' . $user_id . '_' . time() . '.webp';
        $target = $uploadDir . $filename;
        
        $sourceImage = load_image($file['tmp_name']);
        if (!$sourceImage) {
            throw new Exception('ไม่สามารถอ่านไฟล์รูปภาพได้ โปรดใช้รูปภาพมาตรฐาน (JPG, PNG, WEBP, AVIF)');
        }
        
        // Resize and crop to 500x500
        $resizedImage = resize_image($sourceImage, 500, 500, true);
        imagedestroy($sourceImage);
        
        if (!save_as_webp($resizedImage, $target, 85)) {
            imagedestroy($resizedImage);
            throw new Exception('Failed to save WebP image');
        }
        imagedestroy($resizedImage);
    }

    $db->beginTransaction();

    // Update user table
    $stmt = $db->prepare("UPDATE user SET user_picture = ? WHERE user_id = ?");
    $stmt->execute([$filename, $user_id]);

    // Log history
    $stmt = $db->prepare("INSERT INTO user_history (user_id, action, details) VALUES (?, 'update_picture', ?)");
    $stmt->execute([$user_id, "Updated profile picture to $filename"]);

    $db->commit();

    echo json_encode(['success' => true, 'filename' => $filename]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
