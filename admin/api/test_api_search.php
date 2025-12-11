<?php
require_once '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

// Simulate API call with search parameter
$_GET['search'] = 'สราวุธ';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search) {
    // Same logic as updated get_users_list.php
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
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'search_term' => $search,
        'users' => $users,
        'count' => count($users)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
