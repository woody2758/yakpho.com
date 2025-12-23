<?php
/**
 * Get Single Hero Slide
 * Returns slide data with all translations
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

try {
    $slide_id = $_GET['id'] ?? 0;
    
    if (!$slide_id) {
        throw new Exception('Slide ID required');
    }
    
    // Get slide main data
    $stmt = $db->prepare("SELECT * FROM hero_slides WHERE slide_id = ?");
    $stmt->execute([$slide_id]);
    $slide = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$slide) {
        throw new Exception('Slide not found');
    }
    
    // Get all translations
    $stmt = $db->prepare("
        SELECT * FROM hero_slides_translations 
        WHERE slide_id = ?
        ORDER BY lang_code
    ");
    $stmt->execute([$slide_id]);
    $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $slide['translations'] = $translations;
    
    echo json_encode([
        'success' => true,
        'slide' => $slide
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
