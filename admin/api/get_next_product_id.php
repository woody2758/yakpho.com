<?php
/**
 * Get Next Product ID
 * Returns the next available product ID for auto-generating product codes
 */

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    global $db;
    
    // Get the highest product_id
    $stmt = $db->query("SELECT MAX(product_id) as max_id FROM product");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $nextId = ($result['max_id'] ?? 0) + 1;
    
    echo json_encode([
        'success' => true,
        'next_id' => $nextId
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
