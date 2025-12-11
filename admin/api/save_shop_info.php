<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : 1;
    $shop_phone = $_POST['shop_phone'] ?? '';
    $shop_email = $_POST['shop_email'] ?? '';
    $shop_tax_id = $_POST['shop_tax_id'] ?? '';
    
    $db->beginTransaction();

    // 1. Update General Info
    $sql = "UPDATE shop_info SET 
            shop_phone = :phone, 
            shop_email = :email, 
            shop_tax_id = :tax_id 
            WHERE shop_id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':phone' => $shop_phone,
        ':email' => $shop_email,
        ':tax_id' => $shop_tax_id,
        ':id' => $shop_id
    ]);

    // 2. Handle Logo Upload
    if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['shop_logo']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $filename = 'shop_logo_' . time() . '.' . $ext;
            $target = '../../uploads/shop/' . $filename;
            
            if (!file_exists('../../uploads/shop/')) {
                mkdir('../../uploads/shop/', 0777, true);
            }

            if (move_uploaded_file($_FILES['shop_logo']['tmp_name'], $target)) {
                // Update DB
                $stmt = $db->prepare("UPDATE shop_info SET shop_logo = ? WHERE shop_id = ?");
                $stmt->execute([$filename, $shop_id]);
            }
        }
    }

    // 3. Update Translations
    $languages = ['th', 'en', 'de', 'fr', 'cn', 'kr'];
    
    $stmtCheck = $db->prepare("SELECT translation_id FROM shop_info_translations WHERE shop_id = ? AND lang_code = ?");
    $stmtInsert = $db->prepare("INSERT INTO shop_info_translations (shop_id, lang_code, shop_name, shop_address, official_name, official_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtUpdate = $db->prepare("UPDATE shop_info_translations SET shop_name = ?, shop_address = ?, official_name = ?, official_address = ? WHERE translation_id = ?");

    foreach ($languages as $lang) {
        $shop_name = $_POST["shop_name_$lang"] ?? '';
        $shop_address = $_POST["shop_address_$lang"] ?? '';
        $official_name = $_POST["official_name_$lang"] ?? '';
        $official_address = $_POST["official_address_$lang"] ?? '';

        $stmtCheck->execute([$shop_id, $lang]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists) {
            $stmtUpdate->execute([$shop_name, $shop_address, $official_name, $official_address, $exists]);
        } else {
            $stmtInsert->execute([$shop_id, $lang, $shop_name, $shop_address, $official_name, $official_address]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
