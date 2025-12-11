<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid input');
    }

    $id = isset($data['transcat_id']) ? (int)$data['transcat_id'] : 0;
    $index = $data['transcat_index'];
    $link = $data['transcat_link'];
    $cod = isset($data['transcat_cod']) ? (int)$data['transcat_cod'] : 0;
    $status = isset($data['transcat_status']) ? (int)$data['transcat_status'] : 1;
    $translations = $data['translations'];

    $db->beginTransaction();

    if ($id > 0) {
        // Update
        $stmt = $db->prepare("UPDATE transcat SET 
            transcat_index = ?, 
            transcat_link = ?, 
            transcat_cod = ?, 
            transcat_status = ?,
            transcat_update = NOW()
            WHERE transcat_id = ?");
        $stmt->execute([$index, $link, $cod, $status, $id]);
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO transcat 
            (transcat_index, transcat_link, transcat_cod, transcat_status, transcat_date, transcat_update) 
            VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$index, $link, $cod, $status]);
        $id = $db->lastInsertId();
    }

    // Update Translations
    foreach ($translations as $lang => $trans) {
        // Check if exists
        $stmt = $db->prepare("SELECT id FROM transcat_translations WHERE transcat_id = ? AND lang_code = ?");
        $stmt->execute([$id, $lang]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $db->prepare("UPDATE transcat_translations SET 
                transcat_name = ?, 
                transcat_nshort = ?, 
                transcat_detail = ? 
                WHERE transcat_id = ? AND lang_code = ?");
            $stmt->execute([
                $trans['transcat_name'],
                $trans['transcat_nshort'],
                $trans['transcat_detail'],
                $id,
                $lang
            ]);
        } else {
            $stmt = $db->prepare("INSERT INTO transcat_translations 
                (transcat_id, lang_code, transcat_name, transcat_nshort, transcat_detail) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $lang,
                $trans['transcat_name'],
                $trans['transcat_nshort'],
                $trans['transcat_detail']
            ]);
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
