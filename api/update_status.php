<?php
require_once "../db.php";
header('Content-Type: application/json');

// Read JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check API Key
$api_key = $data['api_key'] ?? '';
if ($api_key !== 'yakpho_secret_api_2026_xyz') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$city_id = (int)($data['city_id'] ?? 0);
$status = $data['status'] ?? 'completed';

try {
    $stmt = $db->prepare("UPDATE target_cities SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $city_id]);

    echo json_encode(['ok' => true, 'msg' => 'City status updated']);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
