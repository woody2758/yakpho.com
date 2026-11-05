<?php
// ตั้งค่าเพื่ออนุญาตให้ yakpho.com หรือโดเมนอื่นที่ได้รับอนุญาต ดึงข้อมูลได้
header("Access-Control-Allow-Origin: *"); // สามารถระบุเป็น https://yakpho.com เมื่อใช้งานจริง
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Api-Key");
header("Content-Type: application/json; charset=UTF-8");

// เช็คคำสั่ง OPTIONS (Preflight Request) สำหรับการยิง Cross-Origin
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ใช้งานไฟล์ config.php ของ admin เพื่อเชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../admin/includes/config.php';

// $db จะถูกสร้างมาจาก ../includes/config.php อัตโนมัติ

// เช็ค API KEY แบบง่าย (เปิดใช้ได้ถ้าต้องการความปลอดภัย)
// $headers = apache_request_headers();
// if (!isset($headers['X-Api-Key']) || $headers['X-Api-Key'] !== 'YAKPHO_SECRET_KEY') {
//     http_response_code(401);
//     echo json_encode(["status" => false, "message" => "Unauthorized", "data" => null], JSON_UNESCAPED_UNICODE);
//     exit();
// }
?>
