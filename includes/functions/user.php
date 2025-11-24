<?php
// includes/functions/user.php

/**
 * Get all users with pagination and filtering
 */
function get_all_users($limit = 20, $offset = 0, $search = '', $role = '', $sort = 'desc') {
    global $db;
    
    $sql = "SELECT * FROM user WHERE user_del = 0";
    $params = [];
    
    if (!empty($search)) {
        // Strip 'C' or 'c' prefix for user_id search
        $userId = (stripos($search, 'C') === 0) ? substr($search, 1) : $search;
        $sql .= " AND (user_name LIKE ? OR user_email LIKE ? OR user_mobile LIKE ? OR user_id = ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = $userId;
    }
    
    if (!empty($role)) {
        $sql .= " AND role = ?";
        $params[] = $role;
    }
    
    // Add sorting
    $orderDirection = ($sort === 'asc') ? 'ASC' : 'DESC';
    $sql .= " ORDER BY user_id {$orderDirection} LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count total users for pagination
 */
function count_all_users($search = '', $role = '') {
    global $db;
    
    $sql = "SELECT COUNT(*) FROM user WHERE user_del = 0";
    $params = [];
    
    if (!empty($search)) {
        // Strip 'C' or 'c' prefix for user_id search
        $userId = (stripos($search, 'C') === 0) ? substr($search, 1) : $search;
        $sql .= " AND (user_name LIKE ? OR user_email LIKE ? OR user_mobile LIKE ? OR user_id = ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = $userId;
    }
    
    if (!empty($role)) {
        $sql .= " AND role = ?";
        $params[] = $role;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * Get user by ID
 */
function get_user_by_id($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM user WHERE user_id = ? AND user_del = 0 LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Check if email exists
 */
function check_user_exists($email, $exclude_id = null) {
    global $db;
    $sql = "SELECT COUNT(*) FROM user WHERE user_email = ? AND user_del = 0";
    $params = [$email];
    
    if ($exclude_id) {
        $sql .= " AND user_id != ?";
        $params[] = $exclude_id;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}
