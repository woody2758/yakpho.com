<?php
/**
 * API: Delete About Us Record
 * Soft delete (set aboutus_del = 1)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['aboutus_id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ID is required');
    }
    
    $stmt = $db->prepare("
        UPDATE aboutus SET 
            aboutus_del = 1,
            aboutus_update = NOW()
        WHERE aboutus_id = ? AND aboutus_del = 0
    ");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('About Us not found');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Deleted successfully'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
