<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../../includes/member_helper.php";
require_once __DIR__ . "/../../includes/line_helper.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $userId = (int)($data['user_id'] ?? 0);
    $newTier = trim((string)($data['member_tier'] ?? 'general'));
    $pointsAdj = (int)($data['points_adjustment'] ?? 0);
    $adjNote = trim((string)($data['adjustment_note'] ?? ''));
    $sendLine = !empty($data['send_line']);

    if ($userId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid User ID']);
        exit;
    }

    // Validate tier
    $allowedTiers = ['general', 'vip_spa', 'therapist_partner', 'oem_export'];
    if (!in_array($newTier, $allowedTiers, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid Member Tier']);
        exit;
    }

    // Update tier
    $up = $db->prepare("UPDATE user SET member_tier = ?, user_update = NOW() WHERE user_id = ?");
    $up->execute([$newTier, $userId]);

    // If partner tier, ensure affiliate record exists
    if ($newTier === 'therapist_partner') {
        register_as_therapist_partner($db, $userId);
    }

    // Adjust points if requested
    if ($pointsAdj > 0) {
        award_m_stamp_points($db, $userId, $pointsAdj, 'admin_adjustment', null, $adjNote ?: 'Admin manual adjustment');
    } elseif ($pointsAdj < 0) {
        deduct_m_stamp_points($db, $userId, abs($pointsAdj), 'admin_adjustment', null, $adjNote ?: 'Admin manual adjustment');
    }

    // Fetch updated user info
    $stmt = $db->prepare("SELECT user_id, user_name, member_tier, m_stamp_points, line_user_id FROM user WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $tierInfo = get_member_tier_info($newTier);

    // Optional LINE push notification
    $lineSent = false;
    if ($sendLine && !empty($user['line_user_id'])) {
        $lineMsg = "🌿 แจ้งเตือนสิทธิประโยชน์สมาชิก Yak Pho Aroma\n"
                 . "คุณ {$user['user_name']}\n"
                 . "ระดับสมาชิก: {$tierInfo['badge']} {$tierInfo['name']}\n"
                 . "แต้มสะสม M-Stamp: " . number_format((int)$user['m_stamp_points']) . " แต้ม (ใช้ลดได้ ฿" . number_format((int)$user['m_stamp_points']) . ")\n"
                 . (!empty($adjNote) ? "หมายเหตุ: {$adjNote}\n" : "")
                 . "เข้าสู่ระบบ: " . ROOT_URL . "/th/profile";
        $lineRes = send_line_push_message($db, $user['line_user_id'], $lineMsg);
        $lineSent = ($lineRes['success'] ?? false);
    }

    http_response_code(200);
    echo json_encode([
        'success'      => true,
        'message'      => "อัปเดตระดับและแต้มของ C{$userId} สำเร็จเรียบร้อยแล้ว",
        'user_id'      => $userId,
        'member_tier'  => $newTier,
        'tier_name'    => $tierInfo['badge'] . ' ' . $tierInfo['short_name'],
        'tier_badge'   => ($newTier === 'vip_spa' ? 'badge-tier-vip' : ($newTier === 'therapist_partner' ? 'badge-tier-partner' : ($newTier === 'oem_export' ? 'badge-tier-oem' : 'badge-tier-general'))),
        'm_stamp_points' => (int)$user['m_stamp_points'],
        'line_sent'    => $lineSent,
    ]);
    exit;

} catch (Throwable $e) {
    error_log('[API Error update_member_tier.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}

