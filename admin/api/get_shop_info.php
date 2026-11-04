<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Auto-ensure promptpay_id column exists in shop_info
    $chkCol = $db->query("SHOW COLUMNS FROM `shop_info` LIKE 'promptpay_id'");
    if ($chkCol && $chkCol->rowCount() === 0) {
        $db->exec("ALTER TABLE `shop_info` ADD COLUMN `promptpay_id` VARCHAR(30) DEFAULT '0800617073' AFTER `shop_tax_id`");
    }

    // 1. Get Main Info
    $stmt = $db->query("SELECT * FROM shop_info WHERE shop_id = 1");
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shop) {
        // Create default if not exists
        $db->query("INSERT INTO shop_info (shop_id, shop_phone, promptpay_id) VALUES (1, '', '0800617073')");
        $shop = ['shop_id' => 1, 'shop_phone' => '', 'shop_email' => '', 'shop_tax_id' => '', 'promptpay_id' => '0800617073', 'shop_logo' => ''];
    } elseif (empty($shop['promptpay_id']) && !empty($shop['shop_phone'])) {
        $shop['promptpay_id'] = preg_replace('/[^0-9]/', '', $shop['shop_phone']);
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
