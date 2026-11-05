<?php
require_once "../db.php";

header('Content-Type: application/json');

// API Key Validation
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

try {
    // Fetch groups that need AI Strategy analysis (Leniency added for existing groups)
    $stmt = $db->query("SELECT id, group_name, last_activity, member_count, privacy_type 
                        FROM facebook_groups 
                        WHERE (
                            (strategy_marker IS NULL OR strategy_marker = '')
                            OR 
                            (strategy_marker = 'Observe' AND (ai_reason IS NULL OR ai_reason = '' OR ai_reason = 'AI V3.6'))
                            OR ai_reason LIKE '%429%' 
                            OR ai_reason LIKE '%quota%' 
                            OR ai_reason LIKE '%exhaust%'
                            OR ai_reason LIKE '%error%'
                        )
                        ORDER BY id ASC 
                        LIMIT 100");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'groups' => $groups]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
