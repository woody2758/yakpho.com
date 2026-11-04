<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Auto-ensure promptpay_id column exists in shop_info (outside transaction as ALTER TABLE commits implicitly in MariaDB)
    $chkCol = $db->query("SHOW COLUMNS FROM `shop_info` LIKE 'promptpay_id'");
    if ($chkCol && $chkCol->rowCount() === 0) {
        $db->exec("ALTER TABLE `shop_info` ADD COLUMN `promptpay_id` VARCHAR(30) DEFAULT '0800617073' AFTER `shop_tax_id`");
    }

    $shop_id = 1; // Always use shop_id 1
    $shop_phone = isset($_POST['shop_phone']) ? trim($_POST['shop_phone']) : '';
    $shop_email = isset($_POST['shop_email']) ? trim($_POST['shop_email']) : '';
    $shop_tax_id = isset($_POST['shop_tax_id']) ? trim($_POST['shop_tax_id']) : '';
    $promptpay_id = isset($_POST['promptpay_id']) ? preg_replace('/[^0-9]/', '', trim($_POST['promptpay_id'])) : '';

    $db->beginTransaction();

    // 1. Handle Logo Upload
    $logo_sql = "";
    $params_main = [$shop_phone, $shop_email, $shop_tax_id, $promptpay_id];
    
    if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/shop/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['shop_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'jfif', 'svg', 'bmp', 'heic', 'heif'];

        if (in_array($file_ext, $allowed)) {
            require_once __DIR__ . '/../../includes/functions/image.php';
            if ($file_ext === 'svg') {
                $new_filename = 'logo_' . time() . '.svg';
                $dest_path = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['shop_logo']['tmp_name'], $dest_path)) {
                    $logo_sql = ", shop_logo = ?";
                    $params_main[] = $new_filename;
                }
            } else {
                $new_filename = 'logo_' . time() . '.webp';
                $dest_path = $upload_dir . $new_filename;
                $sourceImage = load_image($_FILES['shop_logo']['tmp_name']);
                if ($sourceImage) {
                    if (save_as_webp($sourceImage, $dest_path, 90)) {
                        $logo_sql = ", shop_logo = ?";
                        $params_main[] = $new_filename;
                    }
                    imagedestroy($sourceImage);
                }
            }
        }
    }

    $params_main[] = $shop_id;
    
    // Update main shop info
    $stmt = $db->prepare("UPDATE shop_info SET shop_phone = ?, shop_email = ?, shop_tax_id = ?, promptpay_id = ? $logo_sql WHERE shop_id = ?");
    $stmt->execute($params_main);

    // 2. Handle Translations (Loop through common languages)
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar', 'he'];
    
    foreach ($languages as $lang) {
        $name = isset($_POST["shop_name_$lang"]) ? trim($_POST["shop_name_$lang"]) : '';
        $addr = isset($_POST["shop_address_$lang"]) ? trim($_POST["shop_address_$lang"]) : '';
        $off_name = isset($_POST["official_name_$lang"]) ? trim($_POST["official_name_$lang"]) : '';
        $off_addr = isset($_POST["official_address_$lang"]) ? trim($_POST["official_address_$lang"]) : '';

        if (empty($name) && empty($addr) && empty($off_name) && empty($off_addr)) continue;

        // Check if exists (Generic check using only shop_id and lang_code)
        $check = $db->prepare("SELECT shop_id FROM shop_info_translations WHERE shop_id = ? AND lang_code = ? LIMIT 1");
        $check->execute([$shop_id, $lang]);
        
        if ($check->fetch()) {
            $upd = $db->prepare("UPDATE shop_info_translations SET 
                                shop_name = ?, shop_address = ?, 
                                official_name = ?, official_address = ? 
                                WHERE shop_id = ? AND lang_code = ?");
            $upd->execute([$name, $addr, $off_name, $off_addr, $shop_id, $lang]);
        } else {
            $ins = $db->prepare("INSERT INTO shop_info_translations 
                                (shop_id, lang_code, shop_name, shop_address, official_name, official_address) 
                                VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$shop_id, $lang, $name, $addr, $off_name, $off_addr]);
        }
    }

    $db->commit();

    // Log Activity
    logStaffAction($db, 'UPDATE', 'shop_info', $shop_id, "Updated shop information and settings");

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
