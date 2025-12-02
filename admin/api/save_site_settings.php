<?php
/**
 * Save Site Settings API
 * Handles saving watermark settings and uploading watermark image
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions/image.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }
    
    // Handle watermark image upload
    if (!empty($_FILES['watermark_image']['name'])) {
        $uploadDir = __DIR__ . '/../../uploads/watermarks';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $tmpPath = $_FILES['watermark_image']['tmp_name'];
        $originalName = $_FILES['watermark_image']['name'];
        
        // Validate file type (PNG only)
        $imageInfo = getimagesize($tmpPath);
        if ($imageInfo['mime'] !== 'image/png') {
            echo json_encode([
                'success' => false,
                'message' => 'Watermark must be a PNG file with transparency'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $filename = 'watermark_' . time() . '.png';
        $destination = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($tmpPath, $destination)) {
            // Delete old watermark if exists
            $stmt = $db->prepare("SELECT setting_value FROM sitesettings WHERE setting_name = 'watermark_image'");
            $stmt->execute();
            $oldWatermark = $stmt->fetchColumn();
            
            if ($oldWatermark && file_exists($uploadDir . '/' . $oldWatermark)) {
                @unlink($uploadDir . '/' . $oldWatermark);
            }
            
            // Update database
            $stmt = $db->prepare("UPDATE sitesettings SET setting_value = ? WHERE setting_name = 'watermark_image'");
            $stmt->execute([$filename]);
        }
    }
    
    // Update other settings
    $settingsToUpdate = [
        'watermark_enabled',
        'watermark_position',
        'watermark_opacity',
        'watermark_padding'
    ];
    
    foreach ($settingsToUpdate as $settingName) {
        if (isset($_POST[$settingName])) {
            $stmt = $db->prepare("UPDATE sitesettings SET setting_value = ? WHERE setting_name = ?");
            $stmt->execute([$_POST[$settingName], $settingName]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
