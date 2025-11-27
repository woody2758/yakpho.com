<?php
// Test API directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing get_users_table.php API...\n\n";

// Simulate GET parameters
$_GET['page'] = 1;
$_GET['search'] = '';
$_GET['role'] = '';
$_GET['sort'] = 'desc';

// Include the API file
ob_start();
include __DIR__ . '/get_users_table.php';
$output = ob_get_clean();

echo "Output:\n";
echo $output;
echo "\n\nDecoded JSON:\n";
$json = json_decode($output, true);
print_r($json);
?>
