-- New tables for FB Scraper Settings
CREATE TABLE IF NOT EXISTS fb_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_keyword (keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fb_configs (
    cfg_key VARCHAR(50) PRIMARY KEY,
    cfg_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT IGNORE INTO fb_configs (cfg_key, cfg_value) VALUES ('scroll_depth', '3');
INSERT IGNORE INTO fb_configs (cfg_key, cfg_value) VALUES ('use_cities_mode', '0');

-- Insert initial keywords
INSERT IGNORE INTO fb_keywords (keyword) VALUES 
('Massage Business UK'),
('Thai Spa Europe'),
('Massage Therapy Groups'),
('Spa Owners Worldwide');
