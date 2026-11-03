<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $orderId = isset($_POST['orders_id']) ? (int)$_POST['orders_id'] : 0;
    $name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
    $lastname = isset($_POST['user_lastname']) ? trim($_POST['user_lastname']) : '';
    $nickname = isset($_POST['user_nickname']) ? trim($_POST['user_nickname']) : '';
    $mobile = isset($_POST['user_mobile']) ? trim($_POST['user_mobile']) : '';
    $email = isset($_POST['user_email']) ? trim($_POST['user_email']) : '';
    $note = isset($_POST['user_note']) ? trim($_POST['user_note']) : '';
    $googleMap = isset($_POST['user_google_map']) ? trim($_POST['user_google_map']) : '';
    $website = isset($_POST['user_website']) ? trim($_POST['user_website']) : '';
    $memberTier = isset($_POST['member_tier']) ? trim($_POST['member_tier']) : '';
    $mStampPoints = isset($_POST['m_stamp_points']) && $_POST['m_stamp_points'] !== '' ? (int)$_POST['m_stamp_points'] : null;

    // Address fields
    $addrId = isset($_POST['addr_id']) ? (int)$_POST['addr_id'] : 0;
    $addrName = isset($_POST['addr_name']) ? trim($_POST['addr_name']) : '';
    $addrMobile = isset($_POST['addr_mobile']) ? trim($_POST['addr_mobile']) : '';
    $addrDetail = isset($_POST['addr_detail']) ? trim($_POST['addr_detail']) : '';
    $addrDetail2 = isset($_POST['addr_detail2']) ? trim($_POST['addr_detail2']) : '';
    $provincesId = isset($_POST['provinces_id']) ? (int)$_POST['provinces_id'] : 0;
    $addrPostcode = isset($_POST['addr_postcode']) ? trim($_POST['addr_postcode']) : '';
    $addrTaxId = isset($_POST['addr_tax_id']) ? trim($_POST['addr_tax_id']) : '';

    if (!$userId) {
        throw new Exception('User ID is required');
    }

    if (empty($name)) {
        throw new Exception('กรุณาระบุชื่อผู้รับ/ลูกค้า');
    }

    // Update User
    $sqlUser = "UPDATE user SET 
        user_name = :name, 
        user_lastname = :lastname, 
        user_nickname = :nickname,
        user_mobile = :mobile, 
        user_email = :email, 
        user_note = :note, 
        user_google_map = :googleMap, 
        user_website = :website";
    
    $paramsUser = [
        ':name' => $name,
        ':lastname' => $lastname,
        ':nickname' => $nickname,
        ':mobile' => $mobile,
        ':email' => $email,
        ':note' => $note,
        ':googleMap' => $googleMap,
        ':website' => $website,
        ':userId' => $userId
    ];

    if (!empty($memberTier)) {
        $sqlUser .= ", member_tier = :memberTier";
        $paramsUser[':memberTier'] = $memberTier;
    }
    if ($mStampPoints !== null) {
        $sqlUser .= ", m_stamp_points = :mStampPoints";
        $paramsUser[':mStampPoints'] = $mStampPoints;
    }

    $sqlUser .= " WHERE user_id = :userId";
    $stmt = $db->prepare($sqlUser);
    $stmt->execute($paramsUser);

    // Save/Update Primary Address in addr table if address details provided
    if (!empty($addrDetail) || !empty($addrPostcode) || $provincesId > 0 || !empty($addrName)) {
        if ($addrId > 0) {
            // Update existing address
            $stmtAddr = $db->prepare("UPDATE addr SET 
                addr_name = :addrName,
                addr_mobile = :addrMobile,
                addr_detail = :addrDetail,
                addr_detail2 = :addrDetail2,
                provinces_id = :provincesId,
                addr_postcode = :addrPostcode,
                addr_tax_id = :addrTaxId,
                addr_update = NOW()
                WHERE addr_id = :addrId AND user_id = :userId");
            $stmtAddr->execute([
                ':addrName' => $addrName ?: ($name . ' ' . $lastname),
                ':addrMobile' => $addrMobile ?: $mobile,
                ':addrDetail' => $addrDetail,
                ':addrDetail2' => $addrDetail2,
                ':provincesId' => $provincesId,
                ':addrPostcode' => $addrPostcode,
                ':addrTaxId' => $addrTaxId,
                ':addrId' => $addrId,
                ':userId' => $userId
            ]);
        } else {
            // Check if user already has an address
            $chkAddr = $db->prepare("SELECT addr_id FROM addr WHERE user_id = ? AND addr_del = 0 ORDER BY addr_position DESC, addr_id DESC LIMIT 1");
            $chkAddr->execute([$userId]);
            $existingAddrId = $chkAddr->fetchColumn();

            if ($existingAddrId) {
                $stmtAddr = $db->prepare("UPDATE addr SET 
                    addr_name = :addrName,
                    addr_mobile = :addrMobile,
                    addr_detail = :addrDetail,
                    addr_detail2 = :addrDetail2,
                    provinces_id = :provincesId,
                    addr_postcode = :addrPostcode,
                    addr_tax_id = :addrTaxId,
                    addr_update = NOW()
                    WHERE addr_id = :addrId");
                $stmtAddr->execute([
                    ':addrName' => $addrName ?: ($name . ' ' . $lastname),
                    ':addrMobile' => $addrMobile ?: $mobile,
                    ':addrDetail' => $addrDetail,
                    ':addrDetail2' => $addrDetail2,
                    ':provincesId' => $provincesId,
                    ':addrPostcode' => $addrPostcode,
                    ':addrTaxId' => $addrTaxId,
                    ':addrId' => $existingAddrId
                ]);
            } else {
                // Insert new primary address
                $stmtAddr = $db->prepare("INSERT INTO addr 
                    (user_id, addr_name, addr_mobile, addr_detail, addr_detail2, provinces_id, addr_postcode, addr_tax_id, addr_position, addr_status, addr_del, addr_date, addr_update) 
                    VALUES 
                    (:userId, :addrName, :addrMobile, :addrDetail, :addrDetail2, :provincesId, :addrPostcode, :addrTaxId, 1, 1, 0, NOW(), NOW())");
                $stmtAddr->execute([
                    ':userId' => $userId,
                    ':addrName' => $addrName ?: ($name . ' ' . $lastname),
                    ':addrMobile' => $addrMobile ?: $mobile,
                    ':addrDetail' => $addrDetail,
                    ':addrDetail2' => $addrDetail2,
                    ':provincesId' => $provincesId,
                    ':addrPostcode' => $addrPostcode,
                    ':addrTaxId' => $addrTaxId
                ]);
            }
        }
    }

    // Update Order Message if orderId is provided
    if ($orderId > 0 && isset($_POST['orders_msg'])) {
        $ordersMsg = trim($_POST['orders_msg']);
        $stmt_order = $db->prepare("UPDATE orders SET orders_msg = ? WHERE orders_id = ?");
        $stmt_order->execute([$ordersMsg, $orderId]);
    }

    echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลลูกค้าเรียบร้อยแล้ว']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
