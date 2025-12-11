<?php
// Test get_product.php directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing get_product.php API...\n\n";

// Simulate the API call
$_GET['id'] = 5119;

ob_start();
include __DIR__ . '/get_product.php';
$output = ob_get_clean();

echo "Output:\n";
echo $output;
echo "\n\n";

// Check if it's valid JSON
$json = json_decode($output, true);
if ($json === null) {
    echo "ERROR: Not valid JSON!\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
} else {
    echo "SUCCESS: Valid JSON\n";
    print_r($json);
}
