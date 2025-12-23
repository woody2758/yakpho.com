<?php
/**
 * Get Hero Slides for Frontend
 * Returns active slides with translations
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

try {
    $lang = $_GET['lang'] ?? 'th';
    
    // Get active slides with translations
    $stmt = $db->prepare("
        SELECT 
            h.slide_id,
            h.slide_image,
            h.slide_bg_color,
            h.button1_link,
            h.button2_link,
            ht.slide_title,
            ht.slide_subtitle,
            ht.button1_text,
            ht.button2_text
        FROM hero_slides h
        LEFT JOIN hero_slides_translations ht ON h.slide_id = ht.slide_id AND ht.lang_code = ?
        WHERE h.slide_status = 'active'
        ORDER BY h.slide_order ASC
    ");
    
    $stmt->execute([$lang]);
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
