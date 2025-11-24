<?php
// API: Add Customer with Address
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['name', 'lastname', 'mobile', 'addr_detail', 'addr_detail2', 'postcode', 'province_id'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "กรุณากรอก {$field}"]);
        exit;
    }
}

// Extract data
$name = trim($input['name']);
$lastname = trim($input['lastname']);
$nickname = trim($input['nickname'] ?? '');
$mobileRaw = $input['mobile'];
$mobile = preg_replace('/[^0-9]/', '', $mobileRaw);
$email = trim($input['email'] ?? '');
$addr_detail = trim($input['addr_detail']);
$addr_detail2 = trim($input['addr_detail2']);
$postcode = trim($input['postcode']);
$province_id = (int)$input['province_id'];
$addr_forword = trim($input['addr_forword'] ?? '');
$addr_type = trim($input['addr_type'] ?? 'ที่บ้าน');
$source_page = $input['source_page'] ?? '';

// Validate mobile length (9-10 digits)
if (strlen($mobile) < 9 || strlen($mobile) > 10) {
    echo json_encode(['success' => false, 'message' => 'เบอร์โทรศัพท์ไม่ถูกต้อง']);
    exit;
}

// Validate email if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'อีเมลไม่ถูกต้อง']);
    exit;
}

try {
    // Check for existing user by mobile or email
    $sql = "SELECT * FROM user WHERE user_mobile = ?";
    $params = [$mobile];
    if (!empty($email)) {
        $sql .= " OR user_email = ?";
        $params[] = $email;
    }
    $sql .= " LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_user) {
        $user_id = $existing_user['user_id'];
        $user_name = $existing_user['user_name'] . ' ' . $existing_user['user_lastname'];
        $_SESSION['customer_id'] = $user_id;
        $redirect = ($source_page === 'users')
            ? ADMIN_URL . '/users/index.php?search=' . $mobile
            : ADMIN_URL . '/orders/';
        echo json_encode([
            'success'   => true,
            'duplicate' => true,
            'message'   => "มีลูกค้าชื่อ \"{$user_name}\" อยู่ในระบบแล้ว",
            'user'      => $existing_user,
            'redirect'  => $redirect
        ]);
        exit;
    }

    // Insert new user
    $stmt = $db->prepare(
        "INSERT INTO user (user_name, user_lastname, user_nickname, user_mobile, user_email, role, user_status, user_start) " .
        "VALUES (?, ?, ?, ?, ?, 'customer', 1, NOW())"
    );
    $result = $stmt->execute([$name, $lastname, $nickname, $mobile, $email]);
    if (!$result) {
        throw new Exception('Failed to insert user: ' . implode(', ', $stmt->errorInfo()));
    }
    $user_id = $db->lastInsertId();
    $is_new_user = true;

    // Insert address
    $addr_name = $name . ' ' . $lastname;
    $stmt = $db->prepare(
        "INSERT INTO addr (addr_name, addr_mobile, addr_detail, addr_detail2, addr_postcode, provinces_id, addr_forword, addr_type, addr_status, addr_del, user_id, addr_date, save_id) " .
        "VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, NOW(), ?)"
    );
    $result = $stmt->execute([
        $addr_name,
        $mobile,
        $addr_detail,
        $addr_detail2,
        $postcode,
        $province_id,
        $addr_forword,
        $addr_type,
        $user_id,
        $_SESSION['admin_id']
    ]);
    if (!$result) {
        throw new Exception('Failed to insert address: ' . implode(', ', $stmt->errorInfo()));
    }
    $addr_id = $db->lastInsertId();

    $_SESSION['customer_id'] = $user_id;
    $redirect = ($source_page === 'users') ? '' : ADMIN_URL . '/orders/';

    echo json_encode([
        'success'      => true,
        'message'      => $is_new_user ? 'เพิ่มลูกค้าใหม่สำเร็จ' : 'พบลูกค้าในระบบและเพิ่มที่อยู่ใหม่สำเร็จ',
        'user_id'      => $user_id,
        'addr_id'      => $addr_id,
        'is_new_user'  => $is_new_user,
        'redirect'     => $redirect
    ]);
} catch (Exception $e) {
    error_log('Add customer error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
