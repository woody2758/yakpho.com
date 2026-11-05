<?php
require_once "../db.php";
header('Content-Type: application/json');

$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') die(json_encode(['ok' => false]));

try {
    // เช็คจำนวนรวมทั้งหมดก่อน
    $total = $db->query("SELECT COUNT(*) FROM facebook_groups")->fetchColumn();
    
    // เช็คค่าในแต่ละแถวว่าว่าง หรือเป็น NULL หรือเป็นอะไร
    $marker_check = $db->query("SELECT strategy_marker, ai_reason, COUNT(*) as count FROM facebook_groups GROUP BY strategy_marker, ai_reason")->fetchAll(PDO::FETCH_ASSOC);
    
    // ทดสอบรัน Query เดียวกับที่ Booster ใช้เป๊ะๆ
    $test_query = $db->query("SELECT id, group_name FROM facebook_groups WHERE (strategy_marker = 'Observe' OR strategy_marker IS NULL OR strategy_marker = '') AND (ai_reason IS NULL OR ai_reason = '') LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'total_groups_in_db' => $total,
        'database_status' => $marker_check,
        'booster_sees_these' => $test_query,
        'sql_logic' => "WHERE (strategy_marker = 'Observe' OR strategy_marker IS NULL OR strategy_marker = '') AND (ai_reason IS NULL OR ai_reason = '')"
    ]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
