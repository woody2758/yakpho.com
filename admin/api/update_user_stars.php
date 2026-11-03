<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$stars = isset($_POST['stars']) ? (int)$_POST['stars'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

if ($stars < 0 || $stars > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid star rating.']);
    exit;
}

try {
    // Ensure column exists
    try {
        $db->query("SELECT user_stars FROM user LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE user ADD COLUMN user_stars INT DEFAULT 0 AFTER user_lastname");
    }

    $stmt = $db->prepare("UPDATE user SET user_stars = ? WHERE user_id = ?");
    if ($stmt->execute([$stars, $user_id])) {
        echo json_encode(['success' => true, 'message' => 'Rating updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update rating.']);
    }
} catch (Exception $e) {
    error_log("update_user_stars.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
