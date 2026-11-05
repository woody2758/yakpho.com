<?php
require_once "../db.php";
header('Content-Type: application/json');

$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$limit = (int)($_GET['limit'] ?? 5);

// Get pages that haven't been deep scraped yet
$stmt = $db->prepare("SELECT id, page_name, page_url, province FROM thai_massage_pages WHERE deep_scraped_at IS NULL AND is_active = 1 ORDER BY id DESC LIMIT ?");
$stmt->execute([$limit]);
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok' => true, 'data' => $pages]);
