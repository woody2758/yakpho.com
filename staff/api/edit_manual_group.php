<?php
require_once "../db.php";
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method Not Allowed']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$group_name = trim($_POST['group_name'] ?? '');
$group_url = trim($_POST['group_url'] ?? '');
$member_count = (int)($_POST['member_count'] ?? 0);
$privacy_type = trim($_POST['privacy_type'] ?? 'Public');
$join_status = trim($_POST['join_status'] ?? 'Not Joined');
$category_tag = trim($_POST['category_tag'] ?? 'Manual');
$target_zone = trim($_POST['target_zone'] ?? 'Uncategorized');

if (!$id || empty($group_name) || empty($group_url)) {
    echo json_encode(['ok' => false, 'msg' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $stmt = $db->prepare("
        UPDATE facebook_groups 
        SET group_name = ?, group_url = ?, member_count = ?, privacy_type = ?, join_status = ?, category_tag = ?, target_zone = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $group_name, 
        $group_url, 
        $member_count, 
        $privacy_type, 
        $join_status, 
        $category_tag, 
        $target_zone,
        $id
    ]);
    
    echo json_encode(['ok' => true]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Database Error: ' . $e->getMessage()]);
}
?>
