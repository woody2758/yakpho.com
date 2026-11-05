<?php
require_once "../db.php";

header('Content-Type: application/json');

// API Key Validation
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$keyword = $data['keyword'] ?? '';

if (!$keyword) {
    echo json_encode(['ok' => false, 'msg' => 'No keyword provided']);
    exit;
}

try {
    $stmt = $db->prepare("INSERT IGNORE INTO fb_scrape_history (keyword) VALUES (?)");
    $stmt->execute([$keyword]);
    echo json_encode(['ok' => true, 'msg' => 'Keyword marked as done']);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Database Error: ' . $e->getMessage()]);
}
?>
