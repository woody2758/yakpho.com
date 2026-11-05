<?php
require_once "../db.php";
header('Content-Type: application/json');

// Get API Key from query string
$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

try {
    // Fetch cities that are still 'pending'
    $stmt = $db->prepare("SELECT id, city, region, country FROM target_cities WHERE status = 'pending' LIMIT 10");
    $stmt->execute();
    $data = $stmt->fetchAll();
    
    echo json_encode([
        'ok' => true,
        'data' => $data
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
