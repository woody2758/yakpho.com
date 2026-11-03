<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS production_batches (
        batch_id INT AUTO_INCREMENT PRIMARY KEY,
        batch_no VARCHAR(20) NOT NULL UNIQUE,
        batch_date DATE NOT NULL,
        batch_data LONGTEXT NOT NULL,
        created_by INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (batch_date),
        INDEX (batch_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql);
    echo "<h3>สร้างตาราง production_batches สำเร็จ!</h3>";
} catch (Exception $e) {
    echo "<h3>เกิดข้อผิดพลาด: " . $e->getMessage() . "</h3>";
}
