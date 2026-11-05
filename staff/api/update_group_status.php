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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['ok' => false, 'msg' => 'ID Required']);
    exit;
}

$id = (int)$data['id'];
$status = $data['status'] ?? 'Not Joined';

try {
    $stmt = $db->prepare("UPDATE facebook_groups SET join_status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);

    echo json_encode(['ok' => true, 'msg' => 'Status updated to ' . $status]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
