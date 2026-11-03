<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['matrix'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีข้อมูลสำหรับบันทึก']);
    exit;
}

try {
    // 1. Generate Batch Number (PD + YYMMDD + 001)
    $today = date('ymd');
    $prefix = "PD" . $today;
    
    // Find last batch number for today
    $stmt = $db->prepare("SELECT batch_no FROM production_batches WHERE batch_no LIKE ? ORDER BY batch_no DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last) {
        $lastSeq = (int)substr($last['batch_no'], -3);
        $newSeq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $newSeq = '001';
    }
    
    $batchNo = $prefix . $newSeq;
    $batchDate = date('Y-m-d');
    $batchData = $json; // Save entire received JSON
    $adminId = $_SESSION['admin_id'] ?? 0;

    // 2. Save to Database
    $sql = "INSERT INTO production_batches (batch_no, batch_date, batch_data, created_by) 
            VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$batchNo, $batchDate, $batchData, $adminId]);
    
    $batchId = $db->lastInsertId();

    echo json_encode([
        'success' => true, 
        'message' => 'บันทึกประวัติการผลิตเรียบร้อยแล้ว',
        'batch_no' => $batchNo,
        'batch_id' => $batchId
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
