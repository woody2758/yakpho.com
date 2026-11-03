<?php
require_once __DIR__ . '/../includes/config.php';
$is_api_call = true;
require_once __DIR__ . '/../includes/scanner_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$orders_no = $_POST['orders_no'] ?? '';
$image_data = $_POST['image_data'] ?? '';

if (empty($orders_no) || empty($image_data)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT orders_id, orders_no, orders_status FROM orders WHERE orders_no = ?");
    $stmt->execute([$orders_no]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found: ' . $orders_no]);
        exit;
    }

    // Check if order is already packed to prevent double packing on the server side
    if (!in_array($order['orders_status'], [3, 4])) {
        echo json_encode(['status' => 'error', 'message' => 'ออเดอร์นี้ถูกแพ็คไปแล้ว กรุณาสแกนออเดอร์ถัดไป']);
        exit;
    }

    if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
        $image_data = substr($image_data, strpos($image_data, ',') + 1);
        $type = strtolower($type[1]); 
        
        $image_data = base64_decode($image_data);
        if ($image_data === false) {
            echo json_encode(['status' => 'error', 'message' => 'Base64 decode failed']);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid image data']);
        exit;
    }

    $upload_dir = __DIR__ . '/../../uploads/packing/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = 'pack_' . $orders_no . '_' . time() . '.webp';
    $filepath = $upload_dir . $filename;
    
    if (file_put_contents($filepath, $image_data)) {
        // Update order status to 11 (เตรียมนำส่ง)
        $new_status = 11; 
        
        $updateStmt = $db->prepare("
            UPDATE orders 
            SET orders_status = ?, 
                packing_image = ?, 
                packed_at = NOW(), 
                packed_by = ?
            WHERE orders_id = ?
        ");
        $updateStmt->execute([$new_status, $filename, $_SESSION['scanner_user_id'], $order['orders_id']]);

        echo json_encode(['status' => 'success', 'message' => 'Order updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save image file']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
