<?php
// API: Get Provinces List
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $stmt = $db->query("SELECT provinces_id as id, name_th, name_en FROM provinces ORDER BY CONVERT(name_th USING tis620) ASC");
    $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'provinces' => $provinces
    ]);
    
} catch (PDOException $e) {
    error_log("Get provinces error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูลจังหวัด: ' . $e->getMessage()
    ]);
}
