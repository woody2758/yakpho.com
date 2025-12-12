<?php
/**
 * API: Save About Us Record
 * Handles create/update of aboutus with translations
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $aboutus_id = $input['aboutus_id'] ?? 0;
    $aboutus_heading = trim($input['aboutus_heading'] ?? '');
    $aboutus_status = $input['aboutus_status'] ?? 1;
    $translations = $input['translations'] ?? [];
    
    // Validation
    if (empty($aboutus_heading)) {
        throw new Exception('Heading is required');
    }
    
    $db->beginTransaction();
    
    if ($aboutus_id) {
        // Update existing
        $stmt = $db->prepare("
            UPDATE aboutus SET
                aboutus_heading = ?,
                aboutus_status = ?,
                aboutus_update = NOW()
            WHERE aboutus_id = ? AND aboutus_del = 0
        ");
        $stmt->execute([$aboutus_heading, $aboutus_status, $aboutus_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('About Us not found or no changes made');
        }
        
    } else {
        // Create new
        // Get next index
        $nextIndex = $db->query("SELECT LPAD(COALESCE(MAX(CAST(aboutus_index AS UNSIGNED)), 0) + 1, 2, '0') FROM aboutus")->fetchColumn();
        
        $stmt = $db->prepare("
            INSERT INTO aboutus (aboutus_heading, aboutus_index, aboutus_status, aboutus_date, aboutus_update)
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$aboutus_heading, $nextIndex, $aboutus_status]);
        $aboutus_id = $db->lastInsertId();
    }
    
    // Save translations
    $languages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
    
    foreach ($languages as $lang) {
        $title = $translations[$lang]['title'] ?? '';
        $subtitle = $translations[$lang]['subtitle'] ?? '';
        $content = $translations[$lang]['content'] ?? '';
        
        // Check if translation exists
        $check = $db->prepare("SELECT id FROM aboutus_translation WHERE aboutus_id = ? AND lang_code = ?");
        $check->execute([$aboutus_id, $lang]);
        
        if ($check->fetchColumn()) {
            // Update
            $stmt = $db->prepare("
                UPDATE aboutus_translation SET
                    title = ?,
                    subtitle = ?,
                    content = ?
                WHERE aboutus_id = ? AND lang_code = ?
            ");
            $stmt->execute([$title, $subtitle, $content, $aboutus_id, $lang]);
        } else {
            // Insert
            $stmt = $db->prepare("
                INSERT INTO aboutus_translation (aboutus_id, lang_code, title, subtitle, content)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$aboutus_id, $lang, $title, $subtitle, $content]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $aboutus_id ? 'Updated successfully' : 'Created successfully',
        'aboutus_id' => $aboutus_id
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
