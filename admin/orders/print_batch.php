<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

/**
 * Format Thai Phone Number
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10) {
        return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    } elseif (strlen($phone) == 9) {
        return substr($phone, 0, 2) . '-' . substr($phone, 2, 3) . '-' . substr($phone, 5);
    }
    return $phone;
}

// 1. Determine which Order IDs to print
if (isset($_GET['ids']) && !empty($_GET['ids'])) {
    $order_ids = explode(',', $_GET['ids']);
    $order_ids = array_map('intval', $order_ids);
} else {
    $stmt_sts = $db->query("SELECT orsts_id FROM orsts WHERE orsts_detail LIKE '%เตรียมสินค้า%'");
    $status_ids = $stmt_sts->fetchAll(PDO::FETCH_COLUMN);
    if (empty($status_ids)) die("No 'Preparing' statuses found.");

    $in_query = implode(',', array_fill(0, count($status_ids), '?'));
    $sql = "SELECT orders_id FROM orders WHERE orders_status IN ($in_query) AND (orders_print = 0 OR orders_print IS NULL) ORDER BY orders_date ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($status_ids);
    $order_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if (empty($order_ids)) {
    echo "<script>alert('ไม่มีรายการออเดอร์ที่ต้องการพิมพ์'); window.close();</script>";
    exit;
}

// Get Shop Info
$stmt_info = $db->query("SELECT * FROM shop_info WHERE shop_id = 1");
$shop = $stmt_info->fetch(PDO::FETCH_ASSOC) ?: [];
$useLang = $_GET['lang'] ?? 'th';
$stmt_shop = $db->prepare("SELECT st.* FROM shop_info_translations st WHERE st.shop_id = 1 AND st.lang_code = ?");
$stmt_shop->execute([$useLang]);
$sender_th = $stmt_shop->fetch(PDO::FETCH_ASSOC) ?: [];
if (empty($sender_th) && $useLang != 'th') {
    $stmt_shop->execute(['th']);
    $sender_th = $stmt_shop->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Multi-language Labels
$labels = [
    'th' => [
        'no' => 'เลขที่', 'date' => 'วันที่', 'sender' => 'ผู้ส่ง (Sender)', 'receiver' => 'ผู้รับ (Receiver)',
        'customer' => 'ลูกค้า (Customer)', 'tel' => 'โทร', 'tax_id' => 'เลขประจำตัวผู้เสียภาษี',
        'branch' => 'สาขา', 'tracking' => 'เลขพัสดุ', 'item' => 'รายการสินค้า (Items)', 'code' => 'รหัสสินค้า', 'qty' => 'จำนวน', 'total' => 'รวมเงิน',
        'subtotal' => 'ราคารวมสินค้า', 'discount' => 'ส่วนลด (Discount)', 'shipping' => 'ค่าจัดส่ง', 'cod_fee' => 'ค่าธรรมเนียม COD (3%)', 'postal_code' => 'รหัสไปรษณีย์',
        'grand_total' => 'ยอดสุทธิ (Grand Total)', 'paid' => 'มัดจำ / ชำระแล้ว', 'balance' => 'ยอดคงเหลือ (Balance)',
        'order_note' => 'หมายเหตุ', 'signature_customer' => 'ผู้รับสินค้า (Receiver Signature)',
        'signature_authorized' => 'ผู้จัดการ (Authorized Signature)', 'footer_thanks' => 'ขอบคุณที่อุดหนุนผลิตภัณฑ์ในเครือ Esther Aroma Group',
        'created_by' => 'Created by', 'updated_by' => 'Updated by', 'printed_by' => 'Printed by',
        'vat_base' => 'ยอดรวม (ก่อนภาษี)', 'vat_7' => 'ภาษีมูลค่าเพิ่ม (VAT 7%)',
        'total_items' => 'รวมจำนวนชิ้นทั้งหมด:'
    ],
    'en' => [
        'no' => 'No.', 'date' => 'Date', 'sender' => 'Sender', 'receiver' => 'Receiver',
        'customer' => 'Customer', 'tel' => 'Tel', 'tax_id' => 'Tax ID',
        'branch' => 'Branch', 'tracking' => 'Tracking No.', 'item' => 'Items', 'code' => 'Code', 'qty' => 'Qty', 'total' => 'Amount',
        'subtotal' => 'Subtotal', 'discount' => 'Discount', 'shipping' => 'Shipping', 'cod_fee' => 'COD Fee (3%)', 'postal_code' => 'Postal Code',
        'grand_total' => 'Grand Total', 'paid' => 'Deposit / Paid', 'balance' => 'Balance Due',
        'order_note' => 'Note', 'signature_customer' => 'Receiver Signature',
        'signature_authorized' => 'Authorized Signature', 'footer_thanks' => 'Thank you for your business',
        'created_by' => 'Created by', 'updated_by' => 'Updated by', 'printed_by' => 'Printed by',
        'vat_base' => 'Total (Excl. VAT)', 'vat_7' => 'VAT 7%',
        'total_items' => 'Total Items:'
    ]
];
$L = $labels[$useLang] ?? $labels['th'];

// Get current admin (user) name
$current_admin_id = $_SESSION['admin_id'] ?? 0;
$stmt_admin = $db->prepare("SELECT user_name, user_lastname FROM user WHERE user_id = ?");
$stmt_admin->execute([$current_admin_id]);
$admin_data = $stmt_admin->fetch(PDO::FETCH_ASSOC);
$current_admin_name = $admin_data ? ($admin_data['user_name'] . ' ' . $admin_data['user_lastname']) : 'Admin';

/**
 * Render Shipping Label (A5 Portrait)
 */
function renderLabel($order, $items, $address, $order_count, $current_admin_name, $shop, $sender_th, $current_box = 1, $total_box = 1) {
    global $L;
    $sender_name = $sender_th['official_name'] ?? ($sender_th['shop_name'] ?? 'Esther Herbal');
    $sender_address = $sender_th['official_address'] ?? ($sender_th['shop_address'] ?? '');
    $has_cod = ($order['paycat_id'] == 2);
    $total_qty = array_sum(array_column($items, 'ordetail_qty'));
    
    $labelChunks = array_chunk($items, 20) ?: [[]];
    foreach ($labelChunks as $chunkIndex => $chunk):
        $isFirstLabelPage = ($chunkIndex == 0);
        $labelPageText = count($labelChunks) > 1 ? " (หน้า " . ($chunkIndex + 1) . "/" . count($labelChunks) . ")" : "";
    ?>
    <div class="print-page label-page">
        <div class="page-inner">
            <div class="label-header">
                <div class="label-logo">
                    <?php if (!empty($shop['shop_logo'])): ?>
                        <img src="<?= ROOT_URL ?>/uploads/shop/<?= $shop['shop_logo'] ?>" style="max-height: 45px;">
                    <?php else: ?>
                        <span class="fw-bold" style="font-size: 18px;"><?= $sender_name ?></span>
                    <?php endif; ?>
                </div>
                <div class="label-meta">
                    <div class="barcode-box">
                        <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=EST<?= $order['orders_no'] ?>&height=10&scale=2" style="height: 30px;">
                        <div class="barcode-text">#<?= $order['orders_no'] ?><?= $labelPageText ?></div>
                        <div class="label-date"><?= $L['date'] ?>: <?= date('d/m/Y', strtotime($order['orders_date'])) ?></div>
                        <?php if ($total_box > 1): ?>
                            <div style="font-weight: bold; font-size: 14px; margin-top: 2px;">กล่องที่ <?= $current_box ?>/<?= $total_box ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="label-body">
                <?php if ($isFirstLabelPage): ?>
                <div class="address-row">
                    <div class="sender-side">
                        <div class="addr-title"><?= $L['sender'] ?></div>
                        <div class="addr-content">
                            <strong><?= $sender_name ?></strong><br>
                            <span style="font-size: 10px;"><?= htmlspecialchars($sender_address) ?></span><br>
                            <strong><?= $L['tel'] ?>:</strong> <?= formatPhone($shop['shop_phone']) ?>
                        </div>
                        <div class="qr-container" style="margin-top: 10px;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=EST<?= $order['orders_no'] ?>" style="width: 65px;">
                            <div style="font-size: 9px; font-weight: bold; margin-top: 2px;">SCAN ORDER</div>
                        </div>
                    </div>
                    <div class="receiver-side">
                        <div class="d-flex align-items-baseline">
                            <div class="addr-title"><?= $L['receiver'] ?> &nbsp;C<?= $order['user_id'] ?>-<?= $order_count ?></div>
                        </div>

                        <div class="addr-content receiver-main">
                            <div class="receiver-name"><?= htmlspecialchars($address['addr_name'] ?? ($order['user_name'] . ' ' . $order['user_lastname'])) ?></div>
                            <div class="receiver-address">
                                <?= htmlspecialchars($address['addr_detail'] ?? '') ?> <?= htmlspecialchars($address['addr_detail2'] ?? '') ?> 
                                <strong><?= htmlspecialchars($address['province_name'] ?? '') ?></strong>
                            </div>
                            
                            <div class="receiver-footer-row d-flex align-items-end <?= $has_cod ? 'justify-content-between' : 'justify-content-start' ?>">
                                <?php if ($has_cod): ?>
                                    <div class="cod-frame-box">
                                        <div class="cod-t1">COD</div>
                                        <div class="cod-t2"><?= number_format($order['orders_grandtotal']) ?> ฿</div>
                                    </div>
                                <?php endif; ?>
                                <div class="receiver-meta-group" style="<?= $has_cod ? 'text-align: right;' : 'text-align: left;' ?>">
                                    <?php if (empty($address['is_foreign'])): ?>
                                    <div class="postcode-label"><?= $L['postal_code'] ?></div>
                                    <div class="receiver-postcode"><?= htmlspecialchars($address['addr_postcode'] ?? '') ?></div>
                                    <div class="receiver-mobile">📞 <?= formatPhone($address['addr_mobile'] ?? '') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="items-summary" <?php if(!$isFirstLabelPage) echo 'style="border-top: none; margin-top: 0;"'; ?>>
                    <div class="addr-title d-flex">
                        <span style="width: 25px;">#</span>
                        <span style="flex: 1;"><?= $L['item'] ?></span>
                        <span style="width: 40px; text-align: right;"><?= $L['qty'] ?></span>
                    </div>
                    <div class="items-list-compact">
                        <?php 
                        $globalIdx = $chunkIndex * 20;
                        foreach ($chunk as $idx => $item): 
                        ?>
                            <div class="item-compact-row d-flex">
                                <div style="width: 25px;"><?= $globalIdx + $idx + 1 ?></div>
                                <div class="name" style="flex: 1;">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                    <?php if(!empty($item['ordetail_memo'])): ?>
                                        <span class="memo">* <?= htmlspecialchars($item['ordetail_memo']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="qty" style="width: 40px; text-align: right;"><?= $item['ordetail_qty'] ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($chunkIndex === count($labelChunks) - 1): ?>
                            <div class="item-compact-row d-flex" style="border-top: 1px solid #ccc; font-weight: bold; background: #f8f9fa;">
                                <div style="flex: 1; text-align: right; padding-right: 10px;"><?= $L['total_items'] ?></div>
                                <span class="qty" style="width: 40px; text-align: right;"><?= $total_qty ?></span>
                            </div>
                            <?php if(!empty($order['orders_msg'])): ?>
                                <div style="font-size: 11px; padding: 2mm 1mm; border: 1px dashed #bbb; margin-top: 2mm; background: #fff; color: #dc3545;">
                                    <strong><?= $L['order_note'] ?>:</strong> <?= nl2br(htmlspecialchars($order['orders_msg'])) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="page-footer">
                <div class="footer-left">
                    <div style="margin-bottom: 2px;"><?= $L['created_by'] ?>: <?= htmlspecialchars(($order['creator_fname'] ?? '') . ' ' . ($order['creator_lname'] ?? '')) ?: 'System' ?></div>
                    <div><?= $L['updated_by'] ?>: <?= htmlspecialchars(($order['updater_fname'] ?? '') . ' ' . ($order['updater_lname'] ?? '')) ?: '-' ?></div>
                </div>
                <div class="footer-right">
                    <div><?= $L['printed_by'] ?>: <?= htmlspecialchars($current_admin_name) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php
    endforeach;
}

/**
 * Render Regular Receipt Style (A5 Portrait) - Compact
 */
function renderBill($order, $items, $address, $order_count, $current_admin_name, $shop, $sender_th) {
    global $L;
    $sender_name = $sender_th['official_name'] ?? ($sender_th['shop_name'] ?? 'Esther Herbal');
    $sender_address = $sender_th['official_address'] ?? ($sender_th['shop_address'] ?? '');
    $total_qty = array_sum(array_column($items, 'ordetail_qty'));
    
    $billChunks = array_chunk($items, 20) ?: [[]];
    foreach ($billChunks as $chunkIndex => $chunk):
        $isLastBillPage = ($chunkIndex === count($billChunks) - 1);
        $billPageText = count($billChunks) > 1 ? " (หน้า " . ($chunkIndex + 1) . "/" . count($billChunks) . ")" : "";
    ?>
    <div class="print-page bill-page">
        <div class="page-inner">
            <div class="bill-header-row">
                <div class="bill-logo-side">
                    <?php if (!empty($shop['shop_logo'])): ?>
                        <img src="<?= ROOT_URL ?>/uploads/shop/<?= $shop['shop_logo'] ?>" style="max-height: 40px;">
                    <?php endif; ?>
                </div>
                <div class="bill-qr-side">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=EST<?= $order['orders_no'] ?>" style="width: 35px; height: 35px;">
                </div>
                <div class="bill-title-side">
                    <h1 class="doc-title">ใบเสร็จรับเงิน (REGULAR RECEIPT)<?= $billPageText ?></h1>
                    <div class="doc-meta">
                        เลขที่: RE<?= $order['orders_no'] ?><br>
                        วันที่: <?= date('d/m/Y H:i', strtotime($order['orders_date'])) ?>
                    </div>
                </div>
            </div>

            <div class="bill-address-row">
                <div class="addr-box">
                    <h3 class="box-title"><?= $L['sender'] ?></h3>
                    <div class="box-content">
                        <strong><?= $sender_name ?></strong><br>
                        <?= htmlspecialchars($sender_address) ?><br>
                        <?= $L['tel'] ?>: <?= formatPhone($shop['shop_phone']) ?>
                    </div>
                </div>
                <div class="addr-box">
                    <h3 class="box-title"><?= $L['customer'] ?></h3>
                    <div class="box-content">
                        <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 3px;">
                            <strong><?= htmlspecialchars($address['addr_name'] ?? ($order['user_name'] . ' ' . $order['user_lastname'])) ?></strong>
                            <span class="customer-id-badge">C<?= $order['user_id'] ?>-<?= $order_count ?></span>
                        </div>
                        <?= htmlspecialchars(($address['addr_detail'] ?? '') . ' ' . ($address['addr_detail2'] ?? '')) ?><br>
                        <?= htmlspecialchars(($address['province_name'] ?? '') . ' ' . (empty($address['is_foreign']) ? ($address['addr_postcode'] ?? '') : '')); ?><br>
                        <?php if (empty($address['is_foreign'])): ?><?= $L['tel'] ?>: <?= formatPhone($address['addr_mobile'] ?? '') ?><?php endif; ?>
                    </div>
                </div>
            </div>

            <table class="bill-items-table">
                <thead>
                    <tr>
                        <th style="width: 25px;">#</th>
                        <th><?= $L['item'] ?></th>
                        <th style="width: 35px;" class="text-center"><?= $L['qty'] ?></th>
                        <th style="width: 55px;" class="text-right"><?= $L['total'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $globalIdx = $chunkIndex * 20;
                    foreach ($chunk as $idx => $item): 
                        $rowTotal = $item['product_price'] * $item['ordetail_qty'];
                    ?>
                    <tr>
                        <td class="text-center"><?= $globalIdx + $idx + 1 ?></td>
                        <td>
                            <?= htmlspecialchars($item['product_name']) ?>
                        </td>
                        <td class="text-center"><?= $item['ordetail_qty'] ?></td>
                        <td class="text-right"><?= number_format($rowTotal, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($isLastBillPage && count($chunk) < 8): ?>
                        <?php for($i = count($chunk); $i < 8; $i++): ?>
                            <tr class="blank-row"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                        <?php endfor; ?>
                    <?php endif; ?>
                    <?php if ($isLastBillPage): ?>
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td colspan="2" class="text-right" style="padding-right: 10px;"><?= $L['total_items'] ?></td>
                            <td class="text-center"><?= $total_qty ?></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($isLastBillPage): ?>
            <div class="bill-summary-row-v2">
                <div class="summary-left-v2">
                    <div class="barcode-container-v2">
                        <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=EST<?= $order['orders_no'] ?>&height=10&scale=2&includetext" style="height: 35px;">
                    </div>
                    <?php if(!empty($order['orders_msg'])): ?>
                        <div class="note-box-v2">
                            <strong>Note:</strong> <?= nl2br(htmlspecialchars($order['orders_msg'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="summary-right-v2">
                    <div class="sum-line-v2">
                        <span><?= $L['subtotal'] ?>:</span>
                        <span><?= number_format($order['orders_total'], 2) ?></span>
                    </div>
                    <div class="sum-line-v2">
                        <span><?= $L['shipping'] ?>:</span>
                        <span><?= number_format($order['orders_delicost'], 2) ?></span>
                    </div>
                    <?php if ($order['orders_cod'] > 0): ?>
                        <div class="sum-line-v2 fw-bold" style="color: #d32f2f;">
                            <span><?= $L['cod_fee'] ?>:</span>
                            <span><?= number_format($order['orders_cod'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php
                    global $db;
                    $redeemedStampsBat = 0;
                    try {
                        if (isset($db)) {
                            $stBat = $db->prepare("SELECT SUM(ABS(points_change)) FROM member_points_log WHERE reference_id = ? AND action_type = 'order_discount' AND points_change < 0");
                            $stBat->execute([(int)$order['orders_id']]);
                            $redeemedStampsBat = (int)$stBat->fetchColumn();
                        }
                    } catch (Throwable $e) {}
                    if ($redeemedStampsBat === 0 && !empty($order['orders_msg']) && preg_match('/E-Stamp:\s*(\d+)\s*แต้ม/u', (string)$order['orders_msg'], $mBt)) {
                        $redeemedStampsBat = (int)$mBt[1];
                    }
                    $otherDiscBat = max(0.0, (float)($order['orders_discount'] ?? 0) - (float)$redeemedStampsBat);
                    ?>
                    <?php if ($redeemedStampsBat > 0): ?>
                        <div class="sum-line-v2 text-danger">
                            <span>ส่วนลด E-Stamp (<?= $redeemedStampsBat ?> แต้ม):</span>
                            <span>-<?= number_format($redeemedStampsBat, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($otherDiscBat > 0): ?>
                        <div class="sum-line-v2 text-danger">
                            <span><?= $L['discount'] ?>:</span>
                            <span>-<?= number_format($otherDiscBat, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="sum-line-v2 grand-total-line-v2">
                        <span><?= $L['grand_total'] ?>:</span>
                        <span><?= number_format($order['orders_grandtotal'], 2) ?></span>
                    </div>
                    <?php 
                        $paid = (float)($order['orders_paid_amount'] ?? 0);
                        $balance = (float)$order['orders_grandtotal'] - $paid;
                    ?>
                    <?php if ($paid > 0): ?>
                        <div class="sum-line-v2 fw-bold" style="color: #27ae60;">
                            <span><?= $L['paid'] ?>:</span>
                            <span><?= number_format($paid, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="sum-line-v2 balance-line-v2">
                        <span><?= $L['balance'] ?>:</span>
                        <span><?= number_format($balance, 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="bill-signatures-v2">
                <div class="sig-item">
                    <div class="sig-space"></div>
                    <div class="sig-name">(...................................................)</div>
                    <div class="sig-label"><?= $L['signature_customer'] ?></div>
                </div>
                <div class="sig-item">
                    <div class="sig-space"></div>
                    <div class="sig-name">(...................................................)</div>
                    <div class="sig-label"><?= $L['signature_authorized'] ?></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="page-footer" style="border-top: 1px solid #eee; padding-top: 1mm; font-size: 8px; color: #999; display: flex; justify-content: center; gap: 4mm;">
                <div>Created by: <?= htmlspecialchars(($order['creator_fname'] ?? '') . ' ' . ($order['creator_lname'] ?? '')) ?></div>
                <div>Updated by: <?= htmlspecialchars(($order['updater_fname'] ?? '') . ' ' . ($order['updater_lname'] ?? '')) ?></div>
                <div>Printed by: <?= htmlspecialchars($current_admin_name) ?></div>
            </div>
        </div>
    </div>
    <?php
    endforeach;
}

// Update Print Status
if (!empty($order_ids)) {
    $db->prepare("UPDATE orders SET orders_print = 1 WHERE orders_id IN (".implode(',', $order_ids).")")->execute();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Print Order Set A5</title>
    <style>
        @page { size: A5 portrait; margin: 0; }
        body { 
            font-family: 'Sarabun', sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f0f2f5; 
            color: #000; 
        }
        
        .print-page { 
            width: 148mm; 
            height: 210mm; 
            margin: 10mm auto; 
            background: #fff; 
            page-break-after: always; 
            box-sizing: border-box; 
            position: relative; 
            overflow: hidden; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
        }
        .page-inner { height: 100%; padding: 6mm; display: flex; flex-direction: column; box-sizing: border-box; }

        /* Helper Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .d-flex { display: flex; }
        .align-items-end { align-items: flex-end; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-start { justify-content: flex-start; }
        .align-items-baseline { align-items: baseline; }
        .align-items-center { align-items: center; }

        /* HEADER */
        .label-header, .bill-header-row { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 2mm; margin-bottom: 5mm; }
        .barcode-box { text-align: right; }
        .barcode-text { font-size: 11px; font-weight: bold; }

        /* LABEL */
        .address-row { display: flex; gap: 3mm; margin-bottom: 4mm; }
        .sender-side { width: 35%; border-right: 1px solid #000; padding-right: 3mm; }
        .receiver-side { width: 65%; position: relative; }
        .addr-title { font-size: 12px; font-weight: bold; text-decoration: underline; margin-bottom: 1mm; }
        .addr-content { font-size: 12px; line-height: 1.3; }
        .receiver-name { font-size: 24px; font-weight: 900; border-bottom: 2px solid #000; display: inline-block; margin-bottom: 2mm; }
        
        /* COD FRAME BOX */
        .cod-frame-box { 
            background: #fff; 
            color: #000; 
            width: 32mm; 
            border: 2px double #000; 
            border-radius: 4px; 
            text-align: center; 
            padding: 1mm 0;
            margin-top: 2mm;
            margin-right: 3mm;
        }
        .cod-t1 { font-size: 32px; font-weight: 900; line-height: 1; border-bottom: 1px solid #000; margin: 0 1mm; padding-bottom: 1mm; }
        .cod-t2 { font-size: 12px; font-weight: normal; padding-top: 1mm; }

        .postcode-label { font-size: 10px; color: #666; margin-bottom: -2px; }
        .receiver-postcode { font-size: 32px; font-weight: 900; letter-spacing: 3px; margin: 0 0 1mm 0; line-height: 1.1; }
        .receiver-mobile { font-size: 20px; font-weight: bold; background: #f0f0f0; padding: 1px 4px; display: inline-block; }
        
        .items-summary { border-top: 2px solid #000; padding: 2mm 0; flex: 1; min-height: 0; overflow: hidden; margin-top: 2mm; }
        .item-compact-row { display: flex; justify-content: space-between; border-bottom: 1px solid #f0f0f0; padding: 0.5mm 0; font-size: 10px; }
        .item-compact-row .qty { font-weight: 900; color: #d35400; min-width: 20px; text-align: right; margin-left: 5px; }

        /* BILL */
        .bill-qr-side { padding: 0 3mm; }
        .doc-title { font-size: 15px; margin: 0; font-weight: 900; color: #000; text-align: right; }
        .doc-meta { font-size: 10px; text-align: right; margin-top: 1px; }
        .bill-address-row { display: flex; gap: 2mm; margin-bottom: 3mm; }
        .addr-box { flex: 1; border: 1px solid #bbb; padding: 1.5mm; border-radius: 4px; }
        .box-title { font-size: 10px; font-weight: bold; text-decoration: underline; margin: 0 0 1mm 0; }
        .box-content { font-size: 10px; line-height: 1.2; }
        .customer-id-badge { font-weight: bold; font-size: 11px; background: #eee; padding: 2px 6px; border-radius: 3px; margin-left: 10px; }
        .bill-items-table { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
        .bill-items-table th { border: 1px solid #bbb; background: #f8f9fa; padding: 1mm; font-size: 10px; }
        .bill-items-table td { border: 1px solid #bbb; padding: 1mm; font-size: 10px; line-height: 1.2; }
        .blank-row td { height: 6mm; }

        /* BILL SUMMARY V2 */
        .bill-summary-row-v2 { display: flex; justify-content: space-between; margin-bottom: 3mm; }
        .summary-left-v2 { flex: 1; margin-right: 5mm; }
        .note-box-v2 { font-size: 9px; padding: 1mm; border: 1px dashed #bbb; background: #fafafa; min-height: 10mm; margin-top: 2mm; }
        .summary-right-v2 { width: 62mm; }
        .sum-line-v2 { display: flex; justify-content: space-between; font-size: 10px; padding: 0.2mm 0; }
        .grand-total-line-v2 { font-weight: bold; font-size: 12px; background: #d4edda; padding: 1mm; border-radius: 3px; margin: 0.5mm 0; }
        .balance-line-v2 { color: #dc3545; font-weight: bold; border-top: 1px solid #ddd; margin-top: 0.5mm; }
        .bill-signatures-v2 { display: flex; justify-content: space-between; margin-top: 2mm; }
        .sig-item { flex: 1; text-align: center; }
        .sig-space { height: 8mm; }
        .sig-name { font-size: 10px; margin-bottom: 1mm; }
        .sig-label { font-size: 9px; color: #666; }

        /* SHARED FOOTER */
        .page-footer { margin-top: auto; border-top: 1px solid #000; padding-top: 1.5mm; display: flex; justify-content: space-between; font-size: 9px; color: #666; }

        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .print-page { 
                border: none; 
                margin: 0; 
                box-shadow: none;
                width: 148mm;
                height: 210mm;
            }
        }
        .btn-print { position: fixed; bottom: 20px; right: 20px; padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; z-index: 1000; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">สั่งพิมพ์ชุดเอกสาร (Print A5 Set)</button>

    <?php foreach ($order_ids as $id): 
        $stmt = $db->prepare("SELECT o.*, u.user_name, u.user_lastname, u.user_id,
                              creator.user_name as creator_fname, creator.user_lastname as creator_lname,
                              updater.user_name as updater_fname, updater.user_lastname as updater_lname
                              FROM orders o 
                              LEFT JOIN user u ON o.user_id = u.user_id 
                              LEFT JOIN user creator ON o.save_id = creator.user_id
                              LEFT JOIN user updater ON o.update_id = updater.user_id
                              WHERE o.orders_id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt_count = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND orders_id <= ?");
        $stmt_count->execute([$order['user_id'], $id]);
        $order_count = $stmt_count->fetchColumn();

        $stmt_items = $db->prepare("SELECT d.*, p.product_name, p.product_code FROM ordetail d LEFT JOIN product p ON d.product_id = p.product_id WHERE d.orders_id = ?");
        $stmt_items->execute([$id]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        $total_qty = array_sum(array_column($items, 'ordetail_qty'));

        // Fetch Addresses
        $stmt_addr = $db->prepare("SELECT a.*, p.name_th as province_name FROM addr a LEFT JOIN provinces p ON a.provinces_id = p.provinces_id WHERE a.addr_id = ?");
        
        // 1. Shipping Address
        $stmt_addr->execute([$order['addr_id']]);
        $shipping_address = $stmt_addr->fetch(PDO::FETCH_ASSOC);
        
        // 2. Billing Address
        $billing_addr_id = null;
        if (!empty($order['billing_addr_id'])) {
            $billing_addr_id = $order['billing_addr_id'];
        } else {
            $stmt_billing = $db->prepare("SELECT addr_id FROM addr WHERE user_id = ? AND addr_type = 3 AND addr_del = 0 ORDER BY addr_id DESC LIMIT 1");
            $stmt_billing->execute([$order['user_id']]);
            $billing_addr = $stmt_billing->fetch(PDO::FETCH_ASSOC);
            if ($billing_addr) {
                $billing_addr_id = $billing_addr['addr_id'];
            }
        }
        
        if ($billing_addr_id && $billing_addr_id != $order['addr_id']) {
            $stmt_addr->execute([$billing_addr_id]);
            $billing_address = $stmt_addr->fetch(PDO::FETCH_ASSOC);
        } else {
            $billing_address = $shipping_address;
        }
        
        // Hide "ต่างประเทศ (Foreign Country)" from province names
        if (isset($shipping_address['province_name']) && strpos($shipping_address['province_name'], 'ต่างประเทศ') !== false) {
            $shipping_address['is_foreign'] = true;
            $shipping_address['province_name'] = '';
        }
        if (isset($billing_address['province_name']) && strpos($billing_address['province_name'], 'ต่างประเทศ') !== false) {
            $billing_address['is_foreign'] = true;
            $billing_address['province_name'] = '';
        }
    ?>
        <?php 
            $boxCount = isset($order['orders_bill']) ? max(1, (int)$order['orders_bill']) : 1;
            for ($b = 1; $b <= $boxCount; $b++):
                renderLabel($order, $items, $shipping_address, $order_count, $current_admin_name, $shop, $sender_th, $b, $boxCount); 
            endfor;
        ?>
        <?php renderBill($order, $items, $billing_address, $order_count, $current_admin_name, $shop, $sender_th); ?>
    <?php endforeach; ?>
</body>
</html>
