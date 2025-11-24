<?php
/**
 * API: Delete User (Soft Delete)
 */
header('Content-Type: application/json');
session_start();

require_once __DIR__ . "/../../includes/config.php";

// 1. Check Auth
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// 2. Get Input
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;

// 3. Validation
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

if ($id == $_SESSION['admin_id']) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบบัญชีตัวเองได้']);
    exit;
}

// 4. Execute Soft Delete
try {
    $stmt = $db->prepare("UPDATE user SET user_del = 1, updated_at = NOW() WHERE user_id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'ลบสมาชิกเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบ']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
