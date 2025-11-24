<?php
header('Content-Type: application/json');

// Include Admin Config (which connects to DB as $db)
require_once __DIR__ . "/../includes/config.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check permission (Admin or Owner only)
$current_role = $_SESSION['admin_role'] ?? '';
$allowed_roles = ['admin', 'owner'];

if (!in_array($current_role, $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id']) || !isset($data['role'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$user_id = (int)$data['user_id'];
$new_role = $data['role'];

// Validate role
$valid_roles = ['customer', 'staff', 'editor', 'manager', 'admin', 'owner'];
if (!in_array($new_role, $valid_roles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

// Prevent changing own role
if ($user_id == $_SESSION['admin_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot change your own role']);
    exit;
}

try {
    // Use $db instead of $pdo
    $stmt = $db->prepare("UPDATE user SET role = :role WHERE user_id = :user_id");
    $stmt->execute([':role' => $new_role, ':user_id' => $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
    } else {
        // Check if user exists
        $check = $db->prepare("SELECT user_id FROM user WHERE user_id = :user_id");
        $check->execute([':user_id' => $user_id]);
        if ($check->fetch()) {
             echo json_encode(['success' => true, 'message' => 'Role updated (No changes made)']);
        } else {
             echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
