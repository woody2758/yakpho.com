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
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        throw new Exception('Invalid file type');
    }

    // Upload
    $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
    $uploadDir = '../../uploads/profile/' . $user_id . '/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $target = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception('Upload failed');
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
