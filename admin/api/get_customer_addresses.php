<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['user_id'])) {
        throw new Exception('User ID is required');
    }

    $userId = (int)$_GET['user_id'];

    $stmt = $db->prepare("SELECT a.*, p.name_th as province_name FROM addr a LEFT JOIN provinces p ON a.provinces_id = p.provinces_id WHERE a.user_id = ? ORDER BY a.addr_id DESC");
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $addresses]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
