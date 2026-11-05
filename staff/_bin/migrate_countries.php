<?php
require_once "db.php";
require_login();

$sql_file = __DIR__ . '/sql/countries.sql';

if (!file_exists($sql_file)) {
    die("Error: SQL file not found at " . htmlspecialchars($sql_file));
}

$sql_content = file_get_contents($sql_file);

try {
    // Execute the SQL dump
    $db->exec($sql_content);
    
    echo "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>";
    echo "<h2 style='color: green;'>✅ Migration Successful</h2>";
    echo "<p>อัปเดตข้อมูลประเทศทั้งหมดเรียบร้อยแล้ว!</p>";
    echo "<a href='fb_settings.php' style='display: inline-block; margin-top: 15px; padding: 10px 20px; background: #0054a6; color: white; text-decoration: none; border-radius: 4px;'>กลับสู่หน้าระบบ</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ffcccc; background: #fff5f5; border-radius: 8px;'>";
    echo "<h2 style='color: red; text-align: center;'>❌ Migration Failed</h2>";
    echo "<p><strong>Error Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
