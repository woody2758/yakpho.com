<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $order = $input['order'] ?? [];
    $page = (int)($input['page'] ?? 1);
    $limit = (int)($input['limit'] ?? 15);
    
    if (empty($order) || !is_array($order)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid order data'
        ]);
        exit;
    }
    
    $offset = ($page - 1) * $limit;
    
    // Update order for each channel
    $stmt = $db->prepare("UPDATE sales_channels SET channel_index = ? WHERE channel_id = ?");
    
    foreach ($order as $index => $channelId) {
        $stmt->execute([$offset + $index + 1, $channelId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'บันทึกลำดับเรียบร้อยแล้ว',
        'count' => count($order),
        'start_index' => $offset + 1
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
