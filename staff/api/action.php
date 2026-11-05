<?php
// api/action.php — Central AJAX Action Handler for Staff Dashboard
require_once "../db.php";

header('Content-Type: application/json');

// ตรวจสอบ Login (Session)
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
$ok     = false;
$msg    = '';

// ===== Cities Actions =====
if ($action === 'city_requeue') {
    $stmt = $db->prepare("UPDATE target_cities SET status = 'pending', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $ok  = true;
    $msg = 'สั่ง Requeue สำเร็จ!';
}

elseif ($action === 'city_complete') {
    $stmt = $db->prepare("UPDATE target_cities SET status = 'completed', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $ok  = true;
    $msg = 'บังคับ Complete สำเร็จ!';
}

elseif ($action === 'city_delete') {
    $stmt = $db->prepare("DELETE FROM target_cities WHERE id = ?");
    $stmt->execute([$id]);
    $ok  = true;
    $msg = 'ลบเมืองออกจากระบบแล้ว';
}

// ===== User/Admin Actions =====
elseif ($action === 'user_status') {
    $status = $_POST['status'] ?? '';
    // ป้องกันเปลี่ยนสถานะตัวเอง
    if ($id == $_SESSION['staff_id']) {
        $msg = 'ไม่สามารถเปลี่ยนสถานะตัวเองได้';
    } else {
        $stmt = $db->prepare("UPDATE staff_admins SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $ok  = true;
        $msg = 'อัปเดตสถานะสำเร็จ';
    }
}

// ===== system_settings Actions =====
elseif ($action === 'toggle_bot') {
    $status = $_POST['status'] ?? 'on';
    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('bot_status', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$status, $status]);
    $ok  = true;
    $msg = 'ปรับสถานะบอทเป็น ' . strtoupper($status) . ' เรียบร้อย';
}

elseif ($action === 'save_telegram_config') {
    $token = $_POST['token'] ?? '';
    $chat_id = $_POST['chat_id'] ?? '';
    
    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('telegram_bot_token', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$token, $token]);
    
    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('telegram_chat_id', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$chat_id, $chat_id]);
    
    $ok = true;
    $msg = 'บันทึกการตั้งค่า Telegram สำเร็จ!';
}

echo json_encode(['ok' => $ok, 'msg' => $msg]);
