<?php
require_once "../db.php";

header('Content-Type: application/json');

// API Key check (simple)
$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('bot_status', 'telegram_bot_token', 'telegram_chat_id', 'bot_current_action', 'bot_progress_percent')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo json_encode([
        'ok' => true,
        'bot_status' => $settings['bot_status'] ?? 'on',
        'telegram_token' => $settings['telegram_bot_token'] ?? '',
        'telegram_chat_id' => $settings['telegram_chat_id'] ?? '',
        'bot_current_action' => $settings['bot_current_action'] ?? 'Waiting...',
        'bot_progress_percent' => (int)($settings['bot_progress_percent'] ?? 0)
    ]);
} catch(PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
