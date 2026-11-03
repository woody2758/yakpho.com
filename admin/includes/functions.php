<?php

/**
 * Log an administrative action to the staff_logs table
 */
function logStaffAction($db, $action_type, $target_table = null, $target_id = null, $action_detail = null) {
    try {
        $admin_id = $_SESSION['admin_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $sql = "INSERT INTO staff_logs (admin_id, action_type, target_table, target_id, action_detail, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$admin_id, $action_type, $target_table, $target_id, $action_detail, $ip_address, $user_agent]);
        
        return true;
    } catch (Exception $e) {
        // Silently fail to prevent breaking the main process if logging fails
        error_log("Staff Logging Error: " . $e->getMessage());
        return false;
    }
}
