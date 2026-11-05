<?php
header('Content-Type: application/json');
require_once "../db.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid API Key']);
    exit;
}

// Find multiple jobs from the queue that are pending
$sql = "SELECT q.id as queue_id, g.strategy_marker, g.id as group_id, g.group_name, g.group_url, g.target_zone 
        FROM creative_post_queue q 
        JOIN facebook_groups g ON q.target_id = g.id 
        WHERE q.status = 'pending' AND q.target_type = 'group'
        ORDER BY q.id ASC 
        LIMIT 10";

$stmt = $db->query($sql);
$queue_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($queue_items)) {
    $groups = [];
    foreach($queue_items as $queue_item) {
        $groups[] = [
            'queue_id' => $queue_item['queue_id'],
            'id' => $queue_item['group_id'],
            'group_name' => $queue_item['group_name'],
            'group_url' => $queue_item['group_url'],
            'strategy_marker' => $queue_item['strategy_marker'] ?? 'Observe',
            'target_zone' => $queue_item['target_zone'] ?? 'Thailand'
        ];
    }
    echo json_encode([
        'ok' => true, 
        'groups' => $groups,
        'msg' => "Found " . count($groups) . " queue items to post."
    ]);
} else {
    echo json_encode(['ok' => false, 'groups' => [], 'msg' => 'No pending queue found.']);
}
