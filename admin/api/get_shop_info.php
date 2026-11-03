<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // 1. Get Main Info
    $stmt = $db->query("SELECT * FROM shop_info WHERE shop_id = 1");
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shop) {
        // Create default if not exists
        $db->query("INSERT INTO shop_info (shop_id, shop_phone) VALUES (1, '')");
        $shop = ['shop_id' => 1, 'shop_phone' => '', 'shop_email' => '', 'shop_tax_id' => '', 'shop_logo' => ''];
    }

    // 2. Get All Translations
    $stmt = $db->query("SELECT * FROM shop_info_translations WHERE shop_id = 1");
    $trans_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $translations = [];
    foreach ($trans_raw as $t) {
        $translations[$t['lang_code']] = $t;
    }

    echo json_encode([
        'success' => true,
        'shop' => $shop,
        'translations' => $translations
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
