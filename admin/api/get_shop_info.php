<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Get general info
    $stmt = $db->query("SELECT * FROM shop_info LIMIT 1");
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shop) {
        // Should not happen if migration ran, but just in case
        $db->exec("INSERT INTO shop_info (shop_id) VALUES (1)");
        $shop = ['shop_id' => 1, 'shop_phone' => '', 'shop_email' => '', 'shop_tax_id' => '', 'shop_logo' => ''];
    }

    // Get translations
    $stmt = $db->prepare("SELECT * FROM shop_info_translations WHERE shop_id = ?");
    $stmt->execute([$shop['shop_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $translations = [];
    foreach ($rows as $row) {
        $translations[$row['lang_code']] = $row;
    }

    echo json_encode([
        'success' => true,
        'shop' => $shop,
        'translations' => $translations
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
