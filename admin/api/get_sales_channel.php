<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM sales_channels WHERE channel_id = ?");
    $stmt->execute([$id]);
    $channel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($channel) {
        echo json_encode(['success' => true, 'channel' => $channel]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลช่องทางขาย']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
