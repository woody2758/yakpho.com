<?php
header('Content-Type: application/json');

// Include Admin Config
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

// Check permission (Staff or higher)
$current_role = $_SESSION['admin_role'] ?? '';
$role_hierarchy = ['owner' => 1, 'admin' => 2, 'manager' => 3, 'editor' => 4, 'staff' => 5];

if (!isset($role_hierarchy[$current_role]) || $role_hierarchy[$current_role] > 5) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$user_id = (int)$data['user_id'];
$new_status = (int)$data['status'];

// Validate status (0 or 1)
if (!in_array($new_status, [0, 1])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Prevent changing own status
if ($user_id == $_SESSION['admin_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot change your own status']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE user SET user_status = :status WHERE user_id = :user_id");
    $stmt->execute([':status' => $new_status, ':user_id' => $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Status updated successfully',
            'new_status' => $new_status
        ]);
    } else {
        // Check if user exists
        $check = $db->prepare("SELECT user_id FROM user WHERE user_id = :user_id");
        $check->execute([':user_id' => $user_id]);
        if ($check->fetch()) {
             echo json_encode([
                 'success' => true, 
                 'message' => 'Status updated (No changes made)',
                 'new_status' => $new_status
             ]);
        } else {
             echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
