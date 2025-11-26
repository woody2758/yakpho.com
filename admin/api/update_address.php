<?php
/**
 * API: Update an existing address
 * Accepts POST data and updates address record
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
if (empty($input['addr_id']) || empty($input['addr_name']) || empty($input['addr_detail'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$addr_id = (int)$input['addr_id'];

// Prepare data
$data = [
    'addr_name' => trim($input['addr_name']),
    'addr_mobile' => trim($input['addr_mobile'] ?? ''),
    'addr_detail' => trim($input['addr_detail']),
    'addr_detail2' => trim($input['addr_detail2'] ?? ''),
    'addr_postcode' => trim($input['addr_postcode'] ?? ''),
    'provinces_id' => (int)($input['provinces_id'] ?? 0),
    'addr_forword' => trim($input['addr_forword'] ?? ''),
    'addr_type' => (int)($input['addr_type'] ?? 1),
    'update_id' => $_SESSION['admin_id']
];

// Update address
$result = update_address($addr_id, $data);

echo json_encode($result);
