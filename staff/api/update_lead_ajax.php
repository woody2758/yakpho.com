<?php
require_once "../db.php";

header('Content-Type: application/json');

// Check authentication
// if (!isset($_SESSION['ai_staff_user'])) {
//     echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
//     exit;
// }

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
    exit;
}

$id = $data['id'] ?? null;
$field = $data['field'] ?? '';
$value = trim($data['value'] ?? '');

$allowed_fields = ['shop_name', 'email', 'status'];

if (!$id || !in_array($field, $allowed_fields)) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE massage_leads SET {$field} = :value WHERE id = :id");
    $stmt->execute([':value' => $value, ':id' => $id]);
    
    echo json_encode(['ok' => true, 'msg' => 'Updated successfully']);
} catch(PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
