<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('ID is required');
    }

    $id = (int)$data['id'];

    // Soft delete
    $stmt = $db->prepare("UPDATE transcat SET transcat_del = 1, transcat_update = NOW() WHERE transcat_id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
