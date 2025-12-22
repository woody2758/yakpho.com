<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Quick Update (Status Toggle)
    if (isset($_POST['quick_update'])) {
        $id = (int)$_POST['paycat_id'];
        $status = (int)$_POST['paycat_status'];

        $stmt = $db->prepare("UPDATE paycat SET paycat_status = ?, paycat_update = NOW() WHERE paycat_id = ?");
        $stmt->execute([$status, $id]);

        echo json_encode(['success' => true]);
        exit;
    }

    // Full Save (Add/Edit)
    $paycat_id = isset($_POST['paycat_id']) ? (int)$_POST['paycat_id'] : 0;
    $paycat_nshort = trim($_POST['paycat_nshort']);
    $paycat_index = (int)$_POST['paycat_index'];
    $paycat_status = isset($_POST['paycat_status']) ? 1 : 0;

    $db->beginTransaction();

    if ($paycat_id > 0) {
        // Update
        $stmt = $db->prepare("UPDATE paycat SET 
            paycat_nshort = ?, 
            paycat_index = ?, 
            paycat_status = ?, 
            paycat_update = NOW() 
            WHERE paycat_id = ?");
        $stmt->execute([$paycat_nshort, $paycat_index, $paycat_status, $paycat_id]);
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO paycat (
            paycat_nshort, paycat_index, paycat_status, paycat_date, paycat_update, paycat_del
        ) VALUES (?, ?, ?, NOW(), NOW(), 0)");
        $stmt->execute([$paycat_nshort, $paycat_index, $paycat_status]);
        $paycat_id = $db->lastInsertId();
    }

    // Handle Translations
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar', 'he'];
    
    // Prepare statements
    $checkStmt = $db->prepare("SELECT translation_id FROM paycat_translations WHERE paycat_id = ? AND lang_code = ?");
    $updateStmt = $db->prepare("UPDATE paycat_translations SET paycat_name = ?, paycat_details = ? WHERE translation_id = ?");
    $insertStmt = $db->prepare("INSERT INTO paycat_translations (paycat_id, lang_code, paycat_name, paycat_details) VALUES (?, ?, ?, ?)");

    foreach ($languages as $lang) {
        $name = $_POST["paycat_name_$lang"] ?? '';
        $details = $_POST["paycat_details_$lang"] ?? '';

        // Check if translation exists
        $checkStmt->execute([$paycat_id, $lang]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            $updateStmt->execute([$name, $details, $exists]);
        } else {
            $insertStmt->execute([$paycat_id, $lang, $name, $details]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
