<?php
session_start();

// Database Configuration
$host = "localhost";
$db_name = "woodysay_yakpho"; 
$username = "woodysay_yakpho"; 
$password = 'Yp!A9#zN2v8$LpQx'; 

try {
    $db = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function สำหรับตรวจสอบการล็อกอิน (เรียกใช้ในหน้าอื่นที่ไม่ใช่หน้าล็อกอิน)
function require_login() {
    if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}

// Function to generate consistent color for tags
if(!function_exists('getTagColor')) {
    function getTagColor($tag) {
        $colors = ['blue', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan'];
        $hash = abs(crc32($tag));
        return $colors[$hash % count($colors)];
    }
}
?>
