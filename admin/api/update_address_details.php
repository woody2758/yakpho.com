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
    $forword = isset($_POST['addr_forword']) ? trim($_POST['addr_forword']) : '';
    $taxId = isset($_POST['addr_tax_id']) ? trim($_POST['addr_tax_id']) : '';
    $branch = isset($_POST['addr_branch']) ? trim($_POST['addr_branch']) : '00000';
    $addrType = isset($_POST['addr_type']) ? (int)$_POST['addr_type'] : 1;

    // Auto-fix missing columns if any
    try {
        $db->exec("ALTER TABLE addr ADD addr_tax_id VARCHAR(20) DEFAULT NULL AFTER addr_mobile");
    } catch (PDOException $e) { /* Column might already exist */ }
    try {
        $db->exec("ALTER TABLE addr ADD addr_branch VARCHAR(10) DEFAULT '00000' AFTER addr_tax_id");
    } catch (PDOException $e) { /* Column might already exist */ }

    $provincesId = isset($_POST['provinces_id']) ? (int)$_POST['provinces_id'] : 0;

    if (!$addrId) throw new Exception('Address ID is required');
    if (!$name) throw new Exception('Name is required');
    if (!$detail) throw new Exception('Address detail is required');
    if (!$mobile) throw new Exception('Mobile number is required');

    // Check Order Status if order_id is provided
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    if ($orderId > 0) {
        $stmt = $db->prepare("SELECT orders_status FROM orders WHERE orders_id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $lockedStatuses = [11, 7, 8, 9];
        if ($order && in_array((int)$order['orders_status'], $lockedStatuses)) {
            throw new Exception('ออเดอร์นี้ถูกล็อกแล้ว ไม่สามารถแก้ไขที่อยู่ได้');
        }
    }

    $stmt = $db->prepare("UPDATE addr SET 
        addr_name = ?,
        addr_detail = ?,
        addr_detail2 = ?,
        addr_postcode = ?,
        addr_mobile = ?,
        provinces_id = ?,
        addr_forword = ?,
        addr_tax_id = ?,
        addr_branch = ?,
        addr_type = ?
        WHERE addr_id = ?");

    $stmt->execute([$name, $detail, $detail2, $postcode, $mobile, $provincesId, $forword, $taxId, $branch, $addrType, $addrId]);

    echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลที่อยู่เรียบร้อยแล้ว']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
