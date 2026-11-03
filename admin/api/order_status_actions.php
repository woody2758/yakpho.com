<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if ($action === 'get') {
        $id = $_GET['id'] ?? 0;
        
        // ดึงข้อมูลหลัก
        $stmt = $db->prepare("SELECT * FROM orsts WHERE orsts_id = ?");
        $stmt->execute([$id]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลแปลภาษา
        $stmt_trans = $db->prepare("SELECT * FROM orsts_translations WHERE orsts_id = ?");
        $stmt_trans->execute([$id]);
        $translations = $stmt_trans->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'status' => $status, 'translations' => $translations]);
    } 
    
    elseif ($action === 'save') {
        $data = $_POST;
        $id = $data['orsts_id'] ?? 0;
        
        $db->beginTransaction();
        
        $thaiName = $data['name']['th'] ?? '';
        $thaiMsg = $data['msg']['th'] ?? '';
        
        if (!empty($id)) {
            // --- UPDATE ---
            $stmt = $db->prepare("UPDATE orsts SET 
                orsts_code = ?, 
                orsts_detail = ?, 
                orsts_msg = ?, 
                orsts_color = ?, 
                orsts_index = ?,
                orsts_update = NOW(),
                update_id = ?
                WHERE orsts_id = ?");
                
            $stmt->execute([
                $data['orsts_code'],
                $thaiName,
                $thaiMsg,
                $data['orsts_color'],
                $data['orsts_index'],
                $_SESSION['admin_id'] ?? 0,
                $id
            ]);
        } else {
            // --- INSERT ---
            $stmt = $db->prepare("INSERT INTO orsts (orsts_code, orsts_detail, orsts_msg, orsts_color, orsts_index, orsts_date, save_id) 
                                 VALUES (?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([
                $data['orsts_code'],
                $thaiName,
                $thaiMsg,
                $data['orsts_color'],
                $data['orsts_index'],
                $_SESSION['admin_id'] ?? 0
            ]);
            $id = $db->lastInsertId();
        }
        
        // 2. อัปเดตตารางแปลภาษา (orsts_translations)
        $db->prepare("DELETE FROM orsts_translations WHERE orsts_id = ?")->execute([$id]);
        
        $insertTrans = $db->prepare("INSERT INTO orsts_translations (orsts_id, lang_code, orsts_name, orsts_msg) VALUES (?, ?, ?, ?)");
        
        if (isset($data['name'])) {
            foreach ($data['name'] as $lang => $name) {
                $msg = $data['msg'][$lang] ?? '';
                $insertTrans->execute([$id, $lang, $name, $msg]);
            }
        }
        
        $db->commit();
        echo json_encode(['success' => true]);
    }
    
    elseif ($action === 'toggle_status') {
        $id = $_POST['orsts_id'] ?? 0;
        $status = $_POST['orsts_status'] ?? 0;
        
        $stmt = $db->prepare("UPDATE orsts SET orsts_status = ? WHERE orsts_id = ?");
        $stmt->execute([$status, $id]);
        
        echo json_encode(['success' => true]);
    }
    
    elseif ($action === 'update_order') {
        $data = json_decode(file_get_contents('php://input'), true);
        if(isset($data['orders']) && is_array($data['orders'])) {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE orsts SET orsts_index = ? WHERE orsts_id = ?");
            foreach($data['orders'] as $o) {
                $stmt->execute([$o['index'], $o['id']]);
            }
            $db->commit();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
        }
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
