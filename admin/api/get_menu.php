<?php
/**
 * Get Single Menu Item with Translations
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        throw new Exception("Missing menu ID");
    }
    
    // Fetch main menu data
    $stmt = $db->prepare("SELECT * FROM footer_menus WHERE menu_id = ? AND delete_date IS NULL");
    $stmt->execute([$id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$menu) {
        throw new Exception("Menu not found");
    }
    
    // Fetch all translations
    $stmt = $db->prepare("SELECT lang_code, menu_text FROM footer_menus_translations WHERE menu_id = ?");
    $stmt->execute([$id]);
    $translations_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $translations = [];
    foreach ($translations_raw as $t) {
        $translations[$t['lang_code']] = $t['menu_text'];
    }
    
    $menu['translations'] = $translations;
    
    echo json_encode([
        'success' => true,
        'menu' => $menu
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
