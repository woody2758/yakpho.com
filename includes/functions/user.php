<?php
// includes/functions/user.php

/**
 * Get all users with pagination and filtering
 */
function get_all_users($limit = 20, $offset = 0, $search = '', $role = '', $sort = 'desc', $tag = '', $tier = '') {
    global $db;
    
    $sql = "SELECT u.* FROM user u WHERE u.user_del = 0";
    $params = [];
    
    if (!empty($tag)) {
        $sql .= " AND EXISTS (
            SELECT 1 FROM user_tag_mapping utm 
            JOIN user_tags ut ON utm.tag_id = ut.tag_id 
            WHERE utm.user_id = u.user_id AND ut.tag_name = ?
        )";
        $params[] = $tag;
    }

    if (!empty($search)) {
        // Clean up search string: trim and normalize whitespace
        $search = trim(preg_replace('/\s+/', ' ', $search));
        
        // Split search into keywords
        $keywords = explode(' ', $search);
        
        // Build search conditions for multiple keywords
        $searchConditions = [];
        
        foreach ($keywords as $keyword) {
            if (empty($keyword)) continue;
            
            // Strip 'C' or 'c' prefix for user_id search
            $userId = (stripos($keyword, 'C') === 0) ? substr($keyword, 1) : $keyword;
            
            // Each keyword searches across all fields
            $searchConditions[] = "(u.user_name LIKE ? OR u.user_lastname LIKE ? OR u.user_nickname LIKE ? OR u.user_email LIKE ? OR u.user_mobile LIKE ? OR u.user_id = ?)";
            
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = $userId;
        }
        
        // Combine all search conditions with AND (all keywords must match)
        if (!empty($searchConditions)) {
            $sql .= " AND (" . implode(" AND ", $searchConditions) . ")";
        }
    }
    
    if (!empty($tier)) {
        $sql .= " AND u.member_tier = ?";
        $params[] = $tier;
    }

    if (!empty($role)) {
        $sql .= " AND u.role = ?";
        $params[] = $role;
    }
    
    // Add sorting
    $orderDirection = ($sort === 'asc') ? 'ASC' : 'DESC';
    $sql .= " ORDER BY u.user_id {$orderDirection} LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count total users for pagination
 */
function count_all_users($search = '', $role = '', $tag = '', $tier = '') {
    global $db;
    
    $sql = "SELECT COUNT(*) FROM user u WHERE u.user_del = 0";
    $params = [];
    
    if (!empty($tag)) {
        $sql .= " AND EXISTS (
            SELECT 1 FROM user_tag_mapping utm 
            JOIN user_tags ut ON utm.tag_id = ut.tag_id 
            WHERE utm.user_id = u.user_id AND ut.tag_name = ?
        )";
        $params[] = $tag;
    }

    if (!empty($search)) {
        // Clean up search string: trim and normalize whitespace
        $search = trim(preg_replace('/\s+/', ' ', $search));
        
        // Split search into keywords
        $keywords = explode(' ', $search);
        
        // Build search conditions for multiple keywords
        $searchConditions = [];
        
        foreach ($keywords as $keyword) {
            if (empty($keyword)) continue;
            
            // Strip 'C' or 'c' prefix for user_id search
            $userId = (stripos($keyword, 'C') === 0) ? substr($keyword, 1) : $keyword;
            
            // Each keyword searches across all fields
            $searchConditions[] = "(u.user_name LIKE ? OR u.user_lastname LIKE ? OR u.user_nickname LIKE ? OR u.user_email LIKE ? OR u.user_mobile LIKE ? OR u.user_id = ?)";
            
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = $userId;
        }
        
        // Combine all search conditions with AND (all keywords must match)
        if (!empty($searchConditions)) {
            $sql .= " AND (" . implode(" AND ", $searchConditions) . ")";
        }
    }
    
    if (!empty($tier)) {
        $sql .= " AND u.member_tier = ?";
        $params[] = $tier;
    }

    if (!empty($role)) {
        $sql .= " AND u.role = ?";
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
