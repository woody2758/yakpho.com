<?php
require_once "../db.php";
header('Content-Type: application/json');

// Read JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check API Key
$api_key = $data['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

try {
    $sql = "INSERT INTO massage_leads (
                shop_name, city, region, country, maps_url, 
                website, email, review_count, review_highlights, 
                praise_draft, status
            ) VALUES (
                :shop_name, :city, :region, :country, :maps_url, 
                :website, :email, :review_count, :review_highlights, 
                :praise_draft, :status
            )";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':shop_name' => $data['shop_name'] ?? 'Not Found',
        ':city' => $data['city'] ?? '',
        ':region' => $data['region'] ?? '',
        ':country' => $data['country'] ?? '',
        ':maps_url' => $data['maps_url'] ?? 'N/A',
        ':website' => $data['website'] ?? 'N/A',
        ':email' => $data['email'] ?? 'Not Found',
        ':review_count' => (int)($data['review_count'] ?? 0),
        ':review_highlights' => $data['review_highlights'] ?? '',
        ':praise_draft' => $data['praise_draft'] ?? '',
        ':status' => $data['status'] ?? 'New Lead'
    ]);

    echo json_encode(['ok' => true, 'msg' => 'Lead saved successfully']);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
