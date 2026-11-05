<?php
require_once "../db.php";
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$ids = $input['ids'] ?? [];

if (empty($ids) || !is_array($ids)) {
    echo json_encode(['ok' => false, 'msg' => 'No IDs provided']);
    exit;
}

// Prepare placehoders (?, ?, ?)
$inQuery = implode(',', array_fill(0, count($ids), '?'));

try {
    if ($action === 'queue') {
        $stmt = $db->prepare("UPDATE facebook_groups SET join_status = 'Queued' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'mark_joined') {
        $stmt = $db->prepare("UPDATE facebook_groups SET join_status = 'Joined' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("UPDATE facebook_groups SET post_quality = 'Delete' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'strategy_observe') {
        $stmt = $db->prepare("UPDATE facebook_groups SET strategy_marker = 'Observe' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'strategy_soft') {
        $stmt = $db->prepare("UPDATE facebook_groups SET strategy_marker = 'Soft' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'strategy_hard') {
        $stmt = $db->prepare("UPDATE facebook_groups SET strategy_marker = 'Hard' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'strategy_blacklist') {
        $stmt = $db->prepare("UPDATE facebook_groups SET strategy_marker = 'Blacklist' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'zone_thailand') {
        $stmt = $db->prepare("UPDATE facebook_groups SET target_zone = 'Thailand' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'zone_global') {
        $stmt = $db->prepare("UPDATE facebook_groups SET target_zone = 'Global' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'quality_good') {
        $stmt = $db->prepare("UPDATE facebook_groups SET post_quality = 'Good' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'quality_poor') {
        $stmt = $db->prepare("UPDATE facebook_groups SET post_quality = 'Poor' WHERE id IN ($inQuery)");
        $stmt->execute($ids);
    } elseif ($action === 'post_queue') {
        $added = 0;
        foreach ($ids as $gid) {
            $g_info = $db->query("SELECT strategy_marker, campaign_tag FROM facebook_groups WHERE id = " . intval($gid))->fetch();
            $sm = $g_info['strategy_marker'] ?? 'Observe';
            if($sm == 'Hard') $insert_strategy = 'Green';
            elseif($sm == 'Soft') $insert_strategy = 'Yellow';
            else $insert_strategy = 'Red';
            
            $insert_tag = $g_info['campaign_tag'] ?? null;
            
            $db->prepare("UPDATE facebook_groups SET post_ready = 1 WHERE id = ?")->execute([$gid]);
            
            $existing = $db->query("SELECT id FROM creative_post_queue WHERE target_id = " . intval($gid) . " AND status = 'pending'")->fetch();
            if (!$existing) {
                $stmt = $db->prepare("INSERT INTO creative_post_queue (target_id, target_type, scheduled_at, status, strategy_marker, campaign_tag) VALUES (?, 'group', NOW(), 'pending', ?, ?)");
                $stmt->execute([$gid, $insert_strategy, $insert_tag]);
                $added++;
            }
        }
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
        exit;
    }
    
    echo json_encode(['ok' => true, 'count' => count($ids)]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
