<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — LINE Login & Messaging API Helper
 * ============================================================
 * Handles LINE Social Login OAuth, Push Messages, Multicast & Broadcasts
 */

require_once __DIR__ . '/resend_helper.php';

/**
 * Generate LINE Login Authorization URL
 */
function get_line_login_url(PDO $db, string $redirectUri, string $state): string {
    $channelId = trim(get_site_setting($db, 'line_login_channel_id'));
    if (empty($channelId)) {
        return '';
    }

    $params = [
        'response_type' => 'code',
        'client_id'     => $channelId,
        'redirect_uri'  => $redirectUri,
        'state'         => $state,
        'scope'         => 'profile openid email',
        'bot_prompt'    => 'normal',
    ];

    return 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query($params);
}

/**
 * Exchange code for LINE token and fetch user profile
 */
function handle_line_oauth_callback(PDO $db, string $code, string $redirectUri): array {
    $channelId = trim(get_site_setting($db, 'line_login_channel_id'));
    $channelSecret = trim(get_site_setting($db, 'line_login_channel_secret'));

    if (empty($channelId) || empty($channelSecret)) {
        return ['success' => false, 'message' => 'LINE Login credentials are not configured.'];
    }

    // 1. Exchange authorization code for access token
    $ch = curl_init('https://api.line.me/oauth2/v2.1/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'client_id'     => $channelId,
            'client_secret' => $channelSecret,
        ]),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $tokenRes = curl_exec($ch);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['success' => false, 'message' => 'LINE token cURL error: ' . $curlErr];
    }

    $tokenData = json_decode((string)$tokenRes, true);
    $accessToken = $tokenData['access_token'] ?? '';
    if (empty($accessToken)) {
        return ['success' => false, 'message' => $tokenData['error_description'] ?? 'Failed to retrieve access token from LINE'];
    }

    // 2. Fetch User Profile
    $ch2 = curl_init('https://api.line.me/v2/profile');
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $profRes = curl_exec($ch2);
    curl_close($ch2);

    $profile = json_decode((string)$profRes, true);
    $lineUserId = $profile['userId'] ?? '';
    if (empty($lineUserId)) {
        return ['success' => false, 'message' => 'Failed to fetch user profile from LINE'];
    }

    return [
        'success'      => true,
        'line_user_id' => $lineUserId,
        'display_name' => $profile['displayName'] ?? '',
        'picture_url'  => $profile['pictureUrl'] ?? '',
        'status_msg'   => $profile['statusMessage'] ?? '',
    ];
}

/**
 * Send 1-on-1 Push Notification to a user via LINE Messaging API
 */
function send_line_push_message(PDO $db, string $lineUserId, string $textMessage): array {
    $channelAccessToken = trim(get_site_setting($db, 'line_channel_access_token'));
    if (empty($channelAccessToken)) {
        return ['success' => false, 'message' => 'LINE Channel Access Token is not configured.'];
    }

    $payload = [
        'to' => $lineUserId,
        'messages' => [
            [
                'type' => 'text',
                'text' => $textMessage,
            ],
        ],
    ];

    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $channelAccessToken,
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'success'   => ($httpCode === 200),
        'http_code' => $httpCode,
        'response'  => json_decode((string)$res, true),
    ];
}

/**
 * Send Multicast Notification to up to 500 users at once
 */
function send_line_multicast(PDO $db, array $lineUserIds, string $textMessage): array {
    $channelAccessToken = trim(get_site_setting($db, 'line_channel_access_token'));
    if (empty($channelAccessToken)) {
        return ['success' => false, 'message' => 'LINE Channel Access Token is not configured.'];
    }

    $lineUserIds = array_values(array_filter(array_unique($lineUserIds)));
    if (empty($lineUserIds)) {
        return ['success' => false, 'message' => 'No recipient LINE User IDs provided.'];
    }

    $chunks = array_chunk($lineUserIds, 500);
    $totalSent = 0;

    foreach ($chunks as $chunk) {
        $payload = [
            'to' => $chunk,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $textMessage,
                ],
            ],
        ];

        $ch = curl_init('https://api.line.me/v2/bot/message/multicast');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $channelAccessToken,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $totalSent += count($chunk);
        }
    }

    return [
        'success'   => ($totalSent > 0),
        'sent_count'=> $totalSent,
    ];
}

/**
 * Targeted Broadcast by Member Tier
 * targets: 'all', 'general', 'vip_spa', 'therapist_partner', 'oem_export'
 */
function broadcast_to_member_tier(PDO $db, string $tier, string $messageText, int $adminUserId = 0): array {
    if ($tier === 'all') {
        $stmt = $db->query("SELECT line_user_id FROM user WHERE line_user_id IS NOT NULL AND line_user_id != '' AND user_del = 0");
    } else {
        $stmt = $db->prepare("SELECT line_user_id FROM user WHERE member_tier = ? AND line_user_id IS NOT NULL AND line_user_id != '' AND user_del = 0");
        $stmt->execute([$tier]);
    }

    $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $count = count($userIds);

    if ($count === 0) {
        return [
            'success' => false,
            'message' => 'ไม่พบสมาชิกที่มีการเชื่อมต่อ LINE ในกลุ่มนี้',
            'count'   => 0,
        ];
    }

    $sendRes = send_line_multicast($db, $userIds, $messageText);

    // Log the broadcast
    try {
        $logStmt = $db->prepare("
            INSERT INTO line_broadcast_logs (target_tier, message_text, recipient_count, sent_by_user_id, sent_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([$tier, $messageText, $sendRes['sent_count'] ?? 0, $adminUserId]);
    } catch (Exception $e) {
        // ignore log error
    }

    return [
        'success'    => $sendRes['success'],
        'sent_count' => $sendRes['sent_count'] ?? 0,
        'recipients' => $sendRes['sent_count'] ?? 0,
        'message'    => "ส่งข้อความสำเร็จไปยัง {$sendRes['sent_count']} บัญชี LINE",
    ];
}

/**
 * Alias for broadcast_to_member_tier (used by admin/settings/line_broadcast.php)
 */
if (!function_exists('broadcast_line_message')) {
    function broadcast_line_message(PDO $db, string $tier, string $messageText, int $adminUserId = 0): array {
        return broadcast_to_member_tier($db, $tier, $messageText, $adminUserId);
    }
}

/**
 * Send LINE Push Notification to all configured Admin / Staff LINE accounts
 * Collects recipients from:
 *  1) sitesettings: `admin_line_user_id` (comma-separated LINE User IDs or Group IDs)
 *  2) `user` table where `role IN ('owner', 'admin', 'manager', 'staff')` and `line_user_id` is linked
 *  3) `user` table where `user_email = 'thaiherbfc@gmail.com'` and `line_user_id` is linked
 */
function send_admin_line_notification(PDO $db, string $textMessage): array {
    $recipientIds = [];

    // 1. Check sitesettings for explicit admin_line_user_id (can be U... user IDs or C... group IDs)
    $configuredIds = trim(get_site_setting($db, 'admin_line_user_id', ''));
    if ($configuredIds !== '') {
        $parts = preg_split('/[\s,;]+/', $configuredIds);
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $recipientIds[] = $p;
            }
        }
    }

    // 2. Also fetch any admin/owner/staff user accounts in `user` table that have linked their LINE account
    try {
        $stmt = $db->query("
            SELECT DISTINCT line_user_id
            FROM user
            WHERE line_user_id IS NOT NULL
              AND line_user_id != ''
              AND user_del = 0
              AND (
                  role IN ('owner', 'admin', 'manager', 'staff')
                  OR user_email = 'thaiherbfc@gmail.com'
              )
        ");
        if ($stmt) {
            while ($lid = $stmt->fetchColumn()) {
                $lid = trim((string)$lid);
                if ($lid !== '') {
                    $recipientIds[] = $lid;
                }
            }
        }
    } catch (Throwable $e) {}

    $recipientIds = array_values(array_unique($recipientIds));
    if (empty($recipientIds)) {
        return [
            'success' => false,
            'message' => 'ยังไม่มีบัญชี LINE ของ Admin ที่ผูกไว้ในระบบ',
        ];
    }

    // Separate Group/Room IDs (start with C or R) from User IDs (start with U)
    $userIds = [];
    $groupIds = [];
    foreach ($recipientIds as $id) {
        if (strpos($id, 'U') === 0 && strlen($id) > 15 && strpos($id, 'U_local_') !== 0) {
            $userIds[] = $id;
        } elseif ((strpos($id, 'C') === 0 || strpos($id, 'R') === 0) && strlen($id) > 15) {
            $groupIds[] = $id;
        }
    }

    $sentCount = 0;
    if (count($userIds) === 1) {
        $r = send_line_push_message($db, $userIds[0], $textMessage);
        if (!empty($r['success'])) {
            $sentCount++;
        }
    } elseif (count($userIds) > 1) {
        $r = send_line_multicast($db, $userIds, $textMessage);
        $sentCount += (int)($r['sent_count'] ?? 0);
    }

    foreach ($groupIds as $gid) {
        $r = send_line_push_message($db, $gid, $textMessage);
        if (!empty($r['success'])) {
            $sentCount++;
        }
    }

    return [
        'success'    => ($sentCount > 0),
        'sent_count' => $sentCount,
        'recipients' => $recipientIds,
    ];
}


