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

    // Update user table
    $stmt = $db->prepare("UPDATE user SET user_picture = ? WHERE user_id = ?");
    $stmt->execute([$filename, $user_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
