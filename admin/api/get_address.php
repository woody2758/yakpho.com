<?php
/**
 * API: Get a single address by ID
 * Returns address details with province information
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

// Get addr_id from query parameter
$addr_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($addr_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid address ID']);
    exit;
}

// Get address
$address = get_address_by_id($addr_id);

if ($address) {
    echo json_encode([
        'success' => true,
        'address' => $address
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Address not found'
    ]);
}
