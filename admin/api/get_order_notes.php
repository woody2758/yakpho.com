<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    if (!$order_id) throw new Exception('Invalid Order ID');

    // Join with 'user' table since employees are also stored there
    $stmt = $db->prepare("
        SELECT n.*, u.user_name, u.user_lastname, u.user_picture 
        FROM order_notes n 
        LEFT JOIN user u ON n.user_id = u.user_id 
        WHERE n.orders_id = ? 
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$order_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($notes as &$note) {
        $note['admin_name'] = $note['user_name'] . ' ' . $note['user_lastname'];
        if (trim($note['admin_name']) == '') $note['admin_name'] = 'พนักงาน (ID: ' . $note['user_id'] . ')';
    }

    echo json_encode(['success' => true, 'notes' => $notes]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
