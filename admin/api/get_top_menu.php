<?php
/**
 * Get Single Top Menu API
 * Returns all translations for a specific menu item
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $id = $_GET['id'] ?? 0;

    if (!$id) {
        throw new Exception("Menu ID required");
    }

    // Get main data
    $stmt = $db->prepare("SELECT * FROM top_menus WHERE menu_id = ? AND delete_date IS NULL");
    $stmt->execute([$id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$menu) {
        throw new Exception("Menu not found");
    }

    // Get translations
    $stmt = $db->prepare("SELECT lang_code, menu_text FROM top_menus_translations WHERE menu_id = ?");
    $stmt->execute([$id]);
    $translations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

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
