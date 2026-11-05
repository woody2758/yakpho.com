<?php
/**
 * Permanently Delete Orders (Hard Delete)
 * Removes order + order items + slips + notes + commissions + payment logs
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $orderIds = [];
    if (!empty($data['orders_ids']) && is_array($data['orders_ids'])) {
        foreach ($data['orders_ids'] as $oid) {
            $id = (int)$oid;
            if ($id > 0) $orderIds[] = $id;
        }
    } elseif (!empty($data['orders_id'])) {
        $id = (int)$data['orders_id'];
        if ($id > 0) $orderIds[] = $id;
    }

    $orderIds = array_values(array_unique($orderIds));
    if (empty($orderIds)) {
        throw new Exception('กรุณาเลือกรายการคำสั่งซื้อที่ต้องการลบอย่างถาวร');
    }

    $inPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));

    // Collect slip files to delete from disk after DB commit
    $slipFiles = [];
    try {
        $stmtSlips = $db->prepare("SELECT slip_filename FROM order_slips WHERE orders_id IN ($inPlaceholders)");
        $stmtSlips->execute($orderIds);
        $slipFiles = array_merge($slipFiles, $stmtSlips->fetchAll(PDO::FETCH_COLUMN));
    } catch (Exception $e) {}

    try {
        $stmtMainSlips = $db->prepare("SELECT orders_slip FROM orders WHERE orders_id IN ($inPlaceholders) AND orders_slip IS NOT NULL AND orders_slip != ''");
        $stmtMainSlips->execute($orderIds);
        $slipFiles = array_merge($slipFiles, $stmtMainSlips->fetchAll(PDO::FETCH_COLUMN));
    } catch (Exception $e) {}

    $slipFiles = array_values(array_unique(array_filter($slipFiles)));

    $db->beginTransaction();

    // Delete child records across related tables safely
    $childTables = [
        'ordetail'              => 'orders_id',
        'order_slips'           => 'orders_id',
        'order_notes'           => 'orders_id',
        'pay'                   => 'orders_id',
        'bcheck'                => 'orders_id',
        'affiliate_commissions' => 'orders_id',
        'agent_commissions'     => 'orders_id',
        'product_stock_log'     => 'order_id',
    ];

    foreach ($childTables as $table => $col) {
        try {
            $delChild = $db->prepare("DELETE FROM `$table` WHERE `$col` IN ($inPlaceholders)");
            $delChild->execute($orderIds);
        } catch (Exception $e) {
            // Ignore if optional table does not exist
        }
    }

    // Delete main orders records permanently
    $delOrders = $db->prepare("DELETE FROM `orders` WHERE `orders_id` IN ($inPlaceholders)");
    $delOrders->execute($orderIds);
    $deletedCount = $delOrders->rowCount();

    $db->commit();

    // Remove slip files from disk if they exist
    $slipsDir = __DIR__ . '/../../uploads/slips/';
    foreach ($slipFiles as $file) {
        $cleanFile = basename((string)$file);
        if ($cleanFile !== '' && file_exists($slipsDir . $cleanFile)) {
            @unlink($slipsDir . $cleanFile);
        }
    }

    echo json_encode([
        'success'       => true,
        'deleted_count' => $deletedCount,
        'message'       => "ลบคำสั่งซื้ออย่างถาวรเรียบร้อยแล้ว ({$deletedCount} รายการ)"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
