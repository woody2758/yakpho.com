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

// Get user ID from query parameter
$user_id = (int)($_GET['id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT user_id, user_name, user_lastname, user_nickname, 
               user_email, user_mobile, user_picture
        FROM user 
        WHERE user_id = :user_id
    ");
    
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'user' => $user
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
