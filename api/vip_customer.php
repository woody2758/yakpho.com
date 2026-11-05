<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/frontend_data.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

switch ($action) {
    case 'validate_token':
        $c_code = trim((string)($_GET['c'] ?? ''));
        $token = trim((string)($_GET['token'] ?? ''));
        
        $userId = (int)str_ireplace('C', '', $c_code);
        if ($userId <= 0 || empty($token)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid VIP link parameters']);
            exit;
        }

        try {
            // Verify token in user_tokens
            $stmt = $db->prepare("SELECT id, user_id, expires_at FROM user_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
            $stmt->execute([$userId, $token]);
            $tok = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tok) {
                echo json_encode(['status' => 'expired', 'message' => 'ลิงก์สิทธิพิเศษ VIP นี้หมดอายุแล้ว กรุณาติดต่อแอดมินทาง LINE เพื่อรับลิงก์ใหม่ค่ะ']);
                exit;
            }

            // Fetch user info
            $stmtUser = $db->prepare("SELECT * FROM user WHERE user_id = ? AND user_del = 0 LIMIT 1");
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลสมาชิก']);
                exit;
            }

            // Parse CRM note if JSON
            $crmData = [];
            if (!empty($user['user_note'])) {
                $decoded = json_decode($user['user_note'], true);
                if (is_array($decoded)) {
                    $crmData = $decoded;
                }
            }

            // Fetch order history statistics
            $stmtOrders = $db->prepare("
                SELECT orders_id, orders_no, orders_grandtotal, orders_status, orders_date
                FROM orders 
                WHERE user_id = ? 
                ORDER BY orders_id DESC
            ");
            $stmtOrders->execute([$userId]);
            $orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

            $orderCount = count($orders);
            $totalSpent = 0;
            foreach ($orders as $o) {
                $totalSpent += (float)$o['orders_grandtotal'];
            }

            // Priority: user_stars > m_stamp_points > totalSpent / 100
            $points = (int)($user['user_stars'] ?? 0);
            if (!empty($user['m_stamp_points']) && (int)$user['m_stamp_points'] > $points) {
                $points = (int)$user['m_stamp_points'];
            }
            if ($points <= 0 && $totalSpent > 0) {
                $points = (int)floor($totalSpent / 100);
            }

            // Member since
            $memberSince = !empty($user['user_start']) 
                ? date('d/m/Y', strtotime($user['user_start'])) 
                : (!empty($orders) ? date('d/m/Y', strtotime(end($orders)['orders_date'])) : date('d/m/Y'));

            echo json_encode([
                'status' => 'success',
                'customer' => [
                    'c_code' => 'C' . $user['user_id'],
                    'user_id' => (int)$user['user_id'],
                    'name' => $user['user_name'] ?? '',
                    'lastname' => $user['user_lastname'] ?? '',
                    'nickname' => $user['user_nickname'] ?? '',
                    'birthdate' => !empty($user['user_birthdate']) ? $user['user_birthdate'] : '',
                    'mobile' => $user['user_mobile'] ?? '',
                    'email' => $user['user_email'] ?? '',
                    'line_id' => $user['line_display_name'] ?? ($crmData['line_id'] ?? ''),
                    'shop_name' => $crmData['shop_name'] ?? '',
                    'shop_address' => $crmData['shop_address'] ?? '',
                    'user_google_map' => $user['user_google_map'] ?? ($crmData['user_google_map'] ?? ''),
                    'user_website' => $user['user_website'] ?? ($crmData['user_website'] ?? ''),
                    'business_type' => $crmData['business_type'] ?? '',
                    'favorite_scents' => $crmData['favorite_scents'] ?? [],
                    'feedback' => $crmData['feedback'] ?? '',
                    'already_rewarded' => !empty($crmData['vip_bonus_50_awarded']),
                    'order_count' => $orderCount,
                    'total_spent' => $totalSpent,
                    'points' => $points,
                    'member_since' => $memberSince,
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'update_profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
            exit;
        }

        $c_code = trim((string)($_POST['c'] ?? ''));
        $token = trim((string)($_POST['token'] ?? ''));
        $userId = (int)str_ireplace('C', '', $c_code);

        if ($userId <= 0 || empty($token)) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized request']);
            exit;
        }

        // Validate token
        $stmtTok = $db->prepare("SELECT id FROM user_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW()");
        $stmtTok->execute([$userId, $token]);
        if (!$stmtTok->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'ลิงก์สิทธิพิเศษ VIP นี้หมดอายุแล้ว กรุณาติดต่อแอดมินทาง LINE เพื่อรับลิงก์ใหม่ค่ะ']);
            exit;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $lastname = trim((string)($_POST['lastname'] ?? ''));
        $nickname = trim((string)($_POST['nickname'] ?? ''));
        $birthdate = trim((string)($_POST['birthdate'] ?? ''));
        $lineId = trim((string)($_POST['line_id'] ?? ''));
        $shopName = trim((string)($_POST['shop_name'] ?? ''));
        $shopAddress = trim((string)($_POST['shop_address'] ?? ''));
        $googleMapUrl = trim((string)($_POST['user_google_map'] ?? ''));
        $websiteUrl = trim((string)($_POST['user_website'] ?? ''));
        $businessType = trim((string)($_POST['business_type'] ?? ''));
        $favoriteScents = trim((string)($_POST['favorite_scents'] ?? ''));
        $feedback = trim((string)($_POST['feedback'] ?? ''));

        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อของท่าน']);
            exit;
        }

        try {
            $db->beginTransaction();

            // Fetch current user note and points
            $stCheck = $db->prepare("SELECT * FROM user WHERE user_id = ? LIMIT 1");
            $stCheck->execute([$userId]);
            $uRow = $stCheck->fetch(PDO::FETCH_ASSOC);

            $crmData = [];
            if (!empty($uRow['user_note'])) {
                $decoded = json_decode($uRow['user_note'], true);
                if (is_array($decoded)) {
                    $crmData = $decoded;
                }
            }

            // Update CRM fields
            $crmData['nickname'] = $nickname;
            $crmData['birthdate'] = $birthdate;
            $crmData['line_id'] = $lineId;
            $crmData['shop_name'] = $shopName;
            $crmData['shop_address'] = $shopAddress;
            $crmData['user_google_map'] = $googleMapUrl;
            $crmData['user_website'] = $websiteUrl;
            $crmData['business_type'] = $businessType;
            $crmData['favorite_scents'] = array_filter(array_map('trim', explode(',', $favoriteScents)));
            $crmData['feedback'] = $feedback;
            $crmData['updated_at'] = date('Y-m-d H:i:s');

            // 50 E-Stamp Incentive Logic:
            $awardedBonus = false;
            if (empty($crmData['vip_bonus_50_awarded'])) {
                $crmData['vip_bonus_50_awarded'] = true;
                $crmData['vip_bonus_awarded_at'] = date('Y-m-d H:i:s');
                $crmData['vip_bonus_amount'] = 50;
                $awardedBonus = true;

                // Award +50 points safely to user_stars (and m_stamp_points if exists)
                try {
                    $stmtAddPts = $db->prepare("
                        UPDATE user 
                        SET user_stars = user_stars + 50,
                            m_stamp_points = m_stamp_points + 50
                        WHERE user_id = ?
                    ");
                    $stmtAddPts->execute([$userId]);
                } catch (Throwable $e) {
                    $stmtAddPts = $db->prepare("
                        UPDATE user 
                        SET user_stars = user_stars + 50
                        WHERE user_id = ?
                    ");
                    $stmtAddPts->execute([$userId]);
                }
            }

            // Save user profile
            $jsonNote = json_encode($crmData, JSON_UNESCAPED_UNICODE);
            $parsedBirthdate = !empty($birthdate) ? date('Y-m-d', strtotime($birthdate)) : null;

            // Update core guaranteed columns
            $stmtUpd = $db->prepare("
                UPDATE user 
                SET user_name = ?,
                    user_lastname = ?,
                    user_note = ?
                WHERE user_id = ?
            ");
            $stmtUpd->execute([
                $name,
                $lastname,
                $jsonNote,
                $userId
            ]);

            // Safely update optional columns if they exist in schema
            if (!empty($nickname)) {
                try { $db->prepare("UPDATE user SET user_nickname = ? WHERE user_id = ?")->execute([$nickname, $userId]); } catch (Throwable $e) {}
            }
            if (!empty($parsedBirthdate)) {
                try { $db->prepare("UPDATE user SET user_birthdate = ? WHERE user_id = ?")->execute([$parsedBirthdate, $userId]); } catch (Throwable $e) {}
            }
            if (!empty($lineId)) {
                try { $db->prepare("UPDATE user SET line_display_name = ? WHERE user_id = ?")->execute([$lineId, $userId]); } catch (Throwable $e) {}
            }
            if ($googleMapUrl !== '') {
                try { $db->prepare("UPDATE user SET user_google_map = ? WHERE user_id = ?")->execute([$googleMapUrl, $userId]); } catch (Throwable $e) {}
            }
            if ($websiteUrl !== '') {
                try { $db->prepare("UPDATE user SET user_website = ? WHERE user_id = ?")->execute([$websiteUrl, $userId]); } catch (Throwable $e) {}
            }
            // Mark user_vip = 1 to indicate VIP profile completion
            try { $db->prepare("UPDATE user SET user_vip = 1 WHERE user_id = ?")->execute([$userId]); } catch (Throwable $e) {}

            // Set login session
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['c_code'] = 'C' . $userId;

            // Fetch new total points
            $stNewPts = $db->prepare("SELECT user_stars FROM user WHERE user_id = ? LIMIT 1");
            $stNewPts->execute([$userId]);
            $nPts = $stNewPts->fetch(PDO::FETCH_ASSOC);
            $finalPoints = (int)$nPts['user_stars'];

            $db->commit();

            echo json_encode([
                'status' => 'success',
                'awarded_bonus' => $awardedBonus,
                'bonus_points' => 50,
                'new_total_points' => $finalPoints,
                'message' => $awardedBonus 
                    ? '🎉 ยินดีด้วยค่ะ! ท่านได้รับ +50 E-Stamp สะสมพิเศษเรียบร้อยแล้วค่ะ' 
                    : '✓ บันทึกข้อมูลสมาชิก VIP เรียบร้อยแล้วค่ะ',
                'redirect' => BASE_URL . '/th/products'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
        break;
}
