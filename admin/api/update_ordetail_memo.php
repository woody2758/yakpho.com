<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $ordetailId = isset($_POST['ordetail_id']) ? (int)$_POST['ordetail_id'] : 0;
    $memo = isset($_POST['memo']) ? trim($_POST['memo']) : '';

    if (!$ordetailId) {
        throw new Exception('Order detail ID is required');
    }

    $stmt = $db->prepare("UPDATE ordetail SET ordetail_memo = ? WHERE ordetail_id = ?");
    $stmt->execute([$memo, $ordetailId]);

    echo json_encode(['success' => true, 'message' => 'บันทึกหมายเหตุเรียบร้อยแล้ว']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
