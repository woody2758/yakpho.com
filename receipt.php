<?php
require_once __DIR__ . "/admin/includes/config.php";

/**
 * Format Thai Phone Number
 */
function formatPhone($phone, $mask = true) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10) {
        $formatted = substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    } elseif (strlen($phone) == 9) {
        $formatted = substr($phone, 0, 2) . '-' . substr($phone, 2, 3) . '-' . substr($phone, 5);
    } else {
        $formatted = $phone;
    }
    
    if ($mask && strlen($phone) >= 9) {
        $parts = explode('-', $formatted);
        if (count($parts) == 3) {
            return $parts[0] . '-' . $parts[1] . '-****';
        }
    }
    return $formatted;
}

$token = $_GET['v'] ?? '';
$mode = 'receipt'; // Always use receipt mode for public link
$useLang = $_GET['lang'] ?? 'th';

if (empty($token)) {
    die("<h1 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Invalid Receipt Link</h1>");
}

// Fetch Order Info
$stmt = $db->prepare("SELECT o.*, u.user_name, u.user_lastname, u.user_email, u.user_mobile, u.user_id,
                             c.user_name as creator_fname, c.user_lastname as creator_lname,
                             up.user_name as updater_fname, up.user_lastname as updater_lname
                      FROM orders o 
                      LEFT JOIN user u ON o.user_id = u.user_id 
                      LEFT JOIN user c ON o.save_id = c.user_id
                      LEFT JOIN user up ON o.update_id = up.user_id
                      WHERE o.track_token = ? OR o.orders_no = ?");
$stmt->execute([$token, $token]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("<h1 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Receipt not found</h1>");
}

$id = $order['orders_id'];

// Fetch Items with Translations
$stmt_items = $db->prepare("SELECT d.*, 
                                  COALESCE(pt.product_name, p.product_name) as display_name,
                                  p.product_code 
                           FROM ordetail d 
                           LEFT JOIN product p ON d.product_id = p.product_id 
                           LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
                           WHERE d.orders_id = ?");
$stmt_items->execute([$useLang, $id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
$total_qty = array_sum(array_column($items, 'ordetail_qty'));

// Fetch Addresses
$stmt_addr = $db->prepare("SELECT a.*, p.name_th as province_name 
                           FROM addr a 
                           LEFT JOIN provinces p ON a.provinces_id = p.provinces_id 
                           WHERE a.addr_id = ?");

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

// For A4 modes, we use the billing address. For label mode, we will use both.
$address = ($mode != 'label') ? $billing_address : $shipping_address;

// Hide \"ต่างประเทศ (Foreign Country)\" from province names
if (isset($address['province_name']) && strpos($address['province_name'], 'ต่างประเทศ') !== false) {
    $address['is_foreign'] = true;
    $address['province_name'] = '';
}
if (isset($billing_address['province_name']) && strpos($billing_address['province_name'], 'ต่างประเทศ') !== false) {
    $billing_address['is_foreign'] = true;
    $billing_address['province_name'] = '';
}
if (isset($shipping_address['province_name']) && strpos($shipping_address['province_name'], 'ต่างประเทศ') !== false) {
    $shipping_address['is_foreign'] = true;
    $shipping_address['province_name'] = '';
}

// Order Count for customer ID tag
$stmt_count = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND orders_id <= ?");
$stmt_count->execute([$order['user_id'], $id]);
$order_count = $stmt_count->fetchColumn();

// Get main shop info
$stmt_info = $db->query("SELECT * FROM shop_info LIMIT 1");
$shop = $stmt_info->fetch(PDO::FETCH_ASSOC) ?: [];

// Fetch Translations for Shop
$stmt_trans = $db->prepare("SELECT * FROM shop_info_translations WHERE shop_id = 1 AND lang_code = ? LIMIT 1");
$stmt_trans->execute([$useLang]);
$current_sender = $stmt_trans->fetch(PDO::FETCH_ASSOC);
if (!$current_sender && $useLang != 'th') {
    $stmt_trans->execute(['th']);
    $current_sender = $stmt_trans->fetch(PDO::FETCH_ASSOC);
}

// Sender Names for A4 documents
$sender_name = !empty($current_sender['official_name']) ? $current_sender['official_name'] : ($current_sender['shop_name'] ?? ($shop['shop_name'] ?? 'Esther Aroma'));
$sender_address = !empty($current_sender['official_address']) ? $current_sender['official_address'] : ($current_sender['shop_address'] ?? ($shop['shop_address'] ?? ''));
$sender_phone = $shop['shop_phone'] ?? ''; 
$sender_tax_id = $shop['shop_tax_id'] ?? '';

// Multi-language Labels
$labels = [
    'th' => [
        'no' => 'เลขที่', 'date' => 'วันที่', 'sender' => 'ผู้ส่ง (Sender)', 'receiver' => 'ผู้รับ (Receiver)',
        'customer' => 'ลูกค้า (Customer)', 'tel' => 'โทร', 'tax_id' => 'เลขประจำตัวผู้เสียภาษี',
        'branch' => 'สาขา', 'tracking' => 'เลขพัสดุ', 'item' => 'รายการสินค้า', 'code' => 'รหัสสินค้า', 'qty' => 'จำนวน', 'total' => 'รวม',
        'subtotal' => 'ราคารวมสินค้า', 'discount' => 'ส่วนลด', 'shipping' => 'ค่าจัดส่ง', 'cod_fee' => 'ค่าธรรมเนียม COD (3%)', 'postal_code' => 'รหัสไปรษณีย์',
        'grand_total' => 'ยอดสุทธิ (Grand Total)', 'paid' => 'มัดจำ/ชำระแล้ว', 'balance' => 'ยอดคงเหลือ',
        'order_note' => 'หมายเหตุ', 'signature_customer' => 'ผู้รับสินค้า (Receiver Signature)',
        'signature_authorized' => 'ผู้จัดการ (Authorized Signature)', 'footer_thanks' => 'ขอบคุณที่อุดหนุนผลิตภัณฑ์ในเครือ Esther Aroma Group',
        'created_by' => 'Created by', 'updated_by' => 'Updated by', 'printed_by' => 'Printed by',
        'vat_base' => 'ยอดรวม (ก่อนภาษี)', 'vat_7' => 'ภาษีมูลค่าเพิ่ม (VAT 7%)',
        'total_items' => 'รวมจำนวนชิ้นทั้งหมด:'
    ],
    'en' => [
        'no' => 'No.', 'date' => 'Date', 'sender' => 'Sender', 'receiver' => 'Receiver',
        'customer' => 'Customer', 'tel' => 'Tel', 'tax_id' => 'Tax ID',
        'branch' => 'Branch', 'tracking' => 'Tracking No.', 'item' => 'Description', 'code' => 'Code', 'qty' => 'Qty', 'total' => 'Amount',
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

// Document Titles
$titles = [
    'th' => [
        'receipt' => 'ใบเสร็จรับเงิน (Regular Receipt)'
    ],
    'en' => [
        'receipt' => 'Regular Receipt'
    ]
];
$docTitle = $titles[$useLang][$mode] ?? $titles['th'][$mode];
$docCode = ['receipt'=>'RE'][$mode];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $docTitle ?> #<?= $order['orders_no'] ?></title>
    <style>
        @page { 
            size: A4; 
            margin: 0; 
        }
        body { 
            font-family: 'Sarabun', sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #eef2f5; 
            color: #000; 
        }
        
        /* A4 STANDARD STYLES */
        .a4-container { width: 100%; max-width: 210mm; margin: 20px auto; background: #fff; padding: 10mm; box-sizing: border-box; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 8px; }
        .a4-header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 5px; margin-bottom: 10px; }
        .a4-items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .a4-items-table th { background: #f8f9fa; border: 1px solid #bbb; padding: 2mm; font-size: 12px; }
        .a4-items-table td { border: 1px solid #bbb; padding: 2mm; font-size: 12px; }

        /* A4 Summary Rows */
        .total-row { display: flex; justify-content: space-between; font-size: 13px; padding: 1mm 0; color: #000; }
        .total-row span:last-child { font-weight: bold; min-width: 30mm; text-align: right; }
        .total-row.grand-total { font-size: 16px; border-top: 2px solid #000; margin-top: 1mm; padding-top: 1mm; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }

        .addr-box-a5 { flex: 1; border: 1px solid #bbb; padding: 3mm; border-radius: 4px; }
        .addr-title { font-size: 14px; font-weight: bold; text-decoration: underline; margin-bottom: 2mm; }
        .addr-content { font-size: 12px; line-height: 1.4; }

        @media print {
            .no-print { display: none !important; }
            body { background: white; margin: 0; padding: 0; }
            .a4-container { width: 100%; max-width: 100%; padding: 0; margin: 0; box-shadow: none; border-radius: 0; }
        }
        .btn-print { position: fixed; bottom: 20px; right: 20px; padding: 12px 24px; background: #3498db; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; z-index: 1000; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: 0.2s; }
        .btn-print:hover { background: #2980b9; transform: translateY(-2px); }
        .lang-switch { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; z-index: 1000; }
        .lang-switch a { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.1); font-size: 14px; }
    </style>
</head>
<body>
    <div class="lang-switch no-print">
        <a href="?lang=th" style="background: <?= $useLang == 'th' ? '#3498db' : '#fff' ?>; color: <?= $useLang == 'th' ? 'white' : '#666' ?>;">TH</a>
        <a href="?lang=en" style="background: <?= $useLang == 'en' ? '#3498db' : '#fff' ?>; color: <?= $useLang == 'en' ? 'white' : '#666' ?>;">EN</a>
    </div>

    <button onclick="window.print()" class="btn-print no-print">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: text-bottom; margin-right: 5px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        พิมพ์ใบเสร็จ / Print
    </button>

    <div class="a4-container">
        <div class="a4-header">
            <div class="logo"><?php if(!empty($shop['shop_logo'])): ?><img src="<?= ROOT_URL ?>/uploads/shop/<?= $shop['shop_logo'] ?>" style="max-height: 20mm;"><?php endif; ?></div>
            <div class="text-right">
                <h2 style="margin:0; font-size: 18px;"><?= $docTitle ?></h2>
                <div style="font-size: 14px; font-weight: bold; margin-top: 2px;"><?= $L['no'] ?>: <?= $docCode ?><?= $order['orders_no'] ?></div>
                <div style="font-size: 11px; color: #666;"><?= $L['date'] ?>: <?= date('d/m/Y H:i', strtotime($order['orders_date'])) ?></div>
            </div>
        </div>

        <div class="d-flex" style="gap: 5mm; margin-bottom: 5mm;">
            <div class="addr-box-a5" style="flex: 1;">
                <div class="addr-title"><?= $L['sender'] ?></div>
                <div class="addr-content"><strong><?= $sender_name ?></strong><br><?= nl2br(htmlspecialchars($sender_address)) ?><br><?= $L['tel'] ?>: <?= formatPhone($shop['shop_phone'], false) ?><?php if(!empty($sender_tax_id)): ?> | Tax: <?= $sender_tax_id ?><?php endif; ?></div>
            </div>
            <div class="addr-box-a5" style="flex: 1;">
                <div class="addr-title"><?= $L['customer'] ?></div>
                <div class="addr-content"><strong><?= htmlspecialchars($address['addr_name'] ?? ($order['user_name'] . ' ' . $order['user_lastname'])) ?></strong><br><?= htmlspecialchars(($address['addr_detail'] ?? '') . ' ' . ($address['addr_detail2'] ?? '')) ?><br><?= htmlspecialchars(($address['province_name'] ?? '') . ' ' . (empty($address['is_foreign']) ? ($address['addr_postcode'] ?? '') : '')) ?><br><?php if(empty($address['is_foreign'])): ?><?= $L['tel'] ?>: <?= formatPhone($address['addr_mobile'] ?? '', true) ?><?php endif; ?></div>
            </div>
        </div>

        <table class="a4-items-table">
            <thead><tr><th style="width: 40px;" class="text-center">#</th><th style="width: 70px;" class="text-center"><?= $L['code'] ?></th><th><?= $L['item'] ?></th><th style="width: 80px;" class="text-center"><?= $L['qty'] ?></th><th style="width: 120px;" class="text-right"><?= $L['total'] ?></th></tr></thead>
            <tbody>
                <?php foreach ($items as $idx => $item): $rowTotal = $item['product_price'] * $item['ordetail_qty']; ?>
                <tr><td class="text-center"><?= $idx + 1 ?></td><td class="text-center"><?= htmlspecialchars($item['product_code']) ?></td><td><?= htmlspecialchars($item['display_name']) ?><?php if(!empty($item['ordetail_memo'])): ?><br><small style="color: #0dcaf0;">* <?= htmlspecialchars($item['ordetail_memo']) ?></small><?php endif; ?></td><td class="text-center"><?= number_format($item['ordetail_qty']) ?></td><td class="text-right"><?= number_format($rowTotal, 2) ?></td></tr>
                <?php endforeach; ?>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td colspan="3" class="text-right" style="padding-right: 10px;"><?= $L['total_items'] ?></td>
                    <td class="text-center"><?= $total_qty ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="d-flex justify-content-between">
            <div style="flex: 1; margin-right: 10mm;"><?php if(!empty($order['orders_msg'])): ?><div style="padding: 3mm; border: 1px dashed #bbb; background: #fafafa; font-size: 11px;"><strong><?= $L['order_note'] ?>:</strong><br><?= nl2br(htmlspecialchars($order['orders_msg'])) ?></div><?php endif; ?></div>
            <div style="width: 80mm;">
                <div class="total-row"><span><?= $L['subtotal'] ?>:</span><span><?= number_format($order['orders_total'], 2) ?></span></div>
                <div class="total-row"><span><?= $L['shipping'] ?>:</span><span><?= number_format($order['orders_delicost'], 2) ?></span></div>
                <?php if ($order['orders_discount'] > 0): ?><div class="total-row" style="color: #dc3545;"><span><?= $L['discount'] ?>:</span><span>-<?= number_format($order['orders_discount'], 2) ?></span></div><?php endif; ?>
                <?php if ($order['orders_cod'] > 0): ?><div class="total-row fw-bold"><span><?= $L['cod_fee'] ?>:</span><span><?= number_format($order['orders_cod'], 2) ?></span></div><?php endif; ?>
                <div class="total-row grand-total"><span><?= $L['grand_total'] ?>:</span><span><?= number_format($order['orders_grandtotal'], 2) ?></span></div>
                <?php if ($order['orders_paid_amount'] > 0): ?><div class="total-row fw-bold" style="color: #000;"><span><?= $L['paid'] ?>:</span><span><?= number_format($order['orders_paid_amount'], 2) ?></span></div><?php endif; ?>
                <div class="total-row" style="color: #000; font-weight: bold; border-top: 1px solid #000; padding-top: 2px;"><span><?= $L['balance'] ?>:</span><span><?= number_format($order['orders_grandtotal'] - $order['orders_paid_amount'], 2) ?></span></div>
            </div>
        </div>

        <div class="d-flex justify-content-between" style="margin-top: 15mm; text-align: center;">
            <div style="flex: 1;"><div style="height: 15mm; border-bottom: 1px solid #333; margin: 0 10mm;"></div><div style="margin-top: 2mm; font-size: 11px;"><?= $L['signature_customer'] ?></div></div>
            <div style="flex: 1;"><div style="height: 15mm; border-bottom: 1px solid #333; margin: 0 10mm;"></div><div style="margin-top: 2mm; font-size: 11px;"><?= $L['signature_authorized'] ?></div></div>
        </div>
        
        <div style="margin-top: 10mm; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #eee; padding-top: 3mm;">
            <div style="margin-top: 2mm;"><?= $L['footer_thanks'] ?></div>
        </div>
    </div>
</body>
</html>
