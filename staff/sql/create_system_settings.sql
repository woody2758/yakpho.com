-- Create system_settings table for Admin Dashboard
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initialize default settings
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('bot_status', 'on');
