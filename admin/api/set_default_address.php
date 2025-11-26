<?php
/**
 * API: Set an address as default
 * Updates addr_position to 1 for selected address and 0 for others
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

// Set as default
$result = set_default_address($addr_id, $_SESSION['admin_id']);

echo json_encode($result);
