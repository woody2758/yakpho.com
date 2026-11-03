<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['channel_id']) && $_POST['channel_id'] !== '' ? (int)$_POST['channel_id'] : null;
$name = isset($_POST['channel_name']) ? trim($_POST['channel_name']) : '';
$code = isset($_POST['channel_code']) ? trim($_POST['channel_code']) : '';
$index = isset($_POST['channel_index']) && $_POST['channel_index'] !== '' ? (int)$_POST['channel_index'] : 0;
$status = isset($_POST['channel_status']) ? (int)$_POST['channel_status'] : 0;

if (empty($name) || empty($code)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน (ชื่อ และ รหัสช่องทาง)']);
    exit;
}

try {
    // Check if code already exists
    $checkSql = "SELECT channel_id FROM sales_channels WHERE channel_code = ?";
    $checkParams = [$code];
    if ($id) {
        $checkSql .= " AND channel_id != ?";
        $checkParams[] = $id;
    }
    $stmtCheck = $db->prepare($checkSql);
    $stmtCheck->execute($checkParams);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'รหัสช่องทางขายซ้ำ (Channel Code) กรุณาใช้รหัสอื่น']);
        exit;
    }

    if ($id) {
        // Update
        $sql = "UPDATE sales_channels 
                SET channel_name = ?, channel_code = ?, channel_index = ?, channel_status = ? 
                WHERE channel_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$name, $code, $index, $status, $id]);
    } else {
        // Insert
        $sql = "INSERT INTO sales_channels (channel_name, channel_code, channel_index, channel_status) 
                VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$name, $code, $index, $status]);
    }
    
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
