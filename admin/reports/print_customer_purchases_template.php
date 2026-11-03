<?php
// Prevent direct access if not included
if (!isset($new_customers) || !isset($returning_customers)) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พิมพ์รายงานพฤติกรรมลูกค้า - Esther Aroma</title>
    <!-- Google Fonts: Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bs-blue: #0d6efd;
            --bs-indigo: #6610f2;
            --bs-purple: #6f42c1;
            --bs-pink: #d63384;
            --bs-red: #dc3545;
            --bs-orange: #fd7e14;
            --bs-yellow: #ffc107;
            --bs-green: #198754;
            --bs-teal: #20c997;
            --bs-cyan: #0dcaf0;
            --bs-gray: #6c757d;
            --bs-gray-dark: #343a40;
            --bs-gray-100: #f8f9fa;
            --bs-gray-200: #e9ecef;
            --bs-gray-300: #dee2e6;
            --bs-gray-400: #ced4da;
            --bs-gray-500: #adb5bd;
            --bs-gray-600: #6c757d;
            --bs-gray-700: #495057;
            --bs-gray-800: #343a40;
            --bs-gray-900: #212529;
            --bs-primary: #0d6efd;
            --bs-secondary: #6c757d;
            --bs-success: #198754;
            --bs-info: #0dcaf0;
            --bs-warning: #ffc107;
            --bs-danger: #dc3545;
            --bs-light: #f8f9fa;
            --bs-dark: #212529;
        }
        
        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
            background-color: #fff;
            margin: 0;
            padding: 20px;
        }

        /* Print Settings */
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .page-break-before { page-break-before: always; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            .print-button-container { display: none; }
        }

        h1, h2, h3, h4, h5, h6 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header-left h1 {
            font-size: 18pt;
            margin: 0 0 5px 0;
        }
        .header-left p {
            margin: 0;
            color: #555;
            font-size: 12pt;
        }

        .header-right {
            text-align: right;
            font-size: 10pt;
            color: #555;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px 6px;
            vertical-align: middle;
        }

        th {
            background-color: #f1f5f9;
            color: #333;
            font-weight: 600;
            text-align: center;
            border-bottom: 2px solid #ccc;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        .text-muted { color: #6c757d; }
        
        .col-no { width: 3%; text-align: center; }
        .col-name { width: 22%; }
        .col-phone { width: 12%; text-align: center; }
        .col-channel { width: 12%; text-align: center; }
        .col-info { width: 15%; text-align: center; }
        .col-stats { width: 12%; text-align: center; }
        .col-notes { width: 24%; } /* Blank space for writing */

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8pt;
            font-weight: 600;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 4px;
            background-color: #6c757d;
        }
        
        /* Channel Colors (Print Friendly) */
        .bg-line { background-color: #06C755; color: white !important;}
        .bg-fb { background-color: #1877F2; color: white !important;}
        .bg-shopee { background-color: #EE4D2D; color: white !important;}
        .bg-lazada { background-color: #101566; color: white !important;}
        .bg-tiktok { background-color: #000000; color: white !important;}
        .bg-web { background-color: #0ea5e9; color: white !important;}
        .bg-phone { background-color: #0d6efd; color: white !important;}
        
        .section-title {
            background-color: #333;
            color: #fff;
            padding: 8px 15px;
            font-size: 12pt;
            margin-bottom: 10px;
            display: inline-block;
            border-radius: 4px;
        }
        
        .customer-tags {
            font-size: 8pt;
            color: #666;
            margin-top: 2px;
        }
        
        .print-button-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .btn-print {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14pt;
            cursor: pointer;
            font-family: 'Sarabun', sans-serif;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-print:hover { background-color: #0b5ed7; }
    </style>
</head>
<body>

    <div class="print-button-container no-print">
        <button class="btn-print" onclick="window.print()">🖨️ พิมพ์หน้านี้ (Print)</button>
    </div>

    <!-- Header -->
    <div class="header-section">
        <div class="header-left">
            <h1>รายงานพฤติกรรมลูกค้า (สำหรับการติดตาม)</h1>
            <p>หมวดหมู่: <strong><?= htmlspecialchars($selectedMaincatName) ?></strong></p>
        </div>
        <div class="header-right">
            <div>วันที่พิมพ์: <?= date('d/m/Y H:i') ?></div>
            <div id="header-active-tab" class="fw-bold text-primary"></div>
            <div id="header-search-term" style="color: #d63384; font-weight: 600;"></div>
        </div>
    </div>

    <!-- Returning Customers Table -->
    <?php if (!empty($returning_customers)): ?>
    <div id="section-return">
    <div class="section-title">ลูกค้าเก่า (ซื้อซ้ำ >= 2 ครั้ง) - <span id="return-count"><?= count($returning_customers) ?></span> ราย</div>
    <table>
        <thead>
            <tr>
                <th class="col-no">ลำดับ</th>
                <th class="col-name">ชื่อลูกค้า</th>
                <th class="col-phone">เบอร์โทรศัพท์</th>
                <th class="col-channel">ช่องทาง / ประเทศ</th>
                <th class="col-info">สั่งล่าสุด / ระยะเวลา</th>
                <th class="col-stats">จำนวนออเดอร์ / ยอดรวม</th>
                <th class="col-notes">บันทึกการติดตาม (Notes)</th>
            </tr>
        </thead>
        <tbody id="tbody-return">
            <?php 
            $i = 1;
            foreach ($returning_customers as $c): 
                $fullname = trim((string)$c['user_name'].' '.(string)$c['user_lastname']);
                if (!empty($c['user_nickname'])) $fullname .= ' (' . trim($c['user_nickname']) . ')';
                if(empty($fullname)) $fullname = "ลูกค้าไม่มีชื่อ (ID: ".$c['user_id'].")";
                $phone = (string)($c['user_mobile'] ?: $c['user_tel']);
                
                // Channel parsing
                $channel = !empty($c['last_channel']) ? $c['last_channel'] : 'ไม่ระบุ';
                $channel_class = '';
                $lc = strtolower($channel);
                if (strpos($lc, 'line') !== false) $channel_class = 'bg-line';
                else if (strpos($lc, 'fb') !== false || strpos($lc, 'face') !== false) $channel_class = 'bg-fb';
                else if (strpos($lc, 'shopee') !== false) $channel_class = 'bg-shopee';
                else if (strpos($lc, 'lazada') !== false) $channel_class = 'bg-lazada';
                else if (strpos($lc, 'tiktok') !== false) $channel_class = 'bg-tiktok';
                else if (strpos($lc, 'web') !== false) $channel_class = 'bg-web';
                
                // Country parsing
                $cid = $c['last_country_id'] ?? 171;
                $country_name = $countries[$cid]['country_name'] ?? 'ไม่ระบุประเทศ';
                
                // Image
                $img_path = __DIR__ . "/../../uploads/profile/{$c['user_id']}/{$c['user_picture']}";
                $img_url  = (!empty($c['user_picture']) && file_exists($img_path)) 
                            ? ROOT_URL . "/uploads/profile/{$c['user_id']}/{$c['user_picture']}" 
                            : ROOT_URL . "/uploads/profile/default.png";
            ?>
            <tr class="data-row" data-search="<?= strtolower(htmlspecialchars($fullname.' '.$phone.' C'.str_pad($c['user_id'], 4, '0', STR_PAD_LEFT))) ?>" data-count="<?= $c['total_orders'] ?>" data-total="<?= $c['total_spent'] ?>" data-lastdate="<?= $c['last_order_date'] ?>" data-avgcycle="<?= $c['avg_days_between_orders'] ?>">
                <td class="col-no text-muted"><?= $i++ ?></td>
                <td class="col-name">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="<?= $img_url ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; display: block; flex-shrink: 0;">
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($fullname) ?></div>
                            <div class="customer-tags text-muted">ID: C<?= str_pad($c['user_id'], 4, '0', STR_PAD_LEFT) ?></div>
                        </div>
                    </div>
                </td>
                <td class="col-phone fw-bold"><?= htmlspecialchars($phone) ?></td>
                <td class="col-channel">
                    <div><span class="badge <?= $channel_class ?>"><?= htmlspecialchars($channel) ?></span></div>
                    <div style="font-size: 8pt; margin-top: 3px;"><?= htmlspecialchars($country_name) ?></div>
                </td>
                <td class="col-info">
                    <div><?= date('d/m/Y', strtotime($c['last_order_date'])) ?></div>
                    <div style="color: <?= $c['days_since_last_order'] <= 30 ? '#198754' : '#d63384' ?>; font-weight: 600; font-size: 9pt;">
                        (<?= $c['days_since_last_order'] ?> วันที่แล้ว)
                    </div>
                </td>
                <td class="col-stats">
                    <div class="fw-bold"><?= $c['total_orders'] ?> ครั้ง</div>
                    <div class="text-muted" style="font-size: 9pt;">฿<?= number_format($c['total_spent'], 2) ?></div>
                </td>
                <td class="col-notes"></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>

    <!-- Page Break before New Customers if both exist -->
    <?php if (!empty($returning_customers) && !empty($new_customers)): ?>
    <div id="page-break" class="page-break-before"></div>
    <?php endif; ?>

    <!-- New Customers Table -->
    <?php if (!empty($new_customers)): ?>
    <div id="section-new">
    <div class="section-title">ลูกค้าใหม่ (สั่งซื้อ 1 ครั้ง) - <span id="new-count"><?= count($new_customers) ?></span> ราย</div>
    <table>
        <thead>
            <tr>
                <th class="col-no">ลำดับ</th>
                <th class="col-name">ชื่อลูกค้า</th>
                <th class="col-phone">เบอร์โทรศัพท์</th>
                <th class="col-channel">ช่องทาง / ประเทศ</th>
                <th class="col-info">สั่งล่าสุด / ระยะเวลา</th>
                <th class="col-stats">ยอดรวม</th>
                <th class="col-notes">บันทึกการติดตาม (Notes)</th>
            </tr>
        </thead>
        <tbody id="tbody-new">
            <?php 
            $i = 1;
            foreach ($new_customers as $c): 
                $fullname = trim((string)$c['user_name'].' '.(string)$c['user_lastname']);
                if (!empty($c['user_nickname'])) $fullname .= ' (' . trim($c['user_nickname']) . ')';
                if(empty($fullname)) $fullname = "ลูกค้าไม่มีชื่อ (ID: ".$c['user_id'].")";
                $phone = (string)($c['user_mobile'] ?: $c['user_tel']);
                
                // Channel parsing
                $channel = !empty($c['last_channel']) ? $c['last_channel'] : 'ไม่ระบุ';
                $channel_class = '';
                $lc = strtolower($channel);
                if (strpos($lc, 'line') !== false) $channel_class = 'bg-line';
                else if (strpos($lc, 'fb') !== false || strpos($lc, 'face') !== false) $channel_class = 'bg-fb';
                else if (strpos($lc, 'shopee') !== false) $channel_class = 'bg-shopee';
                else if (strpos($lc, 'lazada') !== false) $channel_class = 'bg-lazada';
                else if (strpos($lc, 'tiktok') !== false) $channel_class = 'bg-tiktok';
                else if (strpos($lc, 'web') !== false) $channel_class = 'bg-web';
                
                // Country parsing
                $cid = $c['last_country_id'] ?? 171;
                $country_name = $countries[$cid]['country_name'] ?? 'ไม่ระบุประเทศ';
                
                // Image
                $img_path = __DIR__ . "/../../uploads/profile/{$c['user_id']}/{$c['user_picture']}";
                $img_url  = (!empty($c['user_picture']) && file_exists($img_path)) 
                            ? ROOT_URL . "/uploads/profile/{$c['user_id']}/{$c['user_picture']}" 
                            : ROOT_URL . "/uploads/profile/default.png";
            ?>
            <tr class="data-row" data-search="<?= strtolower(htmlspecialchars($fullname.' '.$phone.' C'.str_pad($c['user_id'], 4, '0', STR_PAD_LEFT))) ?>" data-total="<?= $c['total_spent'] ?>" data-lastdate="<?= $c['last_order_date'] ?>" data-days="<?= $c['days_since_last_order'] ?>">
                <td class="col-no text-muted"><?= $i++ ?></td>
                <td class="col-name">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="<?= $img_url ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; display: block; flex-shrink: 0;">
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($fullname) ?></div>
                            <div class="customer-tags text-muted">ID: C<?= str_pad($c['user_id'], 4, '0', STR_PAD_LEFT) ?></div>
                        </div>
                    </div>
                </td>
                <td class="col-phone fw-bold"><?= htmlspecialchars($phone) ?></td>
                <td class="col-channel">
                    <div><span class="badge <?= $channel_class ?>"><?= htmlspecialchars($channel) ?></span></div>
                    <div style="font-size: 8pt; margin-top: 3px;"><?= htmlspecialchars($country_name) ?></div>
                </td>
                <td class="col-info">
                    <div><?= date('d/m/Y', strtotime($c['last_order_date'])) ?></div>
                    <div style="color: <?= $c['days_since_last_order'] <= 30 ? '#198754' : '#d63384' ?>; font-weight: 600; font-size: 9pt;">
                        (<?= $c['days_since_last_order'] ?> วันที่แล้ว)
                    </div>
                </td>
                <td class="col-stats">
                    <div class="fw-bold">฿<?= number_format($c['total_spent'], 2) ?></div>
                </td>
                <td class="col-notes"></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Get state from localStorage
    const activeTabId = localStorage.getItem('cp_active_tab') || 'new-tab'; // 'new-tab' or 'return-tab'
    const searchTerm = (localStorage.getItem('cp_search_term') || '').toLowerCase().trim();
    
    const sortByReturn = localStorage.getItem('cp_sort_by') || 'count';
    const sortDirReturn = localStorage.getItem('cp_sort_dir') || 'desc';
    
    const sortByNew = localStorage.getItem('cp_sort_by_new') || 'lastdate';
    const sortDirNew = localStorage.getItem('cp_sort_dir_new') || 'desc';

    // 2. Hide the inactive section
    const returnSection = document.getElementById('section-return');
    const newSection = document.getElementById('section-new');
    const pageBreak = document.getElementById('page-break');
    
    if (activeTabId === 'new-tab') {
        if(returnSection) returnSection.style.display = 'none';
        if(pageBreak) pageBreak.style.display = 'none';
    } else {
        if(newSection) newSection.style.display = 'none';
        if(pageBreak) pageBreak.style.display = 'none';
    }

    // 3. Search and sort returning customers
    if (returnSection && activeTabId === 'return-tab') {
        const tbody = document.getElementById('tbody-return');
        if (tbody) {
            let rows = Array.from(tbody.querySelectorAll('tr.data-row'));
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                if (searchData.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Sort
            rows.sort((a, b) => {
                let valA = 0, valB = 0;
                if (sortByReturn === 'count') {
                    valA = parseInt(a.getAttribute('data-count')) || 0;
                    valB = parseInt(b.getAttribute('data-count')) || 0;
                } else if (sortByReturn === 'total') {
                    valA = parseFloat(a.getAttribute('data-total')) || 0;
                    valB = parseFloat(b.getAttribute('data-total')) || 0;
                } else if (sortByReturn === 'lastdate') {
                    valA = new Date(a.getAttribute('data-lastdate')).getTime() || 0;
                    valB = new Date(b.getAttribute('data-lastdate')).getTime() || 0;
                } else if (sortByReturn === 'avgcycle') {
                    valA = parseInt(a.getAttribute('data-avgcycle')) || 0;
                    valB = parseInt(b.getAttribute('data-avgcycle')) || 0;
                }

                if (sortDirReturn === 'desc') {
                    return valB - valA;
                } else {
                    return valA - valB;
                }
            });
            
            // Reorder in DOM and re-number
            let visibleCount = 0;
            rows.forEach(row => {
                tbody.appendChild(row);
                if (row.style.display !== 'none') {
                    visibleCount++;
                    row.querySelector('.col-no').innerText = visibleCount;
                }
            });
            
            document.getElementById('return-count').innerText = visibleCount;
        }
    }

    // 4. Search and sort new customers
    if (newSection && activeTabId === 'new-tab') {
        const tbody = document.getElementById('tbody-new');
        if (tbody) {
            let rows = Array.from(tbody.querySelectorAll('tr.data-row'));
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                if (searchData.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Sort
            rows.sort((a, b) => {
                let valA = 0, valB = 0;
                if (sortByNew === 'total') {
                    valA = parseFloat(a.getAttribute('data-total')) || 0;
                    valB = parseFloat(b.getAttribute('data-total')) || 0;
                } else if (sortByNew === 'lastdate') {
                    valA = new Date(a.getAttribute('data-lastdate')).getTime() || 0;
                    valB = new Date(b.getAttribute('data-lastdate')).getTime() || 0;
                } else if (sortByNew === 'days') {
                    valA = parseInt(a.getAttribute('data-days')) || 0;
                    valB = parseInt(b.getAttribute('data-days')) || 0;
                }

                if (sortDirNew === 'desc') {
                    return valB - valA;
                } else {
                    return valA - valB;
                }
            });
            
            // Reorder in DOM and re-number
            let visibleCount = 0;
            rows.forEach(row => {
                tbody.appendChild(row);
                if (row.style.display !== 'none') {
                    visibleCount++;
                    row.querySelector('.col-no').innerText = visibleCount;
                }
            });
            
            document.getElementById('new-count').innerText = visibleCount;
        }
    }

    // 5. Update header info
    document.getElementById('header-active-tab').innerText = activeTabId === 'new-tab' ? 'แสดง: ลูกค้าใหม่ (สั่ง 1 ครั้ง)' : 'แสดง: ลูกค้าเก่า (ซื้อซ้ำ)';
    if (searchTerm) {
        document.getElementById('header-search-term').innerText = 'ค้นหา: "' + searchTerm + '"';
    }

});
</script>
</body>
</html>
