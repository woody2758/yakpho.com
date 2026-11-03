<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $db->beginTransaction();

    // Tag IDs from user_tags table
    $TAG_YAKPHO  = 1; // ยักษ์โพธิ์อโรม่า - maincat_id = 1
    $TAG_THAIHERB = 2; // ไทยเฮิร์บเดิม - all others
    // TAG 3 = เอสเธอร์ - ยังไม่ได้จำหน่าย, ไม่ต้องใส่

    // Step 1: Get all users who have purchased from maincat_id = 1 (ยักษ์โพธิ์อโรม่า)
    // Path: orders.user_id → ordetail.product_id → product.productcat_id → productcat.maincat_id = 1
    $stmtYakpho = $db->query("
        SELECT DISTINCT o.user_id
        FROM orders o
        JOIN ordetail od ON od.orders_id = o.orders_id
        JOIN product p ON p.product_id = od.product_id
        JOIN productcat pc ON pc.productcat_id = p.productcat_id
        WHERE pc.maincat_id = 1
          AND o.orders_del = 0
          AND o.user_id > 0
    ");
    $yakphoUserIds = $stmtYakpho->fetchAll(PDO::FETCH_COLUMN);

    // Step 2: Get all users who have any orders at all (for ไทยเฮิร์บเดิม tag)
    $stmtAll = $db->query("
        SELECT DISTINCT user_id
        FROM orders
        WHERE orders_del = 0
          AND user_id > 0
    ");
    $allOrderUserIds = $stmtAll->fetchAll(PDO::FETCH_COLUMN);

    $yakphoSet = array_flip($yakphoUserIds);

    $insertTag = $db->prepare("
        INSERT IGNORE INTO user_tag_mapping (user_id, tag_id)
        VALUES (?, ?)
    ");

    $countYakpho  = 0;
    $countThaiherb = 0;

    // Step 3: For each user with any order:
    //   - if bought from maincat=1 → tag ยักษ์โพธิ์อโรม่า (tag_id=1)
    //   - else → tag ไทยเฮิร์บเดิม (tag_id=2)
    foreach ($allOrderUserIds as $uid) {
        if (isset($yakphoSet[$uid])) {
            // Assign ยักษ์โพธิ์อโรม่า
            $insertTag->execute([$uid, $TAG_YAKPHO]);
            $countYakpho++;
        } else {
            // Assign ไทยเฮิร์บเดิม
            $insertTag->execute([$uid, $TAG_THAIHERB]);
            $countThaiherb++;
        }
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => "สำเร็จ! ใส่แท็กยักษ์โพธิ์อโรม่า: {$countYakpho} คน, ไทยเฮิร์บเดิม: {$countThaiherb} คน",
        'yakpho_count'   => $countYakpho,
        'thaiherb_count' => $countThaiherb,
        'total'          => count($allOrderUserIds),
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
