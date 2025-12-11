<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: text/plain');

try {
    echo "=== Tables ===\n";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);
    
    echo "\n=== Product Table ===\n";
    $stmt = $db->query("DESCRIBE product");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    if (in_array('sitesettings', $tables)) {
        echo "\n=== Site Settings Table ===\n";
        $stmt = $db->query("DESCRIBE sitesettings");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo "\n=== Site Settings Table NOT FOUND ===\n";
    }

    if (in_array('product_images', $tables)) {
        echo "\n=== Product Images Table ===\n";
        $stmt = $db->query("DESCRIBE product_images");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo "\n=== Product Images Table NOT FOUND ===\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
