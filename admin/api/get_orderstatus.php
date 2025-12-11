<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$statusId = $_GET['id'] ?? 0;

if (empty($statusId)) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบ ID สถานะ'
    ]);
    exit;
}

try {
    // Get status data
    $stmt = $db->prepare("SELECT * FROM orsts WHERE orsts_id = ? AND orsts_del = 0");
    $stmt->execute([$statusId]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$status) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบสถานะ'
        ]);
        exit;
    }
    
    // Get translations for all languages
    $stmt = $db->prepare("SELECT * FROM orsts_translations WHERE orsts_id = ?");
    $stmt->execute([$statusId]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'status' => $status,
        'translations' => $translations
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
