<?php
require_once '../includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Testing Price Tier Calculation ===\n\n";

// Test cases
$testCases = [
    0.5,   // Less than 1kg
    1,     // 1kg
    5,     // Between 1-6kg
    6,     // Exactly 6kg
    12,    // 12 bottles × 500g
    15,    // Between tiers
    25,    // 25kg
    75,    // 75kg
    150    // Over 100kg
];

foreach ($testCases as $qty) {
    echo "Testing {$qty} kg:\n";
    
    // Find applicable tier
    $stmt = $db->prepare("
        SELECT tier_name, tier_min_kg, tier_price_per_kg
        FROM price_tiers 
        WHERE tier_status = 1 
        AND tier_min_kg <= ?
        ORDER BY tier_min_kg DESC
        LIMIT 1
    ");
    $stmt->execute([$qty]);
    $tier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tier) {
        $totalPrice = $qty * $tier['tier_price_per_kg'];
        echo "  → Tier: {$tier['tier_name']} (min {$tier['tier_min_kg']} kg)\n";
        echo "  → Price/kg: {$tier['tier_price_per_kg']} บาท\n";
        echo "  → Total: " . number_format($totalPrice, 2) . " บาท\n";
    } else {
        echo "  → No tier found (quantity too low)\n";
    }
    echo "\n";
}
?>
