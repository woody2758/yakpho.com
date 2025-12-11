<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Get all active price tiers
    $stmt = $db->query("
        SELECT 
            tier_id,
            tier_name,
            tier_description,
            tier_min_kg,
            tier_price_per_kg,
            tier_sort_order,
            tier_status
        FROM price_tiers 
        WHERE tier_status = 1 
        ORDER BY tier_sort_order ASC, tier_min_kg ASC
    ");
    
    $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'tiers' => $tiers,
        'count' => count($tiers)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
