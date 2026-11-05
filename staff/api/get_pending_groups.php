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
    // Fetch up to 10 pending groups
    $stmt = $db->query("SELECT id, group_name, group_url 
                        FROM facebook_groups 
                        WHERE join_status = 'Pending' 
                        ORDER BY id ASC 
                        LIMIT 10");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'groups' => $groups]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
