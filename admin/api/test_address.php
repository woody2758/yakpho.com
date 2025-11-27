<?php
// Test address functions
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../includes/functions/address.php';

// Test with user_id = 1
$user_id = 1;

echo "<h3>Testing Address Functions</h3>";

// Test count
try {
    $count = count_user_addresses($user_id);
    echo "✅ count_user_addresses($user_id) = $count<br>";
} catch (Exception $e) {
    echo "❌ count_user_addresses error: " . $e->getMessage() . "<br>";
}

// Test get addresses
try {
    $addresses = get_user_addresses($user_id);
    echo "✅ get_user_addresses($user_id) returned " . count($addresses) . " addresses<br>";
    echo "<pre>";
    print_r($addresses);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ get_user_addresses error: " . $e->getMessage() . "<br>";
}
