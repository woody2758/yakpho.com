<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";

echo "<h2>Debug: Database Check</h2>";

try {
    // 1. Check connection
    echo "✅ Database connection: OK<br>";

    // 2. Check table structure
    $table = "target_cities";
    $stmt = $db->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Table: $table</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
    }
    echo "</table>";

    // 3. Check for 'region' column specifically
    $has_region = false;
    foreach ($columns as $col) {
        if ($col['Field'] == 'region') $has_region = true;
    }

    if ($has_region) {
        echo "<p style='color:green'>✅ Column 'region' exists.</p>";
    } else {
        echo "<p style='color:red'>❌ Column 'region' IS MISSING! You need to run the SQL migration.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
