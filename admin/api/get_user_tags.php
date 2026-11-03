<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid User ID']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT t.tag_name 
        FROM user_tags t
        JOIN user_tag_mapping m ON t.tag_id = m.tag_id
        WHERE m.user_id = ?
        ORDER BY t.tag_name ASC
    ");
    $stmt->execute([$userId]);
    $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'tags' => $tags]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
