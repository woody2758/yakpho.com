<?php
/**
 * API: Delete an address (Soft or Permanent)
 * Soft delete for all admins, permanent delete for Admin/Owner only
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/functions/address.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($input['addr_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid address ID']);
    exit;
}

$addr_id = (int)$input['addr_id'];
$permanent = isset($input['permanent']) && $input['permanent'] === true;

// Check permission for permanent delete
if ($permanent) {
    $admin_role = $_SESSION['admin_role'] ?? '';
    if (!in_array($admin_role, ['admin', 'owner'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'คุณไม่มีสิทธิ์ลบที่อยู่ถาวร (เฉพาะ Admin/Owner เท่านั้น)'
        ]);
        exit;
    }
    
    // Permanent delete
    $result = permanent_delete_address($addr_id, $_SESSION['admin_id']);
} else {
    // Soft delete
    $result = soft_delete_address($addr_id, $_SESSION['admin_id']);
}

echo json_encode($result);
