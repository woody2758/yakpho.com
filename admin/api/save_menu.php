<?php
/**
 * Save / Update Menu Item
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $db->beginTransaction();

    // Check for toggle status action
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $menu_id = $_POST['menu_id'];
        $status = $_POST['menu_status'];
        
        $stmt = $db->prepare("UPDATE footer_menus SET menu_status = ?, update_id = ?, update_date = NOW() WHERE menu_id = ?");
        $stmt->execute([$status, $_SESSION['admin_user_id'] ?? 1, $menu_id]);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'ปรับปรุงสถานะเรียบร้อย']);
        exit;
    }

    // Standard Save/Update
    $menu_id = (int)($_POST['menu_id'] ?? 0);
    $menu_column = $_POST['menu_column'];
    $menu_link = $_POST['menu_link'];
    $menu_icon = $_POST['menu_icon'] ?: null;
    $menu_target = $_POST['menu_target'] ?? '_self';
    $menu_status = $_POST['menu_status'] ?? 'active';
    
    if ($menu_id > 0) {
        // Update
        $stmt = $db->prepare("
            UPDATE footer_menus SET 
                menu_column = ?, 
                menu_link = ?, 
                menu_icon = ?, 
                menu_target = ?, 
                menu_status = ?, 
                update_id = ?, 
                update_date = NOW() 
            WHERE menu_id = ?
        ");
        $stmt->execute([$menu_column, $menu_link, $menu_icon, $menu_target, $menu_status, $_SESSION['admin_user_id'] ?? 1, $menu_id]);
    } else {
        // Get next order for this column
        $stmt = $db->prepare("SELECT MAX(menu_order) FROM footer_menus WHERE menu_column = ? AND delete_date IS NULL");
        $stmt->execute([$menu_column]);
        $next_order = (int)$stmt->fetchColumn() + 1;

        // Insert
        $stmt = $db->prepare("
            INSERT INTO footer_menus 
            (menu_column, menu_link, menu_icon, menu_target, menu_status, menu_order, save_id, save_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$menu_column, $menu_link, $menu_icon, $menu_target, $menu_status, $next_order, $_SESSION['admin_user_id'] ?? 1]);
        $menu_id = $db->lastInsertId();
    }

    // Handle Translations
    $languages = ['th', 'en', 'cn', 'de', 'fr', 'ja', 'kr', 'ru', 'ar', 'he'];
    
    // Clear old translations if updating
    $stmt = $db->prepare("DELETE FROM footer_menus_translations WHERE menu_id = ?");
    $stmt->execute([$menu_id]);
    
    foreach ($languages as $code) {
        $text = $_POST["menu_text_{$code}"] ?? '';
        if ($text) {
            $stmt = $db->prepare("INSERT INTO footer_menus_translations (menu_id, lang_code, menu_text) VALUES (?, ?, ?)");
            $stmt->execute([$menu_id, $code, $text]);
        }
    }

    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => ($menu_id > 0) ? 'บันทึกข้อมูลเรียบร้อย' : 'เพิ่มข้อมูลเรียบร้อย'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
