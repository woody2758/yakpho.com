<?php
declare(strict_types=1);

/**
 * ============================================================
 * Yak Pho Aroma — Member, M-Stamp & Therapist Affiliate Helper
 * ============================================================
 */

require_once __DIR__ . '/line_helper.php';
require_once __DIR__ . '/resend_helper.php';

/**
 * Get human-readable tier information with Badge Symbol
 */
function get_member_tier_info(string $tier): array {
    $tiers = [
        'general' => [
            'code'        => 'general',
            'name'        => 'สมาชิกทั่วไป (General Member)',
            'short_name'  => 'General',
            'badge'       => '🌿',
            'badge_class' => 'tier-general',
            'color'       => '#16a34a',
            'desc'        => 'ลูกค้าปลีก สะสมแต้ม M-Stamp ทุกยอดซื้อ',
        ],
        'vip_spa' => [
            'code'        => 'vip_spa',
            'name'        => 'VIP Spa Member (ร้านนวด & สปา)',
            'short_name'  => 'VIP Spa',
            'badge'       => '👑',
            'badge_class' => 'tier-vip-spa',
            'color'       => '#d97706',
            'desc'        => 'เรทราคาส่งโรงงาน 1 กก. (฿590 / ฿490)',
        ],
        'therapist_partner' => [
            'code'        => 'therapist_partner',
            'name'        => 'Yak Pho Partner (หมอนวด Affiliate)',
            'short_name'  => 'Partner',
            'badge'       => '⭐',
            'badge_class' => 'tier-partner',
            'color'       => '#0c3b2e',
            'desc'        => 'รับค่าคอมมิชชัน 10-15% จากการแนะนำลูกค้า',
        ],
        'oem_export' => [
            'code'        => 'oem_export',
            'name'        => 'OEM & Export Member',
            'short_name'  => 'OEM & Export',
            'badge'       => '💎',
            'badge_class' => 'tier-oem',
            'color'       => '#2563eb',
            'desc'        => 'สั่งผลิตล็อตใหญ่ 100 กก.+ เอกสารส่งออกครบวงจร',
        ],
    ];

    return $tiers[$tier] ?? $tiers['general'];
}

/**
 * Get or Award M-Stamp Points (Safe for nested DB transactions)
 */
function award_m_stamp_points(PDO $db, int $userId, int $points, string $actionType, ?int $referenceId = null, string $note = ''): int {
    if ($points <= 0 || $userId <= 0) {
        return 0;
    }

    $isOwner = false;
    if (!$db->inTransaction()) {
        $db->beginTransaction();
        $isOwner = true;
    }

    try {
        // Fetch current points
        $stmt = $db->prepare("SELECT m_stamp_points FROM user WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $current = (int)$stmt->fetchColumn();

        $newBalance = $current + $points;

        $updateStmt = $db->prepare("UPDATE user SET m_stamp_points = ? WHERE user_id = ?");
        $updateStmt->execute([$newBalance, $userId]);

        $logStmt = $db->prepare("
            INSERT INTO member_points_log (user_id, points_change, balance_after, action_type, reference_id, note, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([$userId, $points, $newBalance, $actionType, $referenceId, $note]);

        if ($isOwner) {
            $db->commit();
        }
        return $newBalance;
    } catch (Exception $e) {
        if ($isOwner) {
            $db->rollBack();
        }
        throw $e;
    }
}

/**
 * Deduct M-Stamp Points (Safe for nested DB transactions)
 */
function deduct_m_stamp_points(PDO $db, int $userId, int $points, ?int $referenceId = null, string $note = ''): bool {
    if ($points <= 0 || $userId <= 0) {
        return false;
    }

    $isOwner = false;
    if (!$db->inTransaction()) {
        $db->beginTransaction();
        $isOwner = true;
    }

    try {
        $stmt = $db->prepare("SELECT m_stamp_points FROM user WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $current = (int)$stmt->fetchColumn();

        if ($current < $points) {
            if ($isOwner) {
                $db->rollBack();
            }
            return false;
        }

        $newBalance = $current - $points;

        $updateStmt = $db->prepare("UPDATE user SET m_stamp_points = ? WHERE user_id = ?");
        $updateStmt->execute([$newBalance, $userId]);

        $logStmt = $db->prepare("
            INSERT INTO member_points_log (user_id, points_change, balance_after, action_type, reference_id, note, created_at)
            VALUES (?, ?, ?, 'order_discount', ?, ?, NOW())
        ");
        $logStmt->execute([$userId, -$points, $newBalance, $referenceId, $note]);

        if ($isOwner) {
            $db->commit();
        }
        return true;
    } catch (Exception $e) {
        if ($isOwner) {
            $db->rollBack();
        }
        throw $e;
    }
}

/**
 * Track Affiliate Referral Cookie (?ref=CODE)
 */
function track_affiliate_visit(PDO $db): void {
    if (!empty($_GET['ref'])) {
        $refCode = strtoupper(trim(preg_replace('/[^A-Za-z0-9_-]/', '', (string)$_GET['ref'])));
        if (!empty($refCode)) {
            // Verify code in DB
            $stmt = $db->prepare("SELECT affiliate_id FROM affiliates WHERE affiliate_code = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$refCode]);
            if ($stmt->fetchColumn()) {
                // Set cookie for 30 days
                setcookie('yp_affiliate', $refCode, [
                    'expires'  => time() + (86400 * 30),
                    'path'     => '/',
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]);
            }
        }
    }
}

/**
 * Record Affiliate Commission for an order
 * Default 10% commission
 */
function process_order_affiliate_commission(PDO $db, int $orderId, float $orderTotal, string $affiliateCode): ?float {
    if (empty($affiliateCode) || $orderTotal <= 0) {
        return null;
    }

    try {
        $stmt = $db->prepare("
            SELECT a.affiliate_id, a.user_id, u.line_user_id, u.user_name 
            FROM affiliates a
            LEFT JOIN user u ON a.user_id = u.user_id
            WHERE a.affiliate_code = ? AND a.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$affiliateCode]);
        $aff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$aff) {
            return null;
        }

        $affiliateId = (int)$aff['affiliate_id'];
        $commissionRate = 0.10; // 10%
        $commissionAmount = round($orderTotal * $commissionRate, 2);

        // Check if commission already recorded for this order
        $checkStmt = $db->prepare("SELECT commission_id FROM affiliate_commissions WHERE orders_id = ? LIMIT 1");
        $checkStmt->execute([$orderId]);
        if ($checkStmt->fetchColumn()) {
            return null; // Already processed
        }

        // Insert commission
        $insStmt = $db->prepare("
            INSERT INTO affiliate_commissions (affiliate_id, orders_id, amount, status, note, created_at)
            VALUES (?, ?, ?, 'unpaid', 'ค่าคอมมิชชัน 10% จากคำสั่งซื้อ #' , NOW())
        ");
        $insStmt->execute([$affiliateId, $orderId, $commissionAmount]);

        // Update total_commission in affiliates
        $upStmt = $db->prepare("UPDATE affiliates SET total_commission = total_commission + ?, total_shops = total_shops + 1, updated_at = NOW() WHERE affiliate_id = ?");
        $upStmt->execute([$commissionAmount, $affiliateId]);

        // Send LINE push notification to therapist partner if connected
        if (!empty($aff['line_user_id'])) {
            $msg = "⭐ Yak Pho Partner แจ้งเตือนยอดขาย!\nมีลูกค้าสั่งซื้อผ่านลิงก์ของคุณสำเร็จ ยอดสั่งซื้อ ฿" . number_format($orderTotal, 2) . " บาท\nคุณได้รับค่าคอมมิชชัน: +฿" . number_format($commissionAmount, 2) . " บาท\nตรวจสอบแดชบอร์ดได้ที่ https://yakpho.com/th/profile";
            send_line_push_message($db, $aff['line_user_id'], $msg);
        }

        return $commissionAmount;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Register or upgrade a user to Therapist Affiliate Partner
 */
function register_as_therapist_partner(PDO $db, int $userId): array {
    try {
        $stmt = $db->prepare("SELECT user_id, affiliate_code, member_tier FROM user WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูลสมาชิก'];
        }

        $code = $user['affiliate_code'];
        if (empty($code)) {
            $code = 'YP' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);
            $upUser = $db->prepare("UPDATE user SET affiliate_code = ?, member_tier = 'therapist_partner' WHERE user_id = ?");
            $upUser->execute([$code, $userId]);
        } else {
            $upUser = $db->prepare("UPDATE user SET member_tier = 'therapist_partner' WHERE user_id = ?");
            $upUser->execute([$userId]);
        }

        // Check if record exists in affiliates
        $checkAff = $db->prepare("SELECT affiliate_id FROM affiliates WHERE user_id = ?");
        $checkAff->execute([$userId]);
        if (!$checkAff->fetchColumn()) {
            $insAff = $db->prepare("
                INSERT INTO affiliates (user_id, affiliate_code, referral_link, total_shops, total_commission, status, created_at)
                VALUES (?, ?, ?, 0, 0.00, 'active', NOW())
            ");
            $referralLink = "https://yakpho.com/?ref=" . $code;
            $insAff->execute([$userId, $code, $referralLink]);
        }

        return [
            'success'        => true,
            'affiliate_code' => $code,
            'message'        => 'อัปเกรดเป็น Yak Pho Partner สำเร็จแล้ว!',
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}
