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

if (!$data || !is_array($data)) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid Data']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE facebook_groups SET 
        category_tag = ?, 
        suitability_score = ?, 
        ai_summary = ?,
        updated_at = NOW()
        WHERE id = ?");

    $count = 0;
    foreach ($data as $item) {
        $stmt->execute([
            $item['tags'] ?? '',
            (int)($item['score'] ?? 0),
            $item['summary'] ?? '',
            (int)($item['id'] ?? 0)
        ]);
        $count += $stmt->rowCount();
    }

    echo json_encode(['ok' => true, 'updated_count' => $count]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
