<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — LINE OAuth Authentication Controller
 * ============================================================
 * Endpoint: /api/line_auth.php?action=login | callback | disconnect
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/line_helper.php';
require_once __DIR__ . '/../includes/member_helper.php';

$action = $_GET['action'] ?? '';
$redirectUri = ROOT_URL . '/api/line_auth.php?action=callback';
$profileUrl = URL_PATH . '/th/profile';

switch ($action) {
    case 'dev_login':
        // Dev Mock Login for Localhost testing
        $lineUserId = 'U_local_demo_member_001';
        $displayName = 'คุณทดสอบ ระบบ (LINE Demo)';
        $pictureUrl = URL_PATH . '/catalog/assets/img/YakPhoAromaLogo.webp';

        $stmt = $db->prepare("SELECT * FROM user WHERE line_user_id = ? AND user_del = 0 LIMIT 1");
        $stmt->execute([$lineUserId]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $userId = (int)$existingUser['user_id'];
            $memberTier = $existingUser['member_tier'] ?: 'general';
            $points = (int)$existingUser['m_stamp_points'];
            $userName = $existingUser['user_name'] ?: $displayName;
            $userMobile = $existingUser['user_mobile'] ?: '';
            $userEmail = $existingUser['user_email'] ?: '';
            $affiliateCode = $existingUser['affiliate_code'] ?: '';
        } else {
            $ins = $db->prepare("
                INSERT INTO user (
                    user_name, line_user_id, line_display_name, line_picture_url,
                    member_tier, m_stamp_points, role, user_status, user_date, created_at
                ) VALUES (
                    ?, ?, ?, ?,
                    'general', 20, 'customer', 1, NOW(), NOW()
                )
            ");
            $ins->execute([$displayName, $lineUserId, $displayName, $pictureUrl]);
            $userId = (int)$db->lastInsertId();
            $memberTier = 'general';
            $points = 20;
            $userName = $displayName;
            $userMobile = '';
            $userEmail = '';
            $affiliateCode = '';
        }

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_mobile'] = $userMobile;
        $_SESSION['user_email'] = $userEmail;
        $_SESSION['member_tier'] = $memberTier;
        $_SESSION['m_stamp_points'] = $points;
        $_SESSION['affiliate_code'] = $affiliateCode;
        $_SESSION['line_user_id'] = $lineUserId;
        $_SESSION['line_display_name'] = $displayName;
        $_SESSION['line_picture_url'] = $pictureUrl;

        $_SESSION['flash_success'] = '🎉 เข้าสู่ระบบทดสอบสำเร็จ (Demo Member)';
        header('Location: ' . $profileUrl);
        exit;

    case 'login':
        $state = bin2hex(random_bytes(16));
        $_SESSION['line_oauth_state'] = $state;
        $_SESSION['line_redirect_after'] = $_GET['redirect'] ?? $profileUrl;

        $authUrl = get_line_login_url($db, $redirectUri, $state);
        if (empty($authUrl)) {
            // If on localhost or Channel ID is missing, redirect with helper flag
            header('Location: ' . $profileUrl . '?line_unconfigured=1');
            exit;
        }

        header('Location: ' . $authUrl);
        exit;

    case 'callback':
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $savedState = $_SESSION['line_oauth_state'] ?? '';
        unset($_SESSION['line_oauth_state']);

        $returnUrl = $_SESSION['line_redirect_after'] ?? $profileUrl;
        unset($_SESSION['line_redirect_after']);

        if (empty($code) || empty($state) || $state !== $savedState) {
            $_SESSION['flash_error'] = 'การยืนยันตัวตนผ่าน LINE ไม่ถูกต้อง หรือเซสชันหมดอายุ';
            header('Location: ' . $returnUrl);
            exit;
        }

        $res = handle_line_oauth_callback($db, $code, $redirectUri);
        if (!$res['success']) {
            $_SESSION['flash_error'] = 'เข้าสู่ระบบ LINE ไม่สำเร็จ: ' . ($res['message'] ?? 'ข้อผิดพลาดไม่ทราบสาเหตุ');
            header('Location: ' . $returnUrl);
            exit;
        }

        $lineUserId = $res['line_user_id'];
        $displayName = $res['display_name'];
        $pictureUrl = $res['picture_url'];

        // 1. Check if user already exists with this line_user_id
        $stmt = $db->prepare("SELECT * FROM user WHERE line_user_id = ? AND user_del = 0 LIMIT 1");
        $stmt->execute([$lineUserId]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            // Update display name and picture
            $up = $db->prepare("UPDATE user SET line_display_name = ?, line_picture_url = ?, user_logindate = NOW() WHERE user_id = ?");
            $up->execute([$displayName, $pictureUrl, $existingUser['user_id']]);

            $userId = (int)$existingUser['user_id'];
            $memberTier = $existingUser['member_tier'] ?: 'general';
            $points = (int)$existingUser['m_stamp_points'];
            $userName = $existingUser['user_name'] ?: $displayName;
            $userMobile = $existingUser['user_mobile'] ?: '';
            $userEmail = $existingUser['user_email'] ?: '';
            $affiliateCode = $existingUser['affiliate_code'] ?: '';
        } else {
            // If already logged in via web session, bind LINE to this user
            if (!empty($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
                $up = $db->prepare("UPDATE user SET line_user_id = ?, line_display_name = ?, line_picture_url = ? WHERE user_id = ?");
                $up->execute([$lineUserId, $displayName, $pictureUrl, $userId]);

                // Reload user data
                $stmt = $db->prepare("SELECT * FROM user WHERE user_id = ?");
                $stmt->execute([$userId]);
                $uData = $stmt->fetch(PDO::FETCH_ASSOC);

                $memberTier = $uData['member_tier'] ?: 'general';
                $points = (int)$uData['m_stamp_points'];
                $userName = $uData['user_name'] ?: $displayName;
                $userMobile = $uData['user_mobile'] ?: '';
                $userEmail = $uData['user_email'] ?: '';
                $affiliateCode = $uData['affiliate_code'] ?: '';
            } else {
                // Create a new member account
                $ins = $db->prepare("
                    INSERT INTO user (
                        user_name, line_user_id, line_display_name, line_picture_url,
                        member_tier, m_stamp_points, role, user_status, user_date, created_at
                    ) VALUES (
                        ?, ?, ?, ?,
                        'general', 20, 'customer', 1, NOW(), NOW()
                    )
                ");
                $ins->execute([$displayName, $lineUserId, $displayName, $pictureUrl]);
                $userId = (int)$db->lastInsertId();

                // Award welcome points
                award_m_stamp_points($db, $userId, 20, 'welcome_bonus', null, 'แต้มต้อนรับสมาชิกใหม่ผ่าน LINE Login');

                $memberTier = 'general';
                $points = 20;
                $userName = $displayName;
                $userMobile = '';
                $userEmail = '';
                $affiliateCode = '';
            }
        }

        // Sync LINE profile picture to local profile directory if available
        if (!empty($pictureUrl) && $userId > 0) {
            try {
                $profileDir = dirname(__DIR__) . "/uploads/profile/{$userId}";
                if (!is_dir($profileDir)) {
                    @mkdir($profileDir, 0755, true);
                }
                $avatarFile = "user_{$userId}_line.jpg";
                $avatarPath = "{$profileDir}/{$avatarFile}";
                
                $ctx = stream_context_create([
                    "http" => ["header" => "User-Agent: Mozilla/5.0\r\n", "timeout" => 5]
                ]);
                $imgBytes = @file_get_contents($pictureUrl, false, $ctx);
                if ($imgBytes !== false && strlen($imgBytes) > 0) {
                    file_put_contents($avatarPath, $imgBytes);
                    
                    // Update user_picture if not set or was from LINE
                    $pStmt = $db->prepare("SELECT user_picture FROM user WHERE user_id = ?");
                    $pStmt->execute([$userId]);
                    $currentPic = (string)$pStmt->fetchColumn();
                    if (empty($currentPic) || str_contains($currentPic, '_line.')) {
                        $upPic = $db->prepare("UPDATE user SET user_picture = ? WHERE user_id = ?");
                        $upPic->execute([$avatarFile, $userId]);
                    }
                }
            } catch (Throwable $e) {
                error_log("Failed to download LINE avatar: " . $e->getMessage());
            }
        }

        // Store into session
        $_SESSION['user_id']          = $userId;
        $_SESSION['user_name']        = $userName;
        $_SESSION['user_mobile']      = $userMobile;
        $_SESSION['user_email']       = $userEmail;
        $_SESSION['member_tier']      = $memberTier;
        $_SESSION['m_stamp_points']   = $points;
        $_SESSION['line_user_id']     = $lineUserId;
        $_SESSION['line_picture_url'] = $pictureUrl;
        $_SESSION['line_display_name']= $displayName;
        $_SESSION['affiliate_code']   = $affiliateCode;

        $_SESSION['flash_success'] = 'เชื่อมต่อและเข้าสู่ระบบด้วย LINE เรียบร้อยแล้ว';
        header('Location: ' . $returnUrl);
        exit;

    case 'disconnect':
        if (!empty($_SESSION['user_id'])) {
            $up = $db->prepare("UPDATE user SET line_user_id = NULL WHERE user_id = ?");
            $up->execute([$_SESSION['user_id']]);
            unset($_SESSION['line_user_id'], $_SESSION['line_picture_url'], $_SESSION['line_display_name']);
            $_SESSION['flash_success'] = 'ยกเลิกการเชื่อมต่อ LINE สำเร็จ';
        }
        header('Location: ' . $profileUrl);
        exit;

    default:
        header('Location: ' . $profileUrl);
        exit;
}

