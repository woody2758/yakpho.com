<?php
/**
 * Check and cleanup orphaned product images
 */

require_once __DIR__ . '/includes/config.php';

echo "=== Check Orphaned Product Images ===\n\n";

// Get all valid product pictures from database
$stmt = $db->query("SELECT DISTINCT product_picture FROM product WHERE product_picture IS NOT NULL AND product_picture != '' AND product_del = 0");
$validPictures = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Valid products in database: " . count($validPictures) . "\n";

// Get all files in uploads/products
$uploadsPath = __DIR__ . '/uploads/products';
$files = glob($uploadsPath . '/*');

echo "Total files in uploads/products: " . count($files) . "\n\n";

// Build valid filenames (including large- and small- variants)
$validFiles = [];
foreach ($validPictures as $picture) {
    $validFiles[] = $uploadsPath . '/' . $picture;
    $validFiles[] = $uploadsPath . '/large-' . $picture;
    $validFiles[] = $uploadsPath . '/small-' . $picture;
}

// Find orphaned files
$orphanedFiles = [];
foreach ($files as $file) {
    if (is_file($file) && !in_array($file, $validFiles)) {
        $orphanedFiles[] = $file;
    }
}

echo "Orphaned files found: " . count($orphanedFiles) . "\n\n";

if (count($orphanedFiles) === 0) {
    echo "No orphaned files to delete.\n";
    exit;
}

// Ask for confirmation
echo "Do you want to delete these orphaned files? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'yes') {
    echo "Cancelled.\n";
    exit;
}

// Delete orphaned files
echo "\nDeleting orphaned files...\n";
$deletedCount = 0;
$failedCount = 0;

foreach ($orphanedFiles as $file) {
    if (unlink($file)) {
        $deletedCount++;
    } else {
        $failedCount++;
        echo "Failed to delete: $file\n";
    }
}

echo "\n=== Summary ===\n";
echo "Deleted: $deletedCount files\n";
echo "Failed: $failedCount files\n";
echo "\n✓ Cleanup completed!\n";
