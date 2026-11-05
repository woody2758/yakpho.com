<?php
require_once "../db.php";
header('Content-Type: application/json');

$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid datal']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE thai_massage_pages SET 
        phone = CASE WHEN phone IS NULL OR phone = '' THEN :phone ELSE phone END, 
        email = CASE WHEN email IS NULL OR email = '' THEN :email ELSE email END, 
        website = CASE WHEN website IS NULL OR website = '' THEN :website ELSE website END, 
        deep_scraped_at = NOW() 
        WHERE id = :id");
        
    $stmt->execute([
        ':phone' => $data['phone'] ?: null,
        ':email' => $data['email'] ?: null,
        ':website' => $data['website'] ?: null,
        ':id' => $data['id']
    ]);
    
    echo json_encode(['ok' => true, 'msg' => 'Deep scrape saved']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
