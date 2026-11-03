<?php
// API: Get Provinces List
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $stmt = $db->query("SELECT provinces_id as id, name_th, name_en FROM provinces ORDER BY CASE WHEN provinces_id = 79 THEN 0 ELSE 1 END, name_th ASC");
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
