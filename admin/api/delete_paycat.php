<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check permission (Admin only)
require_role('admin');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['paycat_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

try {
    $id = (int)$data['paycat_id'];

    // Soft Delete
    $stmt = $db->prepare("UPDATE paycat SET paycat_del = 1, paycat_update = NOW() WHERE paycat_id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
