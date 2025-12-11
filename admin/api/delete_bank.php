<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check permission (Admin only)
require_role('admin');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['bank_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

try {
    $id = (int)$data['bank_id'];

    // Soft Delete
    $stmt = $db->prepare("UPDATE bank SET bank_del = 1, bank_update = NOW() WHERE bank_id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
