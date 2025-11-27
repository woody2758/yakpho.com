<?php
/**
 * Price Tier Management Functions
 * Handles dynamic pricing based on quantity tiers
 */

/**
 * Get all price tiers
 */
function get_all_price_tiers() {
    global $db;
    
    $sql = "SELECT * FROM price_tiers WHERE tier_status = 1 ORDER BY tier_id";
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get price tier by ID
 */
function get_price_tier($tierId) {
    global $db;
    
    $sql = "SELECT * FROM price_tiers WHERE tier_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$tierId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get price tier levels
 */
function get_price_tier_levels($tierId) {
    global $db;
    
    $sql = "SELECT * FROM price_tier_levels 
            WHERE tier_id = ? 
            ORDER BY min_quantity ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$tierId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calculate price based on quantity and tier
 * 
 * @param int $tierId Price tier ID
 * @param int $quantity Quantity
 * @return float Price per unit
 */
function calculate_price_from_tier($tierId, $quantity) {
    global $db;
    
    if (!$tierId || $quantity <= 0) {
        return 0;
    }
    
    $sql = "SELECT price_per_unit 
            FROM price_tier_levels 
            WHERE tier_id = ? AND min_quantity <= ?
            ORDER BY min_quantity DESC 
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$tierId, $quantity]);
    $price = $stmt->fetchColumn();
    
    return $price ? (float)$price : 0;
}

/**
 * Calculate total price for product
 * 
 * @param int $productId Product ID
 * @param int $quantity Quantity
 * @return array ['unit_price' => float, 'total' => float, 'tier_name' => string]
 */
function calculate_product_price($productId, $quantity) {
    global $db;
    
    // Get product's price tier
    $stmt = $db->prepare("SELECT price_tier_id, product_price FROM product WHERE product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        return ['unit_price' => 0, 'total' => 0, 'tier_name' => null];
    }
    
    $tierId = $product['price_tier_id'];
    
    // If no tier, use base price
    if (!$tierId) {
        $unitPrice = (float)$product['product_price'];
        return [
            'unit_price' => $unitPrice,
            'total' => $unitPrice * $quantity,
            'tier_name' => null
        ];
    }
    
    // Calculate from tier
    $unitPrice = calculate_price_from_tier($tierId, $quantity);
    $tier = get_price_tier($tierId);
    
    return [
        'unit_price' => $unitPrice,
        'total' => $unitPrice * $quantity,
        'tier_name' => $tier['tier_name'] ?? null
    ];
}

/**
 * Get price breakdown for display
 * Shows all tier levels and current price
 */
function get_price_breakdown($productId, $quantity = 1) {
    global $db;
    
    $stmt = $db->prepare("SELECT price_tier_id FROM product WHERE product_id = ?");
    $stmt->execute([$productId]);
    $tierId = $stmt->fetchColumn();
    
    if (!$tierId) {
        return null;
    }
    
    $levels = get_price_tier_levels($tierId);
    $currentPrice = calculate_price_from_tier($tierId, $quantity);
    
    return [
        'levels' => $levels,
        'current_quantity' => $quantity,
        'current_price' => $currentPrice,
        'current_total' => $currentPrice * $quantity
    ];
}

/**
 * Create price tier
 */
function create_price_tier($name, $description = null) {
    global $db;
    
    $sql = "INSERT INTO price_tiers (tier_name, tier_description) VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$name, $description]);
    return $db->lastInsertId();
}

/**
 * Add price tier level
 */
function add_price_tier_level($tierId, $minQuantity, $pricePerUnit, $order = 0) {
    global $db;
    
    $sql = "INSERT INTO price_tier_levels (tier_id, min_quantity, price_per_unit, level_order)
            VALUES (?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([$tierId, $minQuantity, $pricePerUnit, $order]);
}

/**
 * Update price tier level
 */
function update_price_tier_level($levelId, $minQuantity, $pricePerUnit) {
    global $db;
    
    $sql = "UPDATE price_tier_levels 
            SET min_quantity = ?, price_per_unit = ?
            WHERE level_id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([$minQuantity, $pricePerUnit, $levelId]);
}

/**
 * Delete price tier level
 */
function delete_price_tier_level($levelId) {
    global $db;
    
    $sql = "DELETE FROM price_tier_levels WHERE level_id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$levelId]);
}
