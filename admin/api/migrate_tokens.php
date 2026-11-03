<?php
require_once __DIR__ . "/../includes/config.php";

try {
    // 1. เพิ่มคอลัมน์ track_token ถ้ายังไม่มี
    $db->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS track_token VARCHAR(64) UNIQUE AFTER orders_no");
    
    // 2. สุ่มรหัสให้กับออเดอร์ที่ยังไม่มี Token
    $stmt = $db->query("SELECT orders_id FROM orders WHERE track_token IS NULL OR track_token = ''");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = 0;
    foreach ($orders as $order) {
        $token = bin2hex(random_bytes(16));
        $db->prepare("UPDATE orders SET track_token = ? WHERE orders_id = ?")
           ->execute([$token, $order['orders_id']]);
        $count++;
    }
    
    echo json_encode(["status" => "success", "message" => "Updated $count orders with tokens."]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
