<?php
require_once "../db.php";
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method Not Allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid ID']);
    exit;
}

try {
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        $allowed = ['Not Joined', 'Pending', 'Joined', 'Blocked', 'Queued'];
        if (!in_array($status, $allowed)) {
            echo json_encode(['ok' => false, 'msg' => 'Invalid Status']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE facebook_groups SET join_status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['ok' => true]);
        
    } elseif ($action === 'delete') {
        // Change to SOFT DELETE
        $stmt = $db->prepare("UPDATE facebook_groups SET post_quality = 'Delete' WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Unknown Action: ' . $action]);
    }
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
