<?php
// Test save_product.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, only return JSON

// Simulate POST data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'product_code' => 'YP5119',
    'product_name_th' => 'ทดสอบ',
    'product_price' => 100
];

// Start session
session_start();
$_SESSION['admin_id'] = 1;

// Capture output
ob_start();
include __DIR__ . '/save_product.php';
$output = ob_get_clean();

// Display result
header('Content-Type: text/plain');
echo "=== RAW OUTPUT ===\n";
echo $output;
echo "\n\n=== JSON CHECK ===\n";

$json = json_decode($output, true);
if ($json === null) {
    echo "ERROR: Not valid JSON\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "\nFirst 1000 chars:\n";
    echo substr($output, 0, 1000);
} else {
    echo "SUCCESS: Valid JSON\n";
    print_r($json);
}
