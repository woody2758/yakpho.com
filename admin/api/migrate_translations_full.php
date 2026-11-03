<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // ล้างข้อมูลเก่าเพื่อเริ่มใหม่ให้สะอาด
    $db->exec("DELETE FROM orsts_translations WHERE lang_code IN ('th', 'en')");
    
    // เริ่ม Transaction
    $db->beginTransaction();

    // ดึงข้อมูลจาก orsts
    $stmt = $db->query("SELECT * FROM orsts");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // คำแปลเพิ่มเติม
    $translations = [
        'ได้ซื้อแล้ว' => ['en' => 'Payment Completed', 'msg_en' => 'Your payment has been received.'],
        'ที่ต้องชำระ' => ['en' => 'Awaiting Payment', 'msg_en' => 'Please complete your payment.'],
        'เตรียมสินค้า' => ['en' => 'Preparing', 'msg_en' => 'We are preparing your items.'],
        'ที่ต้องได้รับ' => ['en' => 'To Receive', 'msg_en' => 'Items are on the way to you.'],
        'ได้รับแล้ว' => ['en' => 'Delivered', 'msg_en' => 'Order has been delivered successfully.'],
        'ยกเลิกแล้ว' => ['en' => 'Cancelled', 'msg_en' => 'Order has been cancelled.'],
        'ยกเลิกโดยแอดมิน' => ['en' => 'Cancelled by Admin', 'msg_en' => 'Order was cancelled by administrator.'],
        'ตีคืนพัสดุสำเร็จ' => ['en' => 'Returned', 'msg_en' => 'Package has been returned to sender.'],
        'รอส่ง สินค้าหมด' => ['en' => 'Out of Stock', 'msg_en' => 'Items are currently out of stock.'],
        'เตรียมนำส่ง' => ['en' => 'Dispatched', 'msg_en' => 'Order is ready for dispatch.'],
    ];

    function translateStatus($th, $map) {
        foreach ($map as $key => $val) {
            if (mb_strpos($th, $key) !== false) return $val;
        }
        return ['en' => $th, 'msg_en' => ''];
    }

    $insertStmt = $db->prepare("INSERT INTO orsts_translations (orsts_id, lang_code, orsts_name, orsts_msg) VALUES (?, ?, ?, ?)");

    foreach ($statuses as $status) {
        // --- TH ---
        $insertStmt->execute([$status['orsts_id'], 'th', $status['orsts_detail'], $status['orsts_msg']]);

        // --- EN ---
        $trans = translateStatus($status['orsts_detail'], $translations);
        $enName = $trans['en'];
        $enMsg = !empty($trans['msg_en']) ? $trans['msg_en'] : "Status updated: " . $enName;
        $insertStmt->execute([$status['orsts_id'], 'en', $enName, $enMsg]);
        
        echo "Processed ID {$status['orsts_id']}: {$status['orsts_detail']} -> {$enName}<br>";
    }

    $db->commit();
    echo "<h3>✅ All status data migrated and translated!</h3>";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
