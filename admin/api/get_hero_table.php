<?php
/**
 * Get Hero Slides Table
 * Returns list of hero slides for admin table
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

try {
    // Get hero slides with Thai translations
    $stmt = $db->prepare("
        SELECT 
            h.*,
            ht.slide_title,
            ht.slide_subtitle
        FROM hero_slides h
        LEFT JOIN hero_slides_translations ht ON h.slide_id = ht.slide_id AND ht.lang_code = 'th'
        ORDER BY h.slide_order ASC
    ");
    
    $stmt->execute();
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'slides' => $slides
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
