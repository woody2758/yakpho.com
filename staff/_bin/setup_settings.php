<?php
require_once "db.php";

try {
    // 1. สร้างตาราง system_settings
    $db->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // 2. เพิ่มค่าตั้งต้นถ้ายังไม่มี
    $settings = [
        'bot_status' => 'on',
        'bot_current_action' => 'Waiting for command...',
        'bot_progress_percent' => '0',
        'telegram_bot_token' => '',
        'telegram_chat_id' => ''
    ];

    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    
    echo "Database setup successful. Table 'system_settings' created and initialized with monitoring keys.";
} catch(PDOException $e) {
    echo "Critical Error: " . $e->getMessage();
}
?>
