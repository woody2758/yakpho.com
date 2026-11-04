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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = $_GET['mode'] ?? 'full';
$useLang = $_GET['lang'] ?? 'th'; // ดึงค่าภาษาจาก URL ถ้าไม่มีให้เป็นไทย

// Get currency and exchange rate from URL
$currency = $_GET['currency'] ?? 'THB';
$currencyRate = isset($_GET['rate']) ? (float)$_GET['rate'] : 1.0;
if ($currencyRate <= 0) $currencyRate = 1.0;
$currSym = ($currency === 'THB') ? '฿' : (($currency === 'USD') ? '$' : (($currency === 'EUR') ? '€' : $currency . ' '));

if ($id <= 0) {
    die("Invalid Order ID");
}

// Fetch Order Info with Creator and Updater
$stmt = $db->prepare("SELECT o.*, u.user_name, u.user_lastname, u.user_email, u.user_mobile, u.user_id,
                             c.user_name as creator_fname, c.user_lastname as creator_lname,
                             up.user_name as updater_fname, up.user_lastname as updater_lname
                      FROM orders o 
                      LEFT JOIN user u ON o.user_id = u.user_id 
                      LEFT JOIN user c ON o.save_id = c.user_id
                      LEFT JOIN user up ON o.update_id = up.user_id
                      WHERE o.orders_id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found");
}

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

// Hide "ต่างประเทศ (Foreign Country)" from province names
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

// Get language from URL
$useLang = isset($_GET['lang']) ? $_GET['lang'] : 'th';

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
        'full' => 'ใบเสร็จรับเงิน/ใบแจ้งหนี้ (Invoice)', 'quotation' => 'ใบเสนอราคา (Quotation)',
        'receipt' => 'ใบเสร็จรับเงิน (Regular Receipt)', 'tax_invoice' => 'ใบเสร็จรับเงิน/ใบกำกับภาษี (Tax Invoice)',
        'label' => 'ใบปะหน้าพัสดุ (Shipping Label)'
    ],
    'en' => [
        'full' => 'Receipt / Invoice', 'quotation' => 'Quotation',
        'receipt' => 'Regular Receipt', 'tax_invoice' => 'Receipt / Tax Invoice',
        'label' => 'Shipping Label'
    ]
];
$docTitle = $titles[$useLang][$mode] ?? $titles['th'][$mode];
$docCode = ['full'=>'INV','quotation'=>'QT','receipt'=>'RE','tax_invoice'=>'TIV','label'=>'LBL'][$mode];

// Get current admin name
$current_admin_id = $_SESSION['admin_id'] ?? 0;
$stmt_admin = $db->prepare("SELECT user_name, user_lastname FROM user WHERE user_id = ?");
$stmt_admin->execute([$current_admin_id]);
$admin_data = $stmt_admin->fetch(PDO::FETCH_ASSOC);
$current_admin_name = $admin_data ? ($admin_data['user_name'] . ' ' . $admin_data['user_lastname']) : 'Admin';

// Update Print Status
$db->prepare("UPDATE orders SET orders_print = 1 WHERE orders_id = ?")->execute([$id]);

// Log Activity
logStaffAction($db, 'PRINT', 'orders', $id, "Generated {$mode} document in " . strtoupper($useLang) . " language");

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?= $docTitle ?> #<?= $order['orders_no'] ?></title>
    <style>
        @page { 
            size: <?= $mode == 'label' ? 'A5 portrait' : 'A4' ?>; 
            margin: 0; 
        }
        body { 
            font-family: 'Sarabun', sans-serif; 
            margin: 0; 
            padding: 0; 
            background: <?= $mode == 'label' ? '#f0f2f5' : '#fff' ?>; 
            color: #000; 
        }
        
        /* A5 DUO SET STYLES (From print_batch) */
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
        .label-header, .bill-header-row { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 2mm; margin-bottom: 5mm; }
        .barcode-box { text-align: right; }
        .barcode-text { font-size: 11px; font-weight: bold; }
        .label-body { flex: 1; }
        .address-row { display: flex; gap: 3mm; margin-bottom: 4mm; }
        .sender-side { width: 35%; border-right: 1px solid #000; padding-right: 3mm; }
        .receiver-side { width: 65%; position: relative; }
        .addr-title { font-size: 12px; font-weight: bold; text-decoration: underline; margin-bottom: 1mm; }
        .addr-content { font-size: 12px; line-height: 1.3; }
        .receiver-name { font-size: 24px; font-weight: 900; border-bottom: 2px solid #000; display: inline-block; margin-bottom: 2mm; }
        
        /* COD FRAME BOX (Security Focused) */
        .cod-frame-box { background: #fff; color: #000; width: 32mm; border: 2px double #000; border-radius: 4px; text-align: center; padding: 1mm 0; margin-top: 2mm; margin-right: 3mm; }
        .cod-t1 { font-size: 32px; font-weight: 900; line-height: 1; border-bottom: 1px solid #000; margin: 0 1mm; padding-bottom: 1mm; }
        .cod-t2 { font-size: 12px; font-weight: normal; padding-top: 1mm; }

        .postcode-label { font-size: 10px; color: #666; margin-bottom: -2px; }
        .receiver-postcode { font-size: 32px; font-weight: 900; letter-spacing: 3px; margin: 0 0 1mm 0; line-height: 1.1; }
        .receiver-mobile { font-size: 20px; font-weight: bold; background: #f0f0f0; padding: 1px 4px; display: inline-block; }
        .items-summary { border-top: 2px solid #000; padding: 2mm 0; flex: 1; min-height: 0; overflow: hidden; margin-top: 2mm; }
        .item-compact-row { display: flex; justify-content: space-between; border-bottom: 1px solid #f0f0f0; padding: 0.5mm 0; font-size: 10px; }
        .item-compact-row .qty { font-weight: 900; color: #d35400; min-width: 20px; text-align: right; margin-left: 5px; }

        /* BILL A5 STYLES */
        .bill-qr-side { padding: 0 3mm; }
        .doc-title-a5 { font-size: 15px; margin: 0; font-weight: 900; color: #000; text-align: right; }
        .doc-meta-a5 { font-size: 10px; text-align: right; margin-top: 1px; }
        .bill-address-row { display: flex; gap: 2mm; margin-bottom: 3mm; }
        .addr-box-a5 { flex: 1; border: 1px solid #bbb; padding: 1.5mm; border-radius: 4px; }
        .customer-id-badge { font-weight: bold; font-size: 11px; background: #eee; padding: 2px 6px; border-radius: 3px; margin-left: 10px; }
        .bill-items-table { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
        .bill-items-table th { border: 1px solid #bbb; background: #f8f9fa; padding: 1mm; font-size: 10px; }
        .bill-items-table td { border: 1px solid #bbb; padding: 1mm; font-size: 10px; line-height: 1.2; }
        .bill-summary-row-v2 { display: flex; justify-content: space-between; margin-bottom: 3mm; }
        .summary-left-v2 { flex: 1; margin-right: 5mm; }
        .summary-right-v2 { width: 62mm; }
        .sum-line-v2 { display: flex; justify-content: space-between; font-size: 10px; padding: 0.2mm 0; }
        .grand-total-line-v2 { font-weight: bold; font-size: 12px; background: #d4edda; padding: 1mm; border-radius: 3px; margin: 0.5mm 0; }
        .balance-line-v2 { color: #dc3545; font-weight: bold; border-top: 1px solid #ddd; margin-top: 0.5mm; }

        /* A4 STANDARD STYLES (Keep existing) */
        .a4-container { width: 210mm; margin: 0 auto; background: #fff; padding: 10mm; box-sizing: border-box; }
        .a4-header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 5px; margin-bottom: 10px; }
        .a4-items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .a4-items-table th { background: #f8f9fa; border: 1px solid #bbb; padding: 2mm; font-size: 12px; }
        .a4-items-table td { border: 1px solid #bbb; padding: 2mm; font-size: 12px; }

        /* A4 Summary Rows */
        .total-row { display: flex; justify-content: space-between; font-size: 13px; padding: 1mm 0; color: #000; }
        .total-row span:last-child { font-weight: bold; min-width: 30mm; text-align: right; }
        .total-row.grand-total { font-size: 16px; border-top: 2px solid #000; margin-top: 1mm; padding-top: 1mm; }

        /* SHARED FOOTER */
        .page-footer { margin-top: auto; border-top: 1px solid #000; padding-top: 1.5mm; display: flex; justify-content: space-between; font-size: 9px; color: #666; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        .justify-content-start { justify-content: flex-start; }
        .align-items-baseline { align-items: baseline; }
        .align-items-center { align-items: center; }
        .align-items-end { align-items: flex-end; }

        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .print-page { border: none; margin: 0; box-shadow: none; }
            .a4-container { width: 100%; padding: 0; margin: 0; }
        }
        .btn-print { position: fixed; bottom: 20px; right: 20px; padding: 10px 20px; background: #e67e22; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; z-index: 1000; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Top Bar Toolbar -->
    <div class="no-print" style="position: fixed; top: 0; left: 0; width: 100%; height: 60px; background: #ffffff; color: #333; display: flex; align-items: center; justify-content: center; gap: 15px; z-index: 9999; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-bottom: 2px solid #3498db; font-family: 'Sarabun', sans-serif;">
        <div style="display: flex; align-items: center; gap: 10px; border-right: 2px solid #eee; padding-right: 20px;">
            <strong style="color: #2c3e50;">จัดการพิมพ์:</strong>
        </div>
        <select onchange="location.href='?id=<?= $id ?>&lang=<?= $useLang ?>&mode=' + this.value" style="padding: 8px 15px; border-radius: 8px; border: 2px solid #3498db; font-weight: bold; cursor: pointer; background: white; min-width: 250px;">
            <option value="full" <?= $mode == 'full' ? 'selected' : '' ?>>A4 - ใบแจ้งหนี้ (Invoice)</option>
            <option value="tax_invoice" <?= $mode == 'tax_invoice' ? 'selected' : '' ?>>A4 - ใบกำกับภาษี (Tax Invoice)</option>
            <option value="receipt" <?= $mode == 'receipt' ? 'selected' : '' ?>>A4 - ใบเสร็จ (Regular Receipt)</option>
            <option value="quotation" <?= $mode == 'quotation' ? 'selected' : '' ?>>A4 - ใบเสนอราคา (Quotation)</option>
            <option value="label" <?= $mode == 'label' ? 'selected' : '' ?>>A5 - ใบปะหน้า + ใบเสร็จ (Duo Set)</option>
        </select>
        <div style="display: flex; gap: 10px;">
            <a href="?id=<?= $id ?>&mode=<?= $mode ?>&lang=th&currency=<?= $currency ?>&rate=<?= $currencyRate ?>" style="padding: 6px 12px; border-radius: 6px; background: <?= $useLang == 'th' ? '#3498db' : '#f8f9fa' ?>; color: <?= $useLang == 'th' ? 'white' : '#666' ?>; text-decoration: none; font-weight: bold;">TH</a>
            <a href="?id=<?= $id ?>&mode=<?= $mode ?>&lang=en&currency=<?= $currency ?>&rate=<?= $currencyRate ?>" style="padding: 6px 12px; border-radius: 6px; background: <?= $useLang == 'en' ? '#3498db' : '#f8f9fa' ?>; color: <?= $useLang == 'en' ? 'white' : '#666' ?>; text-decoration: none; font-weight: bold;">EN</a>
        </div>
        
        <div style="display: flex; align-items: center; gap: 5px; margin-left: 10px; border-left: 2px solid #eee; padding-left: 15px;">
            <select id="currency_select" onchange="updateCurrencyUI()" style="padding: 6px; border-radius: 6px; border: 1px solid #ccc; font-weight: bold;">
                <option value="THB" <?= $currency == 'THB' ? 'selected' : '' ?>>THB (฿)</option>
                <option value="USD" <?= $currency == 'USD' ? 'selected' : '' ?>>USD ($)</option>
                <option value="EUR" <?= $currency == 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
            </select>
            <input type="number" id="currency_rate" value="<?= $currencyRate ?>" step="0.01" style="width: 80px; padding: 6px; border-radius: 6px; border: 1px solid #ccc; <?= $currency == 'THB' ? 'display: none;' : '' ?>" placeholder="เรทเงิน">
            <button onclick="applyCurrency()" class="btn-print no-print" style="padding: 6px 12px; font-size: 13px; margin-left: 5px; background: #2ecc71; <?= $currency == 'THB' ? 'display: none;' : '' ?>" id="btn_apply_rate">แปลงค่าเงิน</button>
        </div>

        <script>
        function updateCurrencyUI() {
            var curr = document.getElementById('currency_select').value;
            if (curr === 'THB') {
                document.getElementById('currency_rate').style.display = 'none';
                document.getElementById('btn_apply_rate').style.display = 'none';
                applyCurrency(); // Auto apply if switching back to THB
            } else {
                document.getElementById('currency_rate').style.display = 'block';
                document.getElementById('btn_apply_rate').style.display = 'block';
            }
        }
        function applyCurrency() {
            var curr = document.getElementById('currency_select').value;
            var rate = document.getElementById('currency_rate').value;
            if (curr === 'THB') rate = 1;
            location.href = '?id=<?= $id ?>&mode=<?= $mode ?>&lang=<?= $useLang ?>&currency=' + curr + '&rate=' + rate;
        }
        </script>

        <button onclick="window.print()" class="btn-print no-print" style="position: static; margin-left: auto;">
            พิมพ์ (Print)
        </button>
    </div>
    <div class="no-print" style="height: 80px;"></div>

    <?php if ($mode == 'label'): ?>
        <!-- A5 DUO SET (LABEL + BILL) -->
        <?php 
            $boxCount = isset($order['orders_bill']) ? max(1, (int)$order['orders_bill']) : 1;
            $labelChunks = array_chunk($items, 20) ?: [[]];
            for ($b = 1; $b <= $boxCount; $b++): 
                foreach ($labelChunks as $chunkIndex => $chunk):
                    $isFirstLabelPage = ($chunkIndex == 0);
                    $labelPageText = count($labelChunks) > 1 ? " (หน้า " . ($chunkIndex + 1) . "/" . count($labelChunks) . ")" : "";
        ?>
        <!-- 1. LABEL PAGE -->
        <div class="print-page label-page">
            <div class="page-inner">
                <div class="label-header">
                    <div class="label-logo">
                        <?php if (!empty($shop['shop_logo'])): ?>
                            <img src="<?= ROOT_URL ?>/uploads/shop/<?= $shop['shop_logo'] ?>" style="max-height: 45px;">
                        <?php endif; ?>
                    </div>
                    <div class="label-meta">
                        <div class="barcode-box">
                            <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=EST<?= $order['orders_no'] ?>&height=10&scale=2" style="height: 30px;">
                            <div class="barcode-text">#<?= $order['orders_no'] ?><?= $labelPageText ?></div>
                            <div class="label-date"><?= $L['date'] ?>: <?= date('d/m/Y', strtotime($order['orders_date'])) ?></div>
                            <?php if ($boxCount > 1): ?>
                                <div style="font-weight: bold; font-size: 14px; margin-top: 2px;">กล่องที่ <?= $b ?>/<?= $boxCount ?></div>
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
                                <strong><?= $current_sender['shop_name'] ?? $shop['shop_name'] ?></strong><br>
                                <span style="font-size: 10px;"><?= htmlspecialchars($current_sender['shop_address'] ?? $shop['shop_address']) ?></span><br>
                                <strong>โทร:</strong> <?= formatPhone($shop['shop_phone']) ?>
                            </div>
                            <div class="qr-container" style="margin-top: 10px;">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=EST<?= $order['orders_no'] ?>" style="width: 65px;">
                                <div style="font-size: 9px; font-weight: bold; margin-top: 2px;">SCAN ORDER</div>
                            </div>
                        </div>
                        <div class="receiver-side">
                            <div class="addr-title"><?= $L['receiver'] ?> &nbsp;C<?= $order['user_id'] ?>-<?= $order_count ?></div>
                            <div class="addr-content receiver-main">
                                <div class="receiver-name"><?= htmlspecialchars($address['addr_name'] ?? ($order['user_name'] . ' ' . $order['user_lastname'])) ?></div>
                                <div class="receiver-address">
                                    <?= htmlspecialchars($address['addr_detail'] ?? '') ?> <?= htmlspecialchars($address['addr_detail2'] ?? '') ?> 
                                    <?php if ($useLang !== 'en' && empty($address['is_foreign'])): ?>
                                        <strong><?= htmlspecialchars($address['province_name'] ?? '') ?></strong>
                                    <?php endif; ?>
                                </div>
                                <div class="receiver-footer-row d-flex align-items-end <?= ($order['paycat_id'] == 2) ? 'justify-content-between' : 'justify-content-start' ?>">
                                    <?php if ($order['paycat_id'] == 2): ?>
                                        <div class="cod-frame-box">
                                            <div class="cod-t1">COD</div>
                                            <div class="cod-t2"><?= $currSym ?><?= number_format($order['orders_grandtotal'] / $currencyRate) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="receiver-meta-group" style="<?= ($order['paycat_id'] == 2) ? 'text-align: right;' : 'text-align: left;' ?>">
                                        <?php if ($useLang !== 'en' && empty($address['is_foreign'])): ?>
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
                                        <?= htmlspecialchars($item['display_name']) ?>
                                        <?php if(!empty($item['ordetail_memo'])): ?>
                                            <span style="font-size: 9px; color: #0dcaf0;">* <?= htmlspecialchars($item['ordetail_memo']) ?></span>
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
                    <div><?= $L['created_by'] ?>: <?= htmlspecialchars($order['creator_fname'] . ' ' . $order['creator_lname']) ?></div>
                    <div><?= $L['printed_by'] ?>: <?= htmlspecialchars($current_admin_name) ?></div>
                </div>
            </div>
        </div>
        <?php 
                endforeach;
            endfor; 
        ?>

        <!-- 2. BILL PAGE -->
        <?php
            $billChunks = array_chunk($items, 20) ?: [[]];
            foreach ($billChunks as $chunkIndex => $chunk):
                $isLastBillPage = ($chunkIndex === count($billChunks) - 1);
                $billPageText = count($billChunks) > 1 ? " (หน้า " . ($chunkIndex + 1) . "/" . count($billChunks) . ")" : "";
        ?>
        <div class="print-page bill-page">
            <div class="page-inner">
                <div class="bill-header-row">
                    <div class="bill-logo-side"><?php if(!empty($shop['shop_logo'])): ?><img src="<?= ROOT_URL ?>/uploads/shop/<?= $shop['shop_logo'] ?>" style="max-height: 40px;"><?php endif; ?></div>
                    <div class="bill-qr-side"><img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=EST<?= $order['orders_no'] ?>" style="width: 35px; height: 35px;"></div>
                    <div class="bill-title-side">
                        <h1 class="doc-title-a5">ใบเสร็จรับเงิน (REGULAR RECEIPT)<?= $billPageText ?></h1>
                        <div class="doc-meta-a5"><?= $L['no'] ?>: RE<?= $order['orders_no'] ?><br><?= $L['date'] ?>: <?= date('d/m/Y H:i', strtotime($order['orders_date'])) ?></div>
                    </div>
                </div>
                <div class="bill-address-row">
                    <div class="addr-box-a5">
                        <div class="addr-title"><?= $L['sender'] ?></div>
                        <div class="addr-content"><strong><?= $sender_name ?></strong><br><?= htmlspecialchars($sender_address) ?><br><?= $L['tel'] ?>: <?= formatPhone($shop['shop_phone']) ?></div>
                    </div>
                    <div class="addr-box-a5">
                        <div class="addr-title"><?= $L['customer'] ?> <span class="customer-id-badge">C<?= $order['user_id'] ?>-<?= $order_count ?></span></div>
                        <div class="addr-content">
                            <strong><?= htmlspecialchars($billing_address['addr_name'] ?? ($order['user_name'] . ' ' . $order['user_lastname'])) ?></strong><br>
                            <?= htmlspecialchars(($billing_address['addr_detail'] ?? '') . ' ' . ($billing_address['addr_detail2'] ?? '')) ?><br>
                            <?php if ($useLang !== 'en' && empty($billing_address['is_foreign'])): ?>
                                <?= htmlspecialchars(($billing_address['province_name'] ?? '') . ' ' . ($billing_address['addr_postcode'] ?? '')) ?><br>
                                <?= $L['tel'] ?>: <?= formatPhone($billing_address['addr_mobile'] ?? '') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <table class="bill-items-table">
                    <thead><tr><th style="width: 25px;">#</th><th style="width: 45px;" class="text-center"><?= $L['code'] ?></th><th><?= $L['item'] ?></th><th style="width: 35px;" class="text-center"><?= $L['qty'] ?></th><th style="width: 55px;" class="text-right"><?= $L['total'] ?></th></tr></thead>
                    <tbody>
                        <?php 
                        $globalIdx = $chunkIndex * 20;
                        foreach ($chunk as $idx => $item): 
                            $rowTotal = $item['product_price'] * $item['ordetail_qty']; 
                        ?>
                        <tr><td class="text-center"><?= $globalIdx + $idx + 1 ?></td><td class="text-center"><?= htmlspecialchars($item['product_code']) ?></td><td><?= htmlspecialchars($item['display_name']) ?></td><td class="text-center"><?= $item['ordetail_qty'] ?></td><td class="text-right"><?= number_format($rowTotal / $currencyRate, 2) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if ($isLastBillPage && count($chunk) < 6): ?>
                            <?php for($i=count($chunk); $i<6; $i++): ?><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr><?php endfor; ?>
                        <?php endif; ?>
                        <?php if ($isLastBillPage): ?>
                            <tr style="background: #f8f9fa; font-weight: bold;">
                                <td colspan="3" class="text-right" style="padding-right: 10px;"><?= $L['total_items'] ?></td>
                                <td class="text-center"><?= $total_qty ?></td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if ($isLastBillPage): ?>
                <div class="bill-summary-row-v2">
                    <div class="summary-left-v2">
                        <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=EST<?= $order['orders_no'] ?>&height=10&scale=2&includetext" style="height: 35px;">
                        <?php if(!empty($order['orders_msg'])): ?><div style="font-size: 9px; padding: 1mm; border: 1px dashed #bbb; margin-top: 2mm;"><strong>Note:</strong> <?= nl2br(htmlspecialchars($order['orders_msg'])) ?></div><?php endif; ?>
                    </div>
                    <div class="summary-right-v2">
                        <div class="sum-line-v2"><span><?= $L['subtotal'] ?>:</span><span><?= $currSym ?><?= number_format($order['orders_total'] / $currencyRate, 2) ?></span></div>
                        <div class="sum-line-v2"><span><?= $L['shipping'] ?>:</span><span><?= $currSym ?><?= number_format($order['orders_delicost'] / $currencyRate, 2) ?></span></div>
                        <?php
                        $redeemedStampsPrn = 0;
                        try {
                            $stPrn = $db->prepare("SELECT SUM(ABS(points_change)) FROM member_points_log WHERE reference_id = ? AND action_type = 'order_discount' AND points_change < 0");
                            $stPrn->execute([(int)$order['orders_id']]);
                            $redeemedStampsPrn = (int)$stPrn->fetchColumn();
                        } catch (Throwable $e) {}
                        if ($redeemedStampsPrn === 0 && !empty($order['orders_msg']) && preg_match('/E-Stamp:\s*(\d+)\s*แต้ม/u', (string)$order['orders_msg'], $mP)) {
                            $redeemedStampsPrn = (int)$mP[1];
                        }
                        $otherDiscPrn = max(0.0, (float)$order['orders_discount'] - (float)$redeemedStampsPrn);
                        ?>
                        <?php if ($redeemedStampsPrn > 0): ?><div class="sum-line-v2" style="color: #dc3545;"><span>ส่วนลด E-Stamp (<?= $redeemedStampsPrn ?> แต้ม):</span><span>-<?= $currSym ?><?= number_format($redeemedStampsPrn / $currencyRate, 2) ?></span></div><?php endif; ?>
                        <?php if ($otherDiscPrn > 0): ?><div class="sum-line-v2" style="color: #dc3545;"><span><?= $L['discount'] ?>:</span><span>-<?= $currSym ?><?= number_format($otherDiscPrn / $currencyRate, 2) ?></span></div><?php endif; ?>
                        <?php if ($order['orders_cod'] > 0): ?><div class="sum-line-v2 fw-bold" style="color: #000;"><span><?= $L['cod_fee'] ?>:</span><span><?= $currSym ?><?= number_format($order['orders_cod'] / $currencyRate, 2) ?></span></div><?php endif; ?>
                        <div class="sum-line-v2 grand-total-line-v2" style="background: #eee; color: #000; border: 1px solid #000;"><span><?= $L['grand_total'] ?>:</span><span><?= $currSym ?><?= number_format($order['orders_grandtotal'] / $currencyRate, 2) ?></span></div>
                        <div class="sum-line-v2 balance-line-v2" style="color: #000; border-top: 1px solid #000;"><span><?= $L['balance'] ?>:</span><span><?= $currSym ?><?= number_format(($order['orders_grandtotal'] - $order['orders_paid_amount']) / $currencyRate, 2) ?></span></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between" style="margin-top: 5mm; text-align: center;">
                    <div style="flex: 1;"><div style="height: 10mm; border-bottom: 1px solid #ddd; margin: 0 10mm;"></div><div style="font-size: 9px; margin-top: 1mm;"><?= $L['signature_customer'] ?></div></div>
                    <div style="flex: 1;"><div style="height: 10mm; border-bottom: 1px solid #ddd; margin: 0 10mm;"></div><div style="font-size: 9px; margin-top: 1mm;"><?= $L['signature_authorized'] ?></div></div>
                </div>
                <?php endif; ?>
                <div class="page-footer">
                    <div><?= $L['created_by'] ?>: <?= htmlspecialchars($order['creator_fname'] . ' ' . $order['creator_lname']) ?></div>
                    <div><?= $L['updated_by'] ?>: <?= htmlspecialchars($order['updater_fname'] . ' ' . $order['updater_lname']) ?></div>
                    <div><?= $L['printed_by'] ?>: <?= htmlspecialchars($current_admin_name) ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php else: ?>
        <!-- STANDARD A4 TEMPLATE (Existing) -->
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
                    <div class="addr-content"><strong><?= $sender_name ?></strong><br><?= nl2br(htmlspecialchars($sender_address)) ?><br><?= $L['tel'] ?>: <?= formatPhone($shop['shop_phone']) ?><?php if(!empty($sender_tax_id)): ?> | Tax: <?= $sender_tax_id ?><?php endif; ?></div>
                </div>
                <div class="addr-box-a5" style="flex: 1;">
                    <div class="addr-title"><?= $L['customer'] ?></div>
                    <div class="addr-content"><strong><?= htmlspecialchars($address['addr_name'] ?? ($order['user_name'] . ' ' . $order['user_lastname'])) ?></strong><br><?= htmlspecialchars(($address['addr_detail'] ?? '') . ' ' . ($address['addr_detail2'] ?? '')) ?>
                        <?php if ($useLang !== 'en' && empty($address['is_foreign'])): ?>
                            <br><?= htmlspecialchars(($address['province_name'] ?? '') . ' ' . ($address['addr_postcode'] ?? '')) ?>
                            <br><?= $L['tel'] ?>: <?= formatPhone($address['addr_mobile'] ?? '') ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <table class="a4-items-table">
                <thead><tr><th style="width: 40px;" class="text-center">#</th><th style="width: 70px;" class="text-center"><?= $L['code'] ?></th><th><?= $L['item'] ?></th><th style="width: 80px;" class="text-center"><?= $L['qty'] ?></th><th style="width: 120px;" class="text-right"><?= $L['total'] ?></th></tr></thead>
                <tbody>
                    <?php foreach ($items as $idx => $item): $rowTotal = $item['product_price'] * $item['ordetail_qty']; ?>
                    <tr><td class="text-center"><?= $idx + 1 ?></td><td class="text-center"><?= htmlspecialchars($item['product_code']) ?></td><td><?= htmlspecialchars($item['display_name']) ?><?php if(!empty($item['ordetail_memo'])): ?><br><small style="color: #0dcaf0;">* <?= htmlspecialchars($item['ordetail_memo']) ?></small><?php endif; ?></td><td class="text-center"><?= number_format($item['ordetail_qty']) ?></td><td class="text-right"><?= number_format($rowTotal / $currencyRate, 2) ?></td></tr>
                    <?php endforeach; ?>
                    <tr style="background: #f8f9fa; font-weight: bold;">
                        <td colspan="3" class="text-right" style="padding-right: 10px;"><?= $L['total_items'] ?></td>
                        <td class="text-center"><?= $total_qty ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <div class="d-flex justify-content-between">
                <div style="flex: 1; margin-right: 10mm;">
                    <?php if(!empty($order['orders_msg'])): ?>
                        <div style="padding: 3mm; border: 1px dashed #bbb; background: #fafafa; font-size: 11px;">
                            <strong><?= $L['order_note'] ?>:</strong><br><?= nl2br(htmlspecialchars($order['orders_msg'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($currency !== 'THB'): ?>
                    <div style="margin-top: 5mm; font-size: 11px; color: #666;">
                        * Exchange Rate: 1 <?= $currency ?> = <?= number_format($currencyRate, 2) ?> THB
                    </div>
                    <?php endif; ?>
                </div>
                <div style="width: 80mm;">
                    <div class="total-row"><span><?= $L['subtotal'] ?>:</span><span><?= $currSym ?><?= number_format($order['orders_total'] / $currencyRate, 2) ?></span></div>
                    <div class="total-row"><span><?= $L['shipping'] ?>:</span><span><?= $currSym ?><?= number_format($order['orders_delicost'] / $currencyRate, 2) ?></span></div>
                    <?php
                    $redeemedStampsPrnA4 = 0;
                    try {
                        $stPrnA4 = $db->prepare("SELECT SUM(ABS(points_change)) FROM member_points_log WHERE reference_id = ? AND action_type = 'order_discount' AND points_change < 0");
                        $stPrnA4->execute([(int)$order['orders_id']]);
                        $redeemedStampsPrnA4 = (int)$stPrnA4->fetchColumn();
                    } catch (Throwable $e) {}
                    if ($redeemedStampsPrnA4 === 0 && !empty($order['orders_msg']) && preg_match('/E-Stamp:\s*(\d+)\s*แต้ม/u', (string)$order['orders_msg'], $mPA4)) {
                        $redeemedStampsPrnA4 = (int)$mPA4[1];
                    }
                    $otherDiscPrnA4 = max(0.0, (float)$order['orders_discount'] - (float)$redeemedStampsPrnA4);
                    ?>
                    <?php if ($redeemedStampsPrnA4 > 0): ?><div class="total-row" style="color: #dc3545;"><span><?= $useLang == 'en' ? "E-Stamp Discount ({$redeemedStampsPrnA4} pts)" : "ส่วนลด E-Stamp ({$redeemedStampsPrnA4} แต้ม)" ?>:</span><span>-<?= $currSym ?><?= number_format($redeemedStampsPrnA4 / $currencyRate, 2) ?></span></div><?php endif; ?>
                    <?php if ($otherDiscPrnA4 > 0): ?><div class="total-row" style="color: #dc3545;"><span><?= $L['discount'] ?>:</span><span>-<?= $currSym ?><?= number_format($otherDiscPrnA4 / $currencyRate, 2) ?></span></div><?php endif; ?>
                    <?php if ($order['orders_cod'] > 0): ?><div class="total-row fw-bold"><span><?= $L['cod_fee'] ?>:</span><span><?= $currSym ?><?= number_format($order['orders_cod'] / $currencyRate, 2) ?></span></div><?php endif; ?>
                    <div class="total-row grand-total"><span><?= $L['grand_total'] ?>:</span><span><?= $currSym ?><?= number_format($order['orders_grandtotal'] / $currencyRate, 2) ?></span></div>
                    <?php if ($order['orders_paid_amount'] > 0): ?><div class="total-row fw-bold" style="color: #000;"><span><?= $L['paid'] ?>:</span><span><?= $currSym ?><?= number_format($order['orders_paid_amount'] / $currencyRate, 2) ?></span></div><?php endif; ?>
                    <div class="total-row" style="color: #000; font-weight: bold; border-top: 1px solid #000; padding-top: 2px;"><span><?= $L['balance'] ?>:</span><span><?= $currSym ?><?= number_format(($order['orders_grandtotal'] - $order['orders_paid_amount']) / $currencyRate, 2) ?></span></div>
                </div>
            </div>

            <div class="d-flex justify-content-between" style="margin-top: 15mm; text-align: center;">
                <div style="flex: 1;"><div style="height: 15mm; border-bottom: 1px solid #333; margin: 0 10mm;"></div><div style="margin-top: 2mm; font-size: 11px;"><?= $L['signature_customer'] ?></div></div>
                <div style="flex: 1;"><div style="height: 15mm; border-bottom: 1px solid #333; margin: 0 10mm;"></div><div style="margin-top: 2mm; font-size: 11px;"><?= $L['signature_authorized'] ?></div></div>
            </div>
            
            <div style="margin-top: 10mm; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #eee; padding-top: 3mm;">
                <div><?= $L['created_by'] ?>: <?= htmlspecialchars($order['creator_fname'] . ' ' . $order['creator_lname']) ?> | <?= $L['printed_by'] ?>: <?= htmlspecialchars($current_admin_name) ?></div>
                <div style="margin-top: 2mm;"><?= $L['footer_thanks'] ?></div>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>
