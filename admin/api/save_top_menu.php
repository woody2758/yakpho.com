<?php
/**
 * Save Top Menu API
 * Handles create, update, and quick status toggle
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $db->beginTransaction();

    $id = $_POST['menu_id'] ?? 0;
    $parent_id = $_POST['parent_id'] ?? 0;
    $menu_link = $_POST['menu_link'] ?? '#';
    $menu_icon = $_POST['menu_icon'] ?? null;
    $menu_status = $_POST['menu_status'] ?? 'active';
    $quick_toggle = isset($_POST['quick_toggle']);

    if ($id && $quick_toggle) {
        // Simple status update
        $stmt = $db->prepare("UPDATE top_menus SET menu_status = ?, update_id = ? WHERE menu_id = ?");
        $stmt->execute([$menu_status, $_SESSION['user_id'] ?? 0, $id]);
    } else {
        // Full save
        if ($id) {
            // Update
            $stmt = $db->prepare("
                UPDATE top_menus 
                SET parent_id = ?, menu_link = ?, menu_icon = ?, menu_status = ?, update_id = ?
                WHERE menu_id = ?
            ");
            $stmt->execute([$parent_id, $menu_link, $menu_icon, $menu_status, $_SESSION['user_id'] ?? 0, $id]);
        } else {
            // Create
            // Get next order
            $stmt = $db->prepare("SELECT MAX(menu_order) as max_order FROM top_menus WHERE parent_id = ? AND delete_date IS NULL");
            $stmt->execute([$parent_id]);
            $max_order = $stmt->fetch(PDO::FETCH_ASSOC)['max_order'] ?? 0;
            $new_order = $max_order + 1;

            $stmt = $db->prepare("
                INSERT INTO top_menus (parent_id, menu_link, menu_icon, menu_status, menu_order, save_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$parent_id, $menu_link, $menu_icon, $menu_status, $new_order, $_SESSION['user_id'] ?? 0]);
            $id = $db->lastInsertId();
        }

        // Save translations
        $languages = ['th', 'en', 'zh', 'de', 'fr', 'ja', 'ko', 'ru', 'ar', 'he'];
        foreach ($languages as $lang) {
            $text = $_POST["menu_text_$lang"] ?? '';
            
            $stmt = $db->prepare("
                INSERT INTO top_menus_translations (menu_id, lang_code, menu_text)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE menu_text = VALUES(menu_text)
            ");
            $stmt->execute([$id, $lang, $text]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'menu_id' => $id]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
