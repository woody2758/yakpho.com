<?php
/**
 * Debug Version of Get Products Grid - Writes to file
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logFile = __DIR__ . '/debug_result.txt';
file_put_contents($logFile, "Starting Debug at " . date('Y-m-d H:i:s') . "\n");

function logDebug($msg) {
    global $logFile;
    file_put_contents($logFile, $msg . "\n", FILE_APPEND);
}

try {
    require_once __DIR__ . '/../includes/config.php';
    logDebug("Config loaded. DB: salebnet_thaiherbold");

    // Test DB connection
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logDebug("Starting categories query...");
    $catStmt = $db->prepare("
        SELECT pc.productcat_id, pct.productcat_name
        FROM productcat pc
        LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id 
            AND pct.lang_code = 'th'
        WHERE pc.productcat_del = 0
        ORDER BY pc.productcat_id ASC
    ");
    $catStmt->execute();
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Found " . count($categories) . " categories.");
    
    logDebug("Starting products query...");
    $prodStmt = $db->prepare("
        SELECT 
            p.product_id,
            p.productcat_id,
            p.product_code,
            p.product_price,
            p.product_picture,
            pt.product_name
        FROM product p
        LEFT JOIN product_translations pt ON p.product_id = pt.product_id 
            AND pt.lang_code = 'th'
        WHERE p.product_del = 0 AND p.product_status = 1
        ORDER BY p.product_id ASC
    ");
    $prodStmt->execute();
    $allProducts = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Found " . count($allProducts) . " products.");
    
    logDebug("Formatting data...");
    // Group products by category
    $result = [];
    foreach ($categories as $cat) {
        $catProducts = array_filter($allProducts, function($p) use ($cat) {
            return $p['productcat_id'] == $cat['productcat_id'];
        });
        
        $formattedProducts = array_map(function($p) {
            $filename = $p['product_picture'];
            if (!empty($filename)) {
                if (strpos($filename, 'uploads/products/') === false) {
                    $p['product_image_url'] = (defined('ROOT_URL') ? ROOT_URL : '') . "/uploads/products/small-" . $filename;
                } else {
                    $p['product_image_url'] = (defined('ROOT_URL') ? ROOT_URL : '') . "/" . $filename;
                }
            } else {
                $p['product_image_url'] = (defined('ROOT_URL') ? ROOT_URL : '') . '/admin/assets/images/placeholder.png';
            }
            unset($p['product_picture']); 
            return $p;
        }, array_values($catProducts));
        
        $result[] = [
            'productcat_id' => $cat['productcat_id'],
            'productcat_name' => $cat['productcat_name'] ?: 'ไม่มีหมวดหมู่',
            'products' => $formattedProducts
        ];
    }
    
    logDebug("Success! Data formatted.");
    file_put_contents($logFile, "\nFINAL JSON RESULT:\n" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
    
} catch (Exception $e) {
    logDebug("ERROR: " . $e->getMessage());
    logDebug("TRACE: " . $e->getTraceAsString());
}
?>
