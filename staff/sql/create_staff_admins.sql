CREATE TABLE IF NOT EXISTS staff_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    status ENUM('active', 'hold', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- รหัสผ่านเริ่มต้นสำหรับแอดมินคนแรกคือ: yakpho2026
-- Generate จาก password_hash('yakpho2026', PASSWORD_BCRYPT);
INSERT IGNORE INTO staff_admins (name, email, password_hash, role, status)
VALUES ('Super Admin', 'admin@yakpho.com', '$2y$10$Nelwqo8XEoR5lU43QKiz5e2D5f9M4E3bWSJR2ldDw9iLboPxu29Fm', 'superadmin', 'active');
