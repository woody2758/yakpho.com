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
                echo json_encode(['status' => 'expired', 'message' => 'VIP link has expired or is invalid. Please request a new link from our staff.']);
                exit;
            }

            // Fetch user info
            $stmtUser = $db->prepare("SELECT user_id, user_name, user_lastname, user_mobile, user_email, user_address, user_province, user_postcode, user_country, user_stars, user_start, role FROM user WHERE user_id = ? AND user_del = 0");
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'Customer profile not found']);
                exit;
            }

            // Fetch primary address (if any)
            $stmtAddr = $db->prepare("SELECT * FROM addr WHERE user_id = ? AND addr_del = 0 ORDER BY addr_type DESC, addr_id DESC LIMIT 1");
            $stmtAddr->execute([$userId]);
            $addr = $stmtAddr->fetch(PDO::FETCH_ASSOC);

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

            // Calculate points: 1 point per 100 THB if user_stars is 0, or user_stars
            $points = (int)($user['user_stars'] > 0 ? $user['user_stars'] : floor($totalSpent / 100));

            // Member since year/date
            $memberSince = !empty($user['user_start']) ? date('d/m/Y', strtotime($user['user_start'])) : (!empty($orders) ? date('d/m/Y', strtotime(end($orders)['orders_date'])) : date('d/m/Y'));

            // Country list for selector
            $countries = $db->query("SELECT country_id, country_name, country_code FROM countries ORDER BY country_name ASC")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'customer' => [
                    'c_code' => 'C' . $user['user_id'],
                    'user_id' => (int)$user['user_id'],
                    'name' => $user['user_name'] ?? '',
                    'lastname' => $user['user_lastname'] ?? '',
                    'mobile' => $user['user_mobile'] ?? ($addr['addr_mobile'] ?? ''),
                    'email' => $user['user_email'] ?? '',
                    'shop_name' => $addr['addr_name'] ?? '',
                    'address' => $addr['addr_detail'] ?? ($user['user_address'] ?? ''),
                    'address2' => $addr['addr_detail2'] ?? '',
                    'postcode' => $addr['addr_postcode'] ?? ($user['user_postcode'] ?? ''),
                    'country_id' => (int)($addr['country_id'] ?? ($user['user_country'] ?? 171)), // 171 = Thailand default
                    'order_count' => $orderCount,
                    'total_spent' => $totalSpent,
                    'points' => $points,
                    'member_since' => $memberSince,
                    'recent_orders' => array_slice($orders, 0, 5),
                ],
                'countries' => $countries
            ]);

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
            echo json_encode(['status' => 'error', 'message' => 'Session expired. Please use a valid VIP link.']);
            exit;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $lastname = trim((string)($_POST['lastname'] ?? ''));
        $mobile = trim((string)($_POST['mobile'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $shopName = trim((string)($_POST['shop_name'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $address2 = trim((string)($_POST['address2'] ?? ''));
        $postcode = trim((string)($_POST['postcode'] ?? ''));
        $countryId = (int)($_POST['country_id'] ?? 171);
        $password = trim((string)($_POST['password'] ?? ''));

        if (empty($name) || empty($mobile)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อและเบอร์โทรศัพท์']);
            exit;
        }

        try {
            $db->beginTransaction();

            // 1. Update user table
            $userSql = "UPDATE user SET user_name = ?, user_lastname = ?, user_mobile = ?, user_email = ?, user_country = ?, user_postcode = ?, updated_at = NOW()";
            $userParams = [$name, $lastname, $mobile, $email, $countryId, $postcode];

            if (!empty($password) && strlen($password) >= 4) {
                $userSql .= ", user_password = ?";
                $userParams[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $userSql .= " WHERE user_id = ?";
            $userParams[] = $userId;

            $stmtUpdUser = $db->prepare($userSql);
            $stmtUpdUser->execute($userParams);

            // 2. Check or update addr table
            $stmtCheckAddr = $db->prepare("SELECT addr_id FROM addr WHERE user_id = ? AND addr_del = 0 ORDER BY addr_type DESC, addr_id DESC LIMIT 1");
            $stmtCheckAddr->execute([$userId]);
            $existingAddrId = $stmtCheckAddr->fetchColumn();

            if ($existingAddrId) {
                $stmtUpdAddr = $db->prepare("
                    UPDATE addr 
                    SET addr_name = ?, addr_mobile = ?, addr_detail = ?, addr_detail2 = ?, addr_postcode = ?, country_id = ?, addr_update = NOW()
                    WHERE addr_id = ?
                ");
                $stmtUpdAddr->execute([$shopName ?: $name, $mobile, $address, $address2, $postcode, $countryId, $existingAddrId]);
            } else {
                $stmtInsAddr = $db->prepare("
                    INSERT INTO addr (user_id, addr_name, addr_mobile, addr_detail, addr_detail2, addr_postcode, country_id, addr_type, addr_status, addr_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, NOW())
                ");
                $stmtInsAddr->execute([$userId, $shopName ?: $name, $mobile, $address, $address2, $postcode, $countryId]);
            }

            // Set session for frontend login
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['c_code'] = 'C' . $userId;

            $db->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'บันทึกข้อมูลสมาชิก VIP สำเร็จเรียบร้อยแล้ว ยินดีต้อนรับสู่ครอบครัว YakPho Aroma!',
                'redirect' => BASE_URL . '/th/products'
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
        break;
}

