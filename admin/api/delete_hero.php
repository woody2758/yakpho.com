<?php
/**
 * Delete Hero Slide
 * Soft delete hero slide and its translations
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

try {
    $slide_id = $_POST['slide_id'] ?? 0;
    
    if (!$slide_id) {
        throw new Exception('Slide ID required');
    }
    
    $db->beginTransaction();
    
    // Get image filename
    $stmt = $db->prepare("SELECT slide_image FROM hero_slides WHERE slide_id = ?");
    $stmt->execute([$slide_id]);
    $slide = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete translations
    $stmt = $db->prepare("DELETE FROM hero_slides_translations WHERE slide_id = ?");
    $stmt->execute([$slide_id]);
    
    // Delete slide
    $stmt = $db->prepare("DELETE FROM hero_slides WHERE slide_id = ?");
    $stmt->execute([$slide_id]);
    
    // Delete image file
    if ($slide && $slide['slide_image']) {
        $image_path = __DIR__ . '/../../uploads/hero/' . $slide['slide_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $db->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
