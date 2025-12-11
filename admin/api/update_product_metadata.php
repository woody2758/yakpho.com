<?php
require_once '../includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Update Product Formula and Scent ===\n\n";

// Mapping patterns
$formulaMap = [
    'สูตรร้อน' => 'HOT',
    'สูตรเย็น' => 'COOL',
    'สูตรนวดตัว' => 'MASSAGE'
];

$scentMap = [
    'ต้นตำรับ' => 'ORIGINAL',
    'ไพล' => 'TURMERIC',
    'เสลดพังพอน' => 'CAMPHOR',
    'ดอกโมก' => 'JASMINE_SAMBAC',
    'ตะไคร้หอม' => 'LEMONGRASS',
    'ลาเวนเดอร์' => 'LAVENDER',
    'หญ้าเอ็นยืด' => 'VETIVER',
    'ยูคาลิปตัส' => 'EUCALYPTUS',
    'มะลิ' => 'JASMINE',
    'กุหลาบ' => 'ROSE',
    'ขิงมินท์' => 'GINGER_MINT',
    'ลีลาวดี' => 'PLUMERIA',
    'น้ำมันมะพร้าว' => 'COCONUT',
    'โรสแมรี่' => 'ROSEMARY',
    'น้ำอบไทย' => 'THAI_HERBAL_WATER',
    'ดอกปีบ' => 'CHAMPAKA'
];

try {
    // Get all balm products
    $stmt = $db->query("
        SELECT product_id, product_code, product_name 
        FROM product 
        WHERE product_del = 0 
        AND product_name LIKE '%ยาหม่อง%'
        ORDER BY product_id ASC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($products) . " products to update\n\n";
    
    $updateStmt = $db->prepare("
        UPDATE product 
        SET formula = ?, scent = ? 
        WHERE product_id = ?
    ");
    
    $updatedCount = 0;
    
    foreach ($products as $p) {
        $formula = null;
        $scent = null;
        $name = $p['product_name'];
        
        // Extract formula
        foreach ($formulaMap as $key => $value) {
            if (stripos($name, $key) !== false) {
                $formula = $value;
                break;
            }
        }
        
        // Extract scent
        foreach ($scentMap as $key => $value) {
            if (stripos($name, $key) !== false) {
                $scent = $value;
                break;
            }
        }
        
        if ($formula || $scent) {
            $updateStmt->execute([$formula, $scent, $p['product_id']]);
            echo "✓ ID {$p['product_id']}: {$p['product_code']} → Formula: {$formula}, Scent: {$scent}\n";
            $updatedCount++;
        } else {
            echo "⚠ ID {$p['product_id']}: {$p['product_code']} → No match found\n";
        }
    }
    
    echo "\n✅ Updated {$updatedCount} products\n";
    
    // Verify
    echo "\n=== Verification ===\n";
    $verify = $db->query("
        SELECT formula, scent, COUNT(*) as count 
        FROM product 
        WHERE product_del = 0 AND product_name LIKE '%ยาหม่อง%'
        GROUP BY formula, scent
        ORDER BY formula, scent
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($verify as $v) {
        echo "{$v['formula']} - {$v['scent']}: {$v['count']} products\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
