<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Get quantity from request
    $input = json_decode(file_get_contents('php://input'), true);
    $quantityKg = isset($input['quantity_kg']) ? floatval($input['quantity_kg']) : 0;
    
    if ($quantityKg <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid quantity'
        ]);
        exit;
    }
    
    // Get applicable tier
    // Find the highest tier where tier_min_kg <= quantityKg
    $stmt = $db->prepare("
        SELECT 
            tier_id,
            tier_name,
            tier_min_kg,
            tier_price_per_kg
        FROM price_tiers 
        WHERE tier_status = 1 
        AND tier_min_kg <= ?
        ORDER BY tier_min_kg DESC
        LIMIT 1
    ");
    $stmt->execute([$quantityKg]);
    $tier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tier) {
        echo json_encode([
            'success' => true,
            'quantity_kg' => $quantityKg,
            'tier_id' => $tier['tier_id'],
            'tier_name' => $tier['tier_name'],
            'tier_min_kg' => $tier['tier_min_kg'],
            'price_per_kg' => $tier['tier_price_per_kg'],
            'total_price' => $quantityKg * $tier['tier_price_per_kg']
        ]);
    } else {
        // If no tier found (quantity < 1kg), use base price
        echo json_encode([
            'success' => false,
            'message' => 'No tier found for quantity less than 1kg'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
