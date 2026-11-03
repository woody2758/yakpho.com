<?php
/**
 * Delete (Soft Delete) Menu Item
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        throw new Exception("Missing menu ID");
    }
    
    $stmt = $db->prepare("UPDATE footer_menus SET delete_date = NOW(), update_id = ? WHERE menu_id = ?");
    $stmt->execute([$_SESSION['admin_user_id'] ?? 1, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'ลบรายการเมนูเรียบร้อย'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
