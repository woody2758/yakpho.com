<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['user_id'])) {
        throw new Exception('Invalid user ID');
    }

    $user_id = (int)$_GET['user_id'];
    $uploadDir = '../../uploads/profile/' . $user_id . '/';
    $images = [];

    if (file_exists($uploadDir)) {
        $files = scandir($uploadDir, SCANDIR_SORT_DESCENDING);
        foreach ($files as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                $images[] = [
                    'filename' => $file,
                    'url' => ROOT_URL . '/uploads/profile/' . $user_id . '/' . $file,
                    'time' => filemtime($uploadDir . $file)
                ];
            }
        }
    }

    echo json_encode(['success' => true, 'images' => $images]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
