<?php
require_once "../db.php";
require_login();

header('Content-Type: application/json');

$url = trim($_GET['url'] ?? '');

if (empty($url)) {
    echo json_encode(['ok' => false, 'msg' => 'Empty URL']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT id, group_name FROM facebook_groups WHERE group_url = ?");
    $stmt->execute([$url]);
    $group = $stmt->fetch();
    
    if ($group) {
        echo json_encode(['ok' => true, 'exists' => true, 'id' => $group['id'], 'group_name' => $group['group_name']]);
    } else {
        echo json_encode(['ok' => true, 'exists' => false]);
    }

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Database Error']);
}
?>
