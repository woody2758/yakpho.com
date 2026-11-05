<?php
require_once "../db.php";
require_login();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
    exit;
}

$id = $data['id'];
$quality = $data['quality'];
$allowed = ['Best', 'Good', 'Poor'];

if (!in_array($quality, $allowed)) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid quality']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE facebook_groups SET post_quality = :quality WHERE id = :id");
    $stmt->execute([':quality' => $quality, ':id' => $id]);
    echo json_encode(['ok' => true]);
} catch(PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
