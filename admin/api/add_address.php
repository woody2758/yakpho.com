<?php
/**
 * API: Add a new address
 * Accepts POST data and creates new address record
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
if (empty($input['user_id']) || empty($input['addr_name']) || empty($input['addr_detail'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// Prepare data
$data = [
    'user_id' => (int)$input['user_id'],
    'addr_name' => trim($input['addr_name']),
    'addr_mobile' => trim($input['addr_mobile'] ?? ''),
    'addr_detail' => trim($input['addr_detail']),
    'addr_detail2' => trim($input['addr_detail2'] ?? ''),
    'addr_postcode' => trim($input['addr_postcode'] ?? ''),
    'provinces_id' => (int)($input['provinces_id'] ?? 0),
    'addr_forword' => trim($input['addr_forword'] ?? ''),
    'addr_type' => (int)($input['addr_type'] ?? 1),
    'save_id' => $_SESSION['admin_id']
];

// Add address
$result = add_address($data);

echo json_encode($result);
