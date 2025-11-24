<?php
// api/affiliate_register.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

/**
 * POST: fullname, phone, email, country
 * If logged-in, use session user_id. Otherwise just register affiliate without user binding.
 */
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { $input = $_POST; }

$fullname = isset($input['fullname']) ? trim($input['fullname']) : '';
$phone    = isset($input['phone']) ? trim($input['phone']) : '';
$email    = isset($input['email']) ? trim($input['email']) : '';
$country  = isset($input['country']) ? trim($input['country']) : '';

if ($fullname === '' || $phone === '') {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อและเบอร์โทร'], JSON_UNESCAPED_UNICODE);
  exit;
}

// use session user_id if exists
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// generate affiliate code (YK + random 6)
$code = 'YK' . strtoupper(bin2hex(random_bytes(3)));

// insert affiliate
$stmt = $pdo->prepare("INSERT INTO affiliates (user_id, affiliate_code, country, referral_link, status, created_at)
                       VALUES (:user_id, :code, :country, :link, 'pending', NOW())");
$link = 'https://yakpho.com/?ref=' . $code;
$stmt->execute([
  ':user_id' => $user_id,
  ':code'    => $code,
  ':country' => $country,
  ':link'    => $link
]);

echo json_encode([
  'status' => 'ok',
  'affiliate_code' => $code,
  'referral_link'  => $link
], JSON_UNESCAPED_UNICODE);