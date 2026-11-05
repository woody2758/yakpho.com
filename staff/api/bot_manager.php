<?php
require_once "../db.php";

header('Content-Type: application/json');

$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(401);
    die(json_encode(['ok' => false, 'msg' => 'Unauthorized']));
}

$action = $_GET['action'] ?? '';
$bot_key = $_GET['bot'] ?? '';

// Handle actions
if ($action === 'status_all') {
    $stmt = $db->query("SELECT * FROM bot_processes");
    $bots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [];
    if ($bots) {
        foreach ($bots as $bot) {
            $last_started = null;
            if (!empty($bot['last_started'])) {
                $ts = strtotime($bot['last_started']);
                if ($ts > 0) $last_started = date('Y-m-d H:i:s', $ts);
            }

            $results[$bot['bot_key']] = [
                'is_running' => (($bot['status'] ?? '') === 'running' && intval($bot['pid'] ?? 0) > 0),
                'status' => $bot['status'] ?? 'idle',
                'pid' => $bot['pid'] ?? 0,
                'pending_command' => $bot['pending_command'] ?? null,
                'last_started' => $last_started,
                'vps_online' => (strtotime($bot['vps_last_sync'] ?? '') > (time() - 120))
            ];
        }
    }
    ob_clean();
    echo json_encode(['ok' => true, 'bots' => $results], JSON_UNESCAPED_UNICODE);
    exit;
}

// Bot-specific actions require bot_key
if (!$bot_key) {
    die(json_encode(['ok' => false, 'msg' => 'Bot key missing']));
}

// REMOTE BRIDGE MODE
// Instead of shell_exec, we update the DB for the remote agent to pick up

if ($action === 'start') {
    $stmt = $db->prepare("UPDATE bot_processes SET pending_command = 'start' WHERE bot_key = ?");
    $stmt->execute([$bot_key]);
    echo json_encode(['ok' => true, 'msg' => 'Command queued for VPS agent']);
} 
elseif ($action === 'stop') {
    $stmt = $db->prepare("UPDATE bot_processes SET pending_command = 'stop' WHERE bot_key = ?");
    $stmt->execute([$bot_key]);
    echo json_encode(['ok' => true, 'msg' => 'Stop command queued for VPS agent']);
} 
elseif ($action === 'report_status') {
    $status = $_GET['status'] ?? 'idle';
    $pid = $_GET['pid'] ?? 0;
    
    // Check current status. If it's error_auth, don't let it be overwritten by 'idle' on exit
    $stmt_check = $db->prepare("SELECT status FROM bot_processes WHERE bot_key = ?");
    $stmt_check->execute([$bot_key]);
    $current_status = $stmt_check->fetchColumn();
    
    if ($current_status === 'error_auth' && $status === 'idle') {
        $status = 'error_auth'; // Preserve error state
    }
    
    $stmt = $db->prepare("UPDATE bot_processes SET status = ?, pid = ?, vps_last_sync = NOW(), pending_command = NULL WHERE bot_key = ?");
    $stmt->execute([$status, $pid, $bot_key]);
    echo json_encode(['ok' => true, 'msg' => 'Status reported']);
}
elseif ($action === 'status') {
    $stmt = $db->prepare("SELECT * FROM bot_processes WHERE bot_key = ?");
    $stmt->execute([$bot_key]);
    $bot = $stmt->fetch();
    
    // Check if vps_last_sync was in the last 60 seconds
    $is_vps_online = (strtotime($bot['vps_last_sync']) > (time() - 60));
    
    echo json_encode([
        'is_running' => ($bot['status'] === 'running' && $bot['pid'] > 0),
        'pid' => $bot['pid'],
        'last_started' => $bot['last_started'],
        'vps_online' => $is_vps_online,
        'status' => $bot['status'],
        'pending_command' => $bot['pending_command']
    ]);
} 
elseif ($action === 'logs') {
    $stmt = $db->prepare("SELECT log_cache FROM bot_processes WHERE bot_key = ?");
    $stmt->execute([$bot_key]);
    $log = $stmt->fetchColumn();
    echo json_encode(['ok' => true, 'logs' => $log ?: 'Waiting for VPS agent to sync logs...']);
}
?>
