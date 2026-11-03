<?php
/**
 * Delete Top Menu API (Soft Delete)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $id = $_GET['id'] ?? 0;

    if (!$id) {
        throw new Exception("Menu ID required");
    }

    $stmt = $db->prepare("UPDATE top_menus SET delete_date = NOW(), update_id = ? WHERE menu_id = ?");
    $stmt->execute([$_SESSION['user_id'] ?? 0, $id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
