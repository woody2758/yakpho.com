<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? 0;
$addrName = $input['addr_name'] ?? '';
$addrDetail = $input['addr_detail'] ?? '';
$addrDetail2 = $input['addr_detail2'] ?? '';
$provinceId = $input['provinces_id'] ?? 0;
$postcode = $input['addr_postcode'] ?? '';
$mobile = $input['addr_mobile'] ?? '';

if (empty($userId) || empty($addrName) || empty($addrDetail) || empty($provinceId)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Auto-fix missing columns if any
try {
    $db->exec("ALTER TABLE addr ADD addr_tax_id VARCHAR(20) DEFAULT NULL AFTER addr_mobile");
} catch (PDOException $e) { /* Column might already exist */ }
try {
    $db->exec("ALTER TABLE addr ADD addr_branch VARCHAR(10) DEFAULT '00000' AFTER addr_tax_id");
} catch (PDOException $e) { /* Column might already exist */ }

try {
    $taxId = $input['addr_tax_id'] ?? '';
    $branch = $input['addr_branch'] ?? '00000';

    $sql = "INSERT INTO addr (user_id, addr_name, addr_detail, addr_detail2, provinces_id, addr_postcode, addr_mobile, addr_tax_id, addr_branch, addr_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $userId,
        $addrName,
        $addrDetail,
        $addrDetail2,
        $provinceId,
        $postcode,
        $mobile,
        $taxId,
        $branch
    ]);
    
    $addrId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'บันทึกที่อยู่ใหม่สำเร็จ',
        'addr_id' => $addrId
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
