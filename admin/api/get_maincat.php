<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

// Check if requesting all categories (for dropdown)
if (isset($_GET['all'])) {
    try {
        $stmt = $db->query("
            SELECT m.maincat_id, 
                   mt_th.maincat_name as name_th,
                   mt_en.maincat_name as name_en
            FROM maincat m
            LEFT JOIN maincat_translations mt_th ON m.maincat_id = mt_th.maincat_id AND mt_th.lang_code = 'th'
            LEFT JOIN maincat_translations mt_en ON m.maincat_id = mt_en.maincat_id AND mt_en.lang_code = 'en'
            WHERE m.maincat_del = 0 AND m.maincat_status = 1
            ORDER BY m.maincat_index ASC
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Get single main category
$id = $_GET['id'] ?? 0;

try {
    if (empty($id)) {
        throw new Exception('ไม่พบ ID');
    }
    
    // Get main category
    $stmt = $db->prepare("SELECT * FROM maincat WHERE maincat_id = ? AND maincat_del = 0");
    $stmt->execute([$id]);
    $maincat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$maincat) {
        throw new Exception('ไม่พบข้อมูล');
    }
    
    // Get translations
    $stmt = $db->prepare("SELECT * FROM maincat_translations WHERE maincat_id = ? ORDER BY lang_code");
    $stmt->execute([$id]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $maincat['translations'] = $translations;
    
    echo json_encode([
        'success' => true,
        'maincat' => $maincat
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

