<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID is required');
    }

    $id = (int)$_GET['id'];

    // Get main data
    $stmt = $db->prepare("SELECT * FROM transcat WHERE transcat_id = ?");
    $stmt->execute([$id]);
    $transcat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transcat) {
        throw new Exception('Shipping method not found');
    }

    // Get translations
    $stmt = $db->prepare("SELECT * FROM transcat_translations WHERE transcat_id = ?");
    $stmt->execute([$id]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format translations
    $transData = [];
    foreach ($translations as $t) {
        $transData[$t['lang_code']] = [
            'transcat_name'   => $t['transcat_name'],
            'transcat_nshort' => $t['transcat_nshort'],
            'transcat_detail' => $t['transcat_detail']
        ];
    }

    // Fallback: if no 'th' translation, use main table values (Thai is stored directly)
    if (!isset($transData['th'])) {
        $transData['th'] = [
            'transcat_name'   => $transcat['transcat_name'] ?? '',
            'transcat_nshort' => $transcat['transcat_nshort'] ?? '',
            'transcat_detail' => $transcat['transcat_detail'] ?? ''
        ];
    }

    echo json_encode([
        'success'      => true,
        'transcat'     => $transcat,
        'translations' => $transData
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
