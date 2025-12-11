<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

try {
    $id = (int)$_GET['id'];

    // Get main data
    $stmt = $db->prepare("SELECT * FROM paycat WHERE paycat_id = ?");
    $stmt->execute([$id]);
    $paycat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paycat) {
        echo json_encode(['success' => false, 'message' => 'Payment category not found']);
        exit;
    }

    // Get translations
    $stmt = $db->prepare("SELECT * FROM paycat_translations WHERE paycat_id = ?");
    $stmt->execute([$id]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'paycat' => $paycat,
        'translations' => $translations
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
