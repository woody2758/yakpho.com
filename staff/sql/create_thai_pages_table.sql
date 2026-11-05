CREATE TABLE IF NOT EXISTS thai_massage_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_name VARCHAR(255) NOT NULL,
    page_url VARCHAR(255) UNIQUE NOT NULL,
    messenger_link VARCHAR(255),
    province VARCHAR(100),
    followers VARCHAR(50),
    last_post_date DATETIME,
    is_active TINYINT DEFAULT 1,
    category_best VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
