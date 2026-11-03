<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if ($action === 'list') {
        $stmt = $db->query("SELECT * FROM languages ORDER BY lang_order ASC");
        $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'languages' => $languages]);
    } 
    
    elseif ($action === 'toggle_status') {
        $code = $_POST['lang_code'] ?? '';
        $status = isset($_POST['lang_status']) ? (int)$_POST['lang_status'] : 0;
        
        if (empty($code)) {
            throw new Exception("Language code is required");
        }
        
        $stmt = $db->prepare("UPDATE languages SET lang_status = ? WHERE lang_code = ?");
        $stmt->execute([$status, $code]);
        
        echo json_encode(['success' => true]);
    }
    
    elseif ($action === 'update_order') {
        $orders = $_POST['orders'] ?? [];
        
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE languages SET lang_order = ? WHERE lang_code = ?");
        foreach ($orders as $item) {
            $stmt->execute([(int)$item['order'], $item['code']]);
        }
        $db->commit();
        
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
