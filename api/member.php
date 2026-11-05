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

        case 'get_addresses':
            if ($userId <= 0) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณาเข้าสู่ระบบ']);
                exit;
            }
            $stList = $db->prepare("
                SELECT a.*, p.name_th AS province_name
                FROM addr a
                LEFT JOIN provinces p ON a.provinces_id = p.provinces_id
                WHERE a.user_id = ? AND a.addr_del = 0
                ORDER BY a.addr_position DESC, CASE WHEN a.addr_type = 1 THEN 0 ELSE 1 END, a.addr_id DESC
            ");
            $stList->execute([$userId]);
            $addresses = $stList->fetchAll(PDO::FETCH_ASSOC) ?: [];
            http_response_code(200);
            echo json_encode([
                'status'  => true,
                'data'    => ['addresses' => $addresses],
                'message' => 'Success'
            ]);
            exit;

        case 'save_address':
            if ($userId <= 0) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณาเข้าสู่ระบบก่อนทำการบันทึกที่อยู่']);
                exit;
            }

            $addrId       = (int)($payload['addr_id'] ?? 0);
            $addrName     = trim((string)($payload['addr_name'] ?? ''));
            $addrMobile   = trim((string)($payload['addr_mobile'] ?? ''));
            $addrDetail   = trim((string)($payload['addr_detail'] ?? ''));
            $addrDetail2  = trim((string)($payload['addr_detail2'] ?? ''));
            $provincesId  = (int)($payload['provinces_id'] ?? 1);
            if ($provincesId <= 0) $provincesId = 1;
            $addrPostcode = trim((string)($payload['addr_postcode'] ?? ''));
            $addrType     = (int)($payload['addr_type'] ?? 1);
            if ($addrType < 1 || $addrType > 6) $addrType = 1;
            $addrTaxId    = trim((string)($payload['addr_tax_id'] ?? ''));
            $addrForword  = trim((string)($payload['addr_forword'] ?? ''));
            $isDefault    = !empty($payload['is_default']) ? 1 : 0;

            if ($addrName === '' || $addrMobile === '' || $addrDetail === '') {
                http_response_code(422);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณากรอกชื่อผู้รับ เบอร์โทรศัพท์ และที่อยู่จัดส่งให้ครบถ้วน']);
                exit;
            }

            // Check how many active addresses the user currently has
            $cntStmt = $db->prepare("SELECT COUNT(*) FROM addr WHERE user_id = ? AND addr_del = 0");
            $cntStmt->execute([$userId]);
            $activeCount = (int)$cntStmt->fetchColumn();
            if ($activeCount === 0) {
                $isDefault = 1;
            }

            if ($isDefault === 1) {
                $db->prepare("UPDATE addr SET addr_position = 0 WHERE user_id = ?")->execute([$userId]);
            }

            if ($addrId > 0) {
                // Verify ownership
                $chk = $db->prepare("SELECT addr_id, addr_position FROM addr WHERE addr_id = ? AND user_id = ? AND addr_del = 0 LIMIT 1");
                $chk->execute([$addrId, $userId]);
                $ownRow = $chk->fetch(PDO::FETCH_ASSOC);
                if ($ownRow) {
                    $finalPos = ($isDefault === 1) ? 1 : (int)($ownRow['addr_position'] ?? 0);
                    $stA = $db->prepare("
                        UPDATE addr
                        SET addr_name = ?, addr_mobile = ?, addr_detail = ?, addr_detail2 = ?,
                            provinces_id = ?, addr_postcode = ?, addr_type = ?, addr_position = ?,
                            addr_tax_id = ?, addr_forword = ?, addr_update = NOW(), update_id = ?
                        WHERE addr_id = ? AND user_id = ?
                    ");
                    $stA->execute([
                        $addrName, $addrMobile, $addrDetail, $addrDetail2,
                        $provincesId, $addrPostcode, $addrType, $finalPos,
                        $addrTaxId, $addrForword, $userId,
                        $addrId, $userId
                    ]);
                } else {
                    $addrId = 0;
                }
            }

            if ($addrId <= 0) {
                $stA = $db->prepare("
                    INSERT INTO addr (
                        addr_name, addr_mobile, addr_detail, addr_detail2, addr_postcode,
                        country_id, provinces_id, addr_status, addr_del, addr_type, addr_position,
                        addr_tax_id, addr_forword, user_id, save_id, update_id, addr_date, addr_update
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        171, ?, 1, 0, ?, ?,
                        ?, ?, ?, ?, ?, NOW(), NOW()
                    )
                ");
                $stA->execute([
                    $addrName, $addrMobile, $addrDetail, $addrDetail2, $addrPostcode,
                    $provincesId, $addrType, $isDefault,
                    $addrTaxId, $addrForword, $userId, $userId, $userId
                ]);
                $addrId = (int)$db->lastInsertId();
            }

            // Ensure at least one address has addr_position = 1
            $stDef = $db->prepare("SELECT a.*, p.name_th AS province_name FROM addr a LEFT JOIN provinces p ON a.provinces_id = p.provinces_id WHERE a.user_id = ? AND a.addr_del = 0 ORDER BY a.addr_position DESC, a.addr_id DESC LIMIT 1");
            $stDef->execute([$userId]);
            $primaryAddr = $stDef->fetch(PDO::FETCH_ASSOC);
            if ($primaryAddr) {
                if ((int)($primaryAddr['addr_position'] ?? 0) !== 1) {
                    $db->prepare("UPDATE addr SET addr_position = 1 WHERE addr_id = ?")->execute([(int)$primaryAddr['addr_id']]);
                    $primaryAddr['addr_position'] = 1;
                }
                $combinedDetail = trim($primaryAddr['addr_detail'] . (!empty($primaryAddr['addr_detail2']) ? (' ' . $primaryAddr['addr_detail2']) : ''));
                $stU = $db->prepare("UPDATE user SET user_address = ?, user_postcode = ?, user_province = ?, user_update = NOW() WHERE user_id = ?");
                $stU->execute([$combinedDetail, $primaryAddr['addr_postcode'] ?? '', (string)($primaryAddr['provinces_id'] ?? '1'), $userId]);
            }

            // Return updated address list
            $stList = $db->prepare("
                SELECT a.*, p.name_th AS province_name
                FROM addr a
                LEFT JOIN provinces p ON a.provinces_id = p.provinces_id
                WHERE a.user_id = ? AND a.addr_del = 0
                ORDER BY a.addr_position DESC, CASE WHEN a.addr_type = 1 THEN 0 ELSE 1 END, a.addr_id DESC
            ");
            $stList->execute([$userId]);
            $allAddresses = $stList->fetchAll(PDO::FETCH_ASSOC) ?: [];

            http_response_code(200);
            echo json_encode([
                'status'  => true,
                'data'    => [
                    'addr_id'         => $addrId,
                    'primary_address' => $primaryAddr,
                    'addresses'       => $allAddresses,
                ],
                'message' => 'บันทึกที่อยู่จัดส่งเรียบร้อยแล้ว',
            ]);
            exit;

        case 'set_default_address':
            if ($userId <= 0) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณาเข้าสู่ระบบ']);
                exit;
            }
            $addrId = (int)($payload['addr_id'] ?? ($_GET['addr_id'] ?? 0));
            if ($addrId <= 0) {
                http_response_code(422);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'ไม่พบรหัสที่อยู่']);
                exit;
            }

            $chk = $db->prepare("SELECT * FROM addr WHERE addr_id = ? AND user_id = ? AND addr_del = 0 LIMIT 1");
            $chk->execute([$addrId, $userId]);
            $targetRow = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$targetRow) {
                http_response_code(404);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'ไม่พบที่อยู่นี้ในระบบ']);
                exit;
            }

            $db->prepare("UPDATE addr SET addr_position = 0 WHERE user_id = ?")->execute([$userId]);
            $db->prepare("UPDATE addr SET addr_position = 1, addr_update = NOW() WHERE addr_id = ? AND user_id = ?")->execute([$addrId, $userId]);

            $combinedDetail = trim($targetRow['addr_detail'] . (!empty($targetRow['addr_detail2']) ? (' ' . $targetRow['addr_detail2']) : ''));
            $db->prepare("UPDATE user SET user_address = ?, user_postcode = ?, user_province = ?, user_update = NOW() WHERE user_id = ?")
               ->execute([$combinedDetail, $targetRow['addr_postcode'] ?? '', (string)($targetRow['provinces_id'] ?? '1'), $userId]);

            $stList = $db->prepare("
                SELECT a.*, p.name_th AS province_name
                FROM addr a
                LEFT JOIN provinces p ON a.provinces_id = p.provinces_id
                WHERE a.user_id = ? AND a.addr_del = 0
                ORDER BY a.addr_position DESC, CASE WHEN a.addr_type = 1 THEN 0 ELSE 1 END, a.addr_id DESC
            ");
            $stList->execute([$userId]);
            $allAddresses = $stList->fetchAll(PDO::FETCH_ASSOC) ?: [];

            http_response_code(200);
            echo json_encode([
                'status'  => true,
                'data'    => [
                    'primary_address' => $allAddresses[0] ?? null,
                    'addresses'       => $allAddresses,
                ],
                'message' => 'ตั้งเป็นที่อยู่หลักเรียบร้อยแล้ว',
            ]);
            exit;

        case 'delete_address':
            if ($userId <= 0) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณาเข้าสู่ระบบ']);
                exit;
            }
            $addrId = (int)($payload['addr_id'] ?? ($_GET['addr_id'] ?? 0));
            if ($addrId <= 0) {
                http_response_code(422);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'ไม่พบรหัสที่อยู่']);
                exit;
            }

            $db->prepare("UPDATE addr SET addr_del = 1, addr_position = 0, addr_update = NOW() WHERE addr_id = ? AND user_id = ?")
               ->execute([$addrId, $userId]);

            // Promote next address to primary if needed
            $stList = $db->prepare("
                SELECT a.*, p.name_th AS province_name
                FROM addr a
                LEFT JOIN provinces p ON a.provinces_id = p.provinces_id
                WHERE a.user_id = ? AND a.addr_del = 0
                ORDER BY a.addr_position DESC, CASE WHEN a.addr_type = 1 THEN 0 ELSE 1 END, a.addr_id DESC
            ");
            $stList->execute([$userId]);
            $allAddresses = $stList->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (!empty($allAddresses)) {
                $hasPrimary = false;
                foreach ($allAddresses as $ua) {
                    if ((int)($ua['addr_position'] ?? 0) === 1) {
                        $hasPrimary = true;
                        break;
                    }
                }
                if (!$hasPrimary) {
                    $firstId = (int)$allAddresses[0]['addr_id'];
                    $db->prepare("UPDATE addr SET addr_position = 1 WHERE addr_id = ?")->execute([$firstId]);
                    $allAddresses[0]['addr_position'] = 1;
                }
                $pRow = $allAddresses[0];
                $combinedDetail = trim($pRow['addr_detail'] . (!empty($pRow['addr_detail2']) ? (' ' . $pRow['addr_detail2']) : ''));
                $db->prepare("UPDATE user SET user_address = ?, user_postcode = ?, user_province = ?, user_update = NOW() WHERE user_id = ?")
                   ->execute([$combinedDetail, $pRow['addr_postcode'] ?? '', (string)($pRow['provinces_id'] ?? '1'), $userId]);
            }

            http_response_code(200);
            echo json_encode([
                'status'  => true,
                'data'    => [
                    'primary_address' => $allAddresses[0] ?? null,
                    'addresses'       => $allAddresses,
                ],
                'message' => 'ลบที่อยู่จัดส่งเรียบร้อยแล้ว',
            ]);
            exit;

        case 'save_store_profile':
            if ($userId <= 0) {
                http_response_code(401);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'กรุณาเข้าสู่ระบบก่อนบันทึกข้อมูลร้าน']);
                exit;
            }

            $nickname       = trim((string)($payload['nickname'] ?? ''));
            $birthdate      = trim((string)($payload['birthdate'] ?? ''));
            $shopName       = trim((string)($payload['shop_name'] ?? ''));
            $shopAddress    = trim((string)($payload['shop_address'] ?? ''));
            $googleMapUrl   = trim((string)($payload['user_google_map'] ?? ''));
            $websiteUrl     = trim((string)($payload['user_website'] ?? ''));
            $businessType   = trim((string)($payload['business_type'] ?? ''));
            $favScentsRaw   = $payload['favorite_scents'] ?? [];
            $favoriteScents = is_array($favScentsRaw)
                ? array_values(array_filter(array_map('trim', $favScentsRaw)))
                : array_values(array_filter(array_map('trim', explode(',', (string)$favScentsRaw))));
            $feedback       = trim((string)($payload['feedback'] ?? ''));

            $db->beginTransaction();

            $stCheck = $db->prepare("SELECT * FROM user WHERE user_id = ? LIMIT 1");
            $stCheck->execute([$userId]);
            $uRow = $stCheck->fetch(PDO::FETCH_ASSOC);

            if (!$uRow) {
                $db->rollBack();
                http_response_code(404);
                echo json_encode(['status' => false, 'data' => null, 'message' => 'ไม่พบข้อมูลสมาชิก']);
                exit;
            }

            $crmData = [];
            if (!empty($uRow['user_note'])) {
                $decoded = json_decode((string)$uRow['user_note'], true);
                if (is_array($decoded)) {
                    $crmData = $decoded;
                }
            }

            $crmData['nickname']        = $nickname;
            $crmData['birthdate']       = $birthdate;
            $crmData['shop_name']       = $shopName;
            $crmData['shop_address']    = $shopAddress;
            $crmData['user_google_map'] = $googleMapUrl;
            $crmData['user_website']    = $websiteUrl;
            $crmData['business_type']   = $businessType;
            $crmData['favorite_scents'] = $favoriteScents;
            $crmData['feedback']        = $feedback;
            $crmData['updated_at']      = date('Y-m-d H:i:s');

            // Award +50 M-Stamp bonus when store info & Google Maps / interactive profile is completed
            $awardedBonus = false;
            $isProfileCompleteForBonus = ($shopName !== '' || $shopAddress !== '' || $businessType !== '') && ($googleMapUrl !== '' || !empty($favoriteScents) || $nickname !== '');

            if (empty($crmData['vip_bonus_50_awarded']) && $isProfileCompleteForBonus) {
                $crmData['vip_bonus_50_awarded'] = true;
                $crmData['vip_bonus_awarded_at'] = date('Y-m-d H:i:s');
                $crmData['vip_bonus_amount'] = 50;
                $awardedBonus = true;

                if (function_exists('award_m_stamp_points')) {
                    award_m_stamp_points($db, $userId, 50, 'vip_store_profile_bonus', null, 'โบนัสกรอกข้อมูลร้านและแผนที่ Google Maps ครบถ้วน (+50 M-Stamp)');
                } else {
                    $db->prepare("UPDATE user SET user_stars = COALESCE(user_stars, 0) + 50, m_stamp_points = COALESCE(m_stamp_points, 0) + 50 WHERE user_id = ?")->execute([$userId]);
                }
            }

            $jsonNote = json_encode($crmData, JSON_UNESCAPED_UNICODE);
            $parsedBirthdate = !empty($birthdate) ? date('Y-m-d', strtotime($birthdate)) : null;

            $stmtUpd = $db->prepare("
                UPDATE user
                SET user_nickname   = ?,
                    user_birthdate  = ?,
                    user_google_map = ?,
                    user_website    = ?,
                    user_note       = ?,
                    user_vip        = 1,
                    user_update     = NOW()
                WHERE user_id = ?
            ");
            $stmtUpd->execute([
                $nickname !== '' ? $nickname : ($uRow['user_nickname'] ?? null),
                $parsedBirthdate ?: ($uRow['user_birthdate'] ?? null),
                $googleMapUrl,
                $websiteUrl,
                $jsonNote,
                $userId
            ]);

            $stPts = $db->prepare("SELECT user_stars, m_stamp_points FROM user WHERE user_id = ? LIMIT 1");
            $stPts->execute([$userId]);
            $ptsRow = $stPts->fetch(PDO::FETCH_ASSOC);
            $newPoints = max((int)($ptsRow['user_stars'] ?? 0), (int)($ptsRow['m_stamp_points'] ?? 0));
            $_SESSION['m_stamp_points'] = $newPoints;
            $_SESSION['user_stars'] = $newPoints;

            $db->commit();

            http_response_code(200);
            echo json_encode([
                'status'  => true,
                'data'    => [
                    'awarded_bonus'    => $awardedBonus,
                    'bonus_points'     => 50,
                    'new_total_points' => $newPoints,
                    'store_profile'    => [
                        'nickname'             => $nickname,
                        'birthdate'            => $birthdate,
                        'shop_name'            => $shopName,
                        'shop_address'         => $shopAddress,
                        'user_google_map'      => $googleMapUrl,
                        'user_website'         => $websiteUrl,
                        'business_type'        => $businessType,
                        'favorite_scents'      => $favoriteScents,
                        'feedback'             => $feedback,
                        'vip_bonus_50_awarded' => !empty($crmData['vip_bonus_50_awarded']),
                    ],
                ],
                'message' => $awardedBonus
                    ? '🎉 ยินดีด้วยค่ะ! บันทึกข้อมูลร้านและแผนที่เรียบร้อย ท่านได้รับโบนัสพิเศษ +50 M-Stamp ทันที!'
                    : 'บันทึกข้อมูลร้านและสิทธิพิเศษเรียบร้อยแล้วค่ะ',
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
