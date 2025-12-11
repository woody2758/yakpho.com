<?php
require_once '../includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Price Tiers Migration Script ===\n\n";

try {
    // 1. Modify price_tiers table structure
    echo "Step 1: Modifying price_tiers table...\n";
    
    $db->exec("
        ALTER TABLE price_tiers
        ADD COLUMN IF NOT EXISTS tier_min_kg DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Minimum kg for this tier',
        ADD COLUMN IF NOT EXISTS tier_price_per_kg DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Price per kg at this tier',
        ADD COLUMN IF NOT EXISTS tier_sort_order INT NOT NULL DEFAULT 0 COMMENT 'Display order'
    ");
    echo "✅ Table structure updated\n\n";
    
    // 2. Clear existing data
    echo "Step 2: Clearing existing tier data...\n";
    $db->exec("DELETE FROM price_tiers");
    echo "✅ Old data cleared\n\n";
    
    // 3. Insert new tier data
    echo "Step 3: Inserting price tier data...\n";
    
    $tiers = [
        ['1 กก.', 1, 690, 1],
        ['6 กก.', 6, 590, 2],
        ['10 กก.', 10, 560, 3],
        ['20 กก.', 20, 530, 4],
        ['30 กก.', 30, 500, 5],
        ['50 กก.', 50, 490, 6],
        ['100 กก.', 100, 470, 7]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO price_tiers 
        (tier_name, tier_min_kg, tier_price_per_kg, tier_sort_order, tier_status, tier_description) 
        VALUES (?, ?, ?, ?, 1, CONCAT('ราคาต่อกิโลกรัมเมื่อสั่งซื้อตั้งแต่ ', ?, ' กิโลขึ้นไป'))
    ");
    
    foreach ($tiers as $tier) {
        $stmt->execute([
            $tier[0],           // tier_name
            $tier[1],           // tier_min_kg
            $tier[2],           // tier_price_per_kg
            $tier[3],           // tier_sort_order
            $tier[1]            // description min_kg param
        ]);
        echo "  ✓ {$tier[0]} - {$tier[2]} บาท/กก.\n";
    }
    
    echo "\n✅ Inserted " . count($tiers) . " price tiers\n\n";
    
    // 4. Verify data
    echo "Step 4: Verifying data...\n";
    $result = $db->query("
        SELECT tier_id, tier_name, tier_min_kg, tier_price_per_kg, tier_sort_order 
        FROM price_tiers 
        WHERE tier_status = 1 
        ORDER BY tier_sort_order ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== Current Price Tiers ===\n";
    echo "ID | Name | Min KG | Price/KG | Order\n";
    echo str_repeat("-", 50) . "\n";
    foreach ($result as $r) {
        echo "{$r['tier_id']} | {$r['tier_name']} | {$r['tier_min_kg']} | {$r['tier_price_per_kg']} | {$r['tier_sort_order']}\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
