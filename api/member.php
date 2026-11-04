<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — Member Operations API (Layer 1 Strict JSON API)
 * Follows md/AGENT.md: Strict JSON, HTTP Status Codes, Try-Catch
 * ============================================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/resend_helper.php';
require_once __DIR__ . '/../includes/member_helper.php';

try {
    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);
    $payload = is_array($jsonInput) ? $jsonInput : $_POST;

    $action = (string)($_GET['action'] ?? $payload['action'] ?? '');
    $userId = (int)($_SESSION['user_id'] ?? 0);

    switch ($action) {
        case 'bind_email':
            if ($userId <= 0) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณาเข้าสู่ระบบก่อนทำการผูกอีเมล']);
                exit;
            }

            $email = trim((string)($payload['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(422);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'รูปแบบอีเมลไม่ถูกต้อง']);
                exit;
            }

            // Generate verification token
            $token = bin2hex(random_bytes(20));
            $verifyUrl = ROOT_URL . '/api/member.php?action=verify_email&token=' . $token . '&uid=' . $userId;

            // Save email and token
            $stmt = $db->prepare("UPDATE user SET user_email = ?, user_key = ? WHERE user_id = ?");
            $stmt->execute([$email, $token, $userId]);
            $_SESSION['user_email'] = $email;

            // User display name
            $name = (string)($_SESSION['user_name'] ?? 'ลูกค้าคนสำคัญ');

            // Send Email via Resend
            $res = send_templated_email($db, $email, 'email_verification_welcome', [
                'customer_name' => $name,
                'verify_url'    => $verifyUrl,
                'm_stamp_bonus' => '20',
            ]);

            http_response_code(200);
            echo json_encode([
                'status'  => true,
                'data'    => ['email' => $email],
                'message' => $res['success'] 
                    ? 'ระบบส่งลิงก์ยืนยันไปยังอีเมล ' . $email . ' เรียบร้อยแล้ว กรุณาตรวจสอบกล่องจดหมาย'
                    : 'บันทึกอีเมลเรียบร้อยแล้ว' . (!empty($res['message']) ? ' (หมายเหตุระบบอีเมล: ' . $res['message'] . ')' : ''),
            ]);
            exit;

        case 'verify_email':
            // Direct email link click: redirect to presentation layer view (NO HTML output from API)
            $token = trim((string)($_GET['token'] ?? ''));
            $uid = (int)($_GET['uid'] ?? 0);

            if (!empty($token) && $uid > 0) {
                $stmt = $db->prepare("SELECT user_id, email_verified, user_stars FROM user WHERE user_id = ? AND user_key = ?");
                $stmt->execute([$uid, $token]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($u) {
                    $up = $db->prepare("UPDATE user SET email_verified = 1, user_key = NULL WHERE user_id = ?");
                    $up->execute([$uid]);

                    if (empty($u['email_verified'])) {
                        award_m_stamp_points($db, $uid, 20, 'welcome_bonus', null, 'โบนัสยืนยันอีเมลสำเร็จ');
                    }

                    $_SESSION['flash_success'] = '🎉 ยืนยันอีเมลสำเร็จ! ได้รับโบนัส 20 M-Stamp เรียบร้อยแล้ว';
                    header('Location: ' . URL_PATH . '/th/profile?verified=1');
                    exit;
                }
            }

            $_SESSION['flash_error'] = 'ลิงก์ยืนยันอีเมลไม่ถูกต้องหรือหมดอายุแล้ว';
            header('Location: ' . URL_PATH . '/th/profile?error=invalid_token');
            exit;

        case 'apply_affiliate':
            if ($userId <= 0) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณาเข้าสู่ระบบก่อนสมัครตัวแทน']);
                exit;
            }

            $res = register_as_therapist_partner($db, $userId);
            if ($res['success']) {
                $_SESSION['member_tier'] = 'therapist_partner';
                $_SESSION['affiliate_code'] = $res['affiliate_code'];

                http_response_code(200);
                echo json_encode([
                    'status'  => true,
                    'data'    => ['affiliate_code' => $res['affiliate_code']],
                    'message' => 'ยินดีต้อนรับสู่ Yak Pho Partner! ลิงก์และ QR Code ประจำตัวของคุณพร้อมใช้งานแล้ว',
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['status' => false, 'data' => null, 'message' => $res['message']]);
            }
            exit;

        case 'get_member_summary':
            if ($userId <= 0) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'Unauthorized']);
                exit;
            }

            $stmt = $db->prepare("SELECT * FROM user WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(404);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'User not found']);
                exit;
            }

            $user['member_tier']       = $user['member_tier'] ?? 'general';
            $user['user_stars']        = (int)($user['user_stars'] ?? $user['m_stamp_points'] ?? 0);
            $user['m_stamp_points']    = $user['user_stars'];
            $user['line_user_id']      = $user['line_user_id'] ?? '';
            $user['line_display_name'] = $user['line_display_name'] ?? '';
            $user['line_picture_url']  = $user['line_picture_url'] ?? '';
            $user['affiliate_code']    = $user['affiliate_code'] ?? '';
            $user['email_verified']    = (int)($user['email_verified'] ?? 0);

            $affiliateStats = null;
            if ($user['member_tier'] === 'therapist_partner' && !empty($user['affiliate_code'])) {
                try {
                    $affStmt = $db->prepare("SELECT total_shops, total_commission FROM affiliates WHERE affiliate_code = ?");
                    $affStmt->execute([$user['affiliate_code']]);
                    $affiliateStats = $affStmt->fetch(PDO::FETCH_ASSOC);
                } catch (Throwable $e) {}
            }

            http_response_code(200);
            echo json_encode([
                'status'  => true,
                'data'    => [
                    'user'            => $user,
                    'affiliate_stats' => $affiliateStats,
                    'tier_info'       => get_member_tier_info($user['member_tier'] ?: 'general'),
                ],
                'message' => 'Success',
            ]);
            exit;

        case 'save_address':
            if ($userId <= 0) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณาเข้าสู่ระบบก่อนทำการบันทึกที่อยู่']);
                exit;
            }

            $addrName     = trim((string)($payload['addr_name'] ?? ''));
            $addrMobile   = trim((string)($payload['addr_mobile'] ?? ''));
            $addrDetail   = trim((string)($payload['addr_detail'] ?? ''));
            $addrPostcode = trim((string)($payload['addr_postcode'] ?? ''));

            if ($addrName === '' || $addrMobile === '' || $addrDetail === '') {
                http_response_code(422);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณากรอกชื่อผู้รับ เบอร์โทรศัพท์ และที่อยู่จัดส่งให้ครบถ้วน']);
                exit;
            }

            // Update user table
            $stU = $db->prepare("UPDATE user SET user_name = ?, user_mobile = ?, user_address = ?, user_postcode = ?, user_update = NOW() WHERE user_id = ?");
            $stU->execute([$addrName, $addrMobile, $addrDetail, $addrPostcode, $userId]);
            $_SESSION['user_name'] = $addrName;
            $_SESSION['user_mobile'] = $addrMobile;

            // Check if user already has an address record in addr table
            $checkAddr = $db->prepare("SELECT addr_id FROM addr WHERE user_id = ? AND addr_del = 0 ORDER BY addr_status DESC, addr_id DESC LIMIT 1");
            $checkAddr->execute([$userId]);
            $existingAddrId = $checkAddr->fetchColumn();

            if ($existingAddrId) {
                $stA = $db->prepare("UPDATE addr SET addr_name = ?, addr_mobile = ?, addr_detail = ?, addr_postcode = ?, addr_update = NOW() WHERE addr_id = ?");
                $stA->execute([$addrName, $addrMobile, $addrDetail, $addrPostcode, $existingAddrId]);
            } else {
                $stA = $db->prepare("INSERT INTO addr (addr_name, addr_mobile, addr_detail, addr_postcode, country_id, provinces_id, addr_status, addr_del, addr_type, user_id, addr_date, addr_update) VALUES (?, ?, ?, ?, 171, 1, 1, 0, 1, ?, NOW(), NOW())");
                $stA->execute([$addrName, $addrMobile, $addrDetail, $addrPostcode, $userId]);
            }

            http_response_code(200);
            echo json_encode([
                'status'  => true,
                'data'    => [
                    'addr_name'     => $addrName,
                    'addr_mobile'   => $addrMobile,
                    'addr_detail'   => $addrDetail,
                    'addr_postcode' => $addrPostcode,
                ],
                'message' => 'บันทึกที่อยู่จัดส่งเรียบร้อยแล้ว',
            ]);
            exit;

        case 'logout':
            if (isset($_COOKIE['yakpho_member_remember'])) {
                setcookie('yakpho_member_remember', '', [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }

            // Secure Session Hardening & Destruction per md/AGENT.md
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            session_start();
            session_regenerate_id(true);

            http_response_code(200);
            echo json_encode([
                'status'  => true,
                'data'    => null,
                'message' => 'ออกจากระบบเรียบร้อยแล้ว',
            ]);
            exit;

        case 'login':
            $loginUser = trim((string)($payload['username'] ?? ''));
            $loginPass = trim((string)($payload['password'] ?? ''));

            if (empty($loginUser) || empty($loginPass)) {
                http_response_code(422);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณากรอกชื่อผู้ใช้/เบอร์โทร และรหัสผ่าน']);
                exit;
            }

            $stmt = $db->prepare("
                SELECT *
                FROM user
                WHERE (user_username = ? OR user_mobile = ? OR user_tel = ? OR user_email = ?) AND user_del = 0
                LIMIT 1
            ");
            $stmt->execute([$loginUser, $loginUser, $loginUser, $loginUser]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$u) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'ไม่พบบัญชีผู้ใช้นี้ในระบบ']);
                exit;
            }

            $passOk = false;
            if (!empty($u['user_password'])) {
                if (password_verify($loginPass, $u['user_password'])) {
                    $passOk = true;
                } elseif (md5($loginPass) === $u['user_password'] || sha1($loginPass) === $u['user_password'] || $loginPass === $u['user_password']) {
                    $passOk = true;
                    try {
                        $upHash = $db->prepare("UPDATE user SET user_password = ? WHERE user_id = ?");
                        $upHash->execute([password_hash($loginPass, PASSWORD_DEFAULT), $u['user_id']]);
                    } catch (Exception $e) {}
                }
            }

            if (!$passOk) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'รหัสผ่านไม่ถูกต้อง']);
                exit;
            }

            // Regenerate session ID for security
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_regenerate_id(true);
            }

            $uName = !empty($u['user_name']) ? $u['user_name'] : (!empty($u['user_username']) ? $u['user_username'] : 'คุณลูกค้า');

            $_SESSION['user_id']           = (int)$u['user_id'];
            $_SESSION['user_name']         = $uName;
            $_SESSION['user_username']     = $u['user_username'] ?? '';
            $_SESSION['user_email']        = $u['user_email'] ?? '';
            $_SESSION['user_mobile']       = !empty($u['user_mobile']) ? $u['user_mobile'] : ($u['user_tel'] ?? '');
            $_SESSION['member_tier']       = $u['member_tier'] ?? 'general';
            $_SESSION['m_stamp_points']    = (int)($u['user_stars'] ?? ($u['m_stamp_points'] ?? 0));
            $_SESSION['user_stars']        = $_SESSION['m_stamp_points'];
            $_SESSION['line_user_id']      = $u['line_user_id'] ?? '';
            $_SESSION['line_display_name'] = $u['line_display_name'] ?? '';
            $_SESSION['line_picture_url']  = $u['line_picture_url'] ?? '';
            $_SESSION['affiliate_code']    = $u['affiliate_code'] ?? '';

            // Remember Me 1 year
            $rememberMe = !empty($payload['remember_me']);
            if ($rememberMe) {
                $secretKey = 'yakpho_member_secret_' . ($u['user_password'] ?? '');
                $cookieVal = base64_encode($u['user_id'] . ':' . hash_hmac('sha256', (string)$u['user_id'], $secretKey));
                setcookie('yakpho_member_remember', $cookieVal, [
                    'expires'  => time() + (365 * 86400),
                    'path'     => '/',
                    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }

            http_response_code(200);
            echo json_encode([
                'status'  => true,
                'data'    => ['user_id' => $u['user_id'], 'name' => $_SESSION['user_name']],
                'message' => 'เข้าสู่ระบบสำเร็จ ยินดีต้อนรับคุณ ' . $_SESSION['user_name'],
            ]);
            exit;

        default:
            http_response_code(400);
            echo json_encode(['status' => false, 'data' => null, 'message' => 'Invalid action']);
            exit;
    }
} catch (Throwable $e) {
    error_log('[API Error api/member.php] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'status'  => false,
        'data'    => null,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
    exit;
}
