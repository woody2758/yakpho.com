<?php
/**
 * Migration: Create sitesettings table
 * Run this once to create the table for storing site-wide settings
 */

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Create sitesettings table
    $sql = "CREATE TABLE IF NOT EXISTS sitesettings (
        setting_id INT AUTO_INCREMENT PRIMARY KEY,
        setting_name VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_setting_name (setting_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    
    // Insert default watermark settings
    $defaults = [
        ['watermark_enabled', '0'],
        ['watermark_image', ''],
        ['watermark_position', 'bottom-right'],
        ['watermark_opacity', '80'],
        ['watermark_padding', '20']
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO sitesettings (setting_name, setting_value) VALUES (?, ?)");
    
    foreach ($defaults as $setting) {
        $stmt->execute($setting);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Table sitesettings created successfully with default values'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
