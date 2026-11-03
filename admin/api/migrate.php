<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM `user` LIKE 'user_google_map'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE `user` ADD COLUMN `user_google_map` TEXT NULL AFTER `user_note`");
        echo "<h3 style='color: green;'>✅ อัปเดตฐานข้อมูลสำเร็จ: เพิ่มคอลัมน์ user_google_map เรียบร้อยแล้ว</h3>";
    } else {
        echo "<h3 style='color: blue;'>ℹ️ คอลัมน์ user_google_map มีอยู่ในฐานข้อมูลอยู่แล้วครับ</h3>";
    }
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</h3>";
}
?>
