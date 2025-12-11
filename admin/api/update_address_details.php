<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $addrId = isset($_POST['addr_id']) ? (int)$_POST['addr_id'] : 0;
    $name = isset($_POST['addr_name']) ? trim($_POST['addr_name']) : '';
    $detail = isset($_POST['addr_detail']) ? trim($_POST['addr_detail']) : '';
    $detail2 = isset($_POST['addr_detail2']) ? trim($_POST['addr_detail2']) : '';
    $postcode = isset($_POST['addr_postcode']) ? trim($_POST['addr_postcode']) : '';
    $mobile = isset($_POST['addr_mobile']) ? trim($_POST['addr_mobile']) : '';

    $provincesId = isset($_POST['provinces_id']) ? (int)$_POST['provinces_id'] : 0;

    if (!$addrId) throw new Exception('Address ID is required');
    if (!$name) throw new Exception('Name is required');
    if (!$detail) throw new Exception('Address detail is required');
    if (!$mobile) throw new Exception('Mobile number is required');

    $stmt = $db->prepare("UPDATE addr SET 
        addr_name = ?,
        addr_detail = ?,
        addr_detail2 = ?,
        addr_postcode = ?,
        addr_mobile = ?,
        provinces_id = ?
        WHERE addr_id = ?");

    $stmt->execute([$name, $detail, $detail2, $postcode, $mobile, $provincesId, $addrId]);

    echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลที่อยู่เรียบร้อยแล้ว']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
