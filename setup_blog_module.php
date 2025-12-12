<?php
/**
 * Create Blog Gallery Table and Upload Directories
 */

require_once __DIR__ . '/includes/config.php';

try {
    echo "=== BLOG MODULE SETUP ===\n\n";
    
    // 1. Create blog_gallery table
    echo "Creating blog_gallery table...\n";
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS blog_gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            blog_id INT NOT NULL,
            gallery_image VARCHAR(255) NOT NULL,
            gallery_order INT DEFAULT 0,
            gallery_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_blog_id (blog_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✓ blog_gallery table created\n\n";
    
    // 2. Create upload directories
    echo "Creating upload directories...\n";
    
    $directories = [
        __DIR__ . '/uploads/blog',
        __DIR__ . '/uploads/blog/cover',
        __DIR__ . '/uploads/blog/gallery'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✓ Created: $dir\n";
        } else {
            echo "✓ Already exists: $dir\n";
        }
    }
    
    echo "\n=== SETUP COMPLETE ===\n\n";
    echo "✅ blog_gallery table ready\n";
    echo "✅ Upload directories ready\n";
    echo "   - /uploads/blog/cover/\n";
    echo "   - /uploads/blog/gallery/\n\n";
    
    echo "Ready to develop Blog module! 🚀\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
