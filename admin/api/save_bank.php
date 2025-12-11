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
        $id = (int)$_POST['bank_id'];
        $status = (int)$_POST['bank_status'];

        $stmt = $db->prepare("UPDATE bank SET bank_status = ?, bank_update = NOW() WHERE bank_id = ?");
        $stmt->execute([$status, $id]);

        echo json_encode(['success' => true]);
        exit;
    }

    // Full Save (Add/Edit)
    $bank_id = isset($_POST['bank_id']) ? (int)$_POST['bank_id'] : 0;
    $bank_accountnumber = trim($_POST['bank_accountnumber']);
    $bank_swiftcode = trim($_POST['bank_swiftcode']);
    $bank_index = (int)$_POST['bank_index'];
    $bank_status = isset($_POST['bank_status']) ? 1 : 0;
    
    // Handle Image Upload
    $bank_picture = null;
    if (isset($_FILES['bank_picture']) && $_FILES['bank_picture']['error'] == 0) {
        $uploadDir = '../../uploads/banks/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = strtolower(pathinfo($_FILES['bank_picture']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExt, $allowed)) {
            $fileName = uniqid('bank_') . '.' . $fileExt;
            if (move_uploaded_file($_FILES['bank_picture']['tmp_name'], $uploadDir . $fileName)) {
                $bank_picture = $fileName;
            }
        }
    }

    $db->beginTransaction();

    if ($bank_id > 0) {
        // Update
        $sql = "UPDATE bank SET 
            bank_accountnumber = ?, 
            bank_swiftcode = ?, 
            bank_index = ?, 
            bank_status = ?, 
            bank_update = NOW()";
        
        $params = [$bank_accountnumber, $bank_swiftcode, $bank_index, $bank_status];

        if ($bank_picture) {
            $sql .= ", bank_picture = ?";
            $params[] = $bank_picture;

            // Delete old picture
            $stmt = $db->prepare("SELECT bank_picture FROM bank WHERE bank_id = ?");
            $stmt->execute([$bank_id]);
            $oldPic = $stmt->fetchColumn();
            if ($oldPic && file_exists("../../uploads/banks/" . $oldPic)) {
                @unlink("../../uploads/banks/" . $oldPic);
            }
        }

        $sql .= " WHERE bank_id = ?";
        $params[] = $bank_id;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO bank (
            bank_accountnumber, bank_swiftcode, bank_index, bank_status, bank_picture, bank_date, bank_update, bank_del
        ) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), 0)");
        $stmt->execute([$bank_accountnumber, $bank_swiftcode, $bank_index, $bank_status, $bank_picture]);
        $bank_id = $db->lastInsertId();
    }

    // Handle Translations
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko'];
    
    // Prepare statements
    $checkStmt = $db->prepare("SELECT translation_id FROM bank_translations WHERE bank_id = ? AND lang_code = ?");
    $updateStmt = $db->prepare("UPDATE bank_translations SET bank_bankname = ?, bank_accountname = ?, bank_accounttype = ?, bank_accountbranch = ? WHERE translation_id = ?");
    $insertStmt = $db->prepare("INSERT INTO bank_translations (bank_id, lang_code, bank_bankname, bank_accountname, bank_accounttype, bank_accountbranch) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($languages as $lang) {
        $bankname = $_POST["bank_bankname_$lang"] ?? '';
        $accountname = $_POST["bank_accountname_$lang"] ?? '';
        $accounttype = $_POST["bank_accounttype_$lang"] ?? '';
        $accountbranch = $_POST["bank_accountbranch_$lang"] ?? '';

        // Check if translation exists
        $checkStmt->execute([$bank_id, $lang]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            $updateStmt->execute([$bankname, $accountname, $accounttype, $accountbranch, $exists]);
        } else {
            $insertStmt->execute([$bank_id, $lang, $bankname, $accountname, $accounttype, $accountbranch]);
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
