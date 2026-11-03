<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['user_id']) || !isset($data['filename'])) {
        throw new Exception('Invalid input');
    }

    $user_id = (int)$data['user_id'];
    $filename = $data['filename'];

    // Security Check: Only Owner and Manager can delete
    $allowed_roles = ['owner', 'manager'];
    if (!in_array($_SESSION['admin_role'], $allowed_roles)) {
        throw new Exception('คุณไม่มีสิทธิ์ในการลบรูปภาพประวัติ');
    }

    // Security Check: Prevent directory traversal
    $filename = basename($filename);
    $filePath = "../../uploads/profile/{$user_id}/{$filename}";

    if (!file_exists($filePath)) {
        throw new Exception('ไม่พบไฟล์รูปภาพที่ต้องการลบ');
    }

    // Optional: Prevent deleting the CURRENT profile picture
    $stmt = $db->prepare("SELECT user_picture FROM user WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $currentUser = $stmt->fetch();
    if ($currentUser && $currentUser['user_picture'] === $filename) {
        throw new Exception('ไม่สามารถลบรูปภาพที่กำลังใช้งานอยู่ได้');
    }

    // Delete file
    if (!unlink($filePath)) {
        throw new Exception('ลบไฟล์ล้มเหลว');
    }

    // Log history
    $stmt = $db->prepare("INSERT INTO user_history (user_id, action, details) VALUES (?, 'delete_history_image', ?)");
    $stmt->execute([$user_id, "Deleted history image: $filename"]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
