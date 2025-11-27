<?php
// Simple test to check if API is accessible
session_start();

// Set fake session for testing
$_SESSION['admin_logged'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'admin';
$_SESSION['admin_name'] = 'Test Admin';

// Set GET parameters
$_GET['page'] = 1;
$_GET['search'] = '';
$_GET['role'] = '';
$_GET['sort'] = 'desc';

// Capture output
ob_start();
try {
    include __DIR__ . '/get_users_table.php';
    $output = ob_get_clean();
    
    echo "<h3>API Response:</h3>";
    echo "<pre>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    echo "<h3>Decoded JSON:</h3>";
    $json = json_decode($output, true);
    echo "<pre>";
    print_r($json);
    echo "</pre>";
} catch (Exception $e) {
    ob_end_clean();
    echo "<h3>Error:</h3>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "</pre>";
}
?>
