<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Get search query if provided
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    if ($search) {
        // Check if search starts with 'C' - search by exact user_id
        if (stripos($search, 'C') === 0) {
            $userId = substr($search, 1); // Remove 'C' prefix
            
            // Search by exact user_id
            $stmt = $db->prepare("SELECT 
                user_id, 
                user_name, 
                user_lastname, 
                user_nickname,
                user_email, 
                user_mobile,
                user_picture
                FROM user 
                WHERE user_del = 0 
                AND user_id = ?
                LIMIT 1");
            $stmt->execute([$userId]);
        } else {
            // Search by name, lastname, nickname, email, or mobile (all users)
            $stmt = $db->prepare("SELECT 
                user_id, 
                user_name, 
                user_lastname, 
                user_nickname,
                user_email, 
                user_mobile,
                user_picture
                FROM user 
                WHERE user_del = 0 
                AND (
                    user_name LIKE ? OR 
                    user_lastname LIKE ? OR 
                    user_nickname LIKE ? OR 
                    user_email LIKE ? OR 
                    user_mobile LIKE ?
                )
                ORDER BY user_id DESC
                LIMIT 50");
            $searchTerm = "%{$search}%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
    } else {
        // Get all active users (no role filter)
        $stmt = $db->prepare("SELECT 
            user_id, 
            user_name, 
            user_lastname,
            user_nickname, 
            user_email, 
            user_mobile,
            user_picture
            FROM user 
            WHERE user_del = 0 
            ORDER BY user_name DESC
            LIMIT 100");
        $stmt->execute();
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
