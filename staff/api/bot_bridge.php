<?php
require_once "../db.php";

header('Content-Type: application/json');

// Security Check
$api_key = trim($_GET['api_key'] ?? $_POST['api_key'] ?? '');
if ($api_key !== 'yakpho2026') {
    http_response_code(401);
    die(json_encode(['ok' => false, 'msg' => 'Unauthorized: ' . $api_key]));
}

$action = $_GET['action'] ?? '';

// 1. VPS Agent Polling (Get all bots and their commands)
if ($action === 'poll') {
    $stmt = $db->query("SELECT bot_key, pending_command FROM bot_processes");
    $commands = $stmt->fetchAll(PDO::FETCH_ASSOC); // Enforce Associative
    
    echo json_encode([
        'ok' => true,
        'commands' => $commands,
        'server_time' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

// 2. VPS Agent Syncing (Update status, PID, and Logs)
elseif ($action === 'sync') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['updates'])) {
        die(json_encode(['ok' => false, 'msg' => 'Invalid data']));
    }

    try {
        foreach ($data['updates'] as $update) {
            $bot_key = $update['bot_key'] ?? '';
            if (!$bot_key) continue;

            $status = $update['status'] ?? 'idle';
            $pid = intval($update['pid'] ?? 0);
            $logs = $update['logs'] ?? '';

            // 1. Prepare base update with status and pid
            $sql = "UPDATE bot_processes SET 
                    status = :status, 
                    pid = :pid, 
                    log_cache = :logs, 
                    vps_last_sync = :now";
            
            $params = [
                ':status'   => $status,
                ':pid'      => $pid,
                ':logs'     => $logs,
                ':now'      => date('Y-m-d H:i:s'),
                ':bot_key'  => $bot_key
            ];

            // 2. Add started/stopped/error conditional flags
            if (!empty($update['started'])) {
                $sql .= ", last_started = NOW(), pending_command = NULL";
            }
            if (!empty($update['stopped'])) {
                $sql .= ", pending_command = NULL";
            }
            if (!empty($update['error'])) {
                $sql .= ", pending_command = NULL";
            }

            // 3. Complete and Execute
            $sql .= " WHERE bot_key = :bot_key";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        echo json_encode(['ok' => true, 'msg' => 'Sync success']);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
}
?>
