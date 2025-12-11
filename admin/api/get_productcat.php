<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$categoryId = $_GET['id'] ?? 0;

if (empty($categoryId)) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบ ID หมวดสินค้า'
    ]);
    exit;
}

try {
    // Get category data
    $stmt = $db->prepare("SELECT * FROM productcat WHERE productcat_id = ? AND productcat_del = 0");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบหมวดสินค้า'
        ]);
        exit;
    }
    
    // Get translations for all languages
    $stmt = $db->prepare("SELECT * FROM productcat_translations WHERE productcat_id = ?");
    $stmt->execute([$categoryId]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'category' => $category,
        'translations' => $translations
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
