<?php
require_once "../db.php";
session_start();

header('Content-Type: application/json');

$api_key = $_POST['api_key'] ?? $_GET['api_key'] ?? '';
if ((!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) && $api_key !== 'yakpho2026') {
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper function for image upload
function handleImageUpload($file) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'bot_' . uniqid() . '.' . $ext;
        $destination = '../img/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }
    }
    return null;
}

if ($action === 'get') {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM ai_staff WHERE id = ?");
    $stmt->execute([$id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($staff) {
        echo json_encode(['ok' => true, 'data' => $staff]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'ไม่พบข้อมูลพนักงาน']);
    }
    exit;
}

if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $bot_script = trim($_POST['bot_script']);
    $name_en = trim($_POST['name_en']);
    $name_th = trim($_POST['name_th']);
    $department = trim($_POST['department']);
    $role = trim($_POST['role']);
    $identity_desc = trim($_POST['identity_desc']);
    $objective = trim($_POST['objective']);
    $details = trim($_POST['details']);
    $start_date = trim($_POST['start_date']) ?: date('Y-m-d');
    $status = trim($_POST['status'] ?? 'Active');
    
    $new_image = handleImageUpload($_FILES['image'] ?? null);

    try {
        if ($id > 0) {
            // Update
            if ($new_image) {
                $stmt = $db->prepare("UPDATE ai_staff SET bot_script=?, name_en=?, name_th=?, department=?, role=?, identity_desc=?, objective=?, details=?, image_path=?, start_date=?, status=? WHERE id=?");
                $stmt->execute([$bot_script, $name_en, $name_th, $department, $role, $identity_desc, $objective, $details, $new_image, $start_date, $status, $id]);
            } else {
                $stmt = $db->prepare("UPDATE ai_staff SET bot_script=?, name_en=?, name_th=?, department=?, role=?, identity_desc=?, objective=?, details=?, start_date=?, status=? WHERE id=?");
                $stmt->execute([$bot_script, $name_en, $name_th, $department, $role, $identity_desc, $objective, $details, $start_date, $status, $id]);
            }
        } else {
            // Insert
            $image_path = $new_image ?? 'bot_default.png';
            $stmt = $db->prepare("INSERT INTO ai_staff (bot_script, name_en, name_th, department, role, identity_desc, objective, details, image_path, start_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$bot_script, $name_en, $name_th, $department, $role, $identity_desc, $objective, $details, $image_path, $start_date, $status]);
            $id = $db->lastInsertId();
        }

        // Check if we need to insert or update bot_processes
        if ($status === 'Active' && !empty($bot_script)) {
            $script_name = $bot_script . '.py';
            $check_bot = $db->prepare("SELECT id FROM bot_processes WHERE bot_key = ?");
            $check_bot->execute([$bot_script]);
            if ($check_bot->fetch()) {
                // Force update the script_name to match the bot_key
                $update_bot = $db->prepare("UPDATE bot_processes SET script_name = ? WHERE bot_key = ?");
                $update_bot->execute([$script_name, $bot_script]);
            } else {
                $insert_bot = $db->prepare("INSERT INTO bot_processes (bot_key, script_name) VALUES (?, ?)");
                $insert_bot->execute([$bot_script, $script_name]);
            }
        }

        // Fetch updated data to return to UI
        $stmt = $db->prepare("SELECT * FROM ai_staff WHERE id = ?");
        $stmt->execute([$id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'msg' => 'บันทึกข้อมูลสำเร็จ', 'data' => $staff]);
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)$_POST['id'];
    try {
        $db->prepare("UPDATE ai_staff SET status='Deleted' WHERE id=?")->execute([$id]);
        echo json_encode(['ok' => true, 'msg' => 'ลบพนักงานเรียบร้อยแล้ว']);
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Invalid action']);
