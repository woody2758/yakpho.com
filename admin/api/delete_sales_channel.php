<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    // Note: If sales_channels are linked to orders, you might want to prevent deletion or do a soft delete.
    // For now, doing a hard delete as requested.
    $stmt = $db->prepare("DELETE FROM sales_channels WHERE channel_id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Handle foreign key constraint failures if there are any
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบได้เนื่องจากมีการใช้งานช่องทางขายนี้อยู่']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}
