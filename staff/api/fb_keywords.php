<?php
require_once "../db.php";

header('Content-Type: application/json; charset=utf-8');

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['status' => false, 'data' => null, 'message' => 'Invalid request']);
        exit;
    }

    if ($data['action'] === 'edit') {
        if (!isset($data['id']) || !isset($data['keyword'])) {
             http_response_code(400);
             echo json_encode(['status' => false, 'data' => null, 'message' => 'Missing ID or keyword']);
             exit;
        }
        $stmt = $db->prepare("UPDATE fb_keywords SET keyword = ? WHERE id = ?");
        $stmt->execute([trim($data['keyword']), $data['id']]);
        echo json_encode(['status' => true, 'data' => null, 'message' => 'Keyword updated successfully']);
        exit;
    }

    if ($data['action'] === 'delete') {
        if (!isset($data['id'])) {
             http_response_code(400);
             echo json_encode(['status' => false, 'data' => null, 'message' => 'Missing ID']);
             exit;
        }
        $stmt = $db->prepare("DELETE FROM fb_keywords WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['status' => true, 'data' => null, 'message' => 'Keyword deleted successfully']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => false, 'data' => null, 'message' => 'Unknown action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'data' => null, 'message' => $e->getMessage()]);
}
?>
