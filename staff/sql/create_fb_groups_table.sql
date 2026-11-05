-- Create table for Facebook Groups (Department 5: Social Automation)
CREATE TABLE IF NOT EXISTS facebook_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL,
    group_url VARCHAR(255) NOT NULL,
    member_count INT DEFAULT 0,
    privacy_type ENUM('Public', 'Private') DEFAULT 'Public',
    location_focus VARCHAR(255),
    category_tag VARCHAR(255),
    last_activity VARCHAR(100), -- Post count/day or specific date
    last_post_date DATETIME,
    join_status ENUM('Not Joined', 'Pending', 'Joined', 'Blocked') DEFAULT 'Not Joined',
    approval_required BOOLEAN DEFAULT FALSE,
    suitability_score INT DEFAULT 0, -- 1-10 scale
    last_scraped_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_group_url (group_url)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
