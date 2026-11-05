<?php
$_GET['action'] = 'poll';
$_GET['api_key'] = 'yakpho_secret_api_2026_xyz';

// Change directory to api/ to simulate direct call
chdir(__DIR__ . '/api');

ob_start();
include "bot_bridge.php";
$output = ob_get_clean();

echo "--- API OUTPUT ---\n";
echo $output;
echo "\n--- BYTES: " . strlen($output) . " ---\n";

$data = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "JSON IS VALID\n";
    print_r($data);
} else {
    echo "JSON ERROR: " . json_last_error_msg() . "\n";
}
?>
