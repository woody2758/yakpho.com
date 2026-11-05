<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — LINE Messaging API Webhook Endpoint
 * ============================================================
 * URL: https://yakpho.com/api/line_webhook.php
 * Automatically captures LINE Group IDs (C...) or Room IDs (R...)
 * when the LINE OA bot is invited to an Admin LINE Group or when
 * an admin types "#groupid" / "#ผูกกลุ่มแอดมิน" in the group.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/line_helper.php';

header('Content-Type: application/json; charset=utf-8');

$rawBody = file_get_contents('php://input');
if (empty($rawBody)) {
    echo json_encode(['status' => 'ok', 'message' => 'LINE Webhook Active']);
    exit;
}

$data = json_decode($rawBody, true);
if (!is_array($data) || empty($data['events'])) {
    echo json_encode(['status' => 'ok']);
    exit;
}

$channelAccessToken = trim(get_site_setting($db, 'line_channel_access_token'));

/**
 * Helper to reply to a LINE event using replyToken
 */
function line_reply_message(string $accessToken, string $replyToken, string $text): void {
    if (empty($accessToken) || empty($replyToken)) {
        return;
    }
    $payload = [
        'replyToken' => $replyToken,
        'messages'   => [
            [
                'type' => 'text',
                'text' => $text,
            ],
        ],
    ];
    $ch = curl_init('https://api.line.me/v2/bot/message/reply');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Helper to append a target ID (Group/Room/User) into admin_line_user_id setting
 */
function register_admin_line_target(PDO $db, string $targetId): void {
    if (empty($targetId)) {
        return;
    }
    try {
        $existing = trim(get_site_setting($db, 'admin_line_user_id', ''));
        $parts = array_filter(array_map('trim', explode(',', $existing)));
        if (!in_array($targetId, $parts, true)) {
            $parts[] = $targetId;
        }
        $updated = implode(',', array_unique($parts));
        $stmt = $db->prepare("INSERT INTO sitesettings (setting_name, setting_value, created_at, updated_at) VALUES ('admin_line_user_id', ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        $stmt->execute([$updated]);
    } catch (Throwable $e) {}
}

foreach ($data['events'] as $event) {
    $type       = $event['type'] ?? '';
    $replyToken = $event['replyToken'] ?? '';
    $source     = $event['source'] ?? [];
    $sourceType = $source['type'] ?? '';
    $groupId    = $source['groupId'] ?? ($source['roomId'] ?? '');
    $userId     = $source['userId'] ?? '';

    // 1. When bot is invited into a LINE Group or Multi-person Room
    if ($type === 'join' && !empty($groupId)) {
        register_admin_line_target($db, $groupId);
        $replyText = "✅ เชื่อมต่อบอท Yak Pho Aroma เข้ากลุ่มเรียบร้อยแล้ว!\n"
                   . "📌 รหัสกลุ่ม (Group ID): {$groupId}\n"
                   . "ระบบได้บันทึกรหัสกลุ่มนี้ไว้รับแจ้งเตือนคำสั่งซื้อใหม่และสลิปโอนเงินอัตโนมัติแล้วครับ";
        line_reply_message($channelAccessToken, $replyToken, $replyText);
        continue;
    }

    // 2. When a user types a command inside a Group or 1-on-1 chat
    if ($type === 'message' && ($event['message']['type'] ?? '') === 'text') {
        $msgText = trim((string)($event['message']['text'] ?? ''));
        $cmd     = mb_strtolower($msgText, 'UTF-8');

        if (in_array($cmd, ['#id', '#groupid', '#ผูกกลุ่ม', '#ผูกกลุ่มแอดมิน', 'รหัสกลุ่ม'], true)) {
            if (!empty($groupId)) {
                register_admin_line_target($db, $groupId);
                $replyText = "✅ ผูกกลุ่มแจ้งเตือน Admin สำเร็จ!\n"
                           . "📌 Group ID: {$groupId}\n"
                           . "เมื่อมีออเดอร์ใหม่หรือการแนบสลิปบนเว็บ ระบบจะแจ้งเตือนเข้ากลุ่มนี้ทันทีครับ";
                line_reply_message($channelAccessToken, $replyToken, $replyText);
            } elseif (!empty($userId)) {
                $replyText = "📌 รหัส LINE User ID ของคุณคือ:\n{$userId}\n(สามารถนำไปใส่ในช่อง Admin LINE User ID ที่หน้าตั้งค่าเว็บไซต์ได้เลยครับ)";
                line_reply_message($channelAccessToken, $replyToken, $replyText);
            }
        }
    }
}

echo json_encode(['status' => 'ok']);

