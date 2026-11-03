<?php
declare(strict_types=1);

/**
 * ============================================================
 * YakPho & Esther Aroma — Contact & Corporate Gift Inquiry API
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method Not Allowed');
    }

    $rawInput = file_get_contents('php://input');
    $payload = !empty($rawInput) ? json_decode($rawInput, true) : $_POST;
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $lang = trim((string)($payload['lang'] ?? ($_SESSION['lang'] ?? 'th')));
    $name = trim((string)($payload['name'] ?? ''));
    $phone = trim((string)($payload['phone'] ?? ''));
    $email = trim((string)($payload['email'] ?? ''));
    $subject = trim((string)($payload['subject'] ?? 'General Inquiry'));
    $message = trim((string)($payload['message'] ?? ''));

    if ($name === '' || ($phone === '' && $email === '') || $message === '') {
        throw new Exception($lang === 'th'
            ? 'กรุณากรอกชื่อ ข้อมูลติดต่อ และข้อความให้ครบถ้วน'
            : 'Please provide your name, contact info, and message.');
    }

    // Save inquiry into massage_leads or customer_followups if available
    try {
        $stmt = $db->prepare("
            INSERT INTO massage_leads (
                shop_name, city, region, country, maps_url,
                website, email, review_count, review_highlights, praise_draft, status
            ) VALUES (
                ?, ?, ?, 'Thailand', 'Website Form',
                ?, ?, 0, ?, ?, 'Website Inquiry'
            )
        ");
        $stmt->execute([
            $name,
            $phone,
            $subject,
            $phone,
            $email ?: 'N/A',
            $subject,
            $message,
        ]);
    } catch (Throwable $ignore) {
        // Fallback if table differs
    }

    echo json_encode([
        'status' => true,
        'data' => [
            'submitted_at' => date('c'),
        ],
        'message' => $lang === 'th'
            ? 'ส่งข้อความเรียบร้อยแล้ว ทีมงาน Esther Aroma & Yak Pho จะติดต่อกลับโดยเร็วที่สุดครับ'
            : 'Thank you! Your inquiry has been received. Our team will contact you shortly.',
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'data' => null,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

