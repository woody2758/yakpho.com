<?php
/**
 * API: Get all addresses for a user
 * Returns list of addresses with province information
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

// Get user_id from query parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Get addresses
$addresses = get_user_addresses($user_id);

echo json_encode([
    'success' => true,
    'addresses' => $addresses,
    'count' => count($addresses)
]);
