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

// Find pending jobs from the campaign schedules where scheduled_time is past
// Join with facebook_groups to get the group details and fb_campaigns to ensure it's active
$sql = "SELECT q.id as queue_id, q.content_id, q.group_id, g.group_name, g.group_url 
        FROM fb_campaign_schedules q 
        JOIN facebook_groups g ON q.group_id = g.id 
        JOIN fb_campaigns c ON q.campaign_id = c.id
        WHERE q.status = 'pending' AND q.scheduled_time <= NOW() AND c.status = 'active'
        ORDER BY q.scheduled_time ASC";

$stmt = $db->query($sql);
$queue_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($queue_items)) {
    $groups = [];
    foreach($queue_items as $queue_item) {
        $groups[] = [
            'queue_id' => $queue_item['queue_id'],
            'content_id' => $queue_item['content_id'],
            'id' => $queue_item['group_id'],
            'group_name' => $queue_item['group_name'],
            'group_url' => $queue_item['group_url']
        ];
    }
    echo json_encode([
        'ok' => true, 
        'groups' => $groups,
        'msg' => "Found " . count($groups) . " campaign queue items to post."
    ]);
} else {
    $future_sql = "SELECT q.scheduled_time 
                   FROM fb_campaign_schedules q 
                   JOIN fb_campaigns c ON q.campaign_id = c.id 
                   WHERE q.status = 'pending' AND q.scheduled_time > NOW() AND c.status = 'active' 
                   ORDER BY q.scheduled_time ASC LIMIT 1";
    $future_stmt = $db->query($future_sql);
    $next_run = $future_stmt->fetchColumn();

    if ($next_run) {
        echo json_encode(['ok' => false, 'groups' => [], 'msg' => 'Waiting for scheduled time.', 'next_run' => $next_run]);
    } else {
        echo json_encode(['ok' => false, 'groups' => [], 'msg' => 'No pending campaign queue found.']);
    }
}
