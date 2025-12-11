<?php
// Simple test - just check what get_product.php returns
$url = 'http://192.168.1.106/yakpho.com/admin/api/get_product.php?id=5119';

$response = file_get_contents($url);

echo "=== RAW RESPONSE ===\n";
echo $response;
echo "\n\n=== END RESPONSE ===\n\n";

// Try to decode
$json = json_decode($response, true);
if ($json === null) {
    echo "ERROR: Not valid JSON\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "\nFirst 500 characters:\n";
    echo substr($response, 0, 500);
} else {
    echo "SUCCESS: Valid JSON\n";
}
