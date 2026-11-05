<?php
require_once "../db.php";

header('Content-Type: application/json');

// Simple API Key check
$api_key = $_POST['api_key'] ?? $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$progress = $_POST['progress'] ?? '';

try {
    if ($action) {
        $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('bot_current_action', ?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
        $stmt->execute([$action, $action]);
    }
    
    if ($progress !== '') {
        $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('bot_progress_percent', ?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
        $stmt->execute([$progress, $progress]);
    }

    echo json_encode(['ok' => true, 'msg' => 'Pulse received']);
} catch(PDOException $e) {
    error_log("Bot Pulse Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
