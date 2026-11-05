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

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
$field = $data['field'] ?? '';
$value = $data['value'] ?? '';

// 🟢 Whitelist: อนุญาตให้บันทึกฟิลด์ใหม่จาก AI (strategy_marker และ ai_reason)
$allowed_fields = ['status', 'score', 'zone', 'target_zone', 'quality', 'post_quality', 'quality_star', 'ai_reason', 'category_tag', 'strategy_marker', 'post_ready', 'suitability_score', 'member_count', 'privacy_type', 'campaign_tag', 'manual_post_count', 'notes'];

if (!$id || !$field || !in_array($field, $allowed_fields)) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid input or field not allowed']);
    exit;
}

try {
    // 🟠 อัปเดตข้อมูลลงฐานข้อมูลโดยตรง
    $stmt = $db->prepare("UPDATE facebook_groups SET $field = ? WHERE id = ?");
    $stmt->execute([$value, $id]);

    echo json_encode(['ok' => true, 'msg' => 'Update Successful']);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Database Error: ' . $e->getMessage()]);
}
?>
