<?php
declare(strict_types=1);

/**
 * ============================================================
 * YakPho Aroma — Smart Address & Customer Auto-Lookup API
 * ============================================================
 * Endpoints:
 * 1. ?action=postcode&code=10510 -> Returns province_id, province_name, subdistricts
 * 2. ?action=check_customer&phone=0800617073 -> Returns customer profile, address, m-stamps
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';

$action = trim((string)($_GET['action'] ?? ($_POST['action'] ?? '')));

// Thailand 2-digit postal prefix fallback mapping to Province ID
$prefixToProvince = [
    '10' => [1, 'กรุงเทพมหานคร'],
    '11' => [2, 'สมุทรปราการ'],
    '12' => [3, 'นนทบุรี'],
    '13' => [4, 'ปทุมธานี'],
    '14' => [5, 'พระนครศรีอยุธยา'],
    '15' => [7, 'ลพบุรี'],
    '16' => [8, 'สิงห์บุรี'],
    '17' => [9, 'ชัยนาท'],
    '18' => [10, 'สระบุรี'],
    '20' => [11, 'ชลบุรี'],
    '21' => [12, 'ระยอง'],
    '22' => [13, 'จันทบุรี'],
    '23' => [14, 'ตราด'],
    '24' => [15, 'ฉะเชิงเทรา'],
    '25' => [16, 'ปราจีนบุรี'],
    '26' => [17, 'นครนายก'],
    '27' => [18, 'สระแก้ว'],
    '30' => [19, 'นครราชสีมา'],
    '31' => [20, 'บุรีรัมย์'],
    '32' => [21, 'สุรินทร์'],
    '33' => [22, 'ศรีสะเกษ'],
    '34' => [23, 'อุบลราชธานี'],
    '35' => [24, 'ยโสธร'],
    '36' => [25, 'ชัยภูมิ'],
    '37' => [26, 'อำนาจเจริญ'],
    '38' => [77, 'บึงกาฬ'],
    '39' => [27, 'หนองบัวลำภู'],
    '40' => [28, 'ขอนแก่น'],
    '41' => [29, 'อุดรธานี'],
    '42' => [30, 'เลย'],
    '43' => [31, 'หนองคาย'],
    '44' => [32, 'มหาสารคาม'],
    '45' => [33, 'ร้อยเอ็ด'],
    '46' => [34, 'กาฬสินธุ์'],
    '47' => [35, 'สกลนคร'],
    '48' => [36, 'นครพนม'],
    '49' => [37, 'มุกดาหาร'],
    '50' => [38, 'เชียงใหม่'],
    '51' => [39, 'ลำพูน'],
    '52' => [40, 'ลำปาง'],
    '53' => [41, 'อุตรดิตถ์'],
    '54' => [42, 'แพร่'],
    '55' => [43, 'น่าน'],
    '56' => [44, 'พะเยา'],
    '57' => [45, 'เชียงราย'],
    '58' => [46, 'แม่ฮ่องสอน'],
    '60' => [47, 'นครสวรรค์'],
    '61' => [48, 'อุทัยธานี'],
    '62' => [49, 'กำแพงเพชร'],
    '63' => [50, 'ตาก'],
    '64' => [51, 'สุโขทัย'],
    '65' => [52, 'พิษณุโลก'],
    '66' => [53, 'พิจิตร'],
    '67' => [54, 'เพชรบูรณ์'],
    '70' => [55, 'ราชบุรี'],
    '71' => [56, 'กาญจนบุรี'],
    '72' => [57, 'สุพรรณบุรี'],
    '73' => [58, 'นครปฐม'],
    '74' => [59, 'สมุทรสาคร'],
    '75' => [60, 'สมุทรสงคราม'],
    '76' => [61, 'เพชรบุรี'],
    '77' => [62, 'ประจวบคีรีขันธ์'],
    '80' => [63, 'นครศรีธรรมราช'],
    '81' => [64, 'กระบี่'],
    '82' => [65, 'พังงา'],
    '83' => [66, 'ภูเก็ต'],
    '84' => [67, 'สุราษฎร์ธานี'],
    '85' => [68, 'ระนอง'],
    '86' => [69, 'ชุมพร'],
    '90' => [70, 'สงขลา'],
    '91' => [71, 'สตูล'],
    '92' => [72, 'ตรัง'],
    '93' => [73, 'พัทลุง'],
    '94' => [74, 'ปัตตานี'],
    '95' => [75, 'ยะลา'],
    '96' => [76, 'นราธิวาส']
];

switch ($action) {
    case 'postcode':
        $code = trim((string)($_GET['code'] ?? ($_POST['code'] ?? '')));
        $cleanCode = preg_replace('/[^0-9]/', '', $code);

        if (strlen($cleanCode) < 5) {
            echo json_encode(['status' => 'error', 'message' => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก']);
            exit;
        }

        $cleanCode = substr($cleanCode, 0, 5);
        $prefix2 = substr($cleanCode, 0, 2);

        // 1. Query existing matching data in addr
        $st = $db->prepare("
            SELECT a.provinces_id, p.name_th as province_name, a.addr_detail2, COUNT(*) as cnt
            FROM addr a
            LEFT JOIN provinces p ON a.provinces_id = p.provinces_id
            WHERE a.addr_postcode = ? AND a.addr_detail2 != '' AND a.addr_detail2 != '.'
            GROUP BY a.provinces_id, a.addr_detail2
            ORDER BY cnt DESC
            LIMIT 12
        ");
        $st->execute([$cleanCode]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $pId = 0;
        $pName = '';
        $suggestions = [];

        if (!empty($rows)) {
            $pId = (int)$rows[0]['provinces_id'];
            $pName = (string)$rows[0]['province_name'];

            $seen = [];
            foreach ($rows as $r) {
                $sub = trim(preg_replace('/\s+/', ' ', (string)$r['addr_detail2']));
                if ($sub !== '' && $sub !== '.' && !isset($seen[$sub])) {
                    $seen[$sub] = true;
                    $suggestions[] = $sub;
                }
            }
        }

        // Fallback to prefix map if not found in addr
        if ($pId === 0 && isset($prefixToProvince[$prefix2])) {
            $pId = $prefixToProvince[$prefix2][0];
            $pName = $prefixToProvince[$prefix2][1];
        }

        echo json_encode([
            'status' => 'success',
            'postcode' => $cleanCode,
            'province_id' => $pId,
            'province_name' => $pName,
            'suggestions' => array_slice($suggestions, 0, 8)
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'check_customer':
        $phone = preg_replace('/[^0-9+]/', '', trim((string)($_GET['phone'] ?? ($_POST['phone'] ?? ''))));
        if (strlen($phone) < 9) {
            echo json_encode(['status' => 'not_found', 'message' => 'Invalid phone number']);
            exit;
        }

        $st = $db->prepare("
            SELECT user_id, user_name, user_lastname, user_nickname, user_mobile, user_tel, user_email,
                   user_address, user_province, user_postcode, user_country, m_stamp_points, user_stars
            FROM user
            WHERE (user_mobile = ? OR user_tel = ? OR user_username = ?) AND user_del = 0
            LIMIT 1
        ");
        $st->execute([$phone, $phone, $phone]);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['status' => 'not_found']);
            exit;
        }

        $userId = (int)$user['user_id'];
        $points = max((int)$user['m_stamp_points'], (int)$user['user_stars']);

        // Check latest address in addr
        $stA = $db->prepare("
            SELECT a.addr_detail, a.addr_detail2, a.addr_postcode, a.provinces_id, a.country_id,
                   p.name_th as province_name, c.country_name
            FROM addr a
            LEFT JOIN provinces p ON a.provinces_id = p.provinces_id
            LEFT JOIN countries c ON a.country_id = c.country_id
            WHERE a.user_id = ? AND a.addr_del = 0
            ORDER BY a.addr_id DESC
            LIMIT 1
        ");
        $stA->execute([$userId]);
        $latestAddr = $stA->fetch(PDO::FETCH_ASSOC);

        $fullName = trim($user['user_name'] . ' ' . $user['user_lastname']) ?: $user['user_name'];

        $addrData = null;
        if ($latestAddr) {
            $addrData = [
                'detail' => $latestAddr['addr_detail'] ?: '',
                'detail2' => $latestAddr['addr_detail2'] ?: '',
                'postcode' => $latestAddr['addr_postcode'] ?: '',
                'province_id' => (int)($latestAddr['provinces_id'] ?: $user['user_province']),
                'province_name' => $latestAddr['province_name'] ?: '',
                'country_id' => (int)($latestAddr['country_id'] ?: 171),
                'country_name' => $latestAddr['country_name'] ?: 'Thailand'
            ];
        } elseif (!empty($user['user_address'])) {
            $addrData = [
                'detail' => $user['user_address'],
                'detail2' => '',
                'postcode' => $user['user_postcode'] ?: '',
                'province_id' => (int)$user['user_province'],
                'province_name' => '',
                'country_id' => 171,
                'country_name' => 'Thailand'
            ];
        }

        echo json_encode([
            'status' => 'success',
            'found' => true,
            'user_id' => $userId,
            'c_code' => 'C' . $userId,
            'name' => $fullName,
            'nickname' => $user['user_nickname'] ?: '',
            'mobile' => $user['user_mobile'] ?: $user['user_tel'],
            'email' => $user['user_email'] ?: '',
            'm_stamp_points' => $points,
            'address' => $addrData
        ], JSON_UNESCAPED_UNICODE);
        exit;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
        exit;
}
