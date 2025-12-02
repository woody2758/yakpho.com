<?php
/**
 * Get Site Settings API
 * Returns all site settings or specific setting by name
 */

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Optional: Get specific setting
    $settingName = $_GET['setting_name'] ?? null;
    
    if ($settingName) {
        $stmt = $db->prepare("SELECT setting_value FROM sitesettings WHERE setting_name = ?");
        $stmt->execute([$settingName]);
        $value = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'setting_name' => $settingName,
            'setting_value' => $value
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Get all settings
        $stmt = $db->query("SELECT setting_name, setting_value FROM sitesettings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        echo json_encode([
            'success' => true,
            'settings' => $settings
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
