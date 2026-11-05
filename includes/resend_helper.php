<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — Resend Email Service Helper
 * ============================================================
 * Handles sending transactional emails via Resend API (https://resend.com)
 * with template loading, merge tags substitution, automatic verified-domain
 * sender fallback, and Admin order alert emails.
 */

if (!function_exists('get_site_setting')) {
    function get_site_setting(PDO $db, string $key, string $default = ''): string {
        try {
            $stmt = $db->prepare("SELECT setting_value FROM sitesettings WHERE setting_name = ? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false && $val !== null ? (string)$val : $default;
        } catch (Throwable $e) {
            return $default;
        }
    }
}

/**
 * Normalize From Email so free webmail domains (gmail.com, etc.) don't trigger Resend HTTP 403
 */
function resolve_resend_from_address(PDO $db): array {
    $rawFrom = trim(get_site_setting($db, 'resend_from_email', 'Yak Pho Aroma <orders@yakpho.com>'));
    $replyTo = trim(get_site_setting($db, 'admin_notify_email', 'thaiherbfc@gmail.com'));
    if ($replyTo === '') {
        $replyTo = 'thaiherbfc@gmail.com';
    }

    // Extract email address inside <...> or plain string
    $emailOnly = $rawFrom;
    if (preg_match('/<([^>]+)>/', $rawFrom, $m)) {
        $emailOnly = trim($m[1]);
    }

    $unverifiedFreeDomains = ['@gmail.com', '@yahoo.com', '@hotmail.com', '@outlook.com', '@live.com', '@icloud.com'];
    $isFreeDomain = false;
    foreach ($unverifiedFreeDomains as $freeDom) {
        if (stripos($emailOnly, $freeDom) !== false) {
            $isFreeDomain = true;
            break;
        }
    }

    if ($rawFrom === '' || $isFreeDomain) {
        if (filter_var($emailOnly, FILTER_VALIDATE_EMAIL)) {
            $replyTo = $emailOnly;
        }
        $from = 'Yak Pho Aroma <orders@yakpho.com>';
    } elseif (strpos($rawFrom, '<') === false && filter_var($rawFrom, FILTER_VALIDATE_EMAIL)) {
        $from = 'Yak Pho Aroma <' . $rawFrom . '>';
    } else {
        $from = $rawFrom;
    }

    return [
        'from'     => $from,
        'reply_to' => $replyTo,
    ];
}

/**
 * Internal helper to execute a single Resend API POST request
 */
function execute_resend_post(string $apiKey, array $payload): array {
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return [
            'success'   => false,
            'http_code' => 0,
            'message'   => 'cURL Error: ' . $curlErr,
        ];
    }

    $resData = json_decode((string)$response, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success'   => true,
            'http_code' => $httpCode,
            'id'        => $resData['id'] ?? '',
            'message'   => 'Email sent successfully',
        ];
    }

    return [
        'success'   => false,
        'http_code' => $httpCode,
        'message'   => $resData['message'] ?? 'Failed to send email via Resend API',
    ];
}

/**
 * Send raw email using Resend API (with automatic verified domain fallback + PHP mail() fallback)
 */
function send_resend_email(PDO $db, string $to, string $subject, string $htmlContent, string $textFallback = ''): array {
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Invalid recipient email address.',
        ];
    }

    $apiKey = trim(get_site_setting($db, 'resend_api_key'));
    $senderInfo = resolve_resend_from_address($db);

    if (!empty($apiKey)) {
        $payload = [
            'from'     => $senderInfo['from'],
            'to'       => [$to],
            'reply_to' => $senderInfo['reply_to'],
            'subject'  => $subject,
            'html'     => $htmlContent,
        ];
        if (!empty($textFallback)) {
            $payload['text'] = $textFallback;
        }

        $res = execute_resend_post($apiKey, $payload);

        // If custom domain in settings is not verified yet (403), retry automatically with verified thaispa.app / jibjib.in.th
        if (!$res['success'] && (int)($res['http_code'] ?? 0) === 403) {
            $fallbackSenders = [
                'Yak Pho Aroma <orders@thaispa.app>',
                'Yak Pho Aroma <noreply@jibjib.in.th>',
            ];
            foreach ($fallbackSenders as $fbFrom) {
                if ($fbFrom === $payload['from']) {
                    continue;
                }
                $payload['from'] = $fbFrom;
                $res = execute_resend_post($apiKey, $payload);
                if ($res['success']) {
                    return $res;
                }
            }
        }

        if ($res['success']) {
            return $res;
        }
    }

    // Final fallback: native PHP mail() on server if Resend API key is missing or unreachable
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Yak Pho Aroma <noreply@yakpho.com>\r\n";
    $headers .= "Reply-To: " . $senderInfo['reply_to'] . "\r\n";

    $mailOk = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlContent, $headers);
    if ($mailOk) {
        return [
            'success' => true,
            'id'      => 'php_mail',
            'message' => 'Sent via server mail()',
        ];
    }

    return [
        'success' => false,
        'message' => isset($res['message']) ? $res['message'] : 'Resend API Key is not configured and mail() failed.',
    ];
}

/**
 * Send email using a template code from resend_email_templates
 * Falls back to built-in HTML layout if template table/record does not exist on server
 */
function send_templated_email(PDO $db, string $to, string $templateCode, array $mergeTags = []): array {
    $subject = '';
    $body = '';

    try {
        $stmt = $db->prepare("SELECT email_subject, email_body_html, is_active FROM resend_email_templates WHERE template_code = ? LIMIT 1");
        $stmt->execute([$templateCode]);
        $tmpl = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tmpl && !empty($tmpl['is_active'])) {
            $subject = (string)$tmpl['email_subject'];
            $body = (string)$tmpl['email_body_html'];
        }
    } catch (Throwable $e) {
        // Table might not exist yet on server; use built-in fallback below
    }

    // Built-in fallback template for customer order_confirmation
    if ($subject === '' || $body === '') {
        if ($templateCode === 'order_confirmation') {
            $subject = 'ยืนยันคำสั่งซื้อ #{order_no} - Yak Pho Aroma (ยักษ์โพธิ์ อโรม่า)';
            $body = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:Prompt,Arial,sans-serif;background:#f4f6f8;padding:20px;color:#1e293b;">'
                  . '<div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">'
                  . '<div style="background:#0B3B35;padding:22px;text-align:center;color:#ffffff;">'
                  . '<h2 style="margin:0;font-size:20px;">YAK PHO AROMA (ยักษ์โพธิ์ อโรม่า)</h2>'
                  . '<p style="margin:4px 0 0;font-size:13px;color:#F59E0B;">ใบยืนยันคำสั่งซื้อและรายละเอียดการจัดส่ง</p>'
                  . '</div>'
                  . '<div style="padding:24px;">'
                  . '<p style="font-size:16px;font-weight:bold;color:#0B3B35;margin-top:0;">เรียน คุณ {customer_name},</p>'
                  . '<p style="font-size:14px;color:#475569;">ขอบพระคุณที่ไว้วางใจสั่งซื้อสินค้ากับ Yak Pho Aroma ระบบได้รับคำสั่งซื้อของท่านเรียบร้อยแล้ว โดยมีรายละเอียดดังนี้:</p>'
                  . '<div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:10px;padding:14px;margin:16px 0;font-size:14px;line-height:1.6;">'
                  . '<strong>เลขที่คำสั่งซื้อ:</strong> #{order_no}<br>'
                  . '<strong>วันที่สั่งซื้อ:</strong> {order_date}<br>'
                  . '<strong>ช่องทางชำระเงิน:</strong> {payment_method}<br>'
                  . '<strong>ที่อยู่จัดส่ง:</strong> {shipping_address}'
                  . '</div>'
                  . '<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">'
                  . '<thead><tr style="border-bottom:2px solid #cbd5e1;color:#64748b;"><th style="text-align:left;padding:8px;">รายการสินค้า</th><th style="text-align:right;padding:8px;">จำนวน</th><th style="text-align:right;padding:8px;">ยอดรวม</th></tr></thead>'
                  . '<tbody>{order_items_html}'
                  . '<tr><td colspan="2" style="text-align:right;padding:6px 8px;color:#64748b;">รวมค่าสินค้า:</td><td style="text-align:right;padding:6px 8px;">฿{subtotal}</td></tr>'
                  . '<tr><td colspan="2" style="text-align:right;padding:6px 8px;color:#64748b;">ค่าจัดส่ง:</td><td style="text-align:right;padding:6px 8px;">฿{delivery_cost}</td></tr>'
                  . '<tr><td colspan="2" style="text-align:right;padding:6px 8px;color:#dc2626;">ส่วนลดรวม:</td><td style="text-align:right;padding:6px 8px;color:#dc2626;">-฿{discount_amount}</td></tr>'
                  . '<tr style="font-size:16px;font-weight:bold;color:#0B3B35;border-top:2px solid #0B3B35;"><td colspan="2" style="text-align:right;padding:10px 8px;">ยอดชำระสุทธิ:</td><td style="text-align:right;padding:10px 8px;">฿{grand_total} บาท</td></tr>'
                  . '</tbody></table>'
                  . '<div style="background:#ecfdf5;border:1px solid #10b981;border-radius:8px;padding:12px;margin:16px 0;font-size:13px;color:#065f46;">'
                  . '🌿 <strong>คะแนนสะสม E-Stamp:</strong> คำสั่งซื้อนี้ท่านได้รับ <strong>+{m_stamp_earned} แต้ม</strong> (แต้มสะสมคงเหลือ: <strong>{m_stamp_balance} แต้ม</strong>)'
                  . '</div>'
                  . '<div style="text-align:center;margin:22px 0;">'
                  . '<a href="{tracking_url}" style="display:inline-block;background:#0B3B35;color:#ffffff;padding:12px 26px;text-decoration:none;border-radius:25px;font-weight:bold;font-size:14px;">ชำระเงิน / ตรวจสอบสถานะคำสั่งซื้อ</a>'
                  . '</div>'
                  . '</div>'
                  . '<div style="background:#f1f5f9;padding:14px;text-align:center;font-size:12px;color:#64748b;">© Yak Pho Aroma • บริษัท เอสเธอร์ อโรม่า จำกัด</div>'
                  . '</div></body></html>';
        } elseif ($templateCode === 'shipping_tracking') {
            $subject = 'แจ้งเลขพัสดุคำสั่งซื้อ #{order_no} ({courier_name}: {tracking_number}) - Yak Pho Aroma';
            $body = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:Prompt,Arial,sans-serif;background:#f4f6f8;padding:20px;color:#1e293b;">'
                  . '<div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">'
                  . '<div style="background:#0B3B35;padding:20px;text-align:center;color:#ffffff;"><h2 style="margin:0;font-size:19px;">🚚 พัสดุของท่านถูกจัดส่งเรียบร้อยแล้ว</h2></div>'
                  . '<div style="padding:24px;font-size:14px;line-height:1.6;">'
                  . '<p>เรียน คุณ <strong>{customer_name}</strong>,</p>'
                  . '<p>คำสั่งซื้อหมายเลข <strong>#{order_no}</strong> ของท่านได้ถูกส่งมอบให้บริษัทขนส่งเรียบร้อยแล้ว:</p>'
                  . '<div style="background:#f0fdf4;border:1px solid #16a34a;border-radius:10px;padding:16px;margin:16px 0;text-align:center;">'
                  . '<div style="color:#64748b;font-size:13px;">ขนส่ง: <strong>{courier_name}</strong></div>'
                  . '<div style="font-size:22px;font-weight:bold;color:#0B3B35;letter-spacing:1px;margin:6px 0;">{tracking_number}</div>'
                  . '</div>'
                  . '<div style="text-align:center;margin-top:20px;"><a href="{tracking_url}" style="display:inline-block;background:#16a34a;color:#ffffff;padding:12px 26px;text-decoration:none;border-radius:25px;font-weight:bold;">ติดตามสถานะพัสดุ</a></div>'
                  . '</div></div></body></html>';
        } else {
            return [
                'success' => false,
                'message' => "Template '{$templateCode}' not found or inactive.",
            ];
        }
    }

    foreach ($mergeTags as $tag => $val) {
        $placeholder = '{' . trim($tag, '{}') . '}';
        $subject = str_replace($placeholder, (string)$val, $subject);
        $body = str_replace($placeholder, (string)$val, $body);
    }

    return send_resend_email($db, $to, $subject, $body);
}

/**
 * Send Admin New Order Notification Email (Default: thaiherbfc@gmail.com)
 */
function send_admin_order_email(PDO $db, array $orderData): array {
    $adminEmailsRaw = trim(get_site_setting($db, 'admin_notify_email', 'thaiherbfc@gmail.com'));
    if ($adminEmailsRaw === '') {
        $adminEmailsRaw = 'thaiherbfc@gmail.com';
    }

    // Ensure thaiherbfc@gmail.com is always included as requested
    $emailList = array_filter(array_map('trim', preg_split('/[,;]+/', $adminEmailsRaw)));
    if (!in_array('thaiherbfc@gmail.com', $emailList, true)) {
        $emailList[] = 'thaiherbfc@gmail.com';
    }

    $orderNo       = (string)($orderData['orders_no'] ?? '-');
    $orderDate     = (string)($orderData['order_date'] ?? date('d/m/Y H:i'));
    $customerName  = (string)($orderData['customer_name'] ?? '-');
    $customerCode  = (string)($orderData['customer_code'] ?? '');
    $customerPhone = (string)($orderData['customer_phone'] ?? '-');
    $customerEmail = (string)($orderData['customer_email'] ?? '-');
    $customerLine  = (string)($orderData['customer_line'] ?? '-');
    $shippingAddr  = (string)($orderData['shipping_address'] ?? '-');
    $paymentMethod = (string)($orderData['payment_method'] ?? '-');
    $statusText    = (string)($orderData['status_text'] ?? '-');
    $itemsHtml     = (string)($orderData['order_items_html'] ?? '');
    $totalQty      = (int)($orderData['total_qty'] ?? 0);
    $totalWeightG  = (int)($orderData['total_weight_g'] ?? 0);
    $subtotal      = (string)($orderData['subtotal'] ?? '0.00');
    $shippingCost  = (string)($orderData['delivery_cost'] ?? '0.00');
    $codFee        = (string)($orderData['cod_fee'] ?? '0.00');
    $stampDiscount = (string)($orderData['stamp_discount'] ?? '0');
    $otherDiscount = (string)($orderData['other_discount'] ?? '0.00');
    $grandTotal    = (string)($orderData['grand_total'] ?? '0.00');
    $orderNote     = (string)($orderData['order_note'] ?? '-');
    $trackUrl      = (string)($orderData['tracking_url'] ?? ROOT_URL);
    $adminUrl      = (string)($orderData['admin_url'] ?? (ROOT_URL . '/admin/orders/'));

    $subject = "🔔 [คำสั่งซื้อใหม่ #{$orderNo}] ยอดสุทธิ ฿{$grandTotal} จากคุณ {$customerName} ({$paymentMethod})";

    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head>'
          . '<body style="font-family:Prompt,Tahoma,Arial,sans-serif;background:#f1f5f9;padding:20px;color:#0f172a;">'
          . '<div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #cbd5e1;box-shadow:0 4px 12px rgba(0,0,0,0.06);">'
          . '<div style="background:linear-gradient(135deg,#0B3B35 0%,#145348 100%);padding:20px 24px;color:#ffffff;">'
          . '<div style="font-size:12px;color:#FBBF24;font-weight:bold;letter-spacing:0.5px;">NEW WEBSITE ORDER NOTIFICATION</div>'
          . '<h2 style="margin:4px 0 0;font-size:20px;">📦 มีคำสั่งซื้อใหม่เข้าสู่ระบบ #' . htmlspecialchars($orderNo) . '</h2>'
          . '<div style="font-size:13px;opacity:0.9;margin-top:4px;">วัน-เวลาที่สั่งซื้อ: ' . htmlspecialchars($orderDate) . ' น.</div>'
          . '</div>'
          . '<div style="padding:22px 24px;">'
          . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:18px;font-size:14px;line-height:1.7;">'
          . '<div style="font-weight:bold;color:#0B3B35;font-size:15px;margin-bottom:6px;border-bottom:1px solid #e2e8f0;padding-bottom:4px;">👤 ข้อมูลลูกค้าและการจัดส่ง</div>'
          . '<strong>ชื่อลูกค้า:</strong> ' . htmlspecialchars($customerName) . ($customerCode !== '' ? ' (' . htmlspecialchars($customerCode) . ')' : '') . '<br>'
          . '<strong>เบอร์โทรศัพท์:</strong> <a href="tel:' . htmlspecialchars($customerPhone) . '" style="color:#0284c7;font-weight:bold;text-decoration:none;">' . htmlspecialchars($customerPhone) . '</a><br>'
          . '<strong>อีเมล:</strong> ' . htmlspecialchars($customerEmail ?: '-') . ' &nbsp;|&nbsp; <strong>LINE:</strong> ' . htmlspecialchars($customerLine ?: '-') . '<br>'
          . '<strong>ที่อยู่จัดส่ง:</strong> ' . htmlspecialchars($shippingAddr) . '<br>'
          . '<strong>วิธีชำระเงิน:</strong> <span style="color:#0B3B35;font-weight:bold;">' . htmlspecialchars($paymentMethod) . '</span><br>'
          . '<strong>สถานะเริ่มต้น:</strong> <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:6px;font-weight:bold;font-size:12px;">' . htmlspecialchars($statusText) . '</span>'
          . ($orderNote !== '' && $orderNote !== '-' ? '<br><strong>หมายเหตุจากลูกค้า:</strong> <span style="color:#dc2626;">' . htmlspecialchars($orderNote) . '</span>' : '')
          . '</div>'
          . '<div style="font-weight:bold;color:#0B3B35;font-size:15px;margin-bottom:8px;">🛒 รายการสินค้าที่สั่งซื้อ (' . $totalQty . ' ชิ้น | น้ำหนักรวม ' . number_format($totalWeightG) . ' กรัม)</div>'
          . '<table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:16px;">'
          . '<thead><tr style="background:#f1f5f9;border-bottom:2px solid #cbd5e1;color:#475569;">'
          . '<th style="text-align:left;padding:8px;">สินค้า</th>'
          . '<th style="text-align:right;padding:8px;width:65px;">จำนวน</th>'
          . '<th style="text-align:right;padding:8px;width:110px;">รวมเงิน</th>'
          . '</tr></thead>'
          . '<tbody>' . $itemsHtml . '</tbody>'
          . '<tfoot>'
          . '<tr><td colspan="2" style="text-align:right;padding:6px 8px;color:#64748b;">ยอดรวมค่าสินค้า:</td><td style="text-align:right;padding:6px 8px;font-weight:bold;">฿' . htmlspecialchars($subtotal) . '</td></tr>'
          . '<tr><td colspan="2" style="text-align:right;padding:6px 8px;color:#64748b;">ค่าจัดส่ง:</td><td style="text-align:right;padding:6px 8px;">฿' . htmlspecialchars($shippingCost) . '</td></tr>'
          . ((float)$codFee > 0 ? '<tr><td colspan="2" style="text-align:right;padding:6px 8px;color:#64748b;">ค่าธรรมเนียม COD (+3%):</td><td style="text-align:right;padding:6px 8px;">฿' . htmlspecialchars($codFee) . '</td></tr>' : '')
          . ((int)$stampDiscount > 0 ? '<tr><td colspan="2" style="text-align:right;padding:6px 8px;color:#dc2626;">ใช้แต้มสะสม E-Stamp (' . (int)$stampDiscount . ' แต้ม):</td><td style="text-align:right;padding:6px 8px;color:#dc2626;">-฿' . number_format((float)$stampDiscount, 2) . '</td></tr>' : '')
          . ((float)str_replace(',', '', $otherDiscount) > 0 ? '<tr><td colspan="2" style="text-align:right;padding:6px 8px;color:#dc2626;">ส่วนลดเพิ่มเติม / คูปอง:</td><td style="text-align:right;padding:6px 8px;color:#dc2626;">-฿' . htmlspecialchars($otherDiscount) . '</td></tr>' : '')
          . '<tr style="background:#ecfdf5;border-top:2px solid #0B3B35;font-size:16px;font-weight:bold;color:#0B3B35;">'
          . '<td colspan="2" style="text-align:right;padding:10px 8px;">ยอดชำระสุทธิ (Grand Total):</td>'
          . '<td style="text-align:right;padding:10px 8px;">฿' . htmlspecialchars($grandTotal) . '</td>'
          . '</tr>'
          . '</tfoot></table>'
          . '<div style="text-align:center;margin-top:22px;">'
          . '<a href="' . htmlspecialchars($adminUrl) . '" style="display:inline-block;background:#0B3B35;color:#ffffff;padding:12px 22px;text-decoration:none;border-radius:8px;font-weight:bold;font-size:14px;margin:4px;">จัดการออเดอร์ในระบบ Admin</a>'
          . '<a href="' . htmlspecialchars($trackUrl) . '" style="display:inline-block;background:#f1f5f9;color:#0f172a;border:1px solid #cbd5e1;padding:12px 22px;text-decoration:none;border-radius:8px;font-weight:bold;font-size:14px;margin:4px;">เปิดลิงก์หน้าสถานะลูกค้า</a>'
          . '</div>'
          . '</div>'
          . '<div style="background:#f8fafc;padding:12px;text-align:center;font-size:11px;color:#64748b;border-top:1px solid #e2e8f0;">ระบบแจ้งเตือนคำสั่งซื้ออัตโนมัติ Yak Pho Aroma (yakpho.com)</div>'
          . '</div></body></html>';

    $lastRes = ['success' => false, 'message' => 'No admin recipients'];
    foreach (array_unique($emailList) as $adminEmail) {
        if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $lastRes = send_resend_email($db, $adminEmail, $subject, $html);
        }
    }
    return $lastRes;
}
