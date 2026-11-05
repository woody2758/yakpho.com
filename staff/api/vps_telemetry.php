<?php
require_once "../db.php";
header('Content-Type: application/json');

$API_KEY = "yakpho2026";

// Create table if not exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `vps_telemetry` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `cpu` float NOT NULL DEFAULT '0',
        `ram` float NOT NULL DEFAULT '0',
        `disk` float NOT NULL DEFAULT '0',
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Ensure at least 1 row exists
    $stmt = $db->query("SELECT COUNT(*) FROM vps_telemetry");
    if($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO vps_telemetry (cpu, ram, disk, updated_at) VALUES (0,0,0, NOW())");
    }
} catch(PDOException $e) {
    // Ignore if failure applies (e.g., restricted permissions to create table)
}

// --- POST: Update Status (from VPS Python Script) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_GET['api_key']) || $_GET['api_key'] !== $API_KEY) {
        echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data && isset($data['cpu']) && isset($data['ram']) && isset($data['disk'])) {
        $stmt = $db->prepare("UPDATE vps_telemetry SET cpu = ?, ram = ?, disk = ?, updated_at = NOW() WHERE id = 1");
        $stmt->execute([$data['cpu'], $data['ram'], $data['disk']]);
        
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Invalid data payload']);
    }
    exit;
}

// --- GET: Fetch Latest Status (for Dashboard UI) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['api_key']) || $_GET['api_key'] !== $API_KEY) {
        echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
        exit;
    }

    $stmt = $db->query("SELECT cpu, ram, disk, DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') as last_updated FROM vps_telemetry WHERE id = 1");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        echo json_encode(['ok' => true, 'data' => $stats]);
    } else {
        echo json_encode(['ok' => true, 'data' => ['cpu' => 0, 'ram' => 0, 'disk' => 0, 'last_updated' => null]]);
    }
}
