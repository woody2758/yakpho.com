<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid input');
    }

    $id     = isset($data['transcat_id']) ? (int)$data['transcat_id'] : 0;
    $index  = $data['transcat_index'] ?? 1;
    $link   = trim($data['transcat_link'] ?? '');
    $cod    = isset($data['transcat_cod']) ? (int)$data['transcat_cod'] : 0;
    $status = isset($data['transcat_status']) ? (int)$data['transcat_status'] : 1;
    $translations = $data['translations'] ?? [];

    // Thai values (stored in main table as fallback)
    $thName    = $translations['th']['transcat_name']    ?? '';
    $thNshort  = $translations['th']['transcat_nshort']  ?? '';
    $thDetail  = $translations['th']['transcat_detail']  ?? '';

    $db->beginTransaction();

    if ($id > 0) {
        // Update main table
        $stmt = $db->prepare("UPDATE transcat SET 
            transcat_index  = ?, 
            transcat_link   = ?, 
            transcat_cod    = ?, 
            transcat_status = ?,
            transcat_name   = ?,
            transcat_nshort = ?,
            transcat_detail = ?,
            transcat_update = NOW()
            WHERE transcat_id = ?");
        $stmt->execute([$index, $link, $cod, $status, $thName, $thNshort, $thDetail, $id]);
    } else {
        // Insert new
        $stmt = $db->prepare("INSERT INTO transcat 
            (transcat_index, transcat_link, transcat_cod, transcat_status, 
             transcat_name, transcat_nshort, transcat_detail,
             transcat_date, transcat_update) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$index, $link, $cod, $status, $thName, $thNshort, $thDetail]);
        $id = $db->lastInsertId();
    }

    // Update / Insert Translations for each language
    foreach ($translations as $lang => $trans) {
        $name    = $trans['transcat_name']    ?? '';
        $nshort  = $trans['transcat_nshort']  ?? '';
        $detail  = $trans['transcat_detail']  ?? '';

        // Check if row already exists
        $stmt = $db->prepare("SELECT id FROM transcat_translations WHERE transcat_id = ? AND lang_code = ?");
        $stmt->execute([$id, $lang]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $db->prepare("UPDATE transcat_translations SET 
                transcat_name   = ?, 
                transcat_nshort = ?, 
                transcat_detail = ? 
                WHERE transcat_id = ? AND lang_code = ?");
            $stmt->execute([$name, $nshort, $detail, $id, $lang]);
        } else {
            $stmt = $db->prepare("INSERT INTO transcat_translations 
                (transcat_id, lang_code, transcat_name, transcat_nshort, transcat_detail) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $lang, $name, $nshort, $detail]);
        }
    }

    $db->commit();

    echo json_encode(['success' => true, 'id' => $id]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
