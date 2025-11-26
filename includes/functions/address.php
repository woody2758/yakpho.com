<?php
/**
 * Address Management Functions
 * Handles CRUD operations for user addresses
 */

/**
 * Get all addresses for a specific user
 * 
 * @param int $user_id User ID
 * @param bool $include_deleted Include soft-deleted addresses
 * @return array Array of addresses
 */
function get_user_addresses($user_id, $include_deleted = false) {
    global $db;
    
    $sql = "SELECT a.*, p.name_th as province_name 
            FROM addr a 
            LEFT JOIN provinces p ON a.provinces_id = p.provinces_id 
            WHERE a.user_id = ?";
    
    if (!$include_deleted) {
        $sql .= " AND a.addr_del = 0";
    }
    
    $sql .= " ORDER BY 
              CASE WHEN a.addr_type = 1 THEN 0 ELSE 1 END,
              a.addr_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get a single address by ID
 * 
 * @param int $addr_id Address ID
 * @return array|null Address data or null if not found
 */
function get_address_by_id($addr_id) {
    global $db;
    
    $sql = "SELECT a.*, p.name_th as province_name 
            FROM addr a 
            LEFT JOIN provinces p ON a.provinces_id = p.provinces_id 
            WHERE a.addr_id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$addr_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Count total addresses for a user (excluding soft-deleted)
 * 
 * @param int $user_id User ID
 * @return int Address count
 */
function count_user_addresses($user_id) {
    global $db;
    
    $sql = "SELECT COUNT(*) as total FROM addr WHERE user_id = ? AND addr_del = 0";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    
    return (int)$stmt->fetchColumn();
}

/**
 * Add a new address
 * 
 * @param array $data Address data
 * @return array Result with success status and message
 */
function add_address($data) {
    global $db;
    
    // Validate required fields
    if (empty($data['user_id']) || empty($data['addr_name']) || empty($data['addr_detail'])) {
        return ['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'];
    }
    
    $sql = "INSERT INTO addr (
                user_id, addr_name, addr_mobile, addr_detail, addr_detail2, 
                addr_postcode, provinces_id, addr_forword, addr_type, 
                addr_date, save_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['addr_name'],
            $data['addr_mobile'],
            $data['addr_detail'],
            $data['addr_detail2'],
            $data['addr_postcode'],
            $data['provinces_id'],
            $data['addr_forword'],
            $data['addr_type'],
            $data['save_id']
        ]);
        
        return [
            'success' => true, 
            'message' => 'เพิ่มที่อยู่สำเร็จ',
            'addr_id' => $db->lastInsertId()
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

/**
 * Update an existing address
 * 
 * @param int $addr_id Address ID
 * @param array $data Address data
 * @return array Result with success status and message
 */
function update_address($addr_id, $data) {
    global $db;
    
    // Validate required fields
    if (empty($data['addr_name']) || empty($data['addr_detail'])) {
        return ['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'];
    }
    
    $sql = "UPDATE addr SET 
                addr_name = ?, 
                addr_mobile = ?, 
                addr_detail = ?, 
                addr_detail2 = ?, 
                addr_postcode = ?, 
                provinces_id = ?, 
                addr_forword = ?, 
                addr_type = ?,
                addr_update = NOW(),
                update_id = ?
            WHERE addr_id = ?";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['addr_name'],
            $data['addr_mobile'],
            $data['addr_detail'],
            $data['addr_detail2'],
            $data['addr_postcode'],
            $data['provinces_id'],
            $data['addr_forword'],
            $data['addr_type'],
            $data['update_id'],
            $addr_id
        ]);
        
        return ['success' => true, 'message' => 'แก้ไขที่อยู่สำเร็จ'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

/**
 * Soft delete an address
 * 
 * @param int $addr_id Address ID
 * @param int $admin_id Admin ID who performed the deletion
 * @return array Result with success status and message
 */
function soft_delete_address($addr_id, $admin_id) {
    global $db;
    
    $sql = "UPDATE addr SET 
                addr_del = 1, 
                addr_update = NOW(),
                update_id = ?
            WHERE addr_id = ?";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$admin_id, $addr_id]);
        
        return ['success' => true, 'message' => 'ลบที่อยู่สำเร็จ'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

/**
 * Permanently delete an address (Admin/Owner only)
 * 
 * @param int $addr_id Address ID
 * @param int $admin_id Admin ID who performed the deletion
 * @return array Result with success status and message
 */
function permanent_delete_address($addr_id, $admin_id) {
    global $db;
    
    // Check if admin has permission (Admin or Owner only)
    // This should be checked in the API endpoint as well
    
    $sql = "DELETE FROM addr WHERE addr_id = ?";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$addr_id]);
        
        return ['success' => true, 'message' => 'ลบที่อยู่ถาวรสำเร็จ'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

/**
 * Set an address as default (addr_position = 1)
 * Automatically unsets other addresses for the same user
 * 
 * @param int $addr_id Address ID to set as default
 * @param int $admin_id Admin ID who performed the action
 * @return array Result with success status and message
 */
function set_default_address($addr_id, $admin_id) {
    global $db;
    
    try {
        // Get the user_id for this address
        $stmt = $db->prepare("SELECT user_id FROM addr WHERE addr_id = ?");
        $stmt->execute([$addr_id]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$address) {
            return ['success' => false, 'message' => 'ไม่พบที่อยู่'];
        }
        
        $user_id = $address['user_id'];
        
        // Start transaction
        $db->beginTransaction();
        
        // Unset all default addresses for this user
        $stmt = $db->prepare("UPDATE addr SET addr_position = 0 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Set this address as default
        $stmt = $db->prepare("UPDATE addr SET addr_position = 1, addr_update = NOW(), update_id = ? WHERE addr_id = ?");
        $stmt->execute([$admin_id, $addr_id]);
        
        // Commit transaction
        $db->commit();
        
        return ['success' => true, 'message' => 'ตั้งเป็นที่อยู่หลักสำเร็จ'];
    } catch (Exception $e) {
        // Rollback on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

/**
 * Get address type label in Thai
 * 
 * @param int $type Address type
 * @return string Type label
 */
function get_address_type_label($type) {
    $types = [
        1 => 'ที่บ้าน',
        2 => 'ที่ทำงาน',
        3 => 'ที่อยู่สำหรับออกบิล/ใบกำกับภาษี',
        4 => 'ใช้เป็นผู้ส่งในนาม',
        5 => 'ผู้รับแทน'
    ];
    
    return $types[$type] ?? 'ไม่ระบุ';
}

/**
 * Get address type icon
 * 
 * @param int $type Address type
 * @return string Lucide icon name
 */
function get_address_type_icon($type) {
    $icons = [
        1 => 'home',
        2 => 'building-2',
        3 => 'file-text',
        4 => 'package',
        5 => 'user-check'
    ];
    
    return $icons[$type] ?? 'map-pin';
}

/**
 * Get address type badge color
 * 
 * @param int $type Address type
 * @return string Bootstrap badge class
 */
function get_address_type_badge($type) {
    $badges = [
        1 => 'bg-success',
        2 => 'bg-primary',
        3 => 'bg-warning text-dark',
        4 => 'bg-info text-dark',
        5 => 'bg-secondary'
    ];
    
    return $badges[$type] ?? 'bg-secondary';
}
