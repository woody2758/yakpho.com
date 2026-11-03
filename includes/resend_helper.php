<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — Resend Email Service Helper
 * ============================================================
 * Handles sending transactional emails via Resend API (https://resend.com)
 * with template loading and merge tags substitution.
 */

if (!function_exists('get_site_setting')) {
    function get_site_setting(PDO $db, string $key, string $default = ''): string {
        try {
            $stmt = $db->prepare("SELECT setting_value FROM sitesettings WHERE setting_name = ? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false && $val !== null ? (string)$val : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

/**
 * Send raw email using Resend API
 */
function send_resend_email(PDO $db, string $to, string $subject, string $htmlContent, string $textFallback = ''): array {
    $apiKey = trim(get_site_setting($db, 'resend_api_key'));
    $from = trim(get_site_setting($db, 'resend_from_email', 'Yak Pho Aroma <orders@yakpho.com>'));

    if (empty($apiKey)) {
        return [
            'success' => false,
            'message' => 'Resend API Key is not configured in settings.',
        ];
    }

    $payload = [
        'from'    => $from,
        'to'      => [$to],
        'subject' => $subject,
        'html'    => $htmlContent,
    ];

    if (!empty($textFallback)) {
        $payload['text'] = $textFallback;
    }

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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return [
            'success' => false,
            'message' => 'cURL Error: ' . $curlErr,
        ];
    }

    $resData = json_decode((string)$response, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'id'      => $resData['id'] ?? '',
            'message' => 'Email sent successfully',
        ];
    }

    return [
        'success' => false,
        'http_code' => $httpCode,
        'message' => $resData['message'] ?? 'Failed to send email via Resend API',
    ];
}

/**
 * Send email using a template code from resend_email_templates
 */
function send_templated_email(PDO $db, string $to, string $templateCode, array $mergeTags = []): array {
    try {
        $stmt = $db->prepare("SELECT email_subject, email_body_html, is_active FROM resend_email_templates WHERE template_code = ? LIMIT 1");
        $stmt->execute([$templateCode]);
        $tmpl = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tmpl || empty($tmpl['is_active'])) {
            return [
                'success' => false,
                'message' => "Template '{$templateCode}' not found or inactive.",
            ];
        }

        $subject = $tmpl['email_subject'];
        $body = $tmpl['email_body_html'];

        foreach ($mergeTags as $tag => $val) {
            $placeholder = '{' . trim($tag, '{}') . '}';
            $subject = str_replace($placeholder, (string)$val, $subject);
            $body = str_replace($placeholder, (string)$val, $body);
        }

        return send_resend_email($db, $to, $subject, $body);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage(),
        ];
    }
}

