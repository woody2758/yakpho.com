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
    $stmt = $db->prepare("SELECT * FROM bank WHERE bank_id = ?");
    $stmt->execute([$id]);
    $bank = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bank) {
        echo json_encode(['success' => false, 'message' => 'Bank account not found']);
        exit;
    }

    // Get translations
    $stmt = $db->prepare("SELECT * FROM bank_translations WHERE bank_id = ?");
    $stmt->execute([$id]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'bank' => $bank,
        'translations' => $translations
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
